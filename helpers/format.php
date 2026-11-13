<?php
declare(strict_types=1);

/**
 * helpers/format.php
 * -----------------------------------------------------------------------------
 * Utility functions used across all views in EquityFlow.
 * - e()           : Safe HTML output escaping (ENT_QUOTES, UTF-8 XSS protection)
 * - format_money(): Formats numbers as ৳1,234.00 (negative as -৳500.00)
 * - flash_set()   : Store a one-time alert in session
 * - flash_show()  : Render all queued flash alerts and clear them
 * - flash_has()   : Check if any alerts are pending
 * -----------------------------------------------------------------------------
 */

// Load constants if not already loaded
if (!defined('CURRENCY_SYMBOL') && file_exists(__DIR__ . '/../config/constants.php')) {
    require_once __DIR__ . '/../config/constants.php';
}

/**
 * Escape any value for safe HTML output.
 * Always use this instead of raw echo on dynamic data.
 *
 * @param mixed $value
 * @return string
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Format a number as Bangladeshi Taka currency, e.g. ৳1,234.00.
 * Negative numbers render with the minus sign in front, e.g. -৳500.00.
 *
 * @param mixed $amount
 * @param string|null $symbol Optional override for currency symbol
 * @return string
 */
function format_money(mixed $amount, ?string $symbol = null): string
{
    $num  = (float)($amount ?? 0.0);
    $sym  = $symbol ?? (defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '৳');
    $sign = $num < 0 ? '-' : '';
    return $sign . $sym . number_format(abs($num), 2, '.', ',');
}

/**
 * Queue a one-time flash notification message in session.
 *
 * @param string $type 'success' | 'error' | 'warning' | 'info'
 * @param string $message User-facing message string
 */
function flash_set(string $type, string $message): void
{
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

/**
 * Check if any flash messages are pending in session.
 *
 * @return bool
 */
function flash_has(): bool
{
    return !empty($_SESSION['_flash']) && is_array($_SESSION['_flash']);
}

/**
 * Render and immediately clear all queued flash notification alerts.
 * Outputs accessible HTML with semantic alert roles and icons (zero JS).
 */
function flash_show(): void
{
    if (!flash_has()) {
        return;
    }

    // Grab and immediately clear session store so messages show only once
    $all = $_SESSION['_flash'];
    unset($_SESSION['_flash']);

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
        $t    = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
        $icon = $icons[$t] ?? "\u{2139}";

        foreach ($messages as $msg) {
            echo '  <div class="alert alert-' . e($t) . '" role="alert">' . "\n";
            echo '    <span class="alert-icon" aria-hidden="true">' . e($icon) . '</span>' . "\n";
            echo '    <span class="alert-text">' . e($msg) . '</span>' . "\n";
            echo '  </div>' . "\n";
        }
    }
    echo '</div>' . "\n";
}
