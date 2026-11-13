<?php
declare(strict_types=1);

/**
 * views/customers/edit.php
 * -----------------------------------------------------------------------------
 * "Edit Customer" form — bilingual, dark theme, zero JavaScript.
 *
 * Variables injected by CustomerController::edit():
 *   $customer  array  — current DB row (pre-fills form)
 *   $old       array  — POST values when re-displaying on error (overrides $customer)
 *   $errors    array  — field-keyed error messages (e.g. $errors['phone'])
 *
 * Validation strategy:
 *   • HTML5: required, pattern, minlength, min, title tooltip
 *   • PHP server-side: validates format, per-user uniqueness (excludes own record)
 *   • CSRF: hidden csrf_field()
 *   • Field errors shown inline in red below each input
 * -----------------------------------------------------------------------------
 */

// If a POST was rejected and $old was set from $_POST, use it; else use the DB row
$v = array_merge($customer, $old ?? []);
$cid = (int)$customer['cust_id'];
?>

<!-- ===== Page Header ========================================================= -->
<div class="d-flex flex-wrap justify-between align-center gap-1" style="margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
            ✏️ <?= e(t('btn_edit')) ?> — <?= e($customer['name']) ?>
        </h1>
        <p class="text-muted text-sm" style="margin-top: .25rem;">
            <?= e(t('customer_title')) ?>
        </p>
    </div>
    <a href="index.php?page=customers&amp;action=profile&amp;id=<?= $cid ?>" class="btn btn-ghost btn-sm">
        &larr; <?= e(t('btn_back')) ?>
    </a>
</div>

<!-- ===== Edit Customer Form ================================================== -->
<section class="card" style="max-width: 680px;">
    <form method="POST"
          action="index.php?page=customers&amp;action=edit&amp;id=<?= $cid ?>"
          novalidate>

        <!-- CSRF hidden token -->
        <?= csrf_field() ?>

        <!-- ---- Row 1: Name + Phone ----------------------------------------- -->
        <div class="form-row">

            <!-- Full Name -->
            <div class="form-group">
                <label for="name" class="form-label">
                    <?= e(t('label_name')) ?> <span class="req" aria-hidden="true">*</span>
                </label>
                <input type="text"
                       id="name"
                       name="name"
                       class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>"
                       value="<?= e($v['name'] ?? '') ?>"
                       maxlength="100"
                       required
                       autocomplete="off"
                       title="<?= e(t('label_name')) ?>"
                       placeholder="<?= e(t('label_name')) ?>"
                       aria-describedby="name_err">
                <?php if (isset($errors['name'])): ?>
                    <div id="name_err" class="form-error" role="alert">
                        <?= e($errors['name']) ?>
                    </div>
                <?php else: ?>
                    <div class="form-hint"><?= e(t('label_name')) ?> (required)</div>
                <?php endif; ?>
            </div>

            <!-- Mobile Phone -->
            <div class="form-group">
                <label for="phone" class="form-label">
                    <?= e(t('label_phone')) ?> <span class="req" aria-hidden="true">*</span>
                </label>
                <input type="tel"
                       id="phone"
                       name="phone"
                       class="form-control font-mono<?= isset($errors['phone']) ? ' is-invalid' : '' ?>"
                       value="<?= e($v['phone'] ?? '') ?>"
                       maxlength="11"
                       minlength="11"
                       pattern="01[3-9][0-9]{8}"
                       required
                       autocomplete="tel"
                       title="<?= e(t('error_invalid_phone')) ?>"
                       placeholder="01XXXXXXXXX"
                       aria-describedby="phone_err">
                <?php if (isset($errors['phone'])): ?>
                    <div id="phone_err" class="form-error" role="alert">
                        <?= e($errors['phone']) ?>
                    </div>
                <?php else: ?>
                    <div class="form-hint"><?= e(t('error_invalid_phone')) ?></div>
                <?php endif; ?>
            </div>

        </div><!-- /.form-row -->

        <!-- ---- Address (optional, full width) ------------------------------ -->
        <div class="form-group">
            <label for="address" class="form-label">
                <?= e(t('label_address')) ?>
            </label>
            <textarea id="address"
                      name="address"
                      class="form-control"
                      rows="2"
                      maxlength="500"
                      placeholder="<?= e(t('label_address')) ?>"
            ><?= e($v['address'] ?? '') ?></textarea>
        </div>

        <!-- ---- Credit Limit (>= 0) ----------------------------------------- -->
        <div class="form-group" style="max-width: 280px;">
            <label for="credit_limit" class="form-label">
                <?= e(t('label_credit_limit')) ?>
            </label>
            <input type="number"
                   id="credit_limit"
                   name="credit_limit"
                   class="form-control font-mono<?= isset($errors['credit_limit']) ? ' is-invalid' : '' ?>"
                   value="<?= e(number_format((float)($v['credit_limit'] ?? 0), 2, '.', '')) ?>"
                   min="0"
                   step="0.01"
                   max="9999999.99"
                   title="<?= e(t('error_credit_limit_invalid')) ?>"
                   placeholder="0.00">
            <?php if (isset($errors['credit_limit'])): ?>
                <div class="form-error" role="alert">
                    <?= e($errors['credit_limit']) ?>
                </div>
            <?php else: ?>
                <div class="form-hint"><?= e(t('label_credit_limit')) ?> (<?= e(t('currency_symbol')) ?>) — 0 = no limit</div>
            <?php endif; ?>
        </div>

        <!-- ---- Note: balance not editable here — changed via transactions -- -->
        <div class="alert alert-info" role="note" style="margin-top: 1rem; padding: .75rem 1rem; background: rgba(59,130,246,.08); border-left: 3px solid #3b82f6; border-radius: 6px;">
            <span class="text-sm text-muted">
                ℹ️ <?= e(t('label_balance')) ?>: <strong><?= e(format_money((float)$customer['balance'])) ?></strong>
                — <?= e(t('label_balance')) ?> is updated automatically via transactions.
            </span>
        </div>

        <!-- ---- Form Actions ------------------------------------------------ -->
        <div class="d-flex justify-between align-center" style="margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color);">
            <a href="index.php?page=customers&amp;action=profile&amp;id=<?= $cid ?>" class="btn btn-ghost">
                &larr; <?= e(t('btn_cancel')) ?>
            </a>
            <button type="submit" class="btn btn-primary">
                <?= e(t('btn_update')) ?> →
            </button>
        </div>

    </form>
</section>
