<?php
declare(strict_types=1);

/**
 * views/customers/profile.php
 * -----------------------------------------------------------------------------
 * Customer profile view displaying personal details, credit limit, current
 * balance, and quick action buttons including Ledger History and Record Transaction.
 * -----------------------------------------------------------------------------
 * Rules:
 *   - Zero JavaScript
 *   - htmlspecialchars via e() on all output
 *   - format_money() for all ৳ currency values
 *   - All text from language pack via t()
 */
?>

<!-- ===== Breadcrumb & Header ===== -->
<div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
                <?= e($customer['name']) ?>
            </h1>
            <p class="text-muted text-sm" style="margin-top: .25rem;">
                <?= e(t('customer_details')) ?> &bull; <?= e(t('label_join_date')) ?>: <?= e(date('d M Y', strtotime($customer['join_date']))) ?>
            </p>
        </div>
        <div class="d-flex gap-1 flex-wrap">
            <a href="index.php?page=transactions&amp;action=create&amp;cust_id=<?= (int)$customer['cust_id'] ?>" class="btn btn-primary">
                + <?= e(t('btn_new_transaction')) ?>
            </a>
            <a href="index.php?page=customers&amp;action=history&amp;id=<?= (int)$customer['cust_id'] ?>" class="btn btn-secondary">
                📑 <?= e(t('btn_view_history')) ?>
            </a>
            <a href="index.php?page=customers&amp;action=edit&amp;id=<?= (int)$customer['cust_id'] ?>" class="btn btn-secondary">
                ✏️ <?= e(t('btn_edit')) ?>
            </a>
            <a href="index.php?page=customers" class="btn btn-ghost">
                &larr; <?= e(t('btn_back')) ?>
            </a>
        </div>
    </div>
</div>

<!-- ===== Stat Cards ===== -->
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

<!-- ===== Profile Detail Table ===== -->
<div class="card" style="max-width: 720px; margin: 0 auto; padding: 2rem;">
    <table class="detail-table" style="width: 100%; margin-bottom: 2rem;">
        <tbody>
            <tr>
                <th style="width: 35%;"><?= e(t('label_name')) ?></th>
                <td><strong><?= e($customer['name']) ?></strong></td>
            </tr>
            <tr>
                <th><?= e(t('label_phone')) ?></th>
                <td class="font-mono text-muted"><?= e($customer['phone']) ?></td>
            </tr>
            <tr>
                <th><?= e(t('label_address')) ?></th>
                <td class="text-muted"><?= e(!empty($customer['address']) ? $customer['address'] : '—') ?></td>
            </tr>
            <tr>
                <th><?= e(t('label_credit_limit')) ?></th>
                <td class="td-money"><?= e(format_money((float)$customer['credit_limit'])) ?></td>
            </tr>
            <tr>
                <th><?= e(t('label_balance')) ?></th>
                <td class="td-money <?= (float)$customer['balance'] > 0 ? 'td-due' : 'td-paid' ?>">
                    <strong><?= e(format_money((float)$customer['balance'])) ?></strong>
                    <?php if ((float)$customer['balance'] > 0): ?>
                        <span class="badge badge-red" style="margin-left: 0.5rem;"><?= e(t('customer_total_due')) ?></span>
                    <?php else: ?>
                        <span class="badge badge-neutral" style="margin-left: 0.5rem;"><?= e(t('customer_zero_balance')) ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><?= e(t('label_join_date')) ?></th>
                <td class="text-muted"><?= e(date('d F Y', strtotime($customer['join_date']))) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Bottom Actions: History, Edit, Delete -->
    <div class="d-flex justify-between align-center flex-wrap gap-1" style="border-top: 1px solid var(--border-color, #2a3754); padding-top: 1.5rem;">
        <div>
            <a href="index.php?page=customers&amp;action=history&amp;id=<?= (int)$customer['cust_id'] ?>" class="btn btn-secondary">
                📑 <?= e(t('btn_view_history')) ?>
            </a>
        </div>
        <div class="d-flex gap-1">
            <a href="index.php?page=customers&amp;action=edit&amp;id=<?= (int)$customer['cust_id'] ?>" class="btn btn-secondary">
                <?= e(t('btn_edit')) ?>
            </a>
            <?php if ((float)$customer['balance'] == 0.0): ?>
                <a href="index.php?page=customers&amp;action=deleteConfirm&amp;id=<?= (int)$customer['cust_id'] ?>" class="btn btn-danger">
                    <?= e(t('btn_delete')) ?>
                </a>
            <?php else: ?>
                <span class="text-muted text-sm" style="display: flex; align-items: center;" title="<?= e(t('error_customer_has_due')) ?>">
                    🔒 <?= e(t('error_customer_has_due')) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>
