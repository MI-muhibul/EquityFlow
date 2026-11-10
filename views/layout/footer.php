<?php
declare(strict_types=1);

/**
 * views/layout/footer.php
 * -----------------------------------------------------------------------
 * Shared page bottom — rendered at the end of every public view.
 *
 * What this provides:
 *   • Closes </main> opened by header.php
 *   • Semantic <footer> with copyright and app tagline
 *   • Closes </body> and </html>
 * -----------------------------------------------------------------------
 * Rules: Zero JavaScript, strings from lang files, e() on all output.
 */

// Helpers loaded by header.php; guard so standalone includes still work
if (!function_exists('t')) {
    require_once __DIR__ . '/../../config/lang.php';
}
if (!function_exists('e')) {
    require_once __DIR__ . '/../../helpers/format.php';
}
?>

    <!-- ↑ ↑ ↑  Page body ends here ↑ ↑ ↑ -->

</main><!-- /#main-content -->

<!-- ===== Footer =========================================================== -->
<footer class="app-footer" role="contentinfo">
    <div class="footer-inner">
        <!-- Left: copyright -->
        <div>
            &copy; <?= date('Y') ?>
            <strong><?= e(t('app_name')) ?></strong>
            &mdash; <?= e(t('app_tagline')) ?>
        </div>

        <!-- Right: stack note -->
        <div class="text-muted text-sm">
            PHP 8 &bull; MySQL &bull; HTML5 &bull; CSS3
        </div>
    </div>
</footer>

</body>
</html>
