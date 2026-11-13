<?php
declare(strict_types=1);

/**
 * EquityFlow - Database Configuration Sample
 *
 * Copy this file to 'config/config.php' and update with your local credentials.
 * Note: config/config.php is ignored by git to protect local credentials.
 */

// Database Host (default localhost in XAMPP)
define('DB_HOST', 'localhost');

// Database Port (default 3306 for MySQL/MariaDB)
define('DB_PORT', '3306');

// Database Name
define('DB_NAME', 'equityflow');

// Database Username (default root in XAMPP)
define('DB_USER', 'root');

// Database Password (default empty in XAMPP)
define('DB_PASS', '');

// Character Set (utf8mb4 for full Bangla Unicode support)
define('DB_CHARSET', 'utf8mb4');

// Google OAuth 2.0 Client Credentials
// Obtain these from Google Cloud Console (APIs & Services > Credentials > OAuth 2.0 Client IDs)
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', 'http://localhost/EquityFlow/index.php?page=google-callback');
