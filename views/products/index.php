<?php
declare(strict_types=1);

/**
 * views/products/index.php
 * -----------------------------------------------------------------------------
 * Product inventory list with GET-based filters and 20-per-page pagination.
 *
 * Variables injected by ProductController::index():
 *   $products       array  — rows for the current page (with status_info & profit_metrics)
 *   $pagination     array  — {items, total, page, per_page, total_pages}
 *   $filters        array  — {name, category, status}
 *   $allCategories  array  — distinct category strings for the dropdown
 * -----------------------------------------------------------------------------
 */
?>

<!-- ===== Page Header ======================================================= -->
<div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
                <?= e(t('product_title')) ?>
            </h1>
            <p class="text-muted text-sm" style="margin-top: .25rem;">
                <?= number_format($pagination['total']) ?> <?= e(t('nav_inventory')) ?>
            </p>
        </div>
        <a href="index.php?page=inventory&amp;action=create" class="btn btn-primary">
            + <?= e(t('btn_add_product')) ?>
        </a>
    </div>
</div>

<!-- ===== Filter Form ======================================================= -->
<div class="filter-card">
    <p class="filter-title">🔍 <?= e(t('filter_heading')) ?></p>
    <form method="GET" action="index.php" class="filter-row" aria-label="<?= e(t('filter_heading')) ?>">
        <input type="hidden" name="page" value="inventory">

        <!-- Name filter -->
        <div class="filter-group">
            <label for="f_name"><?= e(t('label_product_name')) ?></label>
            <input type="text"
                   id="f_name"
                   name="name"
                   value="<?= e($filters['name'] ?? '') ?>"
                   placeholder="<?= e(t('label_product_name')) ?>…"
                   maxlength="150">
        </div>

        <!-- Category dropdown — populated from DB -->
        <div class="filter-group">
            <label for="f_cat"><?= e(t('filter_category')) ?></label>
            <select id="f_cat" name="category">
                <option value=""><?= e(t('filter_all_categories')) ?></option>
                <?php foreach ($allCategories as $cat): ?>
                    <option value="<?= e($cat) ?>"
                        <?= ($filters['category'] ?? '') === $cat ? 'selected' : '' ?>>
                        <?= e($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Stock status dropdown -->
        <div class="filter-group">
            <label for="f_status"><?= e(t('filter_status')) ?></label>
            <select id="f_status" name="status">
                <option value="" <?= ($filters['status'] ?? '') === ''            ? 'selected' : '' ?>>
                    <?= e(t('filter_all_statuses')) ?>
                </option>
                <option value="in_stock"     <?= ($filters['status'] ?? '') === 'in_stock'     ? 'selected' : '' ?>>
                    <?= e(t('filter_in_stock')) ?>
                </option>
                <option value="low_stock"    <?= ($filters['status'] ?? '') === 'low_stock'    ? 'selected' : '' ?>>
                    <?= e(t('filter_low_stock')) ?>
                </option>
                <option value="out_of_stock" <?= ($filters['status'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>
                    <?= e(t('filter_out_of_stock')) ?>
                </option>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary" style="height:2.1rem; padding:.4rem .9rem; font-size:.88rem;">
                <?= e(t('filter_btn_apply')) ?>
            </button>
            <a href="index.php?page=inventory" class="btn-clear-filter">
                <?= e(t('filter_btn_clear')) ?>
            </a>
        </div>
    </form>
</div>

<!-- ===== Product Table ===================================================== -->
<?php if (empty($products) && $pagination['total'] === 0): ?>
    <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
        <?php
            $hasFilter = (
                ($filters['name']     ?? '') !== '' ||
                ($filters['category'] ?? '') !== '' ||
                ($filters['status']   ?? '') !== ''
            );
        ?>
        <p class="text-muted">
            <?= $hasFilter ? e(t('filter_results')) . ': 0' : e(t('product_empty_list')) ?>
        </p>
        <?php if (!$hasFilter): ?>
            <a href="index.php?page=inventory&amp;action=create"
               class="btn btn-primary" style="margin-top: 1rem;">
                + <?= e(t('btn_add_product')) ?>
            </a>
        <?php endif; ?>
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
                        <th style="text-align:center;"><?= e(t('product_margin')) ?></th>
                        <th style="text-align:center;"><?= e(t('label_stock')) ?></th>
                        <th style="text-align:center;"><?= e(t('product_status')) ?></th>
                        <th style="text-align:right;"><?= e(t('btn_edit')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rowStart = ($pagination['page'] - 1) * $pagination['per_page'] + 1; ?>
                    <?php foreach ($products as $i => $p): ?>
                        <?php
                            $statusInfo  = $p['status_info'];
                            $statusLabel = t($statusInfo['key']);
                            $badgeClass  = $statusInfo['badge_class'];
                            $metrics     = $p['profit_metrics'];
                            $profitUnit  = (float) $metrics['profit_per_unit'];
                            $marginPct   = (float) $metrics['margin_pct'];
                            $isLoss      = (bool)  $metrics['is_loss'];
                        ?>
                        <tr style="<?= $isLoss ? 'background-color: rgba(239,68,68,.08);' : '' ?>">
                            <td class="text-muted text-sm"><?= $rowStart + $i ?></td>

                            <td>
                                <a href="index.php?page=inventory&amp;action=detail&amp;id=<?= (int)$p['prod_id'] ?>"
                                   style="color:inherit; text-decoration:none; font-weight:700;">
                                    <?= e($p['name']) ?>
                                </a>
                                <?php if ($isLoss): ?>
                                    <div>
                                        <span class="badge badge-red" style="font-size:.75rem; margin-top:.2rem;"
                                              title="<?= e(t('product_loss_warning')) ?>">
                                            ⚠ Loss
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <!-- Clicking category applies that category filter -->
                                <a href="index.php?page=inventory&amp;category=<?= urlencode((string)$p['category']) ?>"
                                   style="text-decoration:none;">
                                    <span class="badge badge-neutral"><?= e($p['category']) ?></span>
                                </a>
                            </td>

                            <td class="td-money font-mono"><?= e(format_money((float)$p['retail_price'])) ?></td>
                            <td class="td-money font-mono text-muted"><?= e(format_money((float)$p['cost_price'])) ?></td>

                            <td class="td-money font-mono <?= $profitUnit >= 0 ? 'td-paid' : 'td-due' ?>">
                                <strong><?= $profitUnit >= 0 ? '+' : '' ?><?= e(format_money($profitUnit)) ?></strong>
                            </td>

                            <td style="text-align:center;" class="font-mono">
                                <span class="badge <?= $isLoss ? 'badge-red' : ($marginPct >= 20 ? 'badge-green' : 'badge-neutral') ?>">
                                    <?= number_format($marginPct, 1) ?>%
                                </span>
                            </td>

                            <td style="text-align:center;" class="font-mono">
                                <strong><?= (int)$p['stock'] ?></strong>
                                <span class="text-muted text-sm" title="<?= e(t('label_min_stock')) ?>">
                                    (min: <?= (int)$p['min_stock'] ?>)
                                </span>
                            </td>

                            <td style="text-align:center;">
                                <span class="badge <?= $badgeClass ?>"><?= e($statusLabel) ?></span>
                            </td>

                            <td style="text-align:right;">
                                <div class="d-flex gap-1" style="justify-content:flex-end; gap:.35rem;">
                                    <a href="index.php?page=inventory&amp;action=detail&amp;id=<?= (int)$p['prod_id'] ?>"
                                       class="btn btn-ghost btn-sm" title="<?= e(t('btn_view_details')) ?>">
                                        📊 <?= e(t('btn_view_details')) ?>
                                    </a>
                                    <a href="index.php?page=inventory&amp;action=stock&amp;id=<?= (int)$p['prod_id'] ?>"
                                       class="btn btn-ghost btn-sm" title="<?= e(t('btn_update_stock')) ?>">📦</a>
                                    <a href="index.php?page=inventory&amp;action=edit&amp;id=<?= (int)$p['prod_id'] ?>"
                                       class="btn btn-ghost btn-sm" title="<?= e(t('btn_edit')) ?>">✏️</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php include __DIR__ . '/../partials/pagination.php'; ?>
    </section>
<?php endif; ?>
