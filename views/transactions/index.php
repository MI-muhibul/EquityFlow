<?php
declare(strict_types=1);

/**
 * views/transactions/index.php
 * -----------------------------------------------------------------------------
 * Transaction list with GET-based filters (date range, customer, type)
 * and 20-per-page pagination.
 *
 * Variables injected by TransactionController::index():
 *   $transactions  array  — rows for the current page
 *   $pagination    array  — {items, total, page, per_page, total_pages}
 *   $filters       array  — active filter values
 *   $allCustomers  array  — all customers for the customer dropdown
 * -----------------------------------------------------------------------------
 */
?>

<!-- ===== Page Header ======================================================= -->
<div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
                <?= e(t('txn_title')) ?>
            </h1>
            <p class="text-muted text-sm" style="margin-top: .25rem;">
                <?= number_format($pagination['total']) ?> <?= e(t('nav_transactions')) ?>
            </p>
        </div>
        <a href="index.php?page=transactions&amp;action=create" class="btn btn-primary">
            + <?= e(t('btn_new_transaction')) ?>
        </a>
    </div>
</div>

<!-- ===== Filter Form ======================================================= -->
<div class="filter-card">
    <p class="filter-title">🔍 <?= e(t('filter_heading')) ?></p>
    <form method="GET" action="index.php" class="filter-row" aria-label="<?= e(t('filter_heading')) ?>">
        <input type="hidden" name="page" value="transactions">

        <!-- Start date -->
        <div class="filter-group">
            <label for="f_start"><?= e(t('filter_start_date')) ?></label>
            <input type="date"
                   id="f_start"
                   name="start_date"
                   value="<?= e($filters['start_date'] ?? '') ?>">
        </div>

        <!-- End date -->
        <div class="filter-group">
            <label for="f_end"><?= e(t('filter_end_date')) ?></label>
            <input type="date"
                   id="f_end"
                   name="end_date"
                   value="<?= e($filters['end_date'] ?? '') ?>">
        </div>

        <!-- Customer dropdown -->
        <div class="filter-group" style="min-width:180px;">
            <label for="f_cust"><?= e(t('filter_customer')) ?></label>
            <select id="f_cust" name="cust_id">
                <option value="0"><?= e(t('filter_all_customers')) ?></option>
                <?php foreach ($allCustomers as $c): ?>
                    <option value="<?= (int)$c['cust_id'] ?>"
                        <?= ((int)($filters['cust_id'] ?? 0) === (int)$c['cust_id']) ? 'selected' : '' ?>>
                        <?= e($c['name']) ?> (<?= e($c['phone']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Type radio-style select -->
        <div class="filter-group">
            <label for="f_type"><?= e(t('filter_type')) ?></label>
            <select id="f_type" name="type">
                <option value=""  <?= ($filters['type'] ?? '') === ''        ? 'selected' : '' ?>>
                    <?= e(t('filter_all_types')) ?>
                </option>
                <option value="credit"  <?= ($filters['type'] ?? '') === 'credit'  ? 'selected' : '' ?>>
                    <?= e(t('txn_type_credit')) ?>
                </option>
                <option value="payment" <?= ($filters['type'] ?? '') === 'payment' ? 'selected' : '' ?>>
                    <?= e(t('txn_type_payment')) ?>
                </option>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary" style="height:2.1rem; padding:.4rem .9rem; font-size:.88rem;">
                <?= e(t('filter_btn_apply')) ?>
            </button>
            <a href="index.php?page=transactions" class="btn-clear-filter">
                <?= e(t('filter_btn_clear')) ?>
            </a>
        </div>
    </form>
</div>

<!-- ===== Transaction Table ================================================= -->
<?php if (empty($transactions) && $pagination['total'] === 0): ?>
    <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">📑</div>
        <?php
            $hasFilter = (
                ($filters['start_date'] ?? '') !== '' ||
                ($filters['end_date']   ?? '') !== '' ||
                (int)($filters['cust_id'] ?? 0) > 0  ||
                ($filters['type']        ?? '') !== ''
            );
        ?>
        <p class="text-muted">
            <?= $hasFilter ? e(t('filter_results')) . ': 0' : e(t('txn_empty_list')) ?>
        </p>
        <?php if (!$hasFilter): ?>
            <a href="index.php?page=transactions&amp;action=create"
               class="btn btn-primary" style="margin-top: 1rem;">
                + <?= e(t('btn_new_transaction')) ?>
            </a>
        <?php endif; ?>
    </div>

<?php else: ?>
    <section class="card" style="padding: 0; overflow: hidden;">
        <div class="table-wrap" style="margin-bottom: 0; border: none; border-radius: 0;">
            <table class="table" aria-label="<?= e(t('txn_title')) ?>">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= e(t('label_date')) ?></th>
                        <th><?= e(t('label_name')) ?></th>
                        <th><?= e(t('label_transaction_type')) ?></th>
                        <th class="td-money"><?= e(t('label_amount')) ?></th>
                        <th><?= e(t('label_note')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $rowStart = ($pagination['page'] - 1) * $pagination['per_page'] + 1;
                    ?>
                    <?php foreach ($transactions as $i => $txn): ?>
                        <?php
                            $isCredit    = ($txn['type'] === 'credit');
                            $badgeClass  = $isCredit ? 'badge-red'   : 'badge-green';
                            $typeLabel   = $isCredit ? t('txn_type_credit') : t('txn_type_payment');
                            $amountClass = $isCredit ? 'td-due'      : 'td-paid';
                            $prefix      = $isCredit ? '+'           : '−';
                        ?>
                        <tr>
                            <td class="text-muted text-sm"><?= $rowStart + $i ?></td>

                            <td class="text-muted text-sm">
                                <?= e(date('d M Y, h:i A', strtotime((string)$txn['created_at']))) ?>
                            </td>

                            <td>
                                <a href="index.php?page=customers&amp;action=profile&amp;id=<?= (int)$txn['cust_id'] ?>"
                                   style="text-decoration:none; color:inherit;">
                                    <strong><?= e($txn['customer_name'] ?? '—') ?></strong>
                                </a>
                                <div class="text-muted text-sm font-mono">
                                    <?= e($txn['customer_phone'] ?? '') ?>
                                </div>
                            </td>

                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= e($typeLabel) ?>
                                </span>
                            </td>

                            <td class="td-money <?= $amountClass ?>">
                                <strong><?= $prefix ?><?= e(format_money((float)$txn['amount'])) ?></strong>
                            </td>

                            <td class="text-muted text-sm">
                                <?= e($txn['note'] ?? '—') ?>
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
