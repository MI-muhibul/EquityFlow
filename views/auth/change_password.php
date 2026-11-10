<?php
declare(strict_types=1);

/**
 * views/auth/change_password.php
 * Change password form view.
 * Protected by require_login(), uses CSRF defense and master layout.
 */
?>

<div class="card" style="max-width: 520px; margin: 1.5rem auto;">
    <div class="card-head">
        <div>
            <h1 class="card-title"><?= e(t('btn_change_password')) ?></h1>
            <div class="card-subtitle">
                নিরাপত্তার স্বার্থে নতুন পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।
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

    <!-- Change Password Form -->
    <form method="POST" action="index.php?page=auth&amp;action=change_password" novalidate>
        <!-- CSRF Defense Hidden Token -->
        <?= csrf_field() ?>

        <!-- Current Password -->
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

        <!-- New Password -->
        <div class="form-group">
            <label for="new_password" class="form-label">
                <?= e(t('label_new_password')) ?> <span class="req">*</span>
            </label>
            <input type="password"
                   id="new_password"
                   name="new_password"
                   class="form-control"
                   minlength="8"
                   required
                   autocomplete="new-password">
            <div class="form-hint">কমপক্ষে ৮টি অক্ষর (অক্ষর, সংখ্যা ও প্রতীকের মিশ্রণ শ্রেয়)।</div>
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
        <div class="card-foot">
            <a href="index.php?page=dashboard" class="btn btn-secondary">
                <?= e(t('btn_cancel')) ?>
            </a>
            <button type="submit" class="btn btn-primary">
                <?= e(t('btn_update')) ?>
            </button>
        </div>
    </form>
</div>
