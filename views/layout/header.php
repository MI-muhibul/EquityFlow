<?php
declare(strict_types=1);

/**
 * EquityFlow - Master Layout Header
 *
 * Provides semantic HTML5 head, viewport meta, dark theme styles,
 * responsive navigation bar, language switcher, and the flash message container.
 * Rules: Zero JavaScript, WCAG high-contrast, all strings from language packs.
 */

// Ensure core configuration and helpers are loaded
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/lang.php';
require_once __DIR__ . '/../../helpers/format.php';

$activePage  = isset($_GET['page']) && is_string($_GET['page']) ? trim($_GET['page']) : 'dashboard';
$currentLang = get_current_lang();
$toggleLang  = ($currentLang === 'bn') ? 'en' : 'bn';
$pageTitle   = isset($pageTitle) ? $pageTitle . ' - ' . t('app_name') : t('app_name') . ' - ' . t('app_tagline');
?>
<!DOCTYPE html>
<html lang="<?= e($currentLang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <!-- Core Dark High-Contrast Theme (Strictly NO JavaScript) -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="app-header">
        <div class="header-container">
            <!-- Brand Identity -->
            <div class="brand-wrapper">
                <a href="index.php?page=dashboard" class="brand-logo" aria-label="<?= e(t('app_name')) ?>">
                    <?= e(t('currency_symbol')) ?>
                </a>
                <div>
                    <a href="index.php?page=dashboard" class="brand-title"><?= e(t('app_name')) ?></a>
                    <span class="brand-tagline"><?= e(t('app_tagline')) ?></span>
                </div>
            </div>

            <!-- Main Navigation Slot -->
            <nav class="app-nav" aria-label="Main Navigation">
                <a href="index.php?page=dashboard" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                    <?= e(t('nav_dashboard')) ?>
                </a>
                <a href="index.php?page=customers" class="nav-link <?= $activePage === 'customers' ? 'active' : '' ?>">
                    <?= e(t('nav_customers')) ?>
                </a>
                <a href="index.php?page=products" class="nav-link <?= $activePage === 'products' ? 'active' : '' ?>">
                    <?= e(t('nav_products')) ?>
                </a>
                <a href="index.php?page=ledger" class="nav-link <?= $activePage === 'ledger' ? 'active' : '' ?>">
                    <?= e(t('nav_transactions')) ?>
                </a>
                <a href="index.php?page=reports" class="nav-link <?= $activePage === 'reports' ? 'active' : '' ?>">
                    <?= e(t('nav_reports')) ?>
                </a>
                <a href="index.php?page=settings" class="nav-link <?= $activePage === 'settings' ? 'active' : '' ?>">
                    <?= e(t('nav_settings')) ?>
                </a>
            </nav>

            <!-- Language Switcher & Account Controls -->
            <div class="header-actions">
                <a href="index.php?lang=<?= e($toggleLang) ?>" class="lang-toggle-btn" title="Change Language">
                    🌐 <?= e(t('toggle_language')) ?>
                </a>
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a href="index.php?page=auth&amp;action=logout" class="btn btn-sm btn-outline-danger">
                        <?= e(t('nav_logout')) ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="main-wrapper" id="main-content">
        <!-- One-Time Flash Messages (Success / Error / Warning / Info) -->
        <?php flash_show(); ?>
