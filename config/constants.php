<?php
declare(strict_types=1);

/**
 * EquityFlow - System Constants
 * Core application constants and security thresholds
 */

// Application Identification
define('APP_NAME', 'EquityFlow');
define('APP_TAGLINE', 'Where Financial Records Flow with Accuracy');

// Currency & Formatting
define('CURRENCY_SYMBOL', '৳');

// Security & Session Lifecycle
define('SESSION_TIMEOUT', 1800);       // Inactivity timeout in seconds (30 minutes)
define('MAX_FAILED_ATTEMPTS', 5);      // Account lockout threshold after 5 failed logins
define('LOCKOUT_TIME', 900);           // Lockout duration in seconds (15 minutes)

// Google OAuth 2.0 Credentials (can be overridden in config/config.php or environment)
if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com');
}
if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET');
}
if (!defined('GOOGLE_REDIRECT_URI')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('GOOGLE_REDIRECT_URI', $scheme . $host . '/EquityFlow/index.php?page=google-callback');
}
