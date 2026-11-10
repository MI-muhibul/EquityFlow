<?php
declare(strict_types=1);

/**
 * EquityFlow - Master Layout Footer
 *
 * Closes the main content wrapper and provides the semantic footer.
 * Rules: Zero JavaScript, all strings from language packs.
 */
?>
    </main>

    <!-- Application Footer -->
    <footer class="app-footer">
        <div class="footer-content">
            <div>
                &copy; <?= date('Y') ?> <strong><?= e(t('app_name')) ?></strong> &bull; <?= e(t('app_tagline')) ?>
            </div>
            <div>
                <?= e(t('currency_symbol')) ?> <?= e(t('app_name')) ?> &bull; Digital Khata Ledger
            </div>
        </div>
    </footer>
</body>
</html>
