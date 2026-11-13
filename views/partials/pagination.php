<?php
declare(strict_types=1);

/**
 * views/partials/pagination.php
 * -----------------------------------------------------------------------------
 * Reusable pagination bar. Zero JavaScript — pure <a> links.
 *
 * Required variables (injected by the including view):
 *   $pagination  array{page, total_pages, total, per_page}
 *   $baseUrl     string  — base URL without 'p=' param, e.g.
 *                          "index.php?page=customers&name=Ali"
 *
 * Usage in a view:
 *   $baseUrl = 'index.php?' . http_build_query(array_diff_key($_GET, ['p' => '']));
 *   include __DIR__ . '/../partials/pagination.php';
 * -----------------------------------------------------------------------------
 */

$curPage    = (int) ($pagination['page']        ?? 1);
$totalPages = (int) ($pagination['total_pages'] ?? 1);
$total      = (int) ($pagination['total']       ?? 0);
$perPage    = (int) ($pagination['per_page']    ?? 20);

// Nothing to paginate
if ($totalPages <= 1 && $total === 0): ?>
    <p class="text-muted text-sm" style="text-align:center; padding:.75rem 0;">
        <?= e(t('filter_results')) ?>: 0
    </p>
<?php return; endif;

// Record-range info: "Showing 21 to 40 of 183 records"
$rangeStart = ($curPage - 1) * $perPage + 1;
$rangeEnd   = min($curPage * $perPage, $total);

// Build prev/next URLs — merge current GET params then override 'p'
$getWithoutPage = array_diff_key($_GET, ['p' => '']);
$prevUrl = e('index.php?' . http_build_query(array_merge($getWithoutPage, ['p' => $curPage - 1])));
$nextUrl = e('index.php?' . http_build_query(array_merge($getWithoutPage, ['p' => $curPage + 1])));

// Window of page numbers to show (max 7 links centered on current page)
$windowStart = max(1, $curPage - 3);
$windowEnd   = min($totalPages, $curPage + 3);
?>

<nav class="pagination-bar" aria-label="<?= e(t('pagination_page')) ?>">
    <!-- Record count info -->
    <span class="pagination-info text-muted text-sm">
        <?= e(t('pagination_showing')) ?>
        <strong><?= $rangeStart ?></strong>
        <?= e(t('pagination_to')) ?>
        <strong><?= $rangeEnd ?></strong>
        <?= e(t('pagination_of')) ?>
        <strong><?= number_format($total) ?></strong>
        <?= e(t('pagination_records')) ?>
    </span>

    <div class="pagination-links">
        <!-- Previous page -->
        <?php if ($curPage > 1): ?>
            <a href="<?= $prevUrl ?>" class="page-link" rel="prev">
                <?= e(t('pagination_prev')) ?>
            </a>
        <?php else: ?>
            <span class="page-link disabled" aria-disabled="true">
                <?= e(t('pagination_prev')) ?>
            </span>
        <?php endif; ?>

        <!-- First page shortcut if window doesn't start at 1 -->
        <?php if ($windowStart > 1): ?>
            <a href="<?= e('index.php?' . http_build_query(array_merge($getWithoutPage, ['p' => 1]))) ?>"
               class="page-link">1</a>
            <?php if ($windowStart > 2): ?>
                <span class="page-link disabled">…</span>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Page number window -->
        <?php for ($pg = $windowStart; $pg <= $windowEnd; $pg++): ?>
            <?php if ($pg === $curPage): ?>
                <span class="page-link active" aria-current="page"><?= $pg ?></span>
            <?php else: ?>
                <a href="<?= e('index.php?' . http_build_query(array_merge($getWithoutPage, ['p' => $pg]))) ?>"
                   class="page-link"><?= $pg ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <!-- Last page shortcut if window doesn't reach end -->
        <?php if ($windowEnd < $totalPages): ?>
            <?php if ($windowEnd < $totalPages - 1): ?>
                <span class="page-link disabled">…</span>
            <?php endif; ?>
            <a href="<?= e('index.php?' . http_build_query(array_merge($getWithoutPage, ['p' => $totalPages]))) ?>"
               class="page-link"><?= $totalPages ?></a>
        <?php endif; ?>

        <!-- Next page -->
        <?php if ($curPage < $totalPages): ?>
            <a href="<?= $nextUrl ?>" class="page-link" rel="next">
                <?= e(t('pagination_next')) ?>
            </a>
        <?php else: ?>
            <span class="page-link disabled" aria-disabled="true">
                <?= e(t('pagination_next')) ?>
            </span>
        <?php endif; ?>
    </div>
</nav>
