<?php
declare(strict_types=1);

/**
 * models/Backup.php
 * -----------------------------------------------------------------------------
 * Model handling pure PDO-based MySQL database dumps and backup management.
 *
 * Rules:
 *   - Strictly multi-tenant: exports ONLY the signed-in user's own rows.
 *   - NEVER includes the `users` table or passwords/hashes.
 *   - Tables dumped: customers, products, transactions, sale_items,
 *     ledger_entries, reports, settings (scoped to user_id).
 *   - Backups saved in backups/ directory with pattern:
 *     equityflow_backup_user_{user_id}_{YYYY-MM-DD_HH-mm-ss}.sql
 *   - Only lists and downloads that user's own backups.
 *   - Strict path traversal prevention: basename checks, regex, realpath checks.
 *   - No external mysqldump dependency (pure PDO).
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/Database.php';

class Backup
{
    /**
     * Tables permitted to be exported in user data backup.
     * Note: `users` table is STRICTLY EXCLUDED.
     */
    private const EXPORT_TABLES = [
        'customers',
        'products',
        'transactions',
        'sale_items',
        'ledger_entries',
        'reports',
        'settings',
    ];

    /**
     * Directory path where backup .sql files reside.
     */
    public static function getBackupDir(): string
    {
        return realpath(__DIR__ . '/../backups') ?: (__DIR__ . '/../backups');
    }

    /**
     * Generate a multi-tenant user-scoped SQL dump using PDO and save it to backups/.
     *
     * @param int $userId Authenticated user ID.
     * @return array{success: bool, filename: ?string, filepath: ?string, size: int, error: ?string}
     */
    public static function createDump(int $userId): array
    {
        if ($userId <= 0) {
            return [
                'success'  => false,
                'filename' => null,
                'filepath' => null,
                'size'     => 0,
                'error'    => 'Invalid user ID for backup export.',
            ];
        }

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

        // Standardized multi-tenant filename format
        $filename = sprintf('equityflow_backup_user_%d_%s.sql', $userId, date('Y-m-d_H-i-s'));
        $filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        try {
            $pdo = Database::getConnection();

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
                    . "-- EquityFlow User Data Backup Dump\n"
                    . "-- Scoped User ID : {$userId}\n"
                    . "-- Generated At   : " . date('Y-m-d H:i:s') . "\n"
                    . "-- PHP Version    : " . PHP_VERSION . "\n"
                    . "-- Stack          : PDO MySQL / MariaDB (Multi-tenant scoped)\n"
                    . "-- Note           : Excludes users authentication table & system secrets\n"
                    . "-- =============================================================================\n\n"
                    . "SET FOREIGN_KEY_CHECKS = 0;\n"
                    . "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n"
                    . "SET NAMES utf8mb4;\n\n";
            fwrite($handle, $header);

            // Table-specific user scoped SELECT queries
            $tableQueries = [
                'customers' => [
                    'sql'    => 'SELECT * FROM `customers` WHERE `user_id` = :uid ORDER BY `cust_id` ASC',
                    'params' => [':uid' => $userId],
                ],
                'products' => [
                    'sql'    => 'SELECT * FROM `products` WHERE `user_id` = :uid ORDER BY `prod_id` ASC',
                    'params' => [':uid' => $userId],
                ],
                'transactions' => [
                    'sql'    => 'SELECT t.* FROM `transactions` t '
                              . 'JOIN `customers` c ON t.cust_id = c.cust_id '
                              . 'WHERE c.user_id = :uid ORDER BY t.txn_id ASC',
                    'params' => [':uid' => $userId],
                ],
                'sale_items' => [
                    'sql'    => 'SELECT si.* FROM `sale_items` si '
                              . 'JOIN `transactions` t ON si.txn_id = t.txn_id '
                              . 'JOIN `customers` c ON t.cust_id = c.cust_id '
                              . 'WHERE c.user_id = :uid ORDER BY si.item_id ASC',
                    'params' => [':uid' => $userId],
                ],
                'ledger_entries' => [
                    'sql'    => 'SELECT * FROM `ledger_entries` WHERE `owner_id` = :uid ORDER BY `ledger_id` ASC',
                    'params' => [':uid' => $userId],
                ],
                'reports' => [
                    'sql'    => 'SELECT * FROM `reports` WHERE `user_id` = :uid ORDER BY `report_id` ASC',
                    'params' => [':uid' => $userId],
                ],
                'settings' => [
                    'sql'    => 'SELECT * FROM `settings` WHERE `user_id` = :uid ORDER BY `skey` ASC',
                    'params' => [':uid' => $userId],
                ],
            ];

            foreach (self::EXPORT_TABLES as $table) {
                if (!isset($tableQueries[$table])) {
                    continue;
                }

                // Table structure header
                $tableHeader = "-- -----------------------------------------------------------------------------\n"
                             . "-- Table structure & scoped data for `{$table}`\n"
                             . "-- -----------------------------------------------------------------------------\n";
                fwrite($handle, $tableHeader);

                // Fetch table schema
                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $createRow = $createStmt->fetch(PDO::FETCH_NUM);
                if (!empty($createRow[1])) {
                    fwrite($handle, $createRow[1] . ";\n\n");
                }

                // Dump user-scoped rows
                $qInfo = $tableQueries[$table];
                $stmt = $pdo->prepare($qInfo['sql']);
                $stmt->execute($qInfo['params']);

                $columns = [];
                $batch = [];
                $batchCount = 0;

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (empty($columns)) {
                        $columns = array_keys($row);
                    }

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
                        $colsSql = '`' . implode('`, `', $columns) . '`';
                        $insertSql = "INSERT INTO `{$table}` ({$colsSql}) VALUES \n" . implode(",\n", $batch) . ";\n";
                        fwrite($handle, $insertSql);
                        $batch = [];
                        $batchCount = 0;
                    }
                }

                // Flush remaining rows
                if (!empty($batch)) {
                    $colsSql = '`' . implode('`, `', $columns) . '`';
                    $insertSql = "INSERT INTO `{$table}` ({$colsSql}) VALUES \n" . implode(",\n", $batch) . ";\n";
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
     * List backup files belonging ONLY to the specified user.
     * Returned newest first.
     *
     * @param int $userId Authenticated user ID.
     * @return array<int, array{filename: string, size: int, size_formatted: string, created_at: string}>
     */
    public static function listBackups(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $dir = self::getBackupDir();
        if (!is_dir($dir)) {
            return [];
        }

        $files = scandir($dir, SCANDIR_SORT_DESCENDING);
        $backups = [];

        // Exact pattern: equityflow_backup_user_{userId}_YYYY-MM-DD_HH-mm-ss.sql
        $pattern = '/^equityflow_backup_user_' . $userId . '_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/';

        if ($files !== false) {
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || $file === '.htaccess' || $file === '.gitkeep') {
                    continue;
                }
                if (preg_match($pattern, $file)) {
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
     * Validate and locate a backup file for download scoped to the user.
     * Protects strictly against directory traversal, cross-tenant leaks, and unauthorized access.
     *
     * @param string $filename Download filename requested by client.
     * @param int $userId Authenticated user ID.
     * @return string|false Absolute file path if valid, exists, and belongs to user; false otherwise.
     */
    public static function getDownloadPath(string $filename, int $userId): string|false
    {
        if ($userId <= 0 || empty($filename)) {
            return false;
        }

        // 1. Path traversal check: must equal its own basename
        $clean = basename($filename);
        if ($clean !== $filename) {
            return false;
        }

        // 2. Reject any null bytes, slashes, or double dots
        if (str_contains($clean, "\0") || str_contains($clean, '..') || str_contains($clean, '/') || str_contains($clean, '\\')) {
            return false;
        }

        // 3. Strict regex validation for format and user ID
        $pattern = '/^equityflow_backup_user_' . $userId . '_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/';
        if (!preg_match($pattern, $clean)) {
            return false;
        }

        $backupDir = self::getBackupDir();
        $fullPath = $backupDir . DIRECTORY_SEPARATOR . $clean;

        // 4. File existence and read check
        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            return false;
        }

        // 5. Canonical realpath verification inside backupDir
        $realFile = realpath($fullPath);
        $realDir  = realpath($backupDir);
        if ($realFile === false || $realDir === false) {
            return false;
        }

        if (!str_starts_with($realFile, $realDir . DIRECTORY_SEPARATOR)) {
            return false;
        }

        return $realFile;
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
