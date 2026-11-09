<?php
declare(strict_types=1);

/**
 * EquityFlow - Singleton Database Connection
 *
 * Provides a single shared PDO database connection instance.
 * Enforces strict error modes, disables emulated prepared statements,
 * and sets full UTF-8 (utf8mb4) support for Bangla characters.
 */

class Database {
    // Holds the singleton PDO instance
    private static ?PDO $instance = null;

    // Private constructor prevents direct instantiation
    private function __construct() {}

    // Private clone prevents copying the instance
    private function __clone() {}

    // Prevent deserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton Database instance.");
    }

    /**
     * Get the shared PDO connection instance.
     *
     * @return PDO
     * @throws PDOException When connection cannot be established
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            // Load credentials: try local config.php first, fallback to config.sample.php
            $configFile = __DIR__ . '/config.php';
            if (file_exists($configFile)) {
                require_once $configFile;
            } elseif (file_exists(__DIR__ . '/config.sample.php')) {
                require_once __DIR__ . '/config.sample.php';
            }

            // Read database constants with safe fallbacks
            $host    = defined('DB_HOST') ? DB_HOST : 'localhost';
            $port    = defined('DB_PORT') ? DB_PORT : '3306';
            $dbname  = defined('DB_NAME') ? DB_NAME : 'equityflow';
            $user    = defined('DB_USER') ? DB_USER : 'root';
            $pass    = defined('DB_PASS') ? DB_PASS : '';
            $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';

            // DSN with host, port, database name, and utf8mb4 charset
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

            // PDO driver options
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Return rows as associative arrays
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Native prepared statements only
            ];

            // Establish connection
            self::$instance = new PDO($dsn, $user, $pass, $options);
        }

        return self::$instance;
    }

    /**
     * Reset the active connection (useful in testing or error recovery).
     */
    public static function resetConnection(): void {
        self::$instance = null;
    }
}
