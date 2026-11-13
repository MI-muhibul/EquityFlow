<?php
declare(strict_types=1);

/**
 * views/customers/index.php
 * -----------------------------------------------------------------------------
 * Customer list table with GET-based search/filter and 20-per-page pagination.
 *
 * Variables injected by CustomerController::index():
 *   $customers   array  — rows for the current page (already user-scoped)
 *   $pagination  array  — {items, total, page, per_page, total_pages}
 *   $filters     array  — active filter values {name, phone}
 * -----------------------------------------------------------------------------
 */
?>

<!-- ===== Page Header ========================================================= -->
<div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
                <?= e(t('customer_title')) ?>
            </h1>
            <p class="text-muted text-sm" style="margin-top: .25rem;">
                <?= number_format($pagination['total']) ?> <?= e(t('nav_customers')) ?>
            </p>
        </div>
        <a href="index.php?page=customers&amp;action=create" class="btn btn-primary">
            + <?= e(t('btn_add_customer')) ?>
        </a>
    </div>
</div>

<!-- ===== Filter Form ========================================================= -->
<div class="filter-card">
    <p class="filter-title">🔍 <?= e(t('filter_heading')) ?></p>
    <form method="GET" action="index.php" class="filter-row" aria-label="<?= e(t('filter_heading')) ?>">
        <input type="hidden" name="page" value="customers">

        <!-- Name filter -->
        <div class="filter-group">
            <label for="f_name"><?= e(t('filter_name')) ?></label>
            <input type="text"
                   id="f_name"
                   name="name"
                   value="<?= e($filters['name'] ?? '') ?>"
                   placeholder="<?= e(t('filter_name')) ?>…"
                   maxlength="100">
        </div>

        <!-- Phone filter -->
        <div class="filter-group">
            <label for="f_phone"><?= e(t('filter_phone')) ?></label>
            <input type="text"
                   id="f_phone"
                   name="phone"
                   value="<?= e($filters['phone'] ?? '') ?>"
                   placeholder="01XXXXXXXXX"
                   maxlength="20">
        </div>

        <!-- Submit & clear -->
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary" style="height:2.1rem; padding:.4rem .9rem; font-size:.88rem;">
                <?= e(t('filter_btn_apply')) ?>
            </button>
            <a href="index.php?page=customers" class="btn-clear-filter">
                <?= e(t('filter_btn_clear')) ?>
            </a>
        </div>
    </form>
</div>

<!-- ===== Customer Table ====================================================== -->
<?php if (empty($customers) && $pagination['total'] === 0): ?>
    <!-- Empty state -->
    <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">👤</div>
        <?php $hasFilter = ($filters['name'] !== '' || $filters['phone'] !== ''); ?>
        <p class="text-muted">
            <?= $hasFilter ? e(t('filter_results')) . ': 0' : e(t('customer_empty_list')) ?>
        </p>
        <?php if (!$hasFilter): ?>
            <a href="index.php?page=customers&amp;action=create"
               class="btn btn-primary" style="margin-top: 1rem;">
                + <?= e(t('btn_add_customer')) ?>
            </a>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Summary strip (page-level totals) -->
    <?php
        $pageDue = array_sum(array_column($customers, 'balance'));
    ?>
    <div class="grid-stats" style="margin-bottom: 1.5rem;">
        <div class="stat-card">
            <span class="stat-label"><?= e(t('card_customers')) ?></span>
            <span class="stat-value total"><?= number_format($pagination['total']) ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label"><?= e(t('card_total_outstanding')) ?> (<?= e(t('pagination_page')) ?>)</span>
            <span class="stat-value due"><?= e(format_money($pageDue)) ?></span>
        </div>
    </div>

    <!-- Table -->
    <section class="card" style="padding: 0; overflow: hidden;">
        <div class="table-wrap" style="margin-bottom: 0; border: none; border-radius: 0;">
            <table class="table" aria-label="<?= e(t('customer_title')) ?>">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= e(t('label_name')) ?></th>
                        <th><?= e(t('label_phone')) ?></th>
                        <th class="td-money"><?= e(t('label_balance')) ?></th>
                        <th class="td-money"><?= e(t('label_credit_limit')) ?></th>
                        <th><?= e(t('label_join_date')) ?></th>
                        <th><?= e(t('btn_edit')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        // Row counter starts at the correct absolute number for this page
                        $rowStart = ($pagination['page'] - 1) * $pagination['per_page'] + 1;
                    ?>
                    <?php foreach ($customers as $i => $cust): ?>
                        <?php
                            $balance = (float)$cust['balance'];
                            if ($balance > 0) {
                                $balanceClass = 'td-due';
                                $badgeClass   = 'badge-red';
                                $badgeLabel   = t('customer_total_due');
                            } elseif ($balance < 0) {
                                $balanceClass = 'td-paid';
                                $badgeClass   = 'badge-green';
                                $badgeLabel   = t('customer_advance_paid');
                            } else {
                                $balanceClass = '';
                                $badgeClass   = 'badge-neutral';
                                $badgeLabel   = t('customer_zero_balance');
                            }
                        ?>
                        <tr>
                            <!-- Absolute row number -->
                            <td class="text-muted text-sm"><?= $rowStart + $i ?></td>

                            <td>
                                <a href="index.php?page=customers&amp;action=profile&amp;id=<?= (int)$cust['cust_id'] ?>"
                                   style="font-weight:600; text-decoration:none; color:var(--text-primary);">
                                    <?= e($cust['name']) ?>
                                </a>
                                <?php if (!empty($cust['address'])): ?>
                                    <div class="text-muted text-sm"><?= e($cust['address']) ?></div>
                                <?php endif; ?>
                            </td>

                            <td class="font-mono text-muted"><?= e($cust['phone']) ?></td>

                            <td class="td-money <?= $balanceClass ?>">
                                <?= e(format_money($balance)) ?>
                                <div>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= e($badgeLabel) ?>
                                    </span>
                                </div>
                            </td>

                            <td class="td-money"><?= e(format_money((float)$cust['credit_limit'])) ?></td>

                            <td class="text-muted text-sm">
                                <?= e(date('d M Y', strtotime((string)$cust['join_date']))) ?>
                            </td>

                            <td>
                                <div class="d-flex gap-1" style="gap:.35rem;">
                                    <a href="index.php?page=customers&amp;action=profile&amp;id=<?= (int)$cust['cust_id'] ?>"
                                       class="btn btn-ghost btn-sm">
                                        <?= e(t('btn_view_profile')) ?>
                                    </a>
                                    <a href="index.php?page=customers&amp;action=edit&amp;id=<?= (int)$cust['cust_id'] ?>"
                                       class="btn btn-ghost btn-sm">
                                        <?= e(t('btn_edit')) ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination bar -->
        <?php include __DIR__ . '/../partials/pagination.php'; ?>
    </section>
<?php endif; ?>
