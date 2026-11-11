<?php
declare(strict_types=1);

/**
 * views/customers/index.php
 * -----------------------------------------------------------------------------
 * Customer list table — shows all customers with their ledger summary.
 *
 * Variables injected by CustomerController::index():
 *   $customers  — array of customer rows from Customer::all()
 * -----------------------------------------------------------------------------
 * Rules: Zero JS, e() on all output, format_money() for ৳ values,
 *        all labels from t(), horizontally scrollable table on mobile.
 */
?>

<!-- ===== Page Header ===================================================== -->
<div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
                <?= e(t('customer_title')) ?>
            </h1>
            <p class="text-muted text-sm" style="margin-top: .25rem;">
                <?= e(t('customer_details')) ?>
                &mdash;
                <?= count($customers) ?> <?= e(t('nav_customers')) ?>
            </p>
        </div>
        <a href="index.php?page=customers&amp;action=create"
           class="btn btn-primary">
            + <?= e(t('btn_add_customer')) ?>
        </a>
    </div>
</div>

<!-- ===== Customer Table ================================================= -->
<?php if (empty($customers)): ?>
    <!-- Empty state: no customers yet -->
    <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">👤</div>
        <p class="text-muted"><?= e(t('customer_empty_list')) ?></p>
        <a href="index.php?page=customers&amp;action=create"
           class="btn btn-primary" style="margin-top: 1rem;">
            + <?= e(t('btn_add_customer')) ?>
        </a>
    </div>

<?php else: ?>
    <!-- Summary strip: total customers / total outstanding -->
    <?php
        // Calculate total outstanding across all customers
        $totalDue = array_sum(array_column($customers, 'balance'));
    ?>
    <div class="grid-stats" style="margin-bottom: 1.5rem;">
        <div class="stat-card">
            <span class="stat-label"><?= e(t('card_customers')) ?></span>
            <span class="stat-value total"><?= count($customers) ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label"><?= e(t('card_total_outstanding')) ?></span>
            <span class="stat-value due"><?= e(format_money($totalDue)) ?></span>
        </div>
    </div>

    <!-- Scrollable table -->
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
                        <th><?= e(t('label_date')) ?></th>
                        <th><?= e(t('btn_edit')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $i => $cust): ?>
                        <?php
                            $balance = (float)$cust['balance'];
                            // Pick badge colour by balance
                            if ($balance > 0) {
                                $badgeClass = 'badge-red';
                                $balanceClass = 'td-due';
                            } elseif ($balance < 0) {
                                $badgeClass = 'badge-green';
                                $balanceClass = 'td-paid';
                            } else {
                                $badgeClass = 'badge-neutral';
                                $balanceClass = '';
                            }
                        ?>
                        <tr>
                            <!-- Row number -->
                            <td class="text-muted text-sm"><?= $i + 1 ?></td>

                            <!-- Customer name -->
                            <td>
                                <strong><?= e($cust['name']) ?></strong>
                                <?php if (!empty($cust['address'])): ?>
                                    <div class="text-muted text-sm"><?= e($cust['address']) ?></div>
                                <?php endif; ?>
                            </td>

                            <!-- Phone number -->
                            <td class="font-mono text-muted">
                                <?= e($cust['phone']) ?>
                            </td>

                            <!-- Balance — colour-coded -->
                            <td class="td-money <?= $balanceClass ?>">
                                <?= e(format_money($balance)) ?>
                                <?php if ($balance > 0): ?>
                                    <div>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= e(t('customer_total_due')) ?>
                                        </span>
                                    </div>
                                <?php elseif ($balance == 0): ?>
                                    <div>
                                        <span class="badge badge-neutral">
                                            <?= e(t('customer_zero_balance')) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Credit limit -->
                            <td class="td-money">
                                <?= e(format_money((float)$cust['credit_limit'])) ?>
                            </td>

                            <!-- Join date — formatted nicely -->
                            <td class="text-muted text-sm">
                                <?= e(date('d M Y', strtotime($cust['join_date']))) ?>
                            </td>

                            <!-- Action links (Edit / View) -->
                            <td>
                                <a href="index.php?page=customers&amp;action=edit&amp;id=<?= (int)$cust['cust_id'] ?>"
                                   class="btn btn-ghost btn-sm">
                                    <?= e(t('btn_edit')) ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

<?php endif; ?>
