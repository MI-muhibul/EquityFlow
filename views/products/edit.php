<?php
declare(strict_types=1);

/**
 * views/products/edit.php
 * -----------------------------------------------------------------------------
 * Form to edit an existing product's catalog details:
 *   - Name, Category, Retail Price, Cost Price, Stock, Min Stock
 *   - Pre-populated with current product record
 *   - CSRF token on POST form
 * -----------------------------------------------------------------------------
 */
?>

<!-- ===== Breadcrumb & Header ===== -->
<div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <div class="d-flex flex-wrap justify-between align-center gap-1">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0;">
                <?= e(t('product_edit_title')) ?>
            </h1>
            <p class="text-muted text-sm" style="margin-top: .25rem;">
                <?= e($product['name']) ?>
            </p>
        </div>
        <div class="d-flex gap-1">
            <a href="index.php?page=inventory&amp;action=stock&amp;id=<?= (int)$product['prod_id'] ?>" class="btn btn-secondary">
                📦 <?= e(t('btn_update_stock')) ?>
            </a>
            <a href="index.php?page=inventory" class="btn btn-ghost">
                &larr; <?= e(t('btn_back')) ?>
            </a>
        </div>
    </div>
</div>

<!-- ===== Product Edit Form Card ===== -->
<div class="card" style="max-width: 680px; margin: 0 auto; padding: 2rem;">
    <form method="POST" action="index.php?page=inventory&amp;action=edit&amp;id=<?= (int)$product['prod_id'] ?>" novalidate>
        <?= csrf_field() ?>

        <!-- Product Name -->
        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label" for="name" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                <?= e(t('label_product_name')) ?> <span style="color: var(--accent-danger, #ef4444); font-weight: bold;">*</span>
            </label>
            <input type="text"
                   name="name"
                   id="name"
                   required
                   value="<?= e($product['name']) ?>"
                   class="form-control"
                   style="width: 100%; padding: 0.75rem 1rem; border-radius: 6px; background-color: var(--bg-base, #0b0f19); color: var(--text-primary, #f8fafc); border: 1px solid var(--border-color, #2a3754);">
        </div>

        <!-- Category -->
        <?php
            $isPredefined = in_array($product['category'], Product::PREDEFINED_CATEGORIES, true);
        ?>
        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label" for="category_select" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                <?= e(t('label_category')) ?> <span style="color: var(--accent-danger, #ef4444); font-weight: bold;">*</span>
            </label>
            <select name="category"
                    id="category_select"
                    required
                    class="form-control"
                    style="width: 100%; padding: 0.75rem 1rem; border-radius: 6px; background-color: var(--bg-base, #0b0f19); color: var(--text-primary, #f8fafc); border: 1px solid var(--border-color, #2a3754);">
                <option value=""><?= e(t('select_category')) ?></option>
                <?php foreach (Product::PREDEFINED_CATEGORIES as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= ($product['category'] === $cat) ? 'selected' : '' ?>>
                        <?= e($cat) ?>
                    </option>
                <?php endforeach; ?>
                <option value="others" <?= !$isPredefined && !empty($product['category']) ? 'selected' : '' ?>>
                    <?= e(t('category_other')) ?>
                </option>
            </select>
        </div>

        <!-- Custom Category Input (If Others selected) -->
        <div class="form-group" id="other_category_box" style="margin-bottom: 1.25rem;">
            <label class="form-label" for="other_category" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                <?= e(t('label_other_category')) ?> <span class="text-muted" style="font-size: 0.85rem; font-weight: normal;">(<?= e(t('category_other')) ?>)</span>
            </label>
            <input type="text"
                   name="other_category"
                   id="other_category"
                   value="<?= !$isPredefined ? e($product['category']) : '' ?>"
                   placeholder="<?= e(t('placeholder_other_cat')) ?>"
                   class="form-control"
                   style="width: 100%; padding: 0.75rem 1rem; border-radius: 6px; background-color: var(--bg-base, #0b0f19); color: var(--text-primary, #f8fafc); border: 1px solid var(--border-color, #2a3754);">
        </div>

        <!-- Prices Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            <div class="form-group">
                <label class="form-label" for="retail_price" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?= e(t('label_retail_price')) ?> <span style="color: var(--accent-danger, #ef4444); font-weight: bold;">*</span>
                </label>
                <input type="number"
                       name="retail_price"
                       id="retail_price"
                       step="0.01"
                       min="0"
                       required
                       value="<?= e((string)$product['retail_price']) ?>"
                       class="form-control"
                       style="width: 100%; padding: 0.75rem 1rem; border-radius: 6px; background-color: var(--bg-base, #0b0f19); color: var(--text-primary, #f8fafc); border: 1px solid var(--border-color, #2a3754); font-family: ui-monospace, SFMono-Regular, monospace;">
            </div>
            <div class="form-group">
                <label class="form-label" for="cost_price" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?= e(t('label_cost_price')) ?> <span style="color: var(--accent-danger, #ef4444); font-weight: bold;">*</span>
                </label>
                <input type="number"
                       name="cost_price"
                       id="cost_price"
                       step="0.01"
                       min="0"
                       required
                       value="<?= e((string)$product['cost_price']) ?>"
                       class="form-control"
                       style="width: 100%; padding: 0.75rem 1rem; border-radius: 6px; background-color: var(--bg-base, #0b0f19); color: var(--text-primary, #f8fafc); border: 1px solid var(--border-color, #2a3754); font-family: ui-monospace, SFMono-Regular, monospace;">
            </div>
        </div>

        <!-- Stock & Min Stock Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <div class="form-group">
                <label class="form-label" for="stock" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?= e(t('label_stock')) ?> <span style="color: var(--accent-danger, #ef4444); font-weight: bold;">*</span>
                </label>
                <input type="number"
                       name="stock"
                       id="stock"
                       step="1"
                       min="0"
                       required
                       value="<?= (int)$product['stock'] ?>"
                       class="form-control"
                       style="width: 100%; padding: 0.75rem 1rem; border-radius: 6px; background-color: var(--bg-base, #0b0f19); color: var(--text-primary, #f8fafc); border: 1px solid var(--border-color, #2a3754); font-family: ui-monospace, SFMono-Regular, monospace;">
            </div>
            <div class="form-group">
                <label class="form-label" for="min_stock" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?= e(t('label_min_stock')) ?> <span style="color: var(--accent-danger, #ef4444); font-weight: bold;">*</span>
                </label>
                <input type="number"
                       name="min_stock"
                       id="min_stock"
                       step="1"
                       min="0"
                       required
                       value="<?= (int)$product['min_stock'] ?>"
                       class="form-control"
                       style="width: 100%; padding: 0.75rem 1rem; border-radius: 6px; background-color: var(--bg-base, #0b0f19); color: var(--text-primary, #f8fafc); border: 1px solid var(--border-color, #2a3754); font-family: ui-monospace, SFMono-Regular, monospace;">
            </div>
        </div>

        <!-- Actions -->
        <div class="d-flex justify-between align-center" style="margin-top: 1.5rem;">
            <a href="index.php?page=inventory" class="btn btn-secondary">
                <?= e(t('btn_cancel')) ?>
            </a>
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.75rem;">
                <?= e(t('btn_save')) ?>
            </button>
        </div>
    </form>
</div>
