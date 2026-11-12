<?php
declare(strict_types=1);

/**
 * views/customers/receipt.php
 * -----------------------------------------------------------------------------
 * Digital receipt view for a single transaction (credit sale or payment).
 * Designed for screen only (no print CSS yet).
 *
 * Information displayed:
 *   - Shop name, phone, address from settings
 *   - Receipt / Voucher number
 *   - Customer name, phone, address
 *   - Transaction date & timestamp
 *   - Transaction type badge (credit / payment)
 *   - Transaction amount (৳)
 *   - Balance after transaction (৳)
 *   - Note / Description
 *   - Navigation back to customer profile & history
 * -----------------------------------------------------------------------------
 * Rules:
 *   - Zero JavaScript
 *   - htmlspecialchars via e() on all output
 *   - format_money() for all ৳ currency values
 *   - All text from language pack via t()
 *   - Dark high-contrast styling matching design system
 */
?>

<!-- ===== Breadcrumb & Header ===== -->
<div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
                <?= e(t('receipt_title')) ?> #<?= (int)$transaction['txn_id'] ?>
            </h1>
            <p class="text-muted text-sm" style="margin-top: .25rem;">
                <?= e(t('app_tagline')) ?>
            </p>
        </div>
        <div class="d-flex gap-1 flex-wrap">
            <a href="index.php?page=customers&amp;action=history&amp;id=<?= (int)$transaction['cust_id'] ?>" class="btn btn-secondary">
                <?= e(t('btn_view_history')) ?>
            </a>
            <a href="index.php?page=customers&amp;action=profile&amp;id=<?= (int)$transaction['cust_id'] ?>" class="btn btn-ghost">
                &larr; <?= e(t('btn_view_profile')) ?>
            </a>
        </div>
    </div>
</div>

<!-- ===== Receipt Voucher Card (Screen View) ===== -->
<div class="card" style="max-width: 620px; margin: 0 auto; padding: 2.25rem; border: 1px solid var(--border-color, #2a3754); box-shadow: 0 16px 40px rgba(0,0,0,0.5);">

    <!-- Shop Branding Header -->
    <div style="text-align: center; border-bottom: 2px dashed var(--border-color, #2a3754); padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
        <div style="font-size: 2rem; margin-bottom: 0.25rem;"><?= e(t('currency_symbol')) ?></div>
        <h2 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 0.25rem; color: var(--text-primary, #f8fafc);">
            <?= e($shopName) ?>
        </h2>
        <?php if (!empty($shopAddress)): ?>
            <p class="text-muted text-sm" style="margin: 0.2rem 0;"><?= e($shopAddress) ?></p>
        <?php endif; ?>
        <?php if (!empty($shopPhone)): ?>
            <p class="text-muted text-sm font-mono" style="margin: 0.2rem 0;"><?= e(t('label_phone')) ?>: <?= e($shopPhone) ?></p>
        <?php endif; ?>
        <div style="margin-top: 0.75rem; display: inline-block; padding: 0.25rem 0.75rem; background-color: var(--bg-base, #0b0f19); border-radius: 4px; border: 1px solid var(--border-color, #2a3754); font-family: ui-monospace, SFMono-Regular, monospace; font-size: 0.85rem;">
            <?= e(t('receipt_voucher_no')) ?>: EF-TXN-<?= str_pad((string)$transaction['txn_id'], 6, '0', STR_PAD_LEFT) ?>
        </div>
    </div>

    <!-- Transaction Meta Details -->
    <table class="detail-table" style="width: 100%; margin-bottom: 1.5rem;">
        <tbody>
            <tr>
                <th style="width: 40%;"><?= e(t('label_date')) ?> / <?= e(t('label_time')) ?? 'সময়' ?></th>
                <td class="text-muted">
                    <?= e(date('d F Y, h:i A', strtotime($transaction['created_at']))) ?>
                </td>
            </tr>
            <tr>
                <th><?= e(t('label_name')) ?></th>
                <td>
                    <strong><?= e($transaction['customer_name']) ?></strong>
                </td>
            </tr>
            <tr>
                <th><?= e(t('label_phone')) ?></th>
                <td class="font-mono text-muted">
                    <?= e($transaction['customer_phone']) ?>
                </td>
            </tr>
            <?php if (!empty($transaction['customer_address'])): ?>
                <tr>
                    <th><?= e(t('label_address')) ?></th>
                    <td class="text-muted"><?= e($transaction['customer_address']) ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <th><?= e(t('label_transaction_type')) ?></th>
                <td>
                    <?php if ($transaction['type'] === 'credit'): ?>
                        <span class="badge badge-red" style="font-size: 0.85rem;">
                            <?= e(t('txn_type_credit')) ?>
                        </span>
                    <?php else: ?>
                        <span class="badge badge-green" style="font-size: 0.85rem;">
                            <?= e(t('txn_type_payment')) ?>
                        </span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if (!empty($transaction['note'])): ?>
                <tr>
                    <th><?= e(t('label_note')) ?></th>
                    <td class="text-muted"><?= e($transaction['note']) ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Amount & Balance Summary Box -->
    <div style="background-color: var(--bg-base, #0b0f19); border: 1px solid var(--border-color, #2a3754); border-radius: 8px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
        <div class="d-flex justify-between align-center" style="margin-bottom: 0.75rem;">
            <span style="font-size: 1rem; color: var(--text-secondary, #94a3b8); font-weight: 600;">
                <?= e(t('label_amount')) ?>:
            </span>
            <span style="font-size: 1.35rem; font-weight: 700; color: <?= $transaction['type'] === 'credit' ? '#f87171' : '#34d399' ?>;">
                <?= $transaction['type'] === 'credit' ? '+' : '-' ?><?= e(format_money((float)$transaction['amount'])) ?>
            </span>
        </div>
        <div style="border-top: 1px solid var(--border-color, #2a3754); padding-top: 0.75rem;" class="d-flex justify-between align-center">
            <span style="font-size: 0.95rem; color: var(--text-secondary, #94a3b8); font-weight: 600;">
                <?= e(t('receipt_balance_after')) ?>:
            </span>
            <span style="font-size: 1.15rem; font-weight: 700; font-family: ui-monospace, SFMono-Regular, monospace; color: var(--text-primary, #f8fafc);">
                <?= e(format_money((float)$transaction['balance_after'])) ?>
            </span>
        </div>
    </div>

    <!-- Shop Footer Note -->
    <?php if (!empty($footerNote)): ?>
        <p class="text-muted text-sm" style="text-align: center; margin: 1rem 0; font-style: italic;">
            <?= e($footerNote) ?>
        </p>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="d-flex justify-between align-center" style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color, #2a3754);">
        <a href="index.php?page=customers&amp;action=history&amp;id=<?= (int)$transaction['cust_id'] ?>" class="btn btn-secondary">
            &larr; <?= e(t('btn_view_history')) ?>
        </a>
        <a href="index.php?page=transactions&amp;action=create&amp;cust_id=<?= (int)$transaction['cust_id'] ?>" class="btn btn-primary">
            + <?= e(t('btn_new_transaction')) ?>
        </a>
    </div>

</div>
