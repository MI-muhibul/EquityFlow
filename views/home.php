<?php
declare(strict_types=1);

/**
 * views/home.php
 * -----------------------------------------------------------------------------
 * Public Landing Page View.
 * - Inviting hero presentation for Bangladeshi retail shop owners
 * - Quick calls-to-action: Sign In, Sign Up, and Continue with Google
 * - Direct language switcher toggle
 * - Feature overview of the digital Khata ledger
 * - Zero JavaScript, 100% semantic HTML5 & responsive CSS3
 * -----------------------------------------------------------------------------
 */

$currentLang = get_current_lang();
$toggleLang  = ($currentLang === 'bn') ? 'en' : 'bn';
?>

<!-- ===== Hero Showcase Section ===== -->
<section class="card" style="padding: 3rem 1.5rem; text-align: center; margin-bottom: 2rem; background: linear-gradient(180deg, var(--bg-surface) 0%, rgba(19, 28, 46, 0.7) 100%);">
    <div style="max-width: 760px; margin: 0 auto;">

        <!-- Brand Identity Badge -->
        <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0.85rem; background: var(--clr-green-bg); border: 1px solid var(--clr-green-border); border-radius: var(--r-pill); margin-bottom: 1.5rem;">
            <span style="color: var(--brand); font-weight: 700;"><?= e(t('currency_symbol')) ?></span>
            <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-high);"><?= e(t('app_tagline')) ?></span>
        </div>

        <!-- Main Headline -->
        <h1 style="font-size: 2.25rem; font-weight: 800; line-height: 1.3; margin-bottom: 1rem; color: var(--text-high);">
            <?= e(t('home_hero_title')) ?>
        </h1>

        <!-- Subtitle & Value Proposition -->
        <p style="font-size: 1.1rem; color: var(--text-mid); line-height: 1.6; margin-bottom: 2rem;">
            <?= e(t('home_hero_desc')) ?>
        </p>

        <!-- Primary CTA Buttons -->
        <div style="display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
            <a href="index.php?page=signup" class="btn btn-primary btn-lg" style="min-width: 170px;">
                <?= e(t('sign_up')) ?> &rarr;
            </a>
            <a href="index.php?page=login" class="btn btn-secondary btn-lg" style="min-width: 170px;">
                <?= e(t('sign_in')) ?>
            </a>
        </div>

        <!-- Continue with Google Button -->
        <div style="max-width: 320px; margin: 0 auto 1.5rem;">
            <a href="index.php?page=google-login" class="btn btn-google btn-block" role="button">
                <svg class="google-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span><?= e(t('continue_with_google')) ?></span>
            </a>
        </div>

        <!-- Language Switcher Notice -->
        <div class="text-muted text-sm">
            <span>🌐 ভাষা পরিবর্তন / Switch Language: </span>
            <a href="index.php?lang=<?= e($toggleLang) ?>" style="color: var(--clr-blue); text-decoration: underline; font-weight: 600;">
                <?= e(t('toggle_language')) ?>
            </a>
        </div>

    </div>
</section>

<!-- ===== Core Feature Grid ===== -->
<section aria-label="Core Features" style="margin-bottom: 2.5rem;">
    <h2 style="font-size: 1.35rem; font-weight: 700; margin-bottom: 1.25rem; text-align: center; color: var(--text-high);">
        <?= e(t('app_name')) ?> &mdash; সুবিশাল সুবিধাসমূহ / Key Capabilities
    </h2>

    <div class="grid-stats">
        <!-- Feature 1: Digital Khata -->
        <div class="stat-card">
            <span style="font-size: 1.75rem; margin-bottom: 0.5rem;" aria-hidden="true">📖</span>
            <strong style="font-size: 1.05rem; margin-bottom: 0.35rem; color: var(--text-high);">
                <?= e(t('nav_customers')) ?> ও বাকির খাতা
            </strong>
            <p class="text-muted text-sm">
                গ্রাহক প্রতি বাকির হিসাব, বাকি সীমা (Credit Limit) এবং হালনাগাদ ব্যালেন্স সংরক্ষণ।
            </p>
        </div>

        <!-- Feature 2: Transactions -->
        <div class="stat-card">
            <span style="font-size: 1.75rem; margin-bottom: 0.5rem;" aria-hidden="true">💳</span>
            <strong style="font-size: 1.05rem; margin-bottom: 0.35rem; color: var(--text-high);">
                <?= e(t('nav_transactions')) ?> ও পরিশোধ
            </strong>
            <p class="text-muted text-sm">
                বাকিতে বিক্রয় ও কিস্তিতে টাকা আদায়ের স্বয়ংক্রিয় লেজার জমার হিসাব।
            </p>
        </div>

        <!-- Feature 3: Inventory -->
        <div class="stat-card">
            <span style="font-size: 1.75rem; margin-bottom: 0.5rem;" aria-hidden="true">📦</span>
            <strong style="font-size: 1.05rem; margin-bottom: 0.35rem; color: var(--text-high);">
                <?= e(t('nav_inventory')) ?> ও স্টক সতর্কতা
            </strong>
            <p class="text-muted text-sm">
                ক্রয় ও বিক্রয় মূল্য সহ পণ্যের মজুত ও লো-স্টক অ্যালার্ট পর্যবেক্ষণ।
            </p>
        </div>

        <!-- Feature 4: Privacy & Zero JS -->
        <div class="stat-card">
            <span style="font-size: 1.75rem; margin-bottom: 0.5rem;" aria-hidden="true">🛡️</span>
            <strong style="font-size: 1.05rem; margin-bottom: 0.35rem; color: var(--text-high);">
                নিরাপদ ও নির্ভরযোগ্য
            </strong>
            <p class="text-muted text-sm">
                গুগল সাইন-ইন, ডেডিকেটেড ডাটাবেস আইসোলেশন ও কোনো ধরনের ট্র্যাকিং স্ক্রিপ্ট ব্যতিরেকে নির্ভরযোগ্য গতি।
            </p>
        </div>
    </div>
</section>
