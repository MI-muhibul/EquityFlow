<?php
declare(strict_types=1);

/**
 * views/auth/change_password.php
 * -----------------------------------------------------------------------------
 * Shop Owner Change / Set Password View.
 * - Protected by require_login()
 * - CSRF defense token
 * - Zero JavaScript, high-contrast dark theme
 * - If user account is Google-only (no existing password), prompts to "Set a Password"
 *   without asking for an old/current password.
 * - Otherwise, requires verification of current password before rehashing new password.
 * -----------------------------------------------------------------------------
 */

$hasPassword = $hasPassword ?? (isset($_SESSION['user_id']) ? User::hasPassword((int)$_SESSION['user_id']) : true);
$formTitle   = $hasPassword ? t('btn_change_password') : t('set_password');
$formDesc    = $hasPassword
    ? (t('min_8_chars_desc') ?? 'Password must be at least 8 characters long.')
    : t('set_password_desc');
?>

<div class="card" style="max-width: 520px; margin: 1.5rem auto;">
    <div class="card-head">
        <div>
            <h1 class="card-title"><?= e($formTitle) ?></h1>
            <div class="card-subtitle text-muted text-sm" style="margin-top: 0.25rem;">
                <?= e($formDesc) ?>
            </div>
        </div>
    </div>

    <!-- Error Alert Banner -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-error" role="alert" style="margin-bottom: 1.25rem;">
            <span class="alert-icon" aria-hidden="true">&#x2715;</span>
            <span class="alert-text"><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Password Form -->
    <form method="POST" action="index.php?page=auth&amp;action=change_password" novalidate>
        <!-- CSRF Defense Hidden Token -->
        <?= csrf_field() ?>

        <?php if ($hasPassword): ?>
            <!-- Current Password (only asked if user already has a password) -->
            <div class="form-group">
                <label for="current_password" class="form-label">
                    <?= e(t('label_current_password')) ?> <span class="req">*</span>
                </label>
                <input type="password"
                       id="current_password"
                       name="current_password"
                       class="form-control"
                       required
                       autocomplete="current-password">
            </div>
        <?php else: ?>
            <div class="alert alert-info" role="status" style="margin-bottom: 1.25rem;">
                <span class="alert-icon" aria-hidden="true">&#x2139;</span>
                <span class="alert-text"><?= e(t('set_password_desc')) ?></span>
            </div>
        <?php endif; ?>

        <!-- New Password -->
        <div class="form-group">
            <label for="new_password" class="form-label">
                <?= e(t('label_new_password')) ?> <span class="req">*</span>
                <span class="text-muted text-sm">(Min 8 chars)</span>
            </label>
            <input type="password"
                   id="new_password"
                   name="new_password"
                   class="form-control"
                   minlength="8"
                   required
                   autocomplete="new-password">
        </div>

        <!-- Confirm New Password -->
        <div class="form-group">
            <label for="confirm_password" class="form-label">
                <?= e(t('label_confirm_password')) ?> <span class="req">*</span>
            </label>
            <input type="password"
                   id="confirm_password"
                   name="confirm_password"
                   class="form-control"
                   minlength="8"
                   required
                   autocomplete="new-password">
        </div>

        <!-- Form Actions -->
        <div class="card-foot" style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <a href="index.php?page=dashboard" class="btn btn-secondary">
                <?= e(t('btn_cancel')) ?>
            </a>
            <button type="submit" class="btn btn-primary">
                <?= e($hasPassword ? t('btn_update') : t('btn_set_password')) ?>
            </button>
        </div>
    </form>
</div>
