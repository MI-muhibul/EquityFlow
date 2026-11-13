<?php
declare(strict_types=1);

/**
 * models/Setting.php
 * -----------------------------------------------------------------------------
 * Data-access model for the `settings` key-value table.
 *
 * Rules:
 *   - Prepared statements only via Database::getConnection()
 *   - Standard key-value store with skey (PRIMARY KEY) and svalue (TEXT)
 *   - Provides get(), set(), all(), and setMultiple()
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/Database.php';

class Setting
{
    /**
     * Get a single setting value by key.
     * Returns $default if key is not found.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function get(string $key, string $default = ''): string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT svalue FROM settings WHERE skey = :key LIMIT 1');
        $stmt->execute([':key' => $key]);
        $val = $stmt->fetchColumn();

        return ($val !== false && $val !== null) ? (string)$val : $default;
    }

    /**
     * Set/update a setting value.
     *
     * @param string $key
     * @param string $value
     * @return bool
     */
    public static function set(string $key, string $value): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO settings (skey, svalue) 
             VALUES (:key, :value) 
             ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)'
        );
        return $stmt->execute([
            ':key'   => $key,
            ':value' => $value,
        ]);
    }

    /**
     * Fetch all settings as an associative key-value array.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT skey, svalue FROM settings');
        $rows = $stmt->fetchAll();

        $settings = [];
        foreach ($rows as $row) {
            $settings[(string)$row['skey']] = (string)($row['svalue'] ?? '');
        }

        return $settings;
    }

    /**
     * Update multiple settings in one go.
     *
     * @param array<string, string> $data
     * @return bool
     */
    public static function setMultiple(array $data): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO settings (skey, svalue) 
             VALUES (:key, :value) 
             ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)'
        );

        $pdo->beginTransaction();
        try {
            foreach ($data as $key => $value) {
                $stmt->execute([
                    ':key'   => (string)$key,
                    ':value' => (string)$value,
                ]);
            }
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }
}
