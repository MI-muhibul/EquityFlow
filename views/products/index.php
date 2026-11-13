<?php
declare(strict_types=1);

/**
 * views/products/index.php
 * -----------------------------------------------------------------------------
 * Product inventory catalog list view:
 *   - Name, Category, Retail Price (৳), Cost Price (৳), Stock, Min Stock
 *   - Profit Per Unit (retail - cost) & Margin %
 *   - Warning highlight / badge when retail < cost
 *   - Status computed in PHP: Out of Stock, Low Stock, or In Stock with colored badges
 *   - Action links to Detail, Update Stock, and Edit
 * -----------------------------------------------------------------------------
 * Rules:
 *   - Zero JavaScript
 *   - htmlspecialchars on all output via e()
 *   - Money formatted as ৳ with 2 decimals via format_money()
 *   - All UI text loaded from lang files via t()
 */
?>

<!-- ===== Header & Controls ===== -->
<div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
                <?= e(t('product_title')) ?>
            </h1>
            <p class="text-muted text-sm" style="margin-top: .25rem;">
                <?= count($products) ?> <?= e(t('nav_inventory')) ?>
            </p>
        </div>
        <div class="d-flex gap-1">
            <a href="index.php?page=inventory&amp;action=create" class="btn btn-primary">
                + <?= e(t('btn_add_product')) ?>
            </a>
        </div>
    </div>
</div>

<?php if (empty($products)): ?>
    <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
        <p class="text-muted"><?= e(t('product_empty_list')) ?></p>
        <a href="index.php?page=inventory&amp;action=create" class="btn btn-primary" style="margin-top: 1rem;">
            + <?= e(t('btn_add_product')) ?>
        </a>
    </div>
<?php else: ?>
    <section class="card" style="padding: 0; overflow: hidden;">
        <div class="table-wrap" style="margin-bottom: 0; border: none; border-radius: 0;">
            <table class="table" aria-label="<?= e(t('product_title')) ?>">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= e(t('label_product_name')) ?></th>
                        <th><?= e(t('label_category')) ?></th>
                        <th class="td-money"><?= e(t('label_retail_price')) ?></th>
                        <th class="td-money"><?= e(t('label_cost_price')) ?></th>
                        <th class="td-money"><?= e(t('product_profit_per_unit')) ?></th>
                        <th style="text-align: center;"><?= e(t('product_margin')) ?></th>
                        <th style="text-align: center;"><?= e(t('label_stock')) ?></th>
                        <th style="text-align: center;"><?= e(t('product_status')) ?></th>
                        <th style="text-align: right;"><?= e(t('btn_edit')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $i => $p): ?>
                        <?php
                            $statusInfo    = $p['status_info'];
                            $statusLabel   = t($statusInfo['key']);
                            $badgeClass    = $statusInfo['badge_class'];
                            $metrics       = $p['profit_metrics'];
                            $profitUnit    = (float)$metrics['profit_per_unit'];
                            $marginPct     = (float)$metrics['margin_pct'];
                            $isLoss        = (bool)$metrics['is_loss'];
                        ?>
                        <tr style="<?= $isLoss ? 'background-color: rgba(239, 68, 68, 0.08);' : '' ?>">
                            <td class="text-muted text-sm"><?= $i + 1 ?></td>
                            <td>
                                <a href="index.php?page=inventory&amp;action=detail&amp;id=<?= (int)$p['prod_id'] ?>" style="color: var(--text-primary, #f8fafc); text-decoration: none; font-weight: 700;">
                                    <?= e($p['name']) ?>
                                </a>
                                <?php if ($isLoss): ?>
                                    <div>
                                        <span class="badge badge-red" style="font-size: 0.75rem; margin-top: 0.2rem;" title="<?= e(t('product_loss_warning')) ?>">
                                            ⚠ <?= e(t('product_loss_warning')) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-neutral"><?= e($p['category']) ?></span>
                            </td>
                            <td class="td-money font-mono">
                                <?= e(format_money((float)$p['retail_price'])) ?>
                            </td>
                            <td class="td-money font-mono text-muted">
                                <?= e(format_money((float)$p['cost_price'])) ?>
                            </td>
                            <!-- Profit Per Unit (retail - cost) -->
                            <td class="td-money font-mono <?= $profitUnit >= 0 ? 'td-paid' : 'td-due' ?>">
                                <strong><?= $profitUnit >= 0 ? '+' : '' ?><?= e(format_money($profitUnit)) ?></strong>
                            </td>
                            <!-- Profit Margin % -->
                            <td style="text-align: center;" class="font-mono">
                                <span class="badge <?= $isLoss ? 'badge-red' : ($marginPct >= 20 ? 'badge-green' : 'badge-neutral') ?>">
                                    <?= number_format($marginPct, 1) ?>%
                                </span>
                            </td>
                            <td style="text-align: center;" class="font-mono">
                                <strong><?= (int)$p['stock'] ?></strong>
                                <span class="text-muted text-sm" title="<?= e(t('label_min_stock')) ?>">
                                    (min: <?= (int)$p['min_stock'] ?>)
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge <?= $badgeClass ?>">
                                    <?= e($statusLabel) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="d-flex gap-05 justify-end">
                                    <a href="index.php?page=inventory&amp;action=detail&amp;id=<?= (int)$p['prod_id'] ?>" class="btn btn-ghost btn-sm" title="<?= e(t('btn_view_details')) ?>">
                                        📊 <?= e(t('btn_view_details')) ?>
                                    </a>
                                    <a href="index.php?page=inventory&amp;action=stock&amp;id=<?= (int)$p['prod_id'] ?>" class="btn btn-ghost btn-sm" title="<?= e(t('btn_update_stock')) ?>">
                                        📦
                                    </a>
                                    <a href="index.php?page=inventory&amp;action=edit&amp;id=<?= (int)$p['prod_id'] ?>" class="btn btn-ghost btn-sm" title="<?= e(t('btn_edit')) ?>">
                                        ✏️
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
