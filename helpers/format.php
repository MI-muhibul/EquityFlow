<?php
declare(strict_types=1);

/**
 * helpers/format.php
 * Utility functions used across all views.
 * - e()           : safe HTML output (XSS protection)
 * - format_money(): formats ৳1,234.00
 * - flash_set()   : store a one-time session alert
 * - flash_show()  : render and clear all alerts
 */

// Load constants if not already loaded
if (!defined('CURRENCY_SYMBOL') && file_exists(__DIR__ . '/../config/constants.php')) {
    require_once __DIR__ . '/../config/constants.php';
}

/* -----------------------------------------------------------------------
 * e() — escape any value for safe HTML output
 * Always use this instead of echo on untrusted data.
 * --------------------------------------------------------------------- */
function e(mixed $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* -----------------------------------------------------------------------
 * format_money() — display a number as ৳1,234.00
 * Negative values render as -৳500.00
 * --------------------------------------------------------------------- */
function format_money(mixed $amount, ?string $symbol = null): string {
    $num = (float)($amount ?? 0);
    $sym = $symbol ?? (defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '৳');
    $sign = $num < 0 ? '-' : '';
    return $sign . $sym . number_format(abs($num), 2, '.', ',');
}

/* -----------------------------------------------------------------------
 * flash_set() — queue a one-time message in the session
 * $type: 'success' | 'error' | 'warning' | 'info'
 * --------------------------------------------------------------------- */
function flash_set(string $type, string $message): void {
    // Start session safely if not running
    if (session_status() === PHP_SESSION_NONE) {
        if (file_exists(__DIR__ . '/../config/session.php')) {
            require_once __DIR__ . '/../config/session.php';
            if (function_exists('init_secure_session')) {
                init_secure_session();
            }
        } elseif (!headers_sent()) {
            session_start();
        } else {
            @session_start();
        }
    }

    $allowed = ['success', 'error', 'warning', 'info'];
    $type    = in_array($type, $allowed, true) ? $type : 'info';

    $_SESSION['_flash'][$type][] = $message;
}

/* -----------------------------------------------------------------------
 * flash_has() — returns true if any flash messages are waiting
 * --------------------------------------------------------------------- */
function flash_has(): bool {
    return !empty($_SESSION['_flash']) && is_array($_SESSION['_flash']);
}

/* -----------------------------------------------------------------------
 * flash_show() — render every queued alert then delete them
 * Outputs accessible <div> alerts; zero JavaScript required.
 * --------------------------------------------------------------------- */
function flash_show(): void {
    if (!flash_has()) {
        return;
    }

    // Grab and immediately clear so messages show only once
    $all = $_SESSION['_flash'];
    unset($_SESSION['_flash']);

    // Unicode icons — stored as UTF-8 string literals
    $icons = [
        'success' => "\u{2713}",   // ✓
        'error'   => "\u{2715}",   // ✕
        'warning' => "\u{26A0}",   // ⚠
        'info'    => "\u{2139}",   // ℹ
    ];

    echo '<div class="flash-container" role="region" aria-label="Notifications">' . "\n";
    foreach ($all as $type => $messages) {
        if (!is_array($messages)) {
            continue;
        }
        $t    = in_array($type, ['success','error','warning','info'], true) ? $type : 'info';
        $icon = $icons[$t] ?? "\u{2139}";

        foreach ($messages as $msg) {
            echo '  <div class="alert alert-' . e($t) . '" role="alert">' . "\n";
            echo '    <span class="alert-icon" aria-hidden="true">' . e($icon) . '</span>' . "\n";
            echo '    <span class="alert-text">'  . e($msg)  . '</span>'  . "\n";
            echo '  </div>' . "\n";
        }
    }
    echo '</div>' . "\n";
}
