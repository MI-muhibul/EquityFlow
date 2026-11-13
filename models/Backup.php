<?php
declare(strict_types=1);

/**
 * models/Backup.php
 * -----------------------------------------------------------------------------
 * Model handling pure PDO-based MySQL database dumps and backup management.
 *
 * Rules:
 *   - No mysqldump binary dependency (uses PDO only).
 *   - Generates clean CREATE TABLE and INSERT statements.
 *   - Disables foreign key checks during import to avoid dependency issues.
 *   - Backups saved in backups/ directory with pattern: equityflow_YYYYMMDD_HHMMSS.sql
 *   - Safe filename sanitization to prevent directory traversal.
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/Database.php';

class Backup
{
    /**
     * Directory path where backup .sql files reside.
     */
    public static function getBackupDir(): string
    {
        return realpath(__DIR__ . '/../backups') ?: (__DIR__ . '/../backups');
    }

    /**
     * Generate a full database SQL dump using PDO and save it to backups/.
     *
     * @return array{success: bool, filename: ?string, filepath: ?string, size: int, error: ?string}
     */
    public static function createDump(): array
    {
        $backupDir = self::getBackupDir();
        if (!is_dir($backupDir)) {
            if (!mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
                return [
                    'success'  => false,
                    'filename' => null,
                    'filepath' => null,
                    'size'     => 0,
                    'error'    => 'Failed to create backup directory.',
                ];
            }
        }

        $filename = 'equityflow_' . date('Ymd_His') . '.sql';
        $filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        try {
            $pdo = Database::getConnection();

            // Open stream to file directly to handle large amounts of rows safely
            $handle = fopen($filepath, 'w');
            if ($handle === false) {
                return [
                    'success'  => false,
                    'filename' => null,
                    'filepath' => null,
                    'size'     => 0,
                    'error'    => 'Cannot write backup file to disk.',
                ];
            }

            // SQL Header
            $header = "-- =============================================================================\n"
                    . "-- EquityFlow Database Backup Dump\n"
                    . "-- Generated At: " . date('Y-m-d H:i:s') . "\n"
                    . "-- PHP Version : " . PHP_VERSION . "\n"
                    . "-- Stack       : PDO MySQL / MariaDB (Zero external tools)\n"
                    . "-- =============================================================================\n\n"
                    . "SET FOREIGN_KEY_CHECKS = 0;\n"
                    . "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n"
                    . "SET NAMES utf8mb4;\n\n";
            fwrite($handle, $header);

            // Fetch list of all tables
            $tablesStmt = $pdo->query('SHOW TABLES');
            $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                // Table header
                $tableHeader = "-- -----------------------------------------------------------------------------\n"
                             . "-- Table structure & data for `{$table}`\n"
                             . "-- -----------------------------------------------------------------------------\n"
                             . "DROP TABLE IF EXISTS `{$table}`;\n";
                fwrite($handle, $tableHeader);

                // Get CREATE TABLE statement
                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $createRow = $createStmt->fetch(PDO::FETCH_NUM);
                if (!empty($createRow[1])) {
                    fwrite($handle, $createRow[1] . ";\n\n");
                }

                // Dump table rows using chunking to conserve memory
                $selectStmt = $pdo->query("SELECT * FROM `{$table}`");
                $batch = [];
                $batchCount = 0;

                while ($row = $selectStmt->fetch(PDO::FETCH_ASSOC)) {
                    $values = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = $pdo->quote((string)$val);
                        }
                    }
                    $batch[] = '(' . implode(', ', $values) . ')';
                    $batchCount++;

                    // Write in batches of 100 rows
                    if ($batchCount >= 100) {
                        $insertSql = "INSERT INTO `{$table}` VALUES \n" . implode(",\n", $batch) . ";\n";
                        fwrite($handle, $insertSql);
                        $batch = [];
                        $batchCount = 0;
                    }
                }

                // Flush remaining rows
                if (!empty($batch)) {
                    $insertSql = "INSERT INTO `{$table}` VALUES \n" . implode(",\n", $batch) . ";\n";
                    fwrite($handle, $insertSql);
                }

                fwrite($handle, "\n");
            }

            // Footer
            $footer = "SET FOREIGN_KEY_CHECKS = 1;\n"
                    . "-- Dump completed on " . date('Y-m-d H:i:s') . "\n";
            fwrite($handle, $footer);

            fclose($handle);

            $fileSize = file_exists($filepath) ? (int)filesize($filepath) : 0;

            return [
                'success'  => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size'     => $fileSize,
                'error'    => null,
            ];
        } catch (\Throwable $e) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
            return [
                'success'  => false,
                'filename' => null,
                'filepath' => null,
                'size'     => 0,
                'error'    => $e->getMessage(),
            ];
        }
    }

    /**
     * List all existing backup files in the backups/ directory.
     * Returned newest first.
     *
     * @return array<int, array{filename: string, size: int, size_formatted: string, created_at: string}>
     */
    public static function listBackups(): array
    {
        $dir = self::getBackupDir();
        if (!is_dir($dir)) {
            return [];
        }

        $files = scandir($dir, SCANDIR_SORT_DESCENDING);
        $backups = [];

        if ($files !== false) {
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || $file === '.htaccess' || $file === '.gitkeep') {
                    continue;
                }
                if (str_ends_with(strtolower($file), '.sql')) {
                    $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
                    $size = (int)filesize($fullPath);
                    $mtime = (int)filemtime($fullPath);

                    $backups[] = [
                        'filename'       => $file,
                        'size'           => $size,
                        'size_formatted' => self::formatBytes($size),
                        'created_at'     => date('Y-m-d H:i:s', $mtime),
                    ];
                }
            }
        }

        // Sort by created_at DESC
        usort($backups, static fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return $backups;
    }

    /**
     * Validate and locate a backup file for download.
     * Protects strictly against directory traversal.
     *
     * @param string $filename
     * @return string|false Absolute file path if valid and exists, false otherwise.
     */
    public static function getDownloadPath(string $filename): string|false
    {
        $clean = basename($filename);
        if ($clean !== $filename || !preg_match('/^equityflow_\d{8}_\d{6}\.sql$/', $clean)) {
            return false;
        }

        $fullPath = self::getBackupDir() . DIRECTORY_SEPARATOR . $clean;
        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            return false;
        }

        return $fullPath;
    }

    /**
     * Helper to format bytes into readable KB/MB.
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
