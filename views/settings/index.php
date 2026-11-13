<?php
declare(strict_types=1);

/**
 * views/settings/index.php
 * -----------------------------------------------------------------------------
 * Shop Settings and Database Backup Management View:
 *   - Shop Name
 *   - Default Credit Limit
 *   - Default Minimum Stock Alert
 *   - Default Language selection ('bn' / 'en')
 *   - Shop Phone & Address
 *   - Full PDO-based SQL Dump Creation Button
 *   - List of available backups with size, date, and secure download link
 * -----------------------------------------------------------------------------
 * Rules:
 *   - Strictly zero JavaScript
 *   - CSRF token on every POST form
 *   - htmlspecialchars on all output via e()
 *   - All text from lang packs via t()
 * -----------------------------------------------------------------------------
 */

$shopName         = $settings['shop_name'] ?? 'EquityFlow Store';
$defaultCredit    = $settings['default_credit_limit'] ?? '10000.00';
$defaultMinStock  = $settings['default_min_stock'] ?? '5';
$defaultLanguage  = $settings['default_language'] ?? 'bn';
$shopPhone        = $settings['shop_phone'] ?? '';
$shopAddress      = $settings['shop_address'] ?? '';
?>

<!-- ===== 1. Page Header ===== -->
<div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
        ⚙️ <?= e(t('settings_title')) ?>
    </h1>
    <p class="text-muted text-sm" style="margin-top: .25rem;">
        <?= e(t('app_name')) ?> &mdash; <?= e(t('app_tagline')) ?>
    </p>
</div>

<!-- ===== 2. Settings Form ===== -->
<div class="card" style="padding: 1.5rem; margin-bottom: 2rem;">
    <h2 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-dim); padding-bottom: .75rem;">
        🏪 <?= e(t('settings_title')) ?>
    </h2>

    <form method="POST" action="index.php?page=settings&amp;action=update" class="d-flex flex-column gap-1">
        <!-- CSRF Token -->
        <?= csrf_field() ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
            <!-- Shop Name -->
            <div class="filter-group">
                <label for="shop_name"><?= e(t('settings_shop_name')) ?> *</label>
                <input type="text" id="shop_name" name="shop_name" value="<?= e($shopName) ?>" required maxlength="150">
            </div>

            <!-- Default Language -->
            <div class="filter-group">
                <label for="default_language"><?= e(t('settings_language')) ?></label>
                <select id="default_language" name="default_language">
                    <option value="bn" <?= ($defaultLanguage === 'bn') ? 'selected' : '' ?>>বাংলা (Bangla)</option>
                    <option value="en" <?= ($defaultLanguage === 'en') ? 'selected' : '' ?>>English</option>
                </select>
            </div>

            <!-- Default Credit Limit -->
            <div class="filter-group">
                <label for="default_credit_limit"><?= e(t('settings_default_credit_limit')) ?></label>
                <input type="number" step="0.01" min="0" id="default_credit_limit" name="default_credit_limit" value="<?= e($defaultCredit) ?>" required>
            </div>

            <!-- Default Minimum Stock -->
            <div class="filter-group">
                <label for="default_min_stock"><?= e(t('settings_default_min_stock')) ?></label>
                <input type="number" step="1" min="0" id="default_min_stock" name="default_min_stock" value="<?= e($defaultMinStock) ?>" required>
            </div>

            <!-- Shop Phone -->
            <div class="filter-group">
                <label for="shop_phone"><?= e(t('settings_shop_phone')) ?></label>
                <input type="text" id="shop_phone" name="shop_phone" value="<?= e($shopPhone) ?>" maxlength="30">
            </div>

            <!-- Shop Address -->
            <div class="filter-group">
                <label for="shop_address"><?= e(t('settings_shop_address')) ?></label>
                <input type="text" id="shop_address" name="shop_address" value="<?= e($shopAddress) ?>" maxlength="255">
            </div>
        </div>

        <div style="margin-top: 1rem;">
            <button type="submit" class="btn btn-primary">
                💾 <?= e(t('btn_save')) ?>
            </button>
        </div>
    </form>
</div>

<!-- ===== 3. Database Backup & Safety Section ===== -->
<div class="card" style="padding: 1.5rem; margin-bottom: 2rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1" style="border-bottom: 1px solid var(--border-dim); padding-bottom: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0;">
                💾 <?= e(t('settings_backup_heading')) ?>
            </h2>
            <p class="text-muted text-sm" style="margin-top: .25rem;">
                <?= e(t('settings_backup_desc')) ?>
            </p>
        </div>

        <!-- Create Backup Button (POST form with CSRF) -->
        <form method="POST" action="index.php?page=settings&amp;action=backup">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary">
                + <?= e(t('settings_btn_create_backup')) ?>
            </button>
        </form>
    </div>

    <!-- Backups List -->
    <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
        📂 <?= e(t('settings_backups_list')) ?>
    </h3>

    <?php if (empty($backups)): ?>
        <div style="text-align: center; padding: 2.5rem 1rem; border: 1px dashed var(--border-dim); border-radius: var(--r-md);">
            <div style="font-size: 2.5rem; margin-bottom: .5rem;">📦</div>
            <p class="text-muted"><?= e(t('settings_no_backups')) ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap" style="margin-bottom: 0;">
            <table class="table" aria-label="<?= e(t('settings_backups_list')) ?>">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= e(t('settings_filename')) ?></th>
                        <th style="text-align: right;"><?= e(t('settings_filesize')) ?></th>
                        <th><?= e(t('settings_created_at')) ?></th>
                        <th style="text-align: right;"><?= e(t('settings_download')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $i => $b): ?>
                        <tr>
                            <td class="text-muted text-sm"><?= $i + 1 ?></td>
                            <td>
                                <strong class="font-mono"><?= e($b['filename']) ?></strong>
                            </td>
                            <td class="font-mono td-money" style="text-align: right;">
                                <?= e($b['size_formatted']) ?>
                            </td>
                            <td class="text-muted text-sm font-mono">
                                <?= e(date('d M Y, h:i:s A', strtotime($b['created_at']))) ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="index.php?page=settings&amp;action=download&amp;file=<?= urlencode($b['filename']) ?>" class="btn btn-ghost btn-sm">
                                    ⬇️ <?= e(t('settings_download')) ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
