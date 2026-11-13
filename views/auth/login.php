<?php
declare(strict_types=1);

/**
 * views/auth/login.php
 * -----------------------------------------------------------------------------
 * Shop Owner Sign In View.
 * - Dark high-contrast auth card layout
 * - Zero JavaScript, 100% semantic HTML5 & CSS3
 * - CSRF defense token
 * - Generic translated error messaging (never discloses which field was wrong)
 * - Server-side Google OAuth 2.0 CTA button
 * - Clean mobile-responsive styling
 * -----------------------------------------------------------------------------
 */

$pageTitle  = t('sign_in');
$activePage = 'login';

require_once __DIR__ . '/../layout/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">

        <!-- Card Header & Shop Mark -->
        <div class="auth-header">
            <div class="brand-mark" aria-hidden="true"><?= e(t('currency_symbol')) ?></div>
            <h1 class="auth-title"><?= e(t('login_title')) ?></h1>
            <p class="auth-subtitle"><?= e(t('login_subtitle')) ?></p>
        </div>

        <!-- Continue with Google CTA -->
        <a href="index.php?page=google-login" class="btn btn-google btn-block" role="button">
            <svg class="google-icon" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span><?= e(t('continue_with_google')) ?></span>
        </a>

        <!-- OR Divider -->
        <div class="auth-divider" aria-hidden="true">
            <span><?= e(t('or')) ?></span>
        </div>

        <!-- Generic Login Error Message (Never reveals which field was wrong) -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert">
                <span class="alert-icon" aria-hidden="true">&#x2715;</span>
                <span class="alert-text"><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Sign In Form -->
        <form method="POST" action="index.php?page=login" novalidate>
            <!-- CSRF Defense Hidden Token -->
            <?= csrf_field() ?>

            <!-- Email Address Input -->
            <div class="form-group">
                <label for="email" class="form-label">
                    <?= e(t('email')) ?> <span class="req">*</span>
                </label>
                <input type="email"
                       id="email"
                       name="email"
                       class="form-control"
                       required
                       autofocus
                       autocomplete="email"
                       value="<?= e($email ?? '') ?>">
            </div>

            <!-- Password Input -->
            <div class="form-group">
                <label for="password" class="form-label">
                    <?= e(t('password')) ?> <span class="req">*</span>
                </label>
                <input type="password"
                       id="password"
                       name="password"
                       class="form-control"
                       required
                       autocomplete="current-password">
            </div>

            <!-- Submit Button -->
            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <?= e(t('sign_in')) ?> &rarr;
                </button>
            </div>
        </form>

        <!-- Switch to Sign Up -->
        <div class="auth-footer">
            <?= e(t('no_account')) ?>
            <a href="index.php?page=signup"><?= e(t('sign_up')) ?></a>
        </div>

    </div>
</div>

<?php
require_once __DIR__ . '/../layout/footer.php';
?>
