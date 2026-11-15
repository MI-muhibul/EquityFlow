<?php
declare(strict_types=1);

/**
 * config/config.sample.php
 * -----------------------------------------------------------------------------
 * EquityFlow — Configuration Sample File
 *
 * NEW (recommended) approach: use .env file at the project root.
 *   1. Copy .env.example → .env
 *   2. Fill in your real credentials in .env
 *   3. The .env loader (config/env.php) is called automatically by constants.php
 *
 * Legacy approach (still supported): copy this file to config/config.php.
 *   - config/config.php is loaded as a fallback by Database.php if .env
 *     values are not found.
 *   - config/config.php is listed in .gitignore — never commit it.
 * -----------------------------------------------------------------------------
 */

// ─── Database ─────────────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');   // XAMPP default
define('DB_PORT',    '3306');        // MySQL/MariaDB default port
define('DB_NAME',    'equityflow'); // Create this database in phpMyAdmin
define('DB_USER',    'root');        // XAMPP default username
define('DB_PASS',    '');            // XAMPP default: empty password
define('DB_CHARSET', 'utf8mb4');    // Full Unicode + Bangla support

// ─── Google OAuth 2.0 ─────────────────────────────────────────────────────────
// Obtain credentials from https://console.cloud.google.com/
//   APIs & Services → Credentials → Create OAuth 2.0 Client ID (Web application)
//
// PREFERRED: set these in .env instead of here.
define('GOOGLE_CLIENT_ID',     'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI',  'http://localhost/EquityFlow/index.php?page=google-callback');
