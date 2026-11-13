<?php
declare(strict_types=1);
/**
 * views/404.php
 * -----------------------------------------------------------------------------
 * Styled fallback page for unknown routes.
 * Uses the shared header/footer layout, dark high‑contrast theme, and shows a
 * translated title / description with a button back to the appropriate start
 * page (dashboard for logged‑in users, home for guests).
 * -----------------------------------------------------------------------------
 */

$activePage = '404';
$pageTitle  = t('page_not_found');
?>
<?php require_once __DIR__ . '/layout/header.php'; ?>
<div class="auth-wrapper" style="margin-top: 4rem;">
    <div class="auth-card" style="max-width: 460px;">
        <div class="auth-header" style="text-align:center;">
            <div class="brand-mark" aria-hidden="true" style="font-size:2.5rem; margin-bottom:0.5rem;">
                <?= e(t('currency_symbol')) ?>
            </div>
            <h1 class="auth-title" style="margin-bottom:0.75rem;">
                <?= e(t('page_not_found')) ?>
            </h1>
            <p class="auth-subtitle" style="color:var(--text-secondary);">
                <?= e(t('page_not_found_desc')) ?>
            </p>
        </div>
        <div style="margin-top:1.5rem; display:flex; justify-content:center; gap:0.75rem;">
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="index.php?page=dashboard" class="btn btn-primary btn-block">
                    <?= e(t('nav_dashboard')) ?>
                </a>
            <?php else: ?>
                <a href="index.php?page=home" class="btn btn-primary btn-block">
                    <?= e(t('home')) ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
