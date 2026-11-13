<?php
declare(strict_types=1);

/**
 * config/auth_guard.php
 * Route access guards and inactivity timeout enforcement.
 * Ensures protected views are accessed by authenticated shop owners only.
 */

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/../helpers/format.php';

/**
 * Enforce that the user is authenticated.
 * If unauthenticated or if inactive for more than 30 minutes (SESSION_TIMEOUT),
 * securely destroys session and redirects to login with a translated message.
 */
function require_login(): void {
    init_secure_session();

    $now = time();
    $timeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 1800; // 30 minutes in seconds

    // 1. Check if user is not logged in
    if (empty($_SESSION['user_id'])) {
        flash_set('error', t('error_invalid_login'));
        header('Location: index.php?page=login');
        exit;
    }

    // 2. Check 30-minute inactivity timeout
    if (isset($_SESSION['last_activity']) && ($now - (int)$_SESSION['last_activity'] > $timeout)) {
        // Terminate expired session completely
        secure_logout();

        // Start fresh empty session to display translated timeout alert
        init_secure_session();
        flash_set('error', t('error_session_timeout'));
        header('Location: index.php?page=login');
        exit;
    }

    // 3. Activity verified: refresh timestamp to current time
    $_SESSION['last_activity'] = $now;
}

/**
 * Redirect logged-in shop owners away from guest pages (e.g. login, signup) to dashboard.
 */
function redirect_if_logged_in(): void {
    if (session_status() === PHP_SESSION_NONE) {
        init_secure_session();
    }
    if (!empty($_SESSION['user_id'])) {
        header('Location: index.php?page=dashboard');
        exit;
    }
}

/**
 * Securely log out current user by wiping session array,
 * expiring the browser cookie, and destroying server session data.
 */
function secure_logout(): void {
    if (session_status() === PHP_SESSION_NONE) {
        init_secure_session();
    }

    // 1. Clear session superglobal array
    $_SESSION = [];

    // 2. Expire session cookie in browser if headers not already sent
    if (ini_get('session.use_cookies') && !headers_sent()) {
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

    // 3. Destroy session on server
    session_destroy();
}

/**
 * Check if the active visitor is logged in.
 *
 * @return bool
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Get the current authenticated user's ID.
 *
 * @return int|null
 */
function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}
