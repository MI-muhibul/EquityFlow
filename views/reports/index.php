<?php
declare(strict_types=1);

/**
 * views/reports/index.php
 * -----------------------------------------------------------------------------
 * Monthly Financial Summary & Outstanding Balance List.
 *
 * Variables injected by ReportController::index():
 *   $summary               array  — Monthly figures (credit, payment, profit, net, txns)
 *   $outstandingCustomers  array  — Customers with balance > 0 sorted DESC
 *   $availableYears        array  — Available transaction years for dropdown
 *   $pastReports           array  — Historic snapshots from `reports` table
 *   $selectedYear          int    — Currently selected year
 *   $selectedMonth         int    — Currently selected month
 * -----------------------------------------------------------------------------
 * Rules:
 *   - Zero JavaScript
 *   - htmlspecialchars on all outputs via e()
 *   - Money formatted as ৳ with 2 decimals via format_money()
 *   - High-contrast responsive dark theme
 * -----------------------------------------------------------------------------
 */

$netChange = (float)$summary['net_change'];
$isNetPositive = ($netChange >= 0);
$netClass = $isNetPositive ? 'text-success' : 'text-danger';
$netSign = $isNetPositive ? '+' : '';

// Prepare breakdown data for pure SVG chart
$breakdownItems = [
    'credit'  => [
        'label'  => t('report_total_credit'),
        'amount' => (float)$summary['total_credit'],
        'color'  => '#ef4444', // Red
    ],
    'payment' => [
        'label'  => t('report_total_cash'),
        'amount' => (float)$summary['total_payments'],
        'color'  => '#10b981', // Emerald
    ],
    'profit'  => [
        'label'  => t('report_net_profit'),
        'amount' => (float)$summary['estimated_profit'],
        'color'  => '#3b82f6', // Blue
    ],
];
?>

<!-- ===== 1. Page Header & Month/Year Selector ===== -->
<div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
                📊 <?= e(t('report_title')) ?>
            </h1>
            <p class="text-muted text-sm" style="margin-top: .25rem;">
                <?= e(t('month_' . $selectedMonth)) ?> <?= e((string)$selectedYear) ?>
                &bull; <span class="badge badge-neutral"><?= e(t('report_snapshot_saved')) ?></span>
            </p>
        </div>

        <!-- Month/Year GET form (Zero JavaScript) -->
        <form method="GET" action="index.php" class="d-flex align-center flex-wrap gap-1" aria-label="<?= e(t('report_select_period')) ?>">
            <input type="hidden" name="page" value="reports">

            <!-- Month Dropdown -->
            <div class="d-flex align-center gap-05">
                <label for="sel_month" class="text-muted text-sm"><?= e(t('report_month')) ?>:</label>
                <select id="sel_month" name="month" style="background: var(--bg-page); border: 1px solid var(--border-dim); color: var(--text-high); padding: .35rem .6rem; border-radius: var(--r-sm);">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= ($m === $selectedMonth) ? 'selected' : '' ?>>
                            <?= e(t('month_' . $m)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Year Dropdown -->
            <div class="d-flex align-center gap-05">
                <label for="sel_year" class="text-muted text-sm"><?= e(t('report_year')) ?>:</label>
                <select id="sel_year" name="year" style="background: var(--bg-page); border: 1px solid var(--border-dim); color: var(--text-high); padding: .35rem .6rem; border-radius: var(--r-sm);">
                    <?php foreach ($availableYears as $yr): ?>
                        <option value="<?= $yr ?>" <?= ($yr === $selectedYear) ? 'selected' : '' ?>>
                            <?= e((string)$yr) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-sm">
                <?= e(t('report_btn_view')) ?>
            </button>
        </form>
    </div>
</div>

<!-- ===== 2. KPI Cards Grid ===== -->
<div class="grid-stats" style="margin-bottom: 1.5rem;">
    <!-- Total Credit Given -->
    <div class="stat-card">
        <span class="stat-label"><?= e(t('report_total_credit')) ?></span>
        <span class="stat-value due"><?= e(format_money((float)$summary['total_credit'])) ?></span>
        <span class="text-muted text-sm" style="margin-top: .25rem; display: block;">
            <?= (int)$summary['credit_count'] ?> <?= e(t('report_credit_txns')) ?>
        </span>
    </div>

    <!-- Payments Received -->
    <div class="stat-card">
        <span class="stat-label"><?= e(t('report_total_cash')) ?></span>
        <span class="stat-value" style="color: var(--clr-green);"><?= e(format_money((float)$summary['total_payments'])) ?></span>
        <span class="text-muted text-sm" style="margin-top: .25rem; display: block;">
            <?= (int)$summary['payment_count'] ?> <?= e(t('report_payment_txns')) ?>
        </span>
    </div>

    <!-- Estimated Profit -->
    <div class="stat-card">
        <span class="stat-label"><?= e(t('report_net_profit')) ?></span>
        <span class="stat-value total"><?= e(format_money((float)$summary['estimated_profit'])) ?></span>
        <span class="text-muted text-sm" style="margin-top: .25rem; display: block;">
            <?= e(t('label_category')) ?>: Sale Items
        </span>
    </div>

    <!-- Net Change -->
    <div class="stat-card">
        <span class="stat-label"><?= e(t('report_net_change')) ?></span>
        <span class="stat-value <?= $netClass ?>">
            <?= $netSign ?><?= e(format_money($netChange)) ?>
        </span>
        <span class="text-muted text-sm" style="margin-top: .25rem; display: block;">
            <?= (int)$summary['txn_count'] ?> <?= e(t('report_txn_count')) ?>
        </span>
    </div>
</div>

<!-- ===== 3. Visual SVG Breakdown ===== -->
<section class="card" style="padding: 1.5rem; margin-bottom: 2rem;">
    <div class="d-flex justify-between align-center flex-wrap gap-1" style="margin-bottom: 1rem;">
        <h2 style="font-size: 1.1rem; font-weight: 700; margin: 0;">
            📈 <?= e(t('report_breakdown_title')) ?> (<?= e(t('month_' . $selectedMonth)) ?> <?= e((string)$selectedYear) ?>)
        </h2>
        <span class="text-muted text-sm">
            <?= e(t('card_total_outstanding')) ?>: <strong><?= e(format_money((float)$summary['total_outstanding'])) ?></strong>
        </span>
    </div>

    <!-- Inline SVG Bar Breakdown -->
    <div style="background: var(--bg-surface); padding: 1rem; border-radius: var(--r-md); border: 1px solid var(--border-dim); margin-bottom: 1.5rem;">
        <?= render_bar_breakdown($breakdownItems, [
            'width' => 700,
            'title' => t('report_breakdown_title'),
        ]) ?>
    </div>

    <!-- Summary Footnotes -->
    <div class="d-flex flex-wrap gap-2 text-muted text-sm" style="border-top: 1px solid var(--border-dim); padding-top: .875rem;">
        <div>
            <span style="display:inline-block; width:10px; height:10px; background:#ef4444; border-radius:2px; margin-right:4px;"></span>
            <?= e(t('report_total_credit')) ?>: <strong><?= e(format_money((float)$summary['total_credit'])) ?></strong>
        </div>
        <div>
            <span style="display:inline-block; width:10px; height:10px; background:#10b981; border-radius:2px; margin-right:4px;"></span>
            <?= e(t('report_total_cash')) ?>: <strong><?= e(format_money((float)$summary['total_payments'])) ?></strong>
        </div>
        <div>
            <span style="display:inline-block; width:10px; height:10px; background:#3b82f6; border-radius:2px; margin-right:4px;"></span>
            <?= e(t('report_net_profit')) ?>: <strong><?= e(format_money((float)$summary['estimated_profit'])) ?></strong>
        </div>
    </div>
</section>

<!-- ===== 4. Outstanding Balances List (Sorted DESC by amount) ===== -->
<section class="card" style="padding: 0; overflow: hidden; margin-bottom: 2rem;">
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-dim);">
        <div class="d-flex flex-wrap justify-between align-center gap-1">
            <div>
                <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0;">
                    👥 <?= e(t('report_outstanding_title')) ?>
                </h2>
                <p class="text-muted text-sm" style="margin-top: .25rem;">
                    <?= e(t('report_outstanding_desc')) ?> &mdash; <?= count($outstandingCustomers) ?> <?= e(t('nav_customers')) ?>
                </p>
            </div>
            <div class="stat-value due" style="font-size: 1.25rem;">
                <?= e(t('card_total_outstanding')) ?>: <?= e(format_money((float)$summary['total_outstanding'])) ?>
            </div>
        </div>
    </div>

    <?php if (empty($outstandingCustomers)): ?>
        <div style="text-align: center; padding: 3rem 1.5rem;">
            <div style="font-size: 2.5rem; margin-bottom: .75rem;">🎉</div>
            <p class="text-muted"><?= e(t('report_no_outstanding')) ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap" style="margin-bottom: 0; border: none; border-radius: 0;">
            <table class="table" aria-label="<?= e(t('report_outstanding_title')) ?>">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= e(t('label_name')) ?></th>
                        <th><?= e(t('label_phone')) ?></th>
                        <th class="td-money"><?= e(t('label_balance')) ?></th>
                        <th class="td-money"><?= e(t('label_credit_limit')) ?></th>
                        <th style="text-align: center;"><?= e(t('report_credit_util')) ?></th>
                        <th><?= e(t('report_last_txn')) ?></th>
                        <th style="text-align: right;"><?= e(t('btn_edit')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($outstandingCustomers as $idx => $cust): ?>
                        <?php
                            $bal = (float)$cust['balance'];
                            $limit = (float)$cust['credit_limit'];
                            $util = (float)$cust['util_pct'];
                            $utilClass = ($util >= 90.0) ? 'badge-red' : (($util >= 60.0) ? 'badge-yellow' : 'badge-neutral');
                        ?>
                        <tr>
                            <td class="text-muted text-sm"><?= $idx + 1 ?></td>
                            <td>
                                <a href="index.php?page=customers&amp;action=profile&amp;id=<?= (int)$cust['cust_id'] ?>" style="color: inherit; text-decoration: none; font-weight: 700;">
                                    <?= e($cust['name']) ?>
                                </a>
                            </td>
                            <td class="font-mono text-muted"><?= e($cust['phone']) ?></td>
                            <td class="td-money td-due">
                                <strong><?= e(format_money($bal)) ?></strong>
                            </td>
                            <td class="td-money font-mono">
                                <?= e(format_money($limit)) ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($limit > 0): ?>
                                    <span class="badge <?= $utilClass ?>">
                                        <?= number_format($util, 1) ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted text-sm">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted text-sm">
                                <?= !empty($cust['last_txn_date']) ? e(date('d M Y, h:i A', strtotime((string)$cust['last_txn_date']))) : '—' ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="d-flex gap-05 justify-end" style="justify-content: flex-end;">
                                    <a href="index.php?page=customers&amp;action=history&amp;id=<?= (int)$cust['cust_id'] ?>" class="btn btn-ghost btn-sm">
                                        <?= e(t('btn_view_history')) ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- ===== 5. Reports Archive Snapshot History ===== -->
<?php if (!empty($pastReports)): ?>
    <section class="card" style="padding: 1.25rem 1.5rem;">
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem;">
            📁 <?= e(t('report_history_title')) ?>
        </h3>
        <div class="table-wrap" style="margin-bottom: 0;">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= e(t('report_period')) ?></th>
                        <th class="td-money"><?= e(t('report_total_credit')) ?></th>
                        <th class="td-money"><?= e(t('report_total_cash')) ?></th>
                        <th class="td-money"><?= e(t('report_net_profit')) ?></th>
                        <th><?= e(t('label_date')) ?></th>
                        <th style="text-align: right;"><?= e(t('btn_view_details')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pastReports as $pKey => $pData): ?>
                        <?php
                            $parts = explode('-', $pKey);
                            $pYear = (int)($parts[0] ?? $selectedYear);
                            $pMonth = (int)($parts[1] ?? $selectedMonth);
                        ?>
                        <tr>
                            <td>
                                <strong><?= e(t('month_' . $pMonth)) ?> <?= e((string)$pYear) ?></strong>
                            </td>
                            <td class="td-money td-due">
                                <?= e(format_money((float)($pData['credit'] ?? 0))) ?>
                            </td>
                            <td class="td-money td-paid">
                                <?= e(format_money((float)($pData['payment'] ?? 0))) ?>
                            </td>
                            <td class="td-money">
                                <?= e(format_money((float)($pData['profit'] ?? 0))) ?>
                            </td>
                            <td class="text-muted text-sm">
                                <?= e(date('d M Y, h:i A', strtotime((string)$pData['generated_at']))) ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="index.php?page=reports&amp;year=<?= $pYear ?>&amp;month=<?= $pMonth ?>" class="btn btn-ghost btn-sm">
                                    🔍 <?= e(t('btn_view_details')) ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
