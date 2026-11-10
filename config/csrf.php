<?php
declare(strict_types=1);

/**
 * config/csrf.php
 * Cross-Site Request Forgery (CSRF) defense engine.
 * Generates cryptographically secure tokens, renders hidden HTML fields,
 * and performs timing-safe token verification.
 */

// Ensure active session before generating/verifying tokens
if (session_status() === PHP_SESSION_NONE) {
    if (file_exists(__DIR__ . '/session.php')) {
        require_once __DIR__ . '/session.php';
        if (function_exists('init_secure_session')) {
            init_secure_session();
        }
    } elseif (!headers_sent()) {
        session_start();
    } else {
        @session_start();
    }
}

/**
 * Generate or retrieve the CSRF token for the current session.
 *
 * @return string 64-character hexadecimal security token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

/**
 * Render a hidden HTML input field containing the current CSRF token.
 *
 * @return string HTML <input type="hidden"> field
 */
function csrf_field(): string {
    $token = csrf_token();
    $escaped = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $escaped . '">';
}

/**
 * Verify a submitted CSRF token using timing-attack safe comparison.
 *
 * @param string|null $token Submitted token from POST body
 * @return bool True if token matches current session token, false otherwise
 */
function csrf_verify(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token) || !is_string($token)) {
        return false;
    }
    return hash_equals((string)$_SESSION['csrf_token'], (string)$token);
}
