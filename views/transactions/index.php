<?php
declare(strict_types=1);

/**
 * views/transactions/index.php
 * -----------------------------------------------------------------------------
 * Transaction history table listing recent credit sales and payments.
 * Zero JavaScript, high-contrast dark theme, all labels via t(), money via format_money().
 */
?>

<!-- ===== Page Header ===== -->
<div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
                <?= e(t('txn_title')) ?>
            </h1>
            <p class="text-muted text-sm" style="margin-top: .25rem;">
                <?= count($transactions) ?> <?= e(t('nav_transactions')) ?>
            </p>
        </div>
        <div class="d-flex gap-1">
            <a href="index.php?page=transactions&amp;action=create" class="btn btn-primary">
                + <?= e(t('btn_new_transaction')) ?>
            </a>
        </div>
    </div>
</div>

<?php if (empty($transactions)): ?>
    <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">📑</div>
        <p class="text-muted"><?= e(t('txn_empty_list')) ?></p>
        <a href="index.php?page=transactions&amp;action=create" class="btn btn-primary" style="margin-top: 1rem;">
            + <?= e(t('btn_new_transaction')) ?>
        </a>
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
                    <?php foreach ($transactions as $txn): ?>
                        <?php
                            $isCredit = ($txn['type'] === 'credit');
                            $badgeClass = $isCredit ? 'badge-red' : 'badge-green';
                            $typeLabel  = $isCredit ? t('txn_type_credit') : t('txn_type_payment');
                            $amountClass = $isCredit ? 'td-due' : 'td-paid';
                            $amountPrefix = $isCredit ? '+' : '-';
                        ?>
                        <tr>
                            <td class="text-muted text-sm">#<?= (int)$txn['txn_id'] ?></td>
                            <td class="text-muted text-sm">
                                <?= e(date('d M Y, h:i A', strtotime($txn['created_at']))) ?>
                            </td>
                            <td>
                                <strong><?= e($txn['customer_name'] ?? '—') ?></strong>
                                <div class="text-muted text-sm font-mono"><?= e($txn['customer_phone'] ?? '') ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= e($typeLabel) ?>
                                </span>
                            </td>
                            <td class="td-money <?= $amountClass ?>">
                                <strong><?= $amountPrefix ?><?= e(format_money((float)$txn['amount'])) ?></strong>
                            </td>
                            <td class="text-muted text-sm">
                                <?= e($txn['note'] ?? '—') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
