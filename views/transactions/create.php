<?php
declare(strict_types=1);

/**
 * views/transactions/create.php
 * -----------------------------------------------------------------------------
 * Form to record a credit sale (baki) or payment (joma).
 * Rules:
 *   - Strictly zero JavaScript
 *   - POST form with CSRF token
 *   - Fixed 5 server-rendered product item rows for credit sales
 *   - Direct amount entry fallback for simple credit sales or payments
 *   - Dark high-contrast styling matching design tokens
 */
?>

<!-- ===== Breadcrumb & Header ===== -->
<div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
                <?= e(t('txn_new_title')) ?>
            </h1>
            <p class="text-muted text-sm" style="margin-top: .25rem;">
                <?= e(t('dashboard_subtitle')) ?>
            </p>
        </div>
        <a href="index.php?page=transactions" class="btn btn-ghost">
            &larr; <?= e(t('btn_back')) ?>
        </a>
    </div>
</div>

<!-- ===== Transaction Form Card ===== -->
<div class="card" style="max-width: 760px; margin: 0 auto; padding: 2rem;">
    <form method="POST" action="index.php?page=transactions&amp;action=create" novalidate>
        <!-- CSRF Token Hidden Input -->
        <?= csrf_field() ?>

        <!-- Customer Selection -->
        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label" for="cust_id" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                <?= e(t('label_name')) ?> / <?= e(t('nav_customers')) ?> <span style="color: var(--accent-danger, #ef4444); font-weight: bold;">*</span>
            </label>
            <select name="cust_id" id="cust_id" required class="form-control" style="width: 100%; padding: 0.75rem 1rem; border-radius: 6px; background-color: var(--bg-base, #0b0f19); color: var(--text-primary, #f8fafc); border: 1px solid var(--border-color, #2a3754);">
                <option value=""><?= e(t('txn_select_customer')) ?></option>
                <?php foreach ($customers as $c): ?>
                    <?php
                        $selected = ($c['cust_id'] == $preselectCustId) ? 'selected' : '';
                        $due = (float)$c['balance'];
                        $limit = (float)$c['credit_limit'];
                    ?>
                    <option value="<?= (int)$c['cust_id'] ?>" <?= $selected ?>>
                        <?= e($c['name']) ?> (<?= e($c['phone']) ?>) — <?= e(t('label_balance')) ?>: <?= e(format_money($due)) ?> | <?= e(t('label_credit_limit')) ?>: <?= e(format_money($limit)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Transaction Type (Credit Sale vs Payment) -->
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                <?= e(t('label_transaction_type')) ?> <span style="color: var(--accent-danger, #ef4444); font-weight: bold;">*</span>
            </label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.85rem 1rem; border: 1px solid var(--border-color, #2a3754); border-radius: 6px; background-color: var(--bg-base, #0b0f19); cursor: pointer;">
                    <input type="radio" name="type" value="credit" checked required>
                    <span style="font-weight: 600; color: #f87171;">
                        <?= e(t('btn_record_credit')) ?>
                    </span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.85rem 1rem; border: 1px solid var(--border-color, #2a3754); border-radius: 6px; background-color: var(--bg-base, #0b0f19); cursor: pointer;">
                    <input type="radio" name="type" value="payment" required>
                    <span style="font-weight: 600; color: #34d399;">
                        <?= e(t('btn_record_payment')) ?>
                    </span>
                </label>
            </div>
        </div>

        <!-- ===== Fixed 5 Server-Rendered Product Rows (for Credit Sales) ===== -->
        <div style="background-color: var(--bg-base, #0b0f19); border: 1px solid var(--border-color, #2a3754); border-radius: 8px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
            <div style="margin-bottom: 1rem;">
                <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0; color: var(--text-primary, #f8fafc);">
                    🛒 <?= e(t('txn_items_heading')) ?>
                </h3>
                <p class="text-muted text-sm" style="margin-top: 0.25rem;">
                    <?= e(t('txn_items_subheading')) ?>
                </p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 0.75rem; align-items: center;">
                        <div>
                            <select name="items[<?= $i ?>][prod_id]" class="form-control" style="width: 100%; padding: 0.65rem 0.85rem; border-radius: 6px; background-color: #151d2e; color: var(--text-primary, #f8fafc); border: 1px solid var(--border-color, #2a3754); font-size: 0.9rem;">
                                <option value=""><?= ($i + 1) ?>. <?= e(t('txn_select_product')) ?></option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?= (int)$p['prod_id'] ?>">
                                        <?= e($p['name']) ?> (Stock: <?= (int)$p['stock'] ?>) &mdash; <?= e(format_money((float)$p['retail_price'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <input type="number"
                                   name="items[<?= $i ?>][qty]"
                                   min="0.5"
                                   step="0.5"
                                   placeholder="Qty"
                                   class="form-control"
                                   style="width: 100%; padding: 0.65rem 0.85rem; border-radius: 6px; background-color: #151d2e; color: var(--text-primary, #f8fafc); border: 1px solid var(--border-color, #2a3754); font-family: ui-monospace, SFMono-Regular, monospace; font-size: 0.95rem; font-weight: 600;">
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Direct Amount Entry (Fallback or for Payment) -->
        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label" for="amount" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                <?= e(t('label_amount')) ?> / <?= e(t('txn_direct_amount')) ?>
            </label>
            <div style="position: relative;">
                <input type="number"
                       name="amount"
                       id="amount"
                       step="0.01"
                       min="0"
                       placeholder="0.00"
                       class="form-control"
                       style="width: 100%; padding: 0.75rem 1rem; border-radius: 6px; background-color: var(--bg-base, #0b0f19); color: var(--text-primary, #f8fafc); border: 1px solid var(--border-color, #2a3754); font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 1.1rem; font-weight: 600;">
            </div>
            <p class="text-muted text-sm" style="margin-top: 0.35rem;">
                Note: When product rows above are chosen, the total amount is automatically computed from the selected products and their retail prices.
            </p>
        </div>

        <!-- Note / Description -->
        <div class="form-group" style="margin-bottom: 1.75rem;">
            <label class="form-label" for="note" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                <?= e(t('label_note')) ?>
            </label>
            <textarea name="note"
                      id="note"
                      rows="2"
                      placeholder="<?= e(t('label_note')) ?>"
                      class="form-control"
                      style="width: 100%; padding: 0.75rem 1rem; border-radius: 6px; background-color: var(--bg-base, #0b0f19); color: var(--text-primary, #f8fafc); border: 1px solid var(--border-color, #2a3754);"></textarea>
        </div>

        <!-- Form Actions -->
        <div class="d-flex justify-between align-center" style="margin-top: 1.5rem;">
            <a href="index.php?page=transactions" class="btn btn-secondary">
                <?= e(t('btn_cancel')) ?>
            </a>
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.75rem;">
                <?= e(t('btn_save')) ?>
            </button>
        </div>
    </form>
</div>
