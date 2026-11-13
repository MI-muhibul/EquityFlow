<?php
declare(strict_types=1);

/**
 * views/layout/footer.php
 * -----------------------------------------------------------------------
 * Shared page bottom footer — rendered at the end of every view.
 *
 * Provides:
 *   • Semantic <footer> with copyright, app tagline, and tech stack details.
 *   • Closes </main>, </body>, and </html>.
 * -----------------------------------------------------------------------
 * Rules: Zero JavaScript, strings from lang files via t(), e() on all output.
 */

if (!function_exists('t')) {
    require_once __DIR__ . '/../../config/lang.php';
}
if (!function_exists('e')) {
    require_once __DIR__ . '/../../helpers/format.php';
}
?>

    <!-- End of main page content -->
</main>

<!-- ===== Footer ===== -->
<footer class="app-footer" role="contentinfo">
    <div class="footer-inner">
        <!-- Left: copyright & branding -->
        <div>
            &copy; <?= date('Y') ?>
            <strong><?= e(t('app_name')) ?></strong>
            &mdash; <?= e(t('app_tagline')) ?>
        </div>

        <!-- Right: technology & privacy badge -->
        <div class="text-muted text-sm">
            PHP 8 &bull; MySQL &bull; HTML5 &bull; CSS3 (No JavaScript)
        </div>
    </div>
</footer>

</body>
</html>
