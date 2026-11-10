<?php
declare(strict_types=1);

/**
 * models/User.php
 * Handles user database lookups, password hashing verification, and PIN checks.
 * Uses 100% PDO prepared statements for database queries.
 */

require_once __DIR__ . '/../config/Database.php';

class User {
    /**
     * Look up a user account by exact username.
     *
     * @param string $username Account username
     * @return array<string, mixed>|null User record or null if not found
     */
    public static function findByUsername(string $username): ?array {
        $pdo = Database::getConnection();

        // PDO prepared statement prevents SQL injection
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => trim($username)]);

        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Look up a user account by primary key ID.
     *
     * @param int $userId Primary user ID
     * @return array<string, mixed>|null User record without sensitive hash, or null
     */
    public static function findById(int $userId): ?array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare('SELECT user_id, username, role, failed_attempts, locked, last_login, created_at FROM users WHERE user_id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);

        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Authenticate login credentials.
     * Verifies username, password_hash via password_verify, and optional pin_hash.
     *
     * @param string $username Submitted username
     * @param string $password Submitted password
     * @param string|null $pin Optional security PIN
     * @return array<string, mixed>|null Authenticated user data on success, null on failure
     */
    public static function verifyLogin(string $username, string $password, ?string $pin = null): ?array {
        $user = self::findByUsername($username);

        // 1. Account must exist and not be locked
        if (!$user || !empty($user['locked'])) {
            return null;
        }

        // 2. Verify password against stored bcrypt hash
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        // 3. Optional PIN verification if pin_hash is configured on the user account
        if (!empty($user['pin_hash'])) {
            $pinClean = $pin !== null ? trim($pin) : '';
            if ($pinClean === '' || !password_verify($pinClean, $user['pin_hash'])) {
                return null;
            }
        }

        // 4. Update last_login timestamp and reset failed attempts counter
        $pdo = Database::getConnection();
        $updateStmt = $pdo->prepare('UPDATE users SET last_login = NOW(), failed_attempts = 0 WHERE user_id = :id');
        $updateStmt->execute(['id' => $user['user_id']]);

        return $user;
    }
}
