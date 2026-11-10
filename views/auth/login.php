<?php
declare(strict_types=1);

/**
 * views/auth/login.php
 * Shop owner login template.
 * High-contrast dark theme, zero JavaScript, semantic HTML5 form with CSRF defense.
 */

// Load core language & formatting dependencies
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/lang.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/format.php';

$currentLang = get_current_lang();
$toggleLang  = ($currentLang === 'bn') ? 'en' : 'bn';
?>
<!DOCTYPE html>
<html lang="<?= e($currentLang) ?>" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('login_title')) ?> &mdash; <?= e(t('app_name')) ?></title>

    <!-- Bangla webfonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap">

    <!-- Core dark high-contrast stylesheet (strictly NO JavaScript) -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: var(--bg-page);">

    <div class="login-wrap">
        <main class="login-card" role="main">

            <!-- Language Switcher & Brand Header -->
            <div class="d-flex justify-between align-center" style="margin-bottom: 1.25rem;">
                <span class="text-muted text-sm font-mono"><?= e(t('app_name')) ?></span>
                <a href="index.php?page=auth&amp;lang=<?= e($toggleLang) ?>" class="lang-btn" aria-label="Switch language">
                    &#127760; <?= e(t('toggle_language')) ?>
                </a>
            </div>

            <!-- Shop Identity Logo -->
            <div class="login-logo">
                <div class="brand-mark" aria-hidden="true"><?= e(t('currency_symbol')) ?></div>
                <h1 class="login-title"><?= e(t('login_title')) ?></h1>
                <p class="login-subtitle"><?= e(t('login_subtitle')) ?></p>
            </div>

            <!-- Flash Notifications Area -->
            <?php flash_show(); ?>

            <!-- Generic Login Error Message (Never reveals which field was wrong) -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-error" role="alert" style="margin-bottom: 1.25rem;">
                    <span class="alert-icon" aria-hidden="true">&#x2715;</span>
                    <span class="alert-text"><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="index.php?page=auth&amp;action=login" novalidate>
                <!-- CSRF Protection Hidden Token -->
                <?= csrf_field() ?>

                <!-- Username Input -->
                <div class="form-group">
                    <label for="username" class="form-label">
                        <?= e(t('label_username')) ?> <span class="req">*</span>
                    </label>
                    <input type="text"
                           id="username"
                           name="username"
                           class="form-control"
                           required
                           autofocus
                           autocomplete="username"
                           value="<?= e($_POST['username'] ?? '') ?>">
                </div>

                <!-- Password Input -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <?= e(t('label_password')) ?> <span class="req">*</span>
                    </label>
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control"
                           required
                           autocomplete="current-password">
                </div>

                <!-- Optional Security PIN Input -->
                <div class="form-group">
                    <label for="pin" class="form-label">
                        <?= e(t('label_security_pin')) ?>
                        <span class="text-muted text-sm">(ঐচ্ছিক / Optional)</span>
                    </label>
                    <input type="password"
                           id="pin"
                           name="pin"
                           class="form-control"
                           maxlength="6"
                           placeholder="****"
                           autocomplete="off">
                    <div class="form-hint">পিন কোড সেট করা থাকলে লিখুন, অন্যথায় খালি রাখুন।</div>
                </div>

                <!-- Submit Button -->
                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <?= e(t('btn_login')) ?> &rarr;
                    </button>
                </div>
            </form>

            <!-- Card Footer -->
            <div class="divider"></div>
            <div class="text-muted text-sm" style="text-align: center;">
                <?= e(t('app_tagline')) ?>
            </div>

        </main>
    </div>

</body>
</html>
