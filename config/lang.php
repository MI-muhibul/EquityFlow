<?php
declare(strict_types=1);

/**
 * config/lang.php
 * -----------------------------------------------------------------------------
 * EquityFlow Bilingual Localization Engine (English & Bangla)
 *
 * Rules:
 *   - t($key, $replace = []): Resolves translation from active language.
 *     Falls back to English ('en'), then to $key itself if missing.
 *   - set_lang(?string $lang = null): Validates 'bn' or 'en', updates session,
 *     and safely redirects back to HTTP_REFERER (sanitized) or dashboard.
 *   - get_current_lang(): Returns active language ('bn' by default).
 * -----------------------------------------------------------------------------
 */

// Memory cache for language arrays to prevent multiple disk reads per request
$GLOBALS['_lang_cache'] = [];

/**
 * Get active language code ('bn' or 'en'). Defaults to 'bn' (Bangla).
 */
function get_current_lang(): string
{
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

    $lang = $_SESSION['lang'] ?? 'bn';
    return in_array($lang, ['bn', 'en'], true) ? $lang : 'bn';
}

/**
 * Load dictionary array for a given language code.
 *
 * @param string $langCode 'bn' or 'en'
 * @return array<string, string>
 */
function load_language_dict(string $langCode): array
{
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
 * Translate a key.
 * Fallback hierarchy: Active Language ('bn') -> English ('en') -> $key.
 *
 * @param string $key Dictionary key
 * @param array<string, scalar> $replace Optional placeholder array e.g. [':attempts' => 3]
 * @return string
 */
function t(string $key, array $replace = []): string
{
    $activeLang = get_current_lang();
    $activeDict = load_language_dict($activeLang);

    // 1. Try active language
    if (isset($activeDict[$key]) && is_string($activeDict[$key])) {
        $text = $activeDict[$key];
    } else {
        // 2. Fall back to English
        $enDict = load_language_dict('en');
        if (isset($enDict[$key]) && is_string($enDict[$key])) {
            $text = $enDict[$key];
        } else {
            // 3. Fall back to raw key
            $text = $key;
        }
    }

    // Replace placeholders if any provided
    if (!empty($replace)) {
        foreach ($replace as $placeholder => $val) {
            $text = str_replace((string)$placeholder, (string)$val, $text);
        }
    }

    return $text;
}

/**
 * Escaped translation output helper.
 */
function _e(string $key, array $replace = []): void
{
    echo htmlspecialchars(t($key, $replace), ENT_QUOTES, 'UTF-8');
}

/**
 * Switch session language after validating 'bn' or 'en', then redirect back.
 *
 * @param string|null $lang
 * @return never
 */
function set_lang(?string $lang = null): void
{
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

    if ($lang === null) {
        $lang = $_GET['lang'] ?? $_POST['lang'] ?? null;
    }

    // Validate strictly: only 'bn' or 'en' allowed
    if (is_string($lang) && in_array(strtolower($lang), ['bn', 'en'], true)) {
        $_SESSION['lang'] = strtolower($lang);
    }

    // Redirection target determination with open-redirect mitigation
    $redirectUrl = 'index.php?page=dashboard';

    if (!empty($_SERVER['HTTP_REFERER'])) {
        $referer = (string)$_SERVER['HTTP_REFERER'];
        $parts   = parse_url($referer);

        $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        $refererHost = $parts['host'] ?? '';

        if ($refererHost === '' || $refererHost === $currentHost) {
            $path = $parts['path'] ?? 'index.php';
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $queryParams);
                unset($queryParams['lang']); // Strip lang switch parameter
                $newQuery = http_build_query($queryParams);
                $redirectUrl = $path . ($newQuery !== '' ? '?' . $newQuery : '');
            } else {
                $redirectUrl = $path;
            }
        }
    }

    header('Location: ' . $redirectUrl);
    exit;
}
