<?php
declare(strict_types=1);

/**
 * views/layout/header.php
 * -----------------------------------------------------------------------
 * Shared page top — rendered at the start of every public/protected view.
 *
 * Before including this file, a controller may set:
 *   $pageTitle  (string) — overrides the default <title>
 *   $activePage (string) — highlights the active navigation link
 *
 * What this provides:
 *   • HTML5 DOCTYPE, <head> with viewport + CSS link
 *   • Sticky header with brand logo and main navigation:
 *       (Dashboard, Customers, Transactions, Inventory, Reports, Settings, Logout, language toggle)
 *   • Highlights the active page via 'active' CSS class
 *   • Opening <main class="main-wrap"> for page content
 *   • flash_show() — renders one-time session alerts
 * -----------------------------------------------------------------------
 * Rules: Zero JavaScript, all strings from lang files via t(), e() on all output.
 */

// --- Load core dependencies ---
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/lang.php';
require_once __DIR__ . '/../../helpers/format.php';

// Active page slug (used to highlight the correct nav link)
$activePage  = $activePage ?? (isset($_GET['page']) ? trim((string)$_GET['page']) : 'dashboard');

// Language switch target — the OTHER language
$currentLang = get_current_lang();
$toggleLang  = ($currentLang === 'bn') ? 'en' : 'bn';

// Build <title> — controllers may set $pageTitle before including header
$htmlTitle = isset($pageTitle)
    ? e($pageTitle) . ' — ' . e(t('app_name'))
    : e(t('app_name')) . ' — ' . e(t('app_tagline'));

// Helper: return 'active' CSS class if slug matches
if (!function_exists('navClass')) {
    function navClass(string $slug, string $active): string {
        if ($slug === $active) {
            return ' active';
        }
        // Match aliases for transactions/inventory
        if ($slug === 'transactions' && in_array($active, ['transactions', 'ledger'], true)) {
            return ' active';
        }
        if ($slug === 'inventory' && in_array($active, ['inventory', 'products'], true)) {
            return ' active';
        }
        return '';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e($currentLang) ?>" dir="ltr">
<head>
    <meta charset="UTF-8">
    <!-- Makes the page fit the device screen width on phones/tablets -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $htmlTitle ?></title>

    <!-- Bangla webfonts via Google Fonts (loads from internet) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap">

    <!-- Main dark-theme stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ===== Sticky Header ===================================================== -->
<header class="app-header" role="banner">
    <div class="header-inner">

        <!-- Brand / Logo -->
        <a href="index.php?page=dashboard" class="brand" aria-label="<?= e(t('app_name')) ?> — Home">
            <div class="brand-mark" aria-hidden="true"><?= e(t('currency_symbol')) ?></div>
            <div class="brand-text">
                <strong><?= e(t('app_name')) ?></strong>
                <small><?= e(t('app_tagline')) ?></small>
            </div>
        </a>

        <!-- ===== Main Navigation =====
             Order: Dashboard, Customers, Transactions, Inventory, Reports, Settings, Logout, language toggle -->
        <nav class="app-nav" aria-label="<?= e(t('app_name')) ?> Main Menu">
            <a href="index.php?page=dashboard"
               class="nav-link<?= navClass('dashboard', $activePage) ?>">
                <?= e(t('nav_dashboard')) ?>
            </a>
            <a href="index.php?page=customers"
               class="nav-link<?= navClass('customers', $activePage) ?>">
                <?= e(t('nav_customers')) ?>
            </a>
            <a href="index.php?page=transactions"
               class="nav-link<?= navClass('transactions', $activePage) ?>">
                <?= e(t('nav_transactions')) ?>
            </a>
            <a href="index.php?page=inventory"
               class="nav-link<?= navClass('inventory', $activePage) ?>">
                <?= e(t('nav_inventory')) ?>
            </a>
            <a href="index.php?page=reports"
               class="nav-link<?= navClass('reports', $activePage) ?>">
                <?= e(t('nav_reports')) ?>
            </a>
            <a href="index.php?page=settings"
               class="nav-link<?= navClass('settings', $activePage) ?>">
                <?= e(t('nav_settings')) ?>
            </a>
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="index.php?page=auth&amp;action=logout"
                   class="nav-link nav-link-logout">
                    <?= e(t('nav_logout')) ?>
                </a>
            <?php endif; ?>
            <a href="index.php?lang=<?= e($toggleLang) ?>"
               class="nav-link lang-btn"
               aria-label="Switch language">
                &#127760; <?= e(t('toggle_language')) ?>
            </a>
        </nav>

        <!-- ===== Account Controls ===== -->
        <div class="header-controls">
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="index.php?page=auth&amp;action=change_password"
                   class="btn btn-sm btn-ghost"
                   title="<?= e(t('btn_change_password')) ?>">
                    &#128273; <?= e(t('btn_change_password')) ?>
                </a>
            <?php endif; ?>
        </div>

    </div><!-- /.header-inner -->
</header>

<!-- ===== Main Content Area ================================================ -->
<main class="main-wrap" id="main-content">

    <!-- Flash alerts — session messages rendered once then deleted -->
    <?php flash_show(); ?>

    <!-- ↓ ↓ ↓  Page body goes here (injected by each controller view) ↓ ↓ ↓ -->
