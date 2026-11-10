<?php
declare(strict_types=1);

/**
 * views/dashboard/index.php
 * -----------------------------------------------------------------------------
 * Protected shop overview dashboard template.
 *
 * Displays the four summary cards:
 *   1. Customers (showing 0)
 *   2. Total Outstanding (showing ৳0.00)
 *   3. Low-Stock Items (showing 0)
 *   4. This Month's Collections (showing ৳0.00)
 * -----------------------------------------------------------------------------
 * Rules:
 *   • Strictly zero JavaScript
 *   • Dark high-contrast responsive layout (WCAG compliant)
 *   • All UI strings loaded from lang files via t()
 *   • All dynamic output sanitized via e()
 *   • Money displayed as ৳ with 2 decimals via format_money()
 *   • Beginner-readable code with short comments
 */
?>

<!-- ===== Dashboard Header & Quick Actions ===== -->
<div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0; color: var(--text-high);">
                <?= e(t('nav_dashboard')) ?> &mdash; <?= e(t('app_name')) ?>
            </h1>
            <p class="text-muted text-sm" style="margin-top: .25rem;">
                <?= e(t('dashboard_subtitle')) ?>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-1">
            <a href="index.php?page=transactions" class="btn btn-primary btn-sm">
                + <?= e(t('btn_new_transaction')) ?>
            </a>
            <a href="index.php?page=customers" class="btn btn-secondary btn-sm">
                + <?= e(t('btn_add_customer')) ?>
            </a>
            <a href="index.php?page=inventory" class="btn btn-ghost btn-sm">
                + <?= e(t('btn_add_product')) ?>
            </a>
        </div>
    </div>
</div>

<!-- ===== Four Summary Cards ===== -->
<section class="grid-stats" aria-label="<?= e(t('nav_dashboard')) ?> Summary">
    <!-- Summary Card 1: Customers -->
    <div class="stat-card">
        <span class="stat-label"><?= e(t('card_customers')) ?></span>
        <span class="stat-value total"><?= e((string)($stats['customers'] ?? 0)) ?></span>
        <span class="text-muted text-sm"><?= e(t('nav_customers')) ?></span>
    </div>

    <!-- Summary Card 2: Total Outstanding -->
    <div class="stat-card">
        <span class="stat-label"><?= e(t('card_total_outstanding')) ?></span>
        <span class="stat-value due"><?= e(format_money((float)($stats['total_outstanding'] ?? 0.0))) ?></span>
        <span class="text-muted text-sm"><?= e(t('txn_type_credit')) ?></span>
    </div>

    <!-- Summary Card 3: Low-Stock Items -->
    <div class="stat-card">
        <span class="stat-label"><?= e(t('card_low_stock')) ?></span>
        <span class="stat-value" style="color: var(--clr-amber);">
            <?= e((string)($stats['low_stock'] ?? 0)) ?>
        </span>
        <span class="text-muted text-sm"><?= e(t('nav_products')) ?></span>
    </div>

    <!-- Summary Card 4: This Month's Collections -->
    <div class="stat-card">
        <span class="stat-label"><?= e(t('card_month_collections')) ?></span>
        <span class="stat-value paid"><?= e(format_money((float)($stats['month_collections'] ?? 0.0))) ?></span>
        <span class="text-muted text-sm"><?= e(t('txn_type_payment')) ?></span>
    </div>
</section>
