<?php
declare(strict_types=1);

/**
 * views/products/detail.php
 * -----------------------------------------------------------------------------
 * Product detail page:
 *   - Displays complete product metadata (name, category, prices, stock, status)
 *   - Profit per unit (retail - cost) & margin %
 *   - Loss warning alert when retail < cost
 *   - Accessible responsive inline SVG sparkline showing profit trend from sale_items
 *   - Itemized sales history table
 * -----------------------------------------------------------------------------
 * Rules:
 *   - Strictly zero JavaScript
 *   - htmlspecialchars via e() on all output
 *   - format_money() for all ৳ currency values
 *   - All text from language pack via t()
 */

$statusInfo  = $product['status_info'];
$statusLabel = t($statusInfo['key']);
$badgeClass  = $statusInfo['badge_class'];
$metrics     = $product['profit_metrics'];
$profitUnit  = (float)$metrics['profit_per_unit'];
$marginPct   = (float)$metrics['margin_pct'];
$isLoss      = (bool)$metrics['is_loss'];
?>

<!-- ===== Breadcrumb & Header ===== -->
<div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
                <?= e($product['name']) ?>
            </h1>
            <p class="text-muted text-sm" style="margin-top: .25rem;">
                <span class="badge badge-neutral"><?= e($product['category']) ?></span> &bull; <?= e(t('product_detail_title')) ?>
            </p>
        </div>
        <div class="d-flex gap-1 flex-wrap">
            <a href="index.php?page=inventory&amp;action=stock&amp;id=<?= (int)$product['prod_id'] ?>" class="btn btn-primary">
                📦 <?= e(t('btn_update_stock')) ?>
            </a>
            <a href="index.php?page=inventory&amp;action=edit&amp;id=<?= (int)$product['prod_id'] ?>" class="btn btn-secondary">
                ✏️ <?= e(t('btn_edit')) ?>
            </a>
            <a href="index.php?page=inventory" class="btn btn-ghost">
                &larr; <?= e(t('btn_back')) ?>
            </a>
        </div>
    </div>
</div>

<!-- ===== Loss Warning Banner (if retail < cost) ===== -->
<?php if ($isLoss): ?>
    <div class="alert alert-error" role="alert" style="margin-bottom: 1.5rem; border-width: 2px;">
        <span class="alert-icon" aria-hidden="true">&#9888;</span>
        <div class="alert-text">
            <strong><?= e(t('product_loss_warning')) ?></strong>
            <div style="font-size: 0.85rem; margin-top: 0.25rem;">
                <?= e(t('label_retail_price')) ?> (<?= e(format_money((float)$product['retail_price'])) ?>) &lt; <?= e(t('label_cost_price')) ?> (<?= e(format_money((float)$product['cost_price'])) ?>).
                Loss per unit: <span style="font-weight: bold;"><?= e(format_money($profitUnit)) ?></span> (<?= number_format($marginPct, 1) ?>%).
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ===== Key Metrics Grid ===== -->
<div class="grid-stats" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <span class="stat-label"><?= e(t('label_retail_price')) ?></span>
        <span class="stat-value total">
            <?= e(format_money((float)$product['retail_price'])) ?>
        </span>
    </div>
    <div class="stat-card">
        <span class="stat-label"><?= e(t('label_cost_price')) ?></span>
        <span class="stat-value total text-muted">
            <?= e(format_money((float)$product['cost_price'])) ?>
        </span>
    </div>
    <div class="stat-card">
        <span class="stat-label"><?= e(t('product_profit_per_unit')) ?></span>
        <span class="stat-value <?= $isLoss ? 'due' : 'paid' ?>">
            <?= $profitUnit >= 0 ? '+' : '' ?><?= e(format_money($profitUnit)) ?>
        </span>
    </div>
    <div class="stat-card">
        <span class="stat-label"><?= e(t('product_margin')) ?></span>
        <span class="stat-value <?= $isLoss ? 'due' : 'paid' ?>">
            <?= number_format($marginPct, 1) ?>%
        </span>
    </div>
</div>

<!-- ===== Detail & Sparkline Analytics Grid ===== -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;" class="grid-responsive">

    <!-- Column 1: Product Specifications -->
    <div class="card" style="padding: 1.75rem;">
        <h2 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem;">
            <?= e(t('product_title')) ?>
        </h2>
        <table class="detail-table" style="width: 100%;">
            <tbody>
                <tr>
                    <th style="width: 40%;"><?= e(t('label_product_name')) ?></th>
                    <td><strong><?= e($product['name']) ?></strong></td>
                </tr>
                <tr>
                    <th><?= e(t('label_category')) ?></th>
                    <td><span class="badge badge-neutral"><?= e($product['category']) ?></span></td>
                </tr>
                <tr>
                    <th><?= e(t('label_stock')) ?></th>
                    <td class="font-mono">
                        <strong><?= (int)$product['stock'] ?></strong>
                    </td>
                </tr>
                <tr>
                    <th><?= e(t('label_min_stock')) ?></th>
                    <td class="font-mono text-muted">
                        <?= (int)$product['min_stock'] ?>
                    </td>
                </tr>
                <tr>
                    <th><?= e(t('product_status')) ?></th>
                    <td>
                        <span class="badge <?= $badgeClass ?>">
                            <?= e($statusLabel) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><?= e(t('label_date')) ?></th>
                    <td class="text-muted text-sm">
                        <?= e(date('d F Y', strtotime($product['created_at']))) ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Column 2: Sparkline Trend Box -->
    <div class="card" style="padding: 1.75rem; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div class="d-flex justify-between align-center" style="margin-bottom: 0.75rem;">
                <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0;">
                    📈 <?= e(t('product_trend_title')) ?>
                </h2>
                <span class="text-muted text-sm font-mono">
                    <?= count($profitTrendPoints) ?> sales
                </span>
            </div>
            <p class="text-muted text-sm" style="margin-bottom: 1.25rem;">
                Historical profit generated across credit sale item records (<code>sale_items</code>).
            </p>

            <!-- Sparkline Container with Accessibility -->
            <div style="background-color: var(--bg-base, #0b0f19); border: 1px solid var(--border-color, #2a3754); border-radius: 8px; padding: 1.25rem; min-height: 90px; display: flex; align-items: center; justify-content: center;">
                <?= render_sparkline($profitTrendPoints, [
                    'width'       => 320,
                    'height'      => 75,
                    'stroke'      => $isLoss ? '#ef4444' : '#10b981',
                    'strokeWidth' => 2.5,
                    'unit'        => '৳',
                    'title'       => $product['name'] . ' Profit Trend across ' . count($profitTrendPoints) . ' transactions',
                ]) ?>
            </div>
        </div>

        <?php if (!empty($profitTrendPoints)): ?>
            <div class="d-flex justify-between text-muted text-sm font-mono" style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color, #2a3754);">
                <span>First: <?= e(format_money($profitTrendPoints[0])) ?></span>
                <span>Max: <?= e(format_money(max($profitTrendPoints))) ?></span>
                <span>Latest: <strong><?= e(format_money(end($profitTrendPoints))) ?></strong></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===== Itemized Sales History from sale_items ===== -->
<div class="card" style="padding: 0; overflow: hidden; margin-top: 1.5rem;">
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color, #2a3754);">
        <h3 style="font-size: 1.15rem; font-weight: 700; margin: 0;">
            <?= e(t('product_trend_title')) ?> &mdash; <?= e(t('txn_title')) ?>
        </h3>
    </div>

    <?php if (empty($saleHistory)): ?>
        <div style="text-align: center; padding: 2.5rem 1.5rem;">
            <p class="text-muted"><?= e(t('product_trend_empty')) ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap" style="margin-bottom: 0; border: none; border-radius: 0;">
            <table class="table" aria-label="<?= e(t('product_trend_title')) ?>">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= e(t('txn_voucher_no')) ?></th>
                        <th><?= e(t('label_date')) ?></th>
                        <th style="text-align: center;"><?= e(t('label_quantity')) ?></th>
                        <th class="td-money"><?= e(t('label_retail_price')) ?></th>
                        <th class="td-money"><?= e(t('label_cost_price')) ?></th>
                        <th class="td-money"><?= e(t('product_profit_per_unit')) ?></th>
                        <th class="td-money"><?= e(t('product_profit_loss')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($saleHistory as $idx => $s): ?>
                        <tr>
                            <td class="text-muted text-sm"><?= $idx + 1 ?></td>
                            <td class="font-mono text-muted text-sm">
                                <a href="index.php?page=customers&amp;action=receipt&amp;id=<?= (int)$s['txn_id'] ?>" style="color: var(--accent-primary, #10b981); text-decoration: none;">
                                    #<?= (int)$s['txn_id'] ?>
                                </a>
                            </td>
                            <td class="text-muted text-sm">
                                <?= e(date('d M Y, h:i A', strtotime($s['created_at']))) ?>
                            </td>
                            <td style="text-align: center;" class="font-mono">
                                <?= (float)$s['qty'] ?>
                            </td>
                            <td class="td-money font-mono">
                                <?= e(format_money((float)$s['unit_price'])) ?>
                            </td>
                            <td class="td-money font-mono text-muted">
                                <?= e(format_money((float)$s['unit_cost'])) ?>
                            </td>
                            <td class="td-money font-mono <?= (float)$s['unit_profit'] >= 0 ? 'td-paid' : 'td-due' ?>">
                                <?= (float)$s['unit_profit'] >= 0 ? '+' : '' ?><?= e(format_money((float)$s['unit_profit'])) ?>
                            </td>
                            <td class="td-money font-mono <?= (float)$s['total_profit'] >= 0 ? 'td-paid' : 'td-due' ?>">
                                <strong><?= (float)$s['total_profit'] >= 0 ? '+' : '' ?><?= e(format_money((float)$s['total_profit'])) ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
