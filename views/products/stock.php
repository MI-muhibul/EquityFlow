<?php
declare(strict_types=1);

/**
 * views/products/stock.php
 * -----------------------------------------------------------------------------
 * Form to adjust or update product stock:
 *   - Shows current stock, min_stock, and computed status badge
 *   - Offers choice of operation:
 *       1. 'set'      : Set exact stock amount
 *       2. 'add'      : Add to stock (restock / purchase)
 *       3. 'subtract' : Deduct from stock (spoilage / return / loss)
 *   - CSRF token on POST form
 * -----------------------------------------------------------------------------
 */

$statusInfo = $product['status_info'];
$statusLabel = t($statusInfo['key']);
$badgeClass  = $statusInfo['badge_class'];
?>

<!-- ===== Breadcrumb & Header ===== -->
<div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
                <?= e(t('product_stock_title')) ?>
            </h1>
            <p class="text-muted text-sm" style="margin-top: .25rem;">
                <strong><?= e($product['name']) ?></strong> &bull; <?= e($product['category']) ?>
            </p>
        </div>
        <div class="d-flex gap-1">
            <a href="index.php?page=inventory&amp;action=edit&amp;id=<?= (int)$product['prod_id'] ?>" class="btn btn-secondary">
                ✏️ <?= e(t('btn_edit')) ?>
            </a>
            <a href="index.php?page=inventory" class="btn btn-ghost">
                &larr; <?= e(t('btn_back')) ?>
            </a>
        </div>
    </div>
</div>

<!-- ===== Current Stock Status Card ===== -->
<div class="grid-stats" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <span class="stat-label"><?= e(t('label_stock')) ?></span>
        <span class="stat-value total">
            <?= (int)$product['stock'] ?>
        </span>
    </div>
    <div class="stat-card">
        <span class="stat-label"><?= e(t('label_min_stock')) ?></span>
        <span class="stat-value due">
            <?= (int)$product['min_stock'] ?>
        </span>
    </div>
    <div class="stat-card">
        <span class="stat-label"><?= e(t('product_status')) ?></span>
        <span class="stat-value" style="font-size: 1.15rem; margin-top: 0.25rem;">
            <span class="badge <?= $badgeClass ?>">
                <?= e($statusLabel) ?>
            </span>
        </span>
    </div>
</div>

<!-- ===== Stock Adjustment Form ===== -->
<div class="card" style="max-width: 580px; margin: 0 auto; padding: 2rem;">
    <form method="POST" action="index.php?page=inventory&amp;action=stock&amp;id=<?= (int)$product['prod_id'] ?>" novalidate>
        <?= csrf_field() ?>

        <!-- Adjustment Mode -->
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label class="form-label" style="display: block; margin-bottom: 0.75rem; font-weight: 600;">
                <?= e(t('label_stock_change')) ?> <span style="color: var(--accent-danger, #ef4444); font-weight: bold;">*</span>
            </label>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <label style="display: flex; align-items: center; gap: 0.6rem; padding: 0.85rem 1rem; border: 1px solid var(--border-color, #2a3754); border-radius: 6px; background-color: var(--bg-base, #0b0f19); cursor: pointer;">
                    <input type="radio" name="stock_action" value="set" checked required>
                    <div>
                        <strong><?= e(t('stock_action_set')) ?></strong>
                        <div class="text-muted text-sm">Replace current count directly</div>
                    </div>
                </label>
                <label style="display: flex; align-items: center; gap: 0.6rem; padding: 0.85rem 1rem; border: 1px solid var(--border-color, #2a3754); border-radius: 6px; background-color: var(--bg-base, #0b0f19); cursor: pointer;">
                    <input type="radio" name="stock_action" value="add" required>
                    <div>
                        <strong style="color: #34d399;"><?= e(t('stock_action_add')) ?></strong>
                        <div class="text-muted text-sm">Add arriving units to current inventory</div>
                    </div>
                </label>
                <label style="display: flex; align-items: center; gap: 0.6rem; padding: 0.85rem 1rem; border: 1px solid var(--border-color, #2a3754); border-radius: 6px; background-color: var(--bg-base, #0b0f19); cursor: pointer;">
                    <input type="radio" name="stock_action" value="subtract" required>
                    <div>
                        <strong style="color: #f87171;"><?= e(t('stock_action_subtract')) ?></strong>
                        <div class="text-muted text-sm">Remove units due to damage or write-off</div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Quantity Input -->
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label class="form-label" for="quantity" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                <?= e(t('label_quantity')) ?> <span style="color: var(--accent-danger, #ef4444); font-weight: bold;">*</span>
            </label>
            <input type="number"
                   name="quantity"
                   id="quantity"
                   step="1"
                   min="0"
                   required
                   value="<?= (int)$product['stock'] ?>"
                   class="form-control"
                   style="width: 100%; padding: 0.75rem 1rem; border-radius: 6px; background-color: var(--bg-base, #0b0f19); color: var(--text-primary, #f8fafc); border: 1px solid var(--border-color, #2a3754); font-family: ui-monospace, SFMono-Regular, monospace; font-size: 1.15rem; font-weight: 700;">
        </div>

        <!-- Actions -->
        <div class="d-flex justify-between align-center" style="margin-top: 1.5rem;">
            <a href="index.php?page=inventory" class="btn btn-secondary">
                <?= e(t('btn_cancel')) ?>
            </a>
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.75rem;">
                <?= e(t('btn_update')) ?>
            </button>
        </div>
    </form>
</div>
