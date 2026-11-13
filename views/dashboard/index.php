<?php
declare(strict_types=1);

/**
 * views/dashboard/index.php
 * -----------------------------------------------------------------------------
 * Protected shop overview dashboard template.
 *
 * Displays the four summary cards with live data:
 *   1. Customers Count
 *   2. Total Outstanding (Due)
 *   3. Low-Stock & Out-of-Stock Items Count
 *   4. This Month's Collections
 *
 * Includes dedicated Low-Stock Attention Panel listing Low/Out items with links.
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
            <a href="index.php?page=transactions&amp;action=create" class="btn btn-primary btn-sm">
                + <?= e(t('btn_new_transaction')) ?>
            </a>
            <a href="index.php?page=customers&amp;action=create" class="btn btn-secondary btn-sm">
                + <?= e(t('btn_add_customer')) ?>
            </a>
            <a href="index.php?page=inventory&amp;action=create" class="btn btn-ghost btn-sm">
                + <?= e(t('btn_add_product')) ?>
            </a>
        </div>
    </div>
</div>

<!-- ===== Four Live Summary Cards ===== -->
<section class="grid-stats" aria-label="<?= e(t('nav_dashboard')) ?> Summary" style="margin-bottom: 1.75rem;">
    <!-- Summary Card 1: Customers -->
    <a href="index.php?page=customers" class="stat-card" style="text-decoration: none; display: block;">
        <span class="stat-label"><?= e(t('card_customers')) ?></span>
        <span class="stat-value total"><?= (int)($stats['customers'] ?? 0) ?></span>
        <span class="text-muted text-sm"><?= e(t('nav_customers')) ?> &rarr;</span>
    </a>

    <!-- Summary Card 2: Total Outstanding -->
    <a href="index.php?page=customers" class="stat-card" style="text-decoration: none; display: block;">
        <span class="stat-label"><?= e(t('card_total_outstanding')) ?></span>
        <span class="stat-value due"><?= e(format_money((float)($stats['total_outstanding'] ?? 0.0))) ?></span>
        <span class="text-muted text-sm"><?= e(t('txn_type_credit')) ?> &rarr;</span>
    </a>

    <!-- Summary Card 3: Low-Stock Items -->
    <a href="index.php?page=inventory" class="stat-card" style="text-decoration: none; display: block;">
        <span class="stat-label"><?= e(t('card_low_stock')) ?></span>
        <span class="stat-value" style="color: var(--clr-amber, #fbbf24);">
            <?= (int)($stats['low_stock'] ?? 0) ?>
        </span>
        <span class="text-muted text-sm"><?= e(t('nav_products')) ?> &rarr;</span>
    </a>

    <!-- Summary Card 4: This Month's Collections -->
    <a href="index.php?page=transactions" class="stat-card" style="text-decoration: none; display: block;">
        <span class="stat-label"><?= e(t('card_month_collections')) ?></span>
        <span class="stat-value paid"><?= e(format_money((float)($stats['month_collections'] ?? 0.0))) ?></span>
        <span class="text-muted text-sm"><?= e(t('txn_type_payment')) ?> &rarr;</span>
    </a>
</section>

<!-- ===== Low / Out of Stock Attention Panel ===== -->
<section class="card" style="padding: 0; overflow: hidden; margin-bottom: 1.5rem;">
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color, #2a3754);" class="d-flex justify-between align-center">
        <div>
            <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: #fbbf24;">
                ⚠️ <?= e(t('dashboard_low_stock_panel')) ?>
            </h2>
            <p class="text-muted text-sm" style="margin-top: 0.2rem;">
                Items that have reached or fallen below minimum threshold.
            </p>
        </div>
        <a href="index.php?page=inventory" class="btn btn-ghost btn-sm">
            <?= e(t('nav_inventory')) ?> &rarr;
        </a>
    </div>

    <?php if (empty($lowStockProducts)): ?>
        <div style="text-align: center; padding: 2.5rem 1.5rem;">
            <p class="text-muted"><?= e(t('dashboard_no_low_stock')) ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap" style="margin-bottom: 0; border: none; border-radius: 0;">
            <table class="table" aria-label="<?= e(t('dashboard_low_stock_panel')) ?>">
                <thead>
                    <tr>
                        <th><?= e(t('label_product_name')) ?></th>
                        <th><?= e(t('label_category')) ?></th>
                        <th style="text-align: center;"><?= e(t('label_stock')) ?></th>
                        <th style="text-align: center;"><?= e(t('label_min_stock')) ?></th>
                        <th style="text-align: center;"><?= e(t('product_status')) ?></th>
                        <th style="text-align: right;"><?= e(t('btn_edit')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowStockProducts as $p): ?>
                        <?php
                            $statusInfo = $p['status_info'];
                            $statusLabel = t($statusInfo['key']);
                            $badgeClass  = $statusInfo['badge_class'];
                        ?>
                        <tr>
                            <td>
                                <a href="index.php?page=inventory&amp;action=detail&amp;id=<?= (int)$p['prod_id'] ?>" style="color: var(--text-primary, #f8fafc); text-decoration: none; font-weight: 700;">
                                    <?= e($p['name']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-neutral"><?= e($p['category']) ?></span>
                            </td>
                            <td style="text-align: center;" class="font-mono">
                                <strong style="color: <?= (int)$p['stock'] === 0 ? '#ef4444' : '#fbbf24' ?>;">
                                    <?= (int)$p['stock'] ?>
                                </strong>
                            </td>
                            <td style="text-align: center;" class="font-mono text-muted">
                                <?= (int)$p['min_stock'] ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge <?= $badgeClass ?>">
                                    <?= e($statusLabel) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="d-flex gap-05 justify-end">
                                    <a href="index.php?page=inventory&amp;action=stock&amp;id=<?= (int)$p['prod_id'] ?>" class="btn btn-primary btn-sm">
                                        📦 <?= e(t('btn_update_stock')) ?>
                                    </a>
                                    <a href="index.php?page=inventory&amp;action=detail&amp;id=<?= (int)$p['prod_id'] ?>" class="btn btn-ghost btn-sm">
                                        📊
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
