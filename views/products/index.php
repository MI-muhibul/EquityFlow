<?php
declare(strict_types=1);

/**
 * views/products/index.php
 * -----------------------------------------------------------------------------
 * Product inventory catalog list view:
 *   - Name, Category, Retail Price (৳), Cost Price (৳), Stock, Min Stock
 *   - Status computed in PHP: Out of Stock, Low Stock, or In Stock with colored badges
 *   - Action links to Edit and Update Stock
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
                        <th style="text-align: center;"><?= e(t('label_stock')) ?></th>
                        <th style="text-align: center;"><?= e(t('product_status')) ?></th>
                        <th style="text-align: right;"><?= e(t('btn_edit')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $i => $p): ?>
                        <?php
                            $statusInfo = $p['status_info'];
                            $statusLabel = t($statusInfo['key']);
                            $badgeClass  = $statusInfo['badge_class'];
                        ?>
                        <tr>
                            <td class="text-muted text-sm"><?= $i + 1 ?></td>
                            <td>
                                <strong><?= e($p['name']) ?></strong>
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
                                    <a href="index.php?page=inventory&amp;action=stock&amp;id=<?= (int)$p['prod_id'] ?>" class="btn btn-ghost btn-sm">
                                        📦 <?= e(t('btn_update_stock')) ?>
                                    </a>
                                    <a href="index.php?page=inventory&amp;action=edit&amp;id=<?= (int)$p['prod_id'] ?>" class="btn btn-ghost btn-sm">
                                        ✏️ <?= e(t('btn_edit')) ?>
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
