<?php
declare(strict_types=1);

/**
 * views/customers/delete_confirm.php
 * -----------------------------------------------------------------------------
 * Server-rendered delete confirmation page — zero JavaScript.
 *
 * Variables injected by CustomerController::deleteConfirm():
 *   $customer   array  — the customer row (already user-scoped)
 *   $canDelete  bool   — true only when balance == 0.00
 *
 * If $canDelete is false the POST button is not shown; instead the translated
 * reason is displayed (error_customer_has_due).
 * -----------------------------------------------------------------------------
 */

$cid = (int)$customer['cust_id'];
$bal = (float)$customer['balance'];
?>

<!-- ===== Page Header ========================================================= -->
<div class="d-flex flex-wrap justify-between align-center gap-1" style="margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0; color: var(--accent-danger, #ef4444);">
            🗑 <?= e(t('btn_delete')) ?> — <?= e($customer['name']) ?>
        </h1>
        <p class="text-muted text-sm" style="margin-top: .25rem;">
            <?= e(t('customer_title')) ?>
        </p>
    </div>
    <a href="index.php?page=customers&amp;action=profile&amp;id=<?= $cid ?>" class="btn btn-ghost btn-sm">
        &larr; <?= e(t('btn_back')) ?>
    </a>
</div>

<!-- ===== Confirmation Card =================================================== -->
<div class="card" style="max-width: 560px; padding: 2rem;">

    <!-- Customer summary -->
    <table class="detail-table" style="width: 100%; margin-bottom: 1.5rem;">
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
                <th><?= e(t('label_balance')) ?></th>
                <td class="td-money <?= $bal > 0 ? 'td-due' : '' ?>">
                    <strong><?= e(format_money($bal)) ?></strong>
                    <?php if ($bal > 0): ?>
                        <span class="badge badge-red" style="margin-left:.5rem;">
                            <?= e(t('customer_total_due')) ?>
                        </span>
                    <?php else: ?>
                        <span class="badge badge-neutral" style="margin-left:.5rem;">
                            <?= e(t('customer_zero_balance')) ?>
                        </span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><?= e(t('label_join_date')) ?></th>
                <td class="text-muted"><?= e(date('d F Y', strtotime($customer['join_date']))) ?></td>
            </tr>
        </tbody>
    </table>

    <?php if (!$canDelete): ?>
        <!-- ============================================================ -->
        <!-- BLOCKED: customer has an outstanding balance                 -->
        <!-- ============================================================ -->
        <div class="alert alert-error" role="alert" style="margin-bottom: 1.5rem;">
            <span class="alert-icon" aria-hidden="true">&#x2715;</span>
            <span class="alert-text">
                <?= e(t('error_customer_has_due')) ?>
                <?= e(t('label_balance')) ?>: <strong><?= e(format_money($bal)) ?></strong>
            </span>
        </div>

        <p class="text-muted text-sm" style="margin-bottom: 1.5rem;">
            <!-- Explain what the user must do first -->
            To delete this customer, all outstanding dues must be cleared first.
            Record a payment transaction to bring the balance to ৳0.00.
        </p>

        <div class="d-flex gap-1 flex-wrap">
            <a href="index.php?page=customers&amp;action=profile&amp;id=<?= $cid ?>"
               class="btn btn-secondary">
                &larr; <?= e(t('btn_back')) ?>
            </a>
            <a href="index.php?page=transactions&amp;action=create&amp;cust_id=<?= $cid ?>"
               class="btn btn-primary">
                <?= e(t('btn_record_payment')) ?>
            </a>
        </div>

    <?php else: ?>
        <!-- ============================================================ -->
        <!-- ALLOWED: balance = 0, show confirmation form                 -->
        <!-- ============================================================ -->
        <div class="alert alert-error" role="alert" style="margin-bottom: 1.5rem;">
            <span class="alert-icon" aria-hidden="true">⚠</span>
            <span class="alert-text">
                <?= e(t('customer_delete_confirm')) ?>
            </span>
        </div>

        <p class="text-muted text-sm" style="margin-bottom: 1.5rem;">
            This action <strong>cannot be undone</strong>. All transaction history
            for this customer will also be permanently removed (cascading delete).
        </p>

        <!-- POST form — zero JS, CSRF protected -->
        <form method="POST" action="index.php?page=customers&amp;action=delete">
            <?= csrf_field() ?>
            <!-- Customer ID sent in hidden field -->
            <input type="hidden" name="id" value="<?= $cid ?>">

            <div class="d-flex justify-between align-center gap-1 flex-wrap">
                <a href="index.php?page=customers&amp;action=profile&amp;id=<?= $cid ?>"
                   class="btn btn-ghost">
                    &larr; <?= e(t('btn_cancel')) ?>
                </a>
                <button type="submit" class="btn btn-danger">
                    🗑 <?= e(t('btn_delete')) ?>
                </button>
            </div>
        </form>

    <?php endif; ?>
</div>
