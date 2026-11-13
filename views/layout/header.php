<?php
declare(strict_types=1);

/**
 * views/layout/header.php
 * -----------------------------------------------------------------------
 * Shared page top header — rendered at the start of every view.
 *
 * Provides:
 *   • HTML5 DOCTYPE, <head> with viewport, Google font preconnect, and stylesheet.
 *   • Navigation logic:
 *       - Guest users: Home, Sign In, Sign Up, language toggle.
 *       - Logged-in shop owners: Dashboard, Customers, Transactions, Inventory,
 *         Reports, Settings, Profile, Sign Out, language toggle.
 *   • Accessible high-contrast dark theme, active page highlighting.
 *   • Opening <main class="main-wrap"> and flash_show() notification area.
 * -----------------------------------------------------------------------
 * Rules: Zero JavaScript, all labels via t(), dynamic values via e().
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/lang.php';
require_once __DIR__ . '/../../helpers/format.php';

// Active page slug (used to highlight the current navigation item)
$activePage = $activePage ?? (isset($_GET['page']) ? trim((string)$_GET['page']) : 'dashboard');

// Language switch target — switches to the alternate language ('bn' <-> 'en')
$currentLang = get_current_lang();
$toggleLang  = ($currentLang === 'bn') ? 'en' : 'bn';

// Page Title
$htmlTitle = isset($pageTitle)
    ? e($pageTitle) . ' — ' . e(t('app_name'))
    : e(t('app_name')) . ' — ' . e(t('app_tagline'));

// User authentication state
$isLoggedIn = !empty($_SESSION['user_id']);

// Helper: return ' active' class if slug matches current page
if (!function_exists('navClass')) {
    function navClass(string $slug, string $active): string {
        if ($slug === $active) {
            return ' active';
        }
        if ($slug === 'transactions' && in_array($active, ['transactions', 'ledger'], true)) {
            return ' active';
        }
        if ($slug === 'inventory' && in_array($active, ['inventory', 'products'], true)) {
            return ' active';
        }
        if ($slug === 'auth' && in_array($active, ['login', 'signin', 'signup', 'register'], true)) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $htmlTitle ?></title>

    <!-- Bangla Google Fonts (Hind Siliguri) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap">

    <!-- High-Contrast Dark Theme CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ===== Sticky Header ===== -->
<header class="app-header" role="banner">
    <div class="header-inner">

        <!-- Brand / Logo -->
        <a href="index.php?page=<?= $isLoggedIn ? 'dashboard' : 'home' ?>" class="brand" aria-label="<?= e(t('app_name')) ?> — Home">
            <div class="brand-mark" aria-hidden="true"><?= e(t('currency_symbol')) ?></div>
            <div class="brand-text">
                <strong><?= e(t('app_name')) ?></strong>
                <small><?= e(t('app_tagline')) ?></small>
            </div>
        </a>

        <!-- Main Navigation -->
        <nav class="app-nav" aria-label="<?= e(t('app_name')) ?> Navigation">
            <?php if (!$isLoggedIn): ?>
                <!-- Guests: Home, Sign In, Sign Up -->
                <a href="index.php?page=home" class="nav-link<?= navClass('home', $activePage) ?>">
                    <?= e(t('home')) ?>
                </a>
                <a href="index.php?page=auth&amp;action=login" class="nav-link<?= navClass('login', $activePage) ?>">
                    <?= e(t('sign_in')) ?>
                </a>
                <a href="index.php?page=auth&amp;action=signup" class="nav-link<?= navClass('signup', $activePage) ?>">
                    <?= e(t('sign_up')) ?>
                </a>
            <?php else: ?>
                <!-- Logged-in Shop Owners: Dashboard, Customers, Transactions, Inventory, Reports, Settings, Profile, Sign Out -->
                <a href="index.php?page=dashboard" class="nav-link<?= navClass('dashboard', $activePage) ?>">
                    <?= e(t('nav_dashboard')) ?>
                </a>
                <a href="index.php?page=customers" class="nav-link<?= navClass('customers', $activePage) ?>">
                    <?= e(t('nav_customers')) ?>
                </a>
                <a href="index.php?page=transactions" class="nav-link<?= navClass('transactions', $activePage) ?>">
                    <?= e(t('nav_transactions')) ?>
                </a>
                <a href="index.php?page=inventory" class="nav-link<?= navClass('inventory', $activePage) ?>">
                    <?= e(t('nav_inventory')) ?>
                </a>
                <a href="index.php?page=reports" class="nav-link<?= navClass('reports', $activePage) ?>">
                    <?= e(t('nav_reports')) ?>
                </a>
                <a href="index.php?page=settings" class="nav-link<?= navClass('settings', $activePage) ?>">
                    <?= e(t('nav_settings')) ?>
                </a>
                <a href="index.php?page=auth&amp;action=profile" class="nav-link<?= navClass('profile', $activePage) ?>">
                    <?= e(t('profile')) ?>
                </a>
                <a href="index.php?page=auth&amp;action=logout" class="nav-link nav-link-logout">
                    <?= e(t('sign_out')) ?>
                </a>
            <?php endif; ?>

            <!-- Language Switcher Pill -->
            <a href="index.php?lang=<?= e($toggleLang) ?>" class="nav-link lang-btn" aria-label="Switch Language">
                &#127760; <?= e(t('toggle_language')) ?>
            </a>
        </nav>

    </div>
</header>

<!-- ===== Main Content Canvas ===== -->
<main class="main-wrap" id="main-content">

    <!-- Flash notifications area -->
    <?php flash_show(); ?>
