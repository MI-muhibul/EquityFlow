<?php
declare(strict_types=1);

/**
 * models/User.php
 * Handles user authentication, password verification, failed attempt tracking,
 * automatic account lockout, and password updates.
 * Uses 100% PDO prepared statements.
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/constants.php';

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
     * Check if an account is currently locked.
     *
     * @param string $username
     * @return bool
     */
    public static function isLocked(string $username): bool {
        $user = self::findByUsername($username);
        return $user !== null && !empty($user['locked']);
    }

    /**
     * Record a failed login attempt.
     * Increments failed_attempts counter, and if threshold (MAX_FAILED_ATTEMPTS=5)
     * is reached, locks the account (locked=1).
     *
     * @param string $username
     * @return array{exists: bool, locked: bool, failed_attempts: int, remaining: int}
     */
    public static function recordFailedAttempt(string $username): array {
        $user = self::findByUsername($username);

        if (!$user) {
            return [
                'exists'          => false,
                'locked'          => false,
                'failed_attempts' => 0,
                'remaining'       => 0,
            ];
        }

        $maxAttempts = defined('MAX_FAILED_ATTEMPTS') ? MAX_FAILED_ATTEMPTS : 5;
        $newAttempts = (int)$user['failed_attempts'] + 1;
        $locked      = $newAttempts >= $maxAttempts ? 1 : (int)$user['locked'];

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET failed_attempts = :attempts, locked = :locked WHERE user_id = :id');
        $stmt->execute([
            'attempts' => $newAttempts,
            'locked'   => $locked,
            'id'       => $user['user_id'],
        ]);

        return [
            'exists'          => true,
            'locked'          => (bool)$locked,
            'failed_attempts' => $newAttempts,
            'remaining'       => max(0, $maxAttempts - $newAttempts),
        ];
    }

    /**
     * Authenticate login credentials.
     * Verifies username, password_hash via password_verify, and optional pin_hash.
     * Resets failed_attempts counter to 0 on success.
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

        // 4. On successful login, reset failed attempts and update last_login
        $pdo = Database::getConnection();
        $updateStmt = $pdo->prepare('UPDATE users SET last_login = NOW(), failed_attempts = 0 WHERE user_id = :id');
        $updateStmt->execute(['id' => $user['user_id']]);

        return $user;
    }

    /**
     * Verify whether a submitted password matches the user's current password hash.
     *
     * @param int $userId
     * @param string $password
     * @return bool
     */
    public static function verifyPassword(int $userId, string $password): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);

        $hash = $stmt->fetchColumn();
        return $hash ? password_verify($password, (string)$hash) : false;
    }

    /**
     * Update the user's password with a fresh bcrypt hash.
     *
     * @param int $userId
     * @param string $newPassword Plaintext new password
     * @return bool True on success
     */
    public static function updatePassword(int $userId, string $newPassword): bool {
        $pdo = Database::getConnection();
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE user_id = :id');
        return $stmt->execute([
            'hash' => $newHash,
            'id'   => $userId,
        ]);
    }

    /**
     * Unlock an account and reset failed attempts (useful for admin reset or testing).
     *
     * @param int $userId
     * @return void
     */
    public static function unlockUser(int $userId): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET failed_attempts = 0, locked = 0 WHERE user_id = :id');
        $stmt->execute(['id' => $userId]);
    }
}
