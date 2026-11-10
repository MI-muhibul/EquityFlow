<?php
declare(strict_types=1);

/**
 * EquityFlow - Bilingual Localization Engine
 *
 * Provides translation helpers, fallback resolution (Bangla -> English -> Key),
 * placeholder replacement, and language session switching with safe redirection.
 */

// Global dictionary cache across function calls
$GLOBALS['_lang_cache'] = [];

/**
 * Get the currently active language code ('bn' or 'en').
 * Defaults to 'bn' (Bangla) if unset or invalid.
 */
function get_current_lang(): string {
    if (session_status() === PHP_SESSION_NONE) {
        // Ensure session is started if not already
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

    $lang = $_SESSION['lang'] ?? 'bn';
    return in_array($lang, ['bn', 'en'], true) ? $lang : 'bn';
}

/**
 * Load and cache a language dictionary array from disk.
 *
 * @param string $langCode 'bn' or 'en'
 * @return array<string, string>
 */
function load_language_dict(string $langCode): array {
    if (isset($GLOBALS['_lang_cache'][$langCode])) {
        return $GLOBALS['_lang_cache'][$langCode];
    }

    $filePath = __DIR__ . '/../lang/' . $langCode . '.php';
    if (file_exists($filePath)) {
        $dict = require $filePath;
        if (is_array($dict)) {
            $GLOBALS['_lang_cache'][$langCode] = $dict;
            return $dict;
        }
    }

    $GLOBALS['_lang_cache'][$langCode] = [];
    return [];
}

/**
 * Translate a key with fallback hierarchy and placeholder interpolation.
 * Hierarchy: Active Language ('bn') -> English ('en') -> $key itself.
 *
 * @param string $key Translation dictionary key
 * @param array<string, scalar> $replace Key-value pairs for string replacement (e.g. [':attempts' => 3])
 * @return string Translated string
 */
function t(string $key, array $replace = []): string {
    $activeLang = get_current_lang();
    $activeDict = load_language_dict($activeLang);

    // 1. Try active language dictionary
    if (isset($activeDict[$key])) {
        $text = $activeDict[$key];
    } else {
        // 2. Fallback to English dictionary if active language is not English
        $enDict = load_language_dict('en');
        if (isset($enDict[$key])) {
            $text = $enDict[$key];
        } else {
            // 3. Fallback to the raw key string
            $text = $key;
        }
    }

    // Perform placeholder replacement if arguments provided
    if (!empty($replace)) {
        foreach ($replace as $placeholder => $value) {
            $text = str_replace((string)$placeholder, (string)$value, $text);
        }
    }

    return $text;
}

/**
 * Echo HTML-escaped translation string.
 *
 * @param string $key Translation dictionary key
 * @param array<string, scalar> $replace Key-value pairs for string replacement
 */
function _e(string $key, array $replace = []): void {
    echo htmlspecialchars(t($key, $replace), ENT_QUOTES, 'UTF-8');
}

/**
 * Store the language choice in session after validating it is 'bn' or 'en',
 * then safely redirect back to the previous page.
 *
 * @param string|null $lang Target language code ('bn' or 'en'). If null, checks $_GET or $_POST.
 * @return never
 */
function set_lang(?string $lang = null): void {
    if (session_status() === PHP_SESSION_NONE) {
        if (file_exists(__DIR__ . '/session.php')) {
            require_once __DIR__ . '/session.php';
            if (function_exists('init_secure_session')) {
                init_secure_session();
            }
        } else {
            session_start();
        }
    }

    // Extract language code from parameter or request superglobals
    if ($lang === null) {
        $lang = $_GET['lang'] ?? $_POST['lang'] ?? null;
    }

    // Validate that language is strictly 'bn' or 'en'
    if (is_string($lang) && in_array(strtolower($lang), ['bn', 'en'], true)) {
        $_SESSION['lang'] = strtolower($lang);
    }

    // Determine safe redirection target
    $redirectUrl = 'index.php?page=dashboard';

    if (!empty($_SERVER['HTTP_REFERER'])) {
        $referer = (string)$_SERVER['HTTP_REFERER'];
        $parts   = parse_url($referer);

        // Security check: only allow same-host redirection (mitigate open redirect)
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        $refererHost = $parts['host'] ?? '';

        if ($refererHost === '' || $refererHost === $currentHost) {
            // Remove 'lang=...' parameter from query string to prevent stale loops
            $path = $parts['path'] ?? 'index.php';
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $queryParams);
                unset($queryParams['lang']);
                $newQuery = http_build_query($queryParams);
                $redirectUrl = $path . ($newQuery !== '' ? '?' . $newQuery : '');
            } else {
                $redirectUrl = $path;
            }
        }
    }

    // Execute safe HTTP redirect
    header('Location: ' . $redirectUrl);
    exit;
}
