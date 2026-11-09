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
