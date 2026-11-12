<?php
declare(strict_types=1);

/**
 * views/customers/history.php
 * -----------------------------------------------------------------------------
 * Customer Khata ledger history:
 *   - Lists all transactions newest first
 *   - Running balance calculated after each transaction
 *   - Type badges (credit vs payment)
 *   - Formatted timestamps
 *   - Direct link to view receipt for each transaction
 * -----------------------------------------------------------------------------
 * Rules:
 *   - Zero JavaScript
 *   - htmlspecialchars on all output via e()
 *   - format_money() on all monetary values (৳)
 *   - All UI strings from language files via t()
 */
?>

<!-- ===== Header & Breadcrumb ===== -->
<div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
                <?= e(t('customer_history_title')) ?>
            </h1>
            <p class="text-muted text-sm" style="margin-top: .25rem;">
                <strong><?= e($customer['name']) ?></strong> (<?= e($customer['phone']) ?>) &bull;
                <?= count($transactions) ?> <?= e(t('nav_transactions')) ?>
            </p>
        </div>
        <div class="d-flex gap-1 flex-wrap">
            <a href="index.php?page=transactions&amp;action=create&amp;cust_id=<?= (int)$customer['cust_id'] ?>" class="btn btn-primary">
                + <?= e(t('btn_new_transaction')) ?>
            </a>
            <a href="index.php?page=customers&amp;action=profile&amp;id=<?= (int)$customer['cust_id'] ?>" class="btn btn-secondary">
                <?= e(t('btn_view_profile')) ?>
            </a>
            <a href="index.php?page=customers" class="btn btn-ghost">
                &larr; <?= e(t('btn_back')) ?>
            </a>
        </div>
    </div>
</div>

<!-- ===== Balance Overview Strip ===== -->
<div class="grid-stats" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <span class="stat-label"><?= e(t('label_balance')) ?></span>
        <span class="stat-value <?= (float)$customer['balance'] > 0 ? 'due' : 'total' ?>">
            <?= e(format_money((float)$customer['balance'])) ?>
        </span>
    </div>
    <div class="stat-card">
        <span class="stat-label"><?= e(t('label_credit_limit')) ?></span>
        <span class="stat-value total">
            <?= e(format_money((float)$customer['credit_limit'])) ?>
        </span>
    </div>
</div>

<!-- ===== Transaction Table ===== -->
<?php if (empty($transactions)): ?>
    <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">📑</div>
        <p class="text-muted"><?= e(t('txn_empty_list')) ?></p>
        <a href="index.php?page=transactions&amp;action=create&amp;cust_id=<?= (int)$customer['cust_id'] ?>" class="btn btn-primary" style="margin-top: 1rem;">
            + <?= e(t('btn_new_transaction')) ?>
        </a>
    </div>
<?php else: ?>
    <section class="card" style="padding: 0; overflow: hidden;">
        <div class="table-wrap" style="margin-bottom: 0; border: none; border-radius: 0;">
            <table class="table" aria-label="<?= e(t('customer_history_title')) ?>">
                <thead>
                    <tr>
                        <th><?= e(t('txn_voucher_no')) ?></th>
                        <th><?= e(t('label_date')) ?></th>
                        <th><?= e(t('label_transaction_type')) ?></th>
                        <th><?= e(t('label_note')) ?></th>
                        <th class="td-money"><?= e(t('label_amount')) ?></th>
                        <th class="td-money"><?= e(t('txn_running_balance')) ?></th>
                        <th style="text-align: center;"><?= e(t('btn_print')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $txn): ?>
                        <?php
                            $isCredit = ($txn['type'] === 'credit');
                            $badgeClass = $isCredit ? 'badge-red' : 'badge-green';
                            $typeLabel  = $isCredit ? t('txn_type_credit') : t('txn_type_payment');
                            $amountClass = $isCredit ? 'td-due' : 'td-paid';
                            $amountPrefix = $isCredit ? '+' : '-';
                            $runningBalance = (float)$txn['running_balance'];
                        ?>
                        <tr>
                            <!-- Receipt # -->
                            <td class="font-mono text-muted text-sm">
                                #<?= (int)$txn['txn_id'] ?>
                            </td>

                            <!-- Date & Time -->
                            <td class="text-muted text-sm">
                                <?= e(date('d M Y, h:i A', strtotime($txn['created_at']))) ?>
                            </td>

                            <!-- Type badge -->
                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= e($typeLabel) ?>
                                </span>
                            </td>

                            <!-- Note -->
                            <td class="text-muted text-sm">
                                <?= e($txn['note'] ?? '—') ?>
                            </td>

                            <!-- Amount -->
                            <td class="td-money <?= $amountClass ?>">
                                <strong><?= $amountPrefix ?><?= e(format_money((float)$txn['amount'])) ?></strong>
                            </td>

                            <!-- Running Balance -->
                            <td class="td-money font-mono">
                                <strong><?= e(format_money($runningBalance)) ?></strong>
                            </td>

                            <!-- View Receipt Link -->
                            <td style="text-align: center;">
                                <a href="index.php?page=customers&amp;action=receipt&amp;id=<?= (int)$txn['txn_id'] ?>" class="btn btn-ghost btn-sm">
                                    🧾 <?= e(t('receipt_title')) ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
