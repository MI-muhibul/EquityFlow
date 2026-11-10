<?php
declare(strict_types=1);

/**
 * EquityFlow - Formatting & Flash Notification Helpers
 *
 * Provides safe HTML escaping, currency formatting (৳),
 * and session-based one-time flash notification messages.
 */

// Ensure constants and session functions are accessible
if (file_exists(__DIR__ . '/../config/constants.php')) {
    require_once __DIR__ . '/../config/constants.php';
}

/**
 * Escape strings safely for HTML output to prevent Cross-Site Scripting (XSS).
 *
 * @param string|int|float|null $value Raw untrusted text
 * @return string HTML-safe escaped string
 */
function e(mixed $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Format monetary values in Bangladeshi Taka (৳) with 2 decimal places and thousands separators.
 * Example: 1234.5 -> "৳1,234.50", -500 -> "-৳500.00"
 *
 * @param float|int|string|null $amount Monetary value
 * @param string|null $symbol Currency symbol (defaults to CURRENCY_SYMBOL or '৳')
 * @return string Formatted currency string
 */
function format_money(mixed $amount, ?string $symbol = null): string {
    $num = (float)($amount ?? 0);
    $sym = $symbol ?? (defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '৳');
    
    $prefix = $num < 0 ? '-' : '';
    return $prefix . $sym . number_format(abs($num), 2, '.', ',');
}

/**
 * Store a flash message in the active session for one-time display.
 *
 * @param string $type Message severity: 'success', 'error', 'warning', or 'info'
 * @param string $message Text content to display to the user
 */
function flash_set(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        if (file_exists(__DIR__ . '/../config/session.php')) {
            require_once __DIR__ . '/../config/session.php';
            if (function_exists('init_secure_session')) {
                init_secure_session();
            }
        } else {
            session_start();
        }
    }

    $validTypes = ['success', 'error', 'warning', 'info'];
    $normalizedType = in_array($type, $validTypes, true) ? $type : 'info';

    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    if (!isset($_SESSION['flash'][$normalizedType])) {
        $_SESSION['flash'][$normalizedType] = [];
    }

    $_SESSION['flash'][$normalizedType][] = $message;
}

/**
 * Check whether any flash messages exist in the current session.
 *
 * @return bool True if there are pending flash messages
 */
function flash_has(): bool {
    return !empty($_SESSION['flash']) && is_array($_SESSION['flash']);
}

/**
 * Render and immediately clear all pending flash messages.
 * Outputs semantic, high-contrast HTML alert boxes without JavaScript.
 */
function flash_show(): void {
    if (!flash_has()) {
        return;
    }

    $flashes = $_SESSION['flash'];
    unset($_SESSION['flash']); // Clear immediately after retrieval

    $icons = [
        'success' => '✓',
        'error'   => '✕',
        'warning' => '⚠',
        'info'    => 'ℹ',
    ];

    echo '<div class="flash-container" role="region" aria-label="Notifications">';
    foreach ($flashes as $type => $messages) {
        if (!is_array($messages)) {
            continue;
        }
        $alertType = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
        $icon = $icons[$alertType] ?? 'ℹ';

        foreach ($messages as $msg) {
            echo '<div class="alert alert-' . e($alertType) . '" role="alert">';
            echo '<span class="alert-icon" aria-hidden="true">' . e($icon) . '</span>';
            echo '<span class="alert-text">' . e($msg) . '</span>';
            echo '</div>';
        }
    }
    echo '</div>';
}
