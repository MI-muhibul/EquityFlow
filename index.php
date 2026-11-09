<?php
declare(strict_types=1);

/**
 * EquityFlow - Front Controller & Request Router
 * Tagline: Where Financial Records Flow with Accuracy
 *
 * This file handles all incoming requests, manages the active language session,
 * validates requested routes against a strict whitelist, and dispatches to
 * the appropriate controller or renders a 404 error fallback.
 */

// Start session to persist user state and language selection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------------------------------------------------------
// 1. Language Loader (Bangla by default, English supported)
// -----------------------------------------------------------------------------
if (isset($_GET['lang']) && in_array($_GET['lang'], ['bn', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}
$currentLang = $_SESSION['lang'] ?? 'bn';

// Load language translation array
$langFile = __DIR__ . '/lang/' . $currentLang . '.php';
if (file_exists($langFile)) {
    $lang = require $langFile;
} else {
    $lang = [];
}

// Helper function to safely output translated text with htmlspecialchars
function __e(string $key, array $lang, string $default = ''): string {
    $text = $lang[$key] ?? $default;
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// -----------------------------------------------------------------------------
// 2. Route Whitelist (Mapping ?page=name to Controller Class)
// -----------------------------------------------------------------------------
$routeWhitelist = [
    'dashboard'  => 'DashboardController',
    'customers'  => 'CustomerController',
    'suppliers'  => 'SupplierController',
    'ledger'     => 'LedgerController',
    'cashbook'   => 'CashbookController',
    'reports'    => 'ReportController',
    'backup'     => 'BackupController',
    'auth'       => 'AuthController',
];

// Determine the requested page from GET parameter (default: 'dashboard')
$requestedPage = isset($_GET['page']) && is_string($_GET['page']) ? trim($_GET['page']) : 'dashboard';

// -----------------------------------------------------------------------------
// 3. Fallback View Function (Dark, High-Contrast, Zero JavaScript)
// -----------------------------------------------------------------------------
function renderErrorView(int $statusCode, string $title, string $message, string $details = '', string $currentLang = 'bn'): void {
    http_response_code($statusCode);
    $toggleLang = ($currentLang === 'bn') ? 'en' : 'bn';
    $toggleLabel = ($currentLang === 'bn') ? 'English' : 'বাংলা';
    ?>
    <!DOCTYPE html>
    <html lang="<?= htmlspecialchars($currentLang, ENT_QUOTES, 'UTF-8') ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> - EquityFlow</title>
        <style>
            :root {
                --bg-base: #0b0f19;
                --bg-surface: #151d2e;
                --border-color: #2a3754;
                --text-primary: #f8fafc;
                --text-secondary: #94a3b8;
                --accent-danger: #ef4444;
                --accent-primary: #10b981;
                --code-bg: #030712;
            }
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }
            body {
                background-color: var(--bg-base);
                color: var(--text-primary);
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
            }
            .card {
                background-color: var(--bg-surface);
                border: 1px solid var(--border-color);
                border-radius: 12px;
                padding: 2.5rem;
                max-width: 520px;
                width: 100%;
                text-align: center;
                box-shadow: 0 12px 36px rgba(0, 0, 0, 0.6);
            }
            .status-badge {
                display: inline-block;
                background-color: rgba(239, 68, 68, 0.15);
                color: var(--accent-danger);
                border: 1px solid rgba(239, 68, 68, 0.3);
                font-weight: 700;
                font-size: 2.25rem;
                padding: 0.25rem 1rem;
                border-radius: 8px;
                margin-bottom: 1.25rem;
            }
            h1 {
                font-size: 1.5rem;
                margin-bottom: 0.75rem;
                color: var(--text-primary);
            }
            p {
                color: var(--text-secondary);
                line-height: 1.6;
                margin-bottom: 1.25rem;
                font-size: 0.95rem;
            }
            .code-text {
                display: inline-block;
                background-color: var(--code-bg);
                color: #fbbf24;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                padding: 0.2rem 0.5rem;
                border-radius: 4px;
                border: 1px solid #1f293d;
            }
            .actions {
                margin-top: 1.75rem;
                display: flex;
                gap: 0.75rem;
                justify-content: center;
                flex-wrap: wrap;
            }
            .btn {
                text-decoration: none;
                display: inline-block;
                padding: 0.75rem 1.25rem;
                border-radius: 6px;
                font-size: 0.9rem;
                font-weight: 600;
                transition: opacity 0.2s ease;
            }
            .btn-primary {
                background-color: var(--accent-primary);
                color: #ffffff;
            }
            .btn-secondary {
                background-color: #1e293b;
                color: var(--text-primary);
                border: 1px solid var(--border-color);
            }
            .btn:hover {
                opacity: 0.9;
            }
            .footer-tag {
                margin-top: 2rem;
                font-size: 0.75rem;
                color: #64748b;
                border-top: 1px solid #1e293b;
                padding-top: 1rem;
            }
        </style>
    </head>
    <body>
        <main class="card">
            <div class="status-badge"><?= (int)$statusCode ?></div>
            <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($details !== ''): ?>
                <p><span class="code-text"><?= htmlspecialchars($details, ENT_QUOTES, 'UTF-8') ?></span></p>
            <?php endif; ?>
            <div class="actions">
                <a href="index.php?page=dashboard" class="btn btn-primary">ড্যাশবোর্ড / Dashboard</a>
                <a href="index.php?page=<?= htmlspecialchars($_GET['page'] ?? 'dashboard', ENT_QUOTES, 'UTF-8') ?>&amp;lang=<?= htmlspecialchars($toggleLang, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary">
                    <?= htmlspecialchars($toggleLabel, ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
            <div class="footer-tag">EquityFlow &bull; Where Financial Records Flow with Accuracy</div>
        </main>
    </body>
    </html>
    <?php
    exit;
}

// -----------------------------------------------------------------------------
// 4. Whitelist Verification
// -----------------------------------------------------------------------------
if (!array_key_exists($requestedPage, $routeWhitelist)) {
    $title = __e('page_not_found', $lang, 'পৃষ্ঠা পাওয়া যায়নি / Page Not Found');
    $msg   = __e('page_not_found_desc', $lang, 'অনুরোধ করা পৃষ্ঠাটি রাউট তালিকায় পাওয়া যায়নি।');
    renderErrorView(404, $title, $msg, 'Route: ?page=' . $requestedPage, $currentLang);
}

// -----------------------------------------------------------------------------
// 5. Controller File Resolution & Dispatch
// -----------------------------------------------------------------------------
$controllerClass = $routeWhitelist[$requestedPage];
$controllerFile  = __DIR__ . '/controllers/' . $controllerClass . '.php';

// Check if controller file exists on disk
if (!file_exists($controllerFile)) {
    $title = __e('controller_missing', $lang, 'কন্ট্রোলার পাওয়া যায়নি / Controller Not Found');
    $msg   = __e('controller_missing_desc', $lang, 'এই রাউটের জন্য নির্ধারিত কন্ট্রোলার ফাইলটি এখনো প্রস্তুত করা হয়নি।');
    renderErrorView(404, $title, $msg, 'File: controllers/' . $controllerClass . '.php', $currentLang);
}

// Load and instantiate controller
require_once $controllerFile;

if (class_exists($controllerClass)) {
    $controller = new $controllerClass();
    
    // Execute default controller action
    if (method_exists($controller, 'index')) {
        $controller->index();
    } elseif (method_exists($controller, 'handleRequest')) {
        $controller->handleRequest();
    }
} else {
    $title = __e('controller_missing', $lang, 'কন্ট্রোলার পাওয়া যায়নি / Controller Not Found');
    $msg   = 'The controller class exists in file but definition was not found.';
    renderErrorView(500, $title, $msg, 'Class: ' . $controllerClass, $currentLang);
}
