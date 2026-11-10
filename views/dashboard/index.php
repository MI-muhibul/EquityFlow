<?php
declare(strict_types=1);

/**
 * views/dashboard/index.php
 * Shop overview dashboard template.
 * High-contrast dark theme, zero JavaScript, mobile-first responsive design.
 */
?>

<!-- =========================================================================
     1. Quick Action Bar
     ========================================================================= -->
<div class="card" style="padding: 1rem 1.25rem; margin-bottom: 1.25rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
                <?= e(t('nav_dashboard')) ?> &mdash; <?= e(t('app_name')) ?>
            </h1>
            <p class="text-muted text-sm" style="margin-top: .15rem;">
                <?= e(t('app_tagline')) ?>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-1">
            <a href="index.php?page=ledger&amp;action=credit" class="btn btn-primary btn-sm">
                + <?= e(t('btn_record_credit')) ?>
            </a>
            <a href="index.php?page=ledger&amp;action=payment" class="btn btn-secondary btn-sm">
                ✓ <?= e(t('btn_record_payment')) ?>
            </a>
            <a href="index.php?page=customers&amp;action=create" class="btn btn-ghost btn-sm">
                👤 <?= e(t('btn_add_customer')) ?>
            </a>
        </div>
    </div>
</div>

<!-- =========================================================================
     2. Primary Stats Overview Grid
     ========================================================================= -->
<section class="grid-stats" aria-label="Key Performance Indicators" style="margin-bottom: 1.5rem;">
    <!-- Stat 1: Total Due / Market Baki -->
    <div class="stat-card">
        <span class="stat-label"><?= e(t('report_outstanding_due')) ?></span>
        <span class="stat-value due"><?= e(format_money($totalDue)) ?></span>
        <span class="text-muted text-sm">বাজারে মোট পাওনা বাকি</span>
    </div>

    <!-- Stat 2: Total Cash Collected -->
    <div class="stat-card">
        <span class="stat-label"><?= e(t('report_total_cash')) ?></span>
        <span class="stat-value paid"><?= e(format_money($totalCollected)) ?></span>
        <span class="text-muted text-sm">মোট আদায়কৃত নগদ টাকা</span>
    </div>

    <!-- Stat 3: Total Customers -->
    <div class="stat-card">
        <span class="stat-label"><?= e(t('customer_title')) ?></span>
        <span class="stat-value total"><?= e((string)$totalCustomers) ?></span>
        <span class="text-muted text-sm">সক্রিয় খাতা রেজিস্টার</span>
    </div>

    <!-- Stat 4: Low Stock Alert -->
    <div class="stat-card">
        <span class="stat-label"><?= e(t('label_min_stock')) ?></span>
        <span class="stat-value" style="color: <?= $lowStockCount > 0 ? 'var(--clr-amber)' : 'var(--clr-green)' ?>;">
            <?= e((string)$lowStockCount) ?>
        </span>
        <span class="text-muted text-sm">
            <?php if ($lowStockCount > 0): ?>
                <span class="badge badge-amber"><?= e(t('status_low_stock')) ?></span>
            <?php else: ?>
                <span class="badge badge-green"><?= e(t('status_in_stock')) ?></span>
            <?php endif; ?>
        </span>
    </div>
</section>

<!-- =========================================================================
     3. Two-Column Operational Grids
     ========================================================================= -->
<div class="grid grid-2">

    <!-- Column Left: Top Due Customers -->
    <section class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">সর্বোচ্চ বকেয়া কাস্টমার তালিকা</h2>
                <div class="card-subtitle"><?= e(t('customer_total_due')) ?> (শীর্ষ ৫ জন)</div>
            </div>
            <a href="index.php?page=customers" class="btn btn-ghost btn-sm">
                সকল কাস্টমার &rarr;
            </a>
        </div>

        <?php if (empty($topDueCustomers)): ?>
            <p class="text-muted text-sm" style="padding: 1rem 0;">
                <?= e(t('customer_zero_balance')) ?>
            </p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table" aria-label="Top Due Customers">
                    <thead>
                        <tr>
                            <th><?= e(t('label_name')) ?></th>
                            <th><?= e(t('label_phone')) ?></th>
                            <th class="td-money"><?= e(t('label_balance')) ?></th>
                            <th>অবস্থা</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topDueCustomers as $cust): ?>
                            <tr>
                                <td>
                                    <strong><?= e($cust['name']) ?></strong>
                                </td>
                                <td class="text-muted font-mono">
                                    <?= e($cust['phone']) ?>
                                </td>
                                <td class="td-money td-due">
                                    <?= e(format_money($cust['balance'])) ?>
                                </td>
                                <td>
                                    <?php if ((float)$cust['balance'] >= (float)$cust['credit_limit'] && (float)$cust['credit_limit'] > 0): ?>
                                        <span class="badge badge-red">সীমা পার</span>
                                    <?php else: ?>
                                        <span class="badge badge-amber"><?= e(t('txn_type_credit')) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- Column Right: Recent Ledger Transactions -->
    <section class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title"><?= e(t('txn_title')) ?></h2>
                <div class="card-subtitle">সাম্প্রতিক লেনদেনের লাইভ খাতা</div>
            </div>
            <a href="index.php?page=ledger" class="btn btn-ghost btn-sm">
                সকল খাতা &rarr;
            </a>
        </div>

        <?php if (empty($recentTxns)): ?>
            <p class="text-muted text-sm" style="padding: 1rem 0;">
                <?= e(t('txn_empty_list')) ?>
            </p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table" aria-label="Recent Transactions">
                    <thead>
                        <tr>
                            <th><?= e(t('label_date')) ?></th>
                            <th>কাস্টমার</th>
                            <th>ধরন</th>
                            <th class="td-money"><?= e(t('label_amount')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTxns as $txn): ?>
                            <tr>
                                <td class="text-muted text-sm">
                                    <?= e(date('d M, h:i A', strtotime($txn['created_at']))) ?>
                                </td>
                                <td>
                                    <strong><?= e($txn['customer_name']) ?></strong>
                                    <?php if (!empty($txn['note'])): ?>
                                        <div class="text-muted text-sm" style="max-width: 140px; overflow: hidden; text-overflow: ellipsis;">
                                            <?= e($txn['note']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($txn['type'] === 'credit'): ?>
                                        <span class="badge badge-red"><?= e(t('txn_type_credit')) ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-green"><?= e(t('txn_type_payment')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="td-money <?= $txn['type'] === 'credit' ? 'td-due' : 'td-paid' ?>">
                                    <?= e(format_money($txn['amount'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</div>
