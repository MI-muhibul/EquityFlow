<?php
declare(strict_types=1);

/**
 * views/auth/profile.php
 * -----------------------------------------------------------------------------
 * Shop Owner Profile View.
 * Displays:
 *  - Name, Email, Shop Name
 *  - Avatar image if logged in via Google OAuth, or fallback stylized monogram
 *  - Sign-in method badge (local, google, both)
 *  - Form to update Name and Shop Name (with CSRF token)
 *  - Direct link to Change / Set Password
 * -----------------------------------------------------------------------------
 */

$provider = $user['auth_provider'] ?? 'local';
$badgeClass = 'badge-neutral';
$badgeLabel = t('auth_local');

if ($provider === 'google') {
    $badgeClass = 'badge-blue';
    $badgeLabel = t('auth_google');
} elseif ($provider === 'both') {
    $badgeClass = 'badge-green';
    $badgeLabel = t('auth_both');
}

$initial = mb_substr($user['name'] ?? 'U', 0, 1, 'UTF-8');
?>

<div class="card" style="max-width: 680px; margin: 1.5rem auto; padding: 2rem;">

    <!-- Profile Header Banner -->
    <div style="display: flex; align-items: center; gap: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-dim); margin-bottom: 1.75rem;">
        <!-- Avatar Display -->
        <?php if (!empty($user['avatar_url'])): ?>
            <img src="<?= e($user['avatar_url']) ?>"
                 alt="<?= e($user['name']) ?>"
                 style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border-mid); box-shadow: var(--shadow-card);">
        <?php else: ?>
            <div style="width: 72px; height: 72px; border-radius: 50%; background: var(--bg-hover); color: var(--brand); display: flex; align-items: center; justify-content: center; font-size: 1.75rem; font-weight: 700; border: 2px solid var(--border-dim);">
                <?= e($initial) ?>
            </div>
        <?php endif; ?>

        <!-- Name & Shop Identity -->
        <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <h1 style="font-size: 1.45rem; font-weight: 700; margin: 0; color: var(--text-high);">
                    <?= e($user['name']) ?>
                </h1>
                <span class="badge <?= e($badgeClass) ?>">
                    <?= e($badgeLabel) ?>
                </span>
            </div>
            <div style="color: var(--text-mid); font-size: 0.95rem; margin-top: 0.25rem;">
                <?= e($user['shop_name']) ?>
            </div>
            <div class="text-muted text-sm font-mono" style="margin-top: 0.15rem;">
                <?= e($user['email']) ?>
            </div>
        </div>
    </div>

    <!-- Edit Profile Form -->
    <form method="POST" action="index.php?page=profile" novalidate style="margin-bottom: 2rem;">
        <!-- CSRF Defense Hidden Token -->
        <?= csrf_field() ?>

        <h2 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 1.25rem; color: var(--text-high);">
            <?= e(t('edit_profile')) ?>
        </h2>

        <!-- Full Name -->
        <div class="form-group">
            <label for="name" class="form-label">
                <?= e(t('name')) ?> <span class="req">*</span>
            </label>
            <input type="text"
                   id="name"
                   name="name"
                   class="form-control"
                   required
                   maxlength="100"
                   value="<?= e($user['name']) ?>">
        </div>

        <!-- Shop Name -->
        <div class="form-group">
            <label for="shop_name" class="form-label">
                <?= e(t('label_shop_name')) ?> <span class="req">*</span>
            </label>
            <input type="text"
                   id="shop_name"
                   name="shop_name"
                   class="form-control"
                   required
                   maxlength="150"
                   value="<?= e($user['shop_name']) ?>">
        </div>

        <!-- Email Address (Read-only Account Identifier) -->
        <div class="form-group">
            <label for="email_static" class="form-label">
                <?= e(t('email')) ?>
            </label>
            <input type="email"
                   id="email_static"
                   class="form-control"
                   disabled
                   value="<?= e($user['email']) ?>"
                   style="background: var(--bg-surface); opacity: 0.75; cursor: not-allowed;">
            <div class="form-help">
                ইমেইল ঠিকানা একাউন্টের প্রধান পরিচিতি এবং এটি পরিবর্তনযোগ্য নয়। (Account identifier)
            </div>
        </div>

        <!-- Submit Update -->
        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary">
                <?= e(t('btn_update')) ?>
            </button>
        </div>
    </form>

    <!-- Account Security Section -->
    <div style="border-top: 1px solid var(--border-dim); padding-top: 1.75rem;">
        <h2 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-high);">
            <?= e(t('security_title') ?? 'Security & Authentication') ?>
        </h2>
        <p class="text-muted text-sm" style="margin-bottom: 1.25rem;">
            <?= ($hasPassword ?? true)
                ? 'আপনার একাউন্টের পাসওয়ার্ড পরিবর্তন করতে পারেন।'
                : 'আপনার একাউন্টটি গুগল দিয়ে খোলা। চাইলে ইমেইল ও পাসওয়ার্ড দিয়ে লগইন করার জন্য একটি পাসওয়ার্ড সেট করতে পারেন।' ?>
        </p>

        <a href="index.php?page=auth&amp;action=change_password" class="btn btn-secondary">
            🔑 <?= e(($hasPassword ?? true) ? t('btn_change_password') : t('btn_set_password')) ?>
        </a>
    </div>

</div>
