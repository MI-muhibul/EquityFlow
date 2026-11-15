<?php
declare(strict_types=1);

/**
 * models/Setting.php
 * -----------------------------------------------------------------------------
 * Data-access model for the `settings` key-value table.
 *
 * Rules:
 *   - Prepared statements only via Database::getConnection()
 *   - Uses composite PRIMARY KEY (`user_id`, `skey`)
 *   - Every query is strictly scoped to `user_id`
 *   - Provides get(), set(), all(), and setMultiple()
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/Database.php';

class Setting
{
    /**
     * Get a single setting value by key for a specific user.
     * Returns $default if key is not found.
     *
     * @param int $userId
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function get(int $userId, string $key, string $default = ''): string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT svalue FROM settings WHERE user_id = :uid AND skey = :key LIMIT 1');
        $stmt->execute([':uid' => $userId, ':key' => $key]);
        $val = $stmt->fetchColumn();

        return ($val !== false && $val !== null) ? (string)$val : $default;
    }

    /**
     * Set/update a setting value for a specific user.
     *
     * @param int $userId
     * @param string $key
     * @param string $value
     * @return bool
     */
    public static function set(int $userId, string $key, string $value): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO settings (user_id, skey, svalue) 
             VALUES (:uid, :key, :value) 
             ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)'
        );
        return $stmt->execute([
            ':uid'   => $userId,
            ':key'   => $key,
            ':value' => $value,
        ]);
    }

    /**
     * Fetch all settings as an associative key-value array for a specific user.
     *
     * @param int $userId
     * @return array<string, string>
     */
    public static function all(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT skey, svalue FROM settings WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll();

        $settings = [];
        foreach ($rows as $row) {
            $settings[(string)$row['skey']] = (string)($row['svalue'] ?? '');
        }

        return $settings;
    }

    /**
     * Update multiple settings for a specific user in one transactional batch.
     *
     * @param int $userId
     * @param array<string, string> $data
     * @return bool
     */
    public static function setMultiple(int $userId, array $data): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO settings (user_id, skey, svalue) 
             VALUES (:uid, :key, :value) 
             ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)'
        );

        $pdo->beginTransaction();
        try {
            foreach ($data as $key => $value) {
                $stmt->execute([
                    ':uid'   => $userId,
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
