<?php
declare(strict_types=1);

/**
 * EquityFlow - Secure Session Manager
 *
 * Configures hardened session cookies (HttpOnly, SameSite, Secure),
 * tracks user activity timestamps, handles automatic inactivity timeout,
 * and provides safe session ID regeneration.
 */

require_once __DIR__ . '/constants.php';

/**
 * Initialize a hardened session with secure cookie parameters.
 */
function init_secure_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Detect if current connection is over HTTPS
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    // Set hardened cookie configuration
    session_set_cookie_params([
        'lifetime' => 0,          // Cookie expires when browser closes
        'path'     => '/',        // Available across entire domain
        'domain'   => '',         // Current host
        'secure'   => $isSecure,  // True for HTTPS, false for local HTTP (XAMPP)
        'httponly' => true,       // Prevent JavaScript access to cookie
        'samesite' => 'Lax',      // Protect against cross-site request forgery
    ]);

    // Enable strict session mode to mitigate session adoption/fixation
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_start();

    // Check inactivity timeout (SESSION_TIMEOUT = 1800s / 30 mins)
    enforce_session_timeout();
}

/**
 * Safely regenerate session ID to prevent session fixation attacks.
 * Should be called whenever authentication status or privileges change.
 */
function regenerate_session_id_safe(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Check if the active session has exceeded the inactivity threshold.
 * Destroys expired sessions and restarts a clean session.
 */
function enforce_session_timeout(): void {
    $now = time();
    $timeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 1800;

    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity'] > $timeout)) {
        // Clear all session variables
        $_SESSION = [];

        // Delete the session cookie in browser
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy server session storage
        session_destroy();

        // Start a fresh empty session
        session_start();
        $_SESSION['session_expired'] = true;
    }

    // Refresh last activity timestamp
    $_SESSION['last_activity'] = $now;
}

/**
 * Terminate and clear the active session completely (used on Logout).
 */
function terminate_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
}
