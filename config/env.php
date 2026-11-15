<?php
declare(strict_types=1);

/**
 * config/env.php
 * -----------------------------------------------------------------------------
 * Minimal .env file loader for EquityFlow.
 *
 * Reads key=value pairs from the project-root .env file and writes each one
 * into $_ENV and putenv() so the rest of the application can read them with
 * getenv() or $_ENV['KEY'].
 *
 * Rules:
 *   - Lines starting with # are comments and are ignored.
 *   - Blank lines are ignored.
 *   - Values may be unquoted or wrapped in single/double quotes.
 *   - Already-set environment variables are NOT overwritten (safe for real
 *     web-server environments that inject vars via Apache/Nginx config).
 *   - If .env does not exist the app falls back to values already defined in
 *     config.php / constants.php — no fatal error.
 *
 * Usage: require_once __DIR__ . '/env.php';  (called once, early in index.php)
 * -----------------------------------------------------------------------------
 */

function load_env(string $filePath): void
{
    // If the .env file does not exist, skip silently (config.php still works)
    if (!is_file($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comment lines (# ...) and empty lines
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Must contain '='
        if (!str_contains($line, '=')) {
            continue;
        }

        // Split on FIRST '=' only so values can contain '='
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Strip surrounding quotes (single or double)
        if (
            strlen($value) >= 2 &&
            (
                (str_starts_with($value, '"')  && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            )
        ) {
            $value = substr($value, 1, -1);
        }

        // Do NOT overwrite an already-set environment variable
        // (lets Apache/Nginx SetEnv directives take priority in production)
        if (getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

// Load project-root .env immediately when this file is required
load_env(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');
