<?php
declare(strict_types=1);

/**
 * views/customers/add.php
 * -----------------------------------------------------------------------------
 * "Register New Customer" form — bilingual, dark theme, zero JavaScript.
 *
 * Variables injected by CustomerController::create():
 *   $old     array  — previously typed values (repopulated on validation error)
 *   $errors  array  — field-keyed error messages (e.g. $errors['phone'])
 *
 * Validation strategy:
 *   • HTML5: required, pattern, minlength, min, title tooltip
 *   • PHP server-side: also validates format, uniqueness (per user), range
 *   • CSRF: hidden csrf_field() on the form
 *   • Field errors shown inline in red below each input
 * -----------------------------------------------------------------------------
 */
?>

<!-- ===== Page Header ========================================================= -->
<div class="d-flex flex-wrap justify-between align-center gap-1" style="margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
            + <?= e(t('customer_add_title')) ?>
        </h1>
        <p class="text-muted text-sm" style="margin-top: .25rem;">
            <?= e(t('customer_title')) ?>
        </p>
    </div>
    <a href="index.php?page=customers" class="btn btn-ghost btn-sm">
        &larr; <?= e(t('btn_back')) ?>
    </a>
</div>

<!-- ===== Add Customer Form =================================================== -->
<section class="card" style="max-width: 680px;">
    <form method="POST"
          action="index.php?page=customers&amp;action=create"
          novalidate>

        <!-- CSRF hidden token — protects against Cross-Site Request Forgery -->
        <?= csrf_field() ?>

        <!-- ---- Row 1: Name + Phone ----------------------------------------- -->
        <div class="form-row">

            <!-- Full Name (required) -->
            <div class="form-group">
                <label for="name" class="form-label">
                    <?= e(t('label_name')) ?> <span class="req" aria-hidden="true">*</span>
                </label>
                <input type="text"
                       id="name"
                       name="name"
                       class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>"
                       value="<?= e($old['name'] ?? '') ?>"
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
                    <div class="form-hint"><?= e(t('label_name')) ?> (<?= e(t('error_required_field')) ?>)</div>
                <?php endif; ?>
            </div>

            <!-- Mobile Phone (required, pattern 01[3-9]\d{8}) -->
            <div class="form-group">
                <label for="phone" class="form-label">
                    <?= e(t('label_phone')) ?> <span class="req" aria-hidden="true">*</span>
                </label>
                <input type="tel"
                       id="phone"
                       name="phone"
                       class="form-control font-mono<?= isset($errors['phone']) ? ' is-invalid' : '' ?>"
                       value="<?= e($old['phone'] ?? '') ?>"
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
                      aria-label="<?= e(t('label_address')) ?>"
            ><?= e($old['address'] ?? '') ?></textarea>
            <div class="form-hint"><?= e(t('label_address')) ?> (optional)</div>
        </div>

        <!-- ---- Credit Limit (optional, >= 0) ------------------------------- -->
        <div class="form-group" style="max-width: 280px;">
            <label for="credit_limit" class="form-label">
                <?= e(t('label_credit_limit')) ?>
            </label>
            <input type="number"
                   id="credit_limit"
                   name="credit_limit"
                   class="form-control font-mono<?= isset($errors['credit_limit']) ? ' is-invalid' : '' ?>"
                   value="<?= e($old['credit_limit'] ?? '0') ?>"
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

        <!-- ---- Form Actions ------------------------------------------------ -->
        <div class="d-flex justify-between align-center" style="margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color);">
            <a href="index.php?page=customers" class="btn btn-ghost">
                &larr; <?= e(t('btn_cancel')) ?>
            </a>
            <button type="submit" class="btn btn-primary">
                <?= e(t('btn_save')) ?> →
            </button>
        </div>

    </form>
</section>
