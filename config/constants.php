<?php
declare(strict_types=1);

/**
 * config/constants.php
 * -----------------------------------------------------------------------------
 * EquityFlow — System Constants
 *
 * Loads the .env file first (via config/env.php), then defines PHP constants
 * from the environment values.  config/config.php is still supported as a
 * fallback for existing local installs.
 *
 * Priority (highest wins):
 *   1. Already-defined PHP constants (guard with !defined)
 *   2. .env file (loaded by env.php → putenv / $_ENV)
 *   3. config/config.php hardcoded values
 *   4. Built-in defaults below
 * -----------------------------------------------------------------------------
 */

// 1. Load .env into $_ENV / putenv() — must happen before any getenv() call
require_once __DIR__ . '/env.php';

// 2. Load config/config.php if it exists (legacy local install support)
if (!defined('DB_HOST') && file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

// ─── Application Identity ────────────────────────────────────────────────────
if (!defined('APP_NAME')) {
    define('APP_NAME', getenv('APP_NAME') ?: 'EquityFlow');
}
if (!defined('APP_TAGLINE')) {
    define('APP_TAGLINE', 'Where Financial Records Flow with Accuracy');
}
if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'local');
}

// ─── Currency & Formatting ────────────────────────────────────────────────────
if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', '৳');
}

// ─── Database Credentials ─────────────────────────────────────────────────────
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'equityflow');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') ?: '');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
}

// ─── Security & Session Lifecycle ─────────────────────────────────────────────
if (!defined('SESSION_TIMEOUT')) {
    // Inactivity timeout in seconds (default 30 minutes)
    define('SESSION_TIMEOUT', (int)(getenv('SESSION_TIMEOUT') ?: 1800));
}
if (!defined('MAX_FAILED_ATTEMPTS')) {
    // Account lockout threshold after N failed logins
    define('MAX_FAILED_ATTEMPTS', (int)(getenv('MAX_FAILED_ATTEMPTS') ?: 5));
}
if (!defined('LOCKOUT_TIME')) {
    // Lockout duration in seconds (default 15 minutes)
    define('LOCKOUT_TIME', (int)(getenv('LOCKOUT_TIME') ?: 900));
}

// ─── Google OAuth 2.0 Credentials ─────────────────────────────────────────────
// These are read from .env — they must NEVER be hard-coded in PHP files.
if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
}
if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
}
if (!defined('GOOGLE_REDIRECT_URI')) {
    $envRedirect = getenv('GOOGLE_REDIRECT_URI');
    if ($envRedirect) {
        define('GOOGLE_REDIRECT_URI', $envRedirect);
    } else {
        // Auto-detect scheme + host (works in both XAMPP http and production https)
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        define('GOOGLE_REDIRECT_URI', $scheme . $host . '/EquityFlow/index.php?page=google-callback');
    }
}
