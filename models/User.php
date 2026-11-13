<?php
declare(strict_types=1);

/**
 * models/User.php
 * -----------------------------------------------------------------------------
 * Shop Owner (User) Model.
 * Handles user authentication, registration, password hashing/verification,
 * PIN management, failed attempt tracking, and automatic account lockout.
 *
 * Rules:
 *  - 100% PDO prepared statements.
 *  - Multi-tenant shop owner account support.
 *  - Supports local email+password and Google OAuth accounts.
 *  - Rejects login if password_hash is NULL (e.g. Google-only accounts).
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/constants.php';

class User {

    /**
     * Find a user account by email address (case-insensitive, trimmed).
     *
     * @param string $email
     * @return array<string, mixed>|null User record or null if not found
     */
    public static function findByEmail(string $email): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $stmt->execute(['email' => trim($email)]);
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
        $stmt = $pdo->prepare('SELECT user_id, name, email, google_id, avatar_url, auth_provider, shop_name, failed_attempts, locked, created_at, last_login FROM users WHERE user_id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Create a new shop owner user account.
     * Generates secure bcrypt password_hash and optional pin_hash.
     *
     * @param array{
     *     name: string,
     *     email: string,
     *     password: string,
     *     shop_name: string,
     *     pin?: string|null,
     *     auth_provider?: string
     * } $data
     * @return int Newly created user_id
     */
    public static function create(array $data): int {
        $pdo = Database::getConnection();

        $name         = trim((string)$data['name']);
        $email        = strtolower(trim((string)$data['email']));
        $passwordHash = password_hash((string)$data['password'], PASSWORD_DEFAULT);
        $shopName     = trim((string)$data['shop_name']);
        $authProvider = isset($data['auth_provider']) && in_array($data['auth_provider'], ['local', 'google', 'both'], true)
            ? $data['auth_provider']
            : 'local';

        $pin = isset($data['pin']) && trim((string)$data['pin']) !== '' ? trim((string)$data['pin']) : null;
        $pinHash = $pin !== null ? password_hash($pin, PASSWORD_DEFAULT) : null;

        $stmt = $pdo->prepare('
            INSERT INTO users (
                name, email, password_hash, google_id, avatar_url,
                auth_provider, pin_hash, shop_name, failed_attempts, locked, created_at
            ) VALUES (
                :name, :email, :password_hash, NULL, NULL,
                :auth_provider, :pin_hash, :shop_name, 0, 0, NOW()
            )
        ');

        $stmt->execute([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => $passwordHash,
            'auth_provider' => $authProvider,
            'pin_hash'      => $pinHash,
            'shop_name'     => $shopName,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Verify login credentials against email and password.
     * Rejects if:
     *   1. Account does not exist.
     *   2. password_hash is NULL (crucial: Google-only accounts cannot log in via password).
     *   3. Account is currently locked (failed_attempts >= threshold).
     *   4. password_verify() fails.
     *
     * On success:
     *   - Resets failed_attempts to 0.
     *   - Updates last_login to current timestamp.
     *
     * @param string $email
     * @param string $password
     * @return array<string, mixed>|null Authenticated user array or null
     */
    public static function verifyLogin(string $email, string $password): ?array {
        $user = self::findByEmail($email);

        // 1. Account must exist
        if (!$user) {
            return null;
        }

        // 2. Reject if password_hash is NULL (Google-only account)
        if (empty($user['password_hash'])) {
            return null;
        }

        // 3. Reject if account is locked
        if (!empty($user['locked'])) {
            return null;
        }

        // 4. Verify password against stored hash
        if (!password_verify($password, (string)$user['password_hash'])) {
            return null;
        }

        // 5. Success: update last_login and reset failed_attempts
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET last_login = NOW(), failed_attempts = 0 WHERE user_id = :id');
        $stmt->execute(['id' => (int)$user['user_id']]);

        return $user;
    }

    /**
     * Set or update the user's security PIN.
     * If $pin is empty string, clears the PIN (sets pin_hash to NULL).
     *
     * @param int $userId
     * @param string $pin 4 to 6 digit string, or empty to clear
     * @return bool
     */
    public static function setPin(int $userId, string $pin): bool {
        $pdo = Database::getConnection();
        $pinClean = trim($pin);
        $pinHash  = $pinClean !== '' ? password_hash($pinClean, PASSWORD_DEFAULT) : null;

        $stmt = $pdo->prepare('UPDATE users SET pin_hash = :pin_hash WHERE user_id = :id');
        return $stmt->execute([
            'pin_hash' => $pinHash,
            'id'       => $userId,
        ]);
    }

    /**
     * Verify a submitted PIN against user's stored pin_hash.
     *
     * @param int $userId
     * @param string $pin
     * @return bool
     */
    public static function verifyPin(int $userId, string $pin): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT pin_hash FROM users WHERE user_id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $hash = $stmt->fetchColumn();

        if (!$hash) {
            return false;
        }
        return password_verify(trim($pin), (string)$hash);
    }

    /**
     * Check if an account is locked by email.
     *
     * @param string $email
     * @return bool
     */
    public static function isLocked(string $email): bool {
        $user = self::findByEmail($email);
        return $user !== null && !empty($user['locked']);
    }

    /**
     * Record a failed login attempt for an email address.
     * If user exists, increments failed_attempts.
     * If threshold (MAX_FAILED_ATTEMPTS = 5) reached, sets locked = 1.
     *
     * @param string $email
     * @return array{exists: bool, locked: bool, failed_attempts: int, remaining: int}
     */
    public static function recordFailedAttempt(string $email): array {
        $user = self::findByEmail($email);

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
            'id'       => (int)$user['user_id'],
        ]);

        return [
            'exists'          => true,
            'locked'          => (bool)$locked,
            'failed_attempts' => $newAttempts,
            'remaining'       => max(0, $maxAttempts - $newAttempts),
        ];
    }

    /**
     * Check if a user currently has a password configured.
     * Google-only accounts will return false.
     *
     * @param int $userId
     * @return bool
     */
    public static function hasPassword(int $userId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $hash = $stmt->fetchColumn();
        return !empty($hash);
    }

    /**
     * Verify whether a submitted password matches current user password.
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
     * @param string $newPassword
     * @return bool
     */
    public static function updatePassword(int $userId, string $newPassword): bool {
        $pdo = Database::getConnection();
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // If previously Google-only, elevate auth_provider to 'both'
        $stmt = $pdo->prepare('
            UPDATE users
            SET password_hash = :hash,
                auth_provider = CASE WHEN auth_provider = "google" THEN "both" ELSE auth_provider END
            WHERE user_id = :id
        ');
        return $stmt->execute([
            'hash' => $newHash,
            'id'   => $userId,
        ]);
    }

    /**
     * Unlock an account and reset failed attempts counter.
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
