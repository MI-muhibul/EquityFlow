<?php
declare(strict_types=1);

/**
 * controllers/DashboardController.php
 * -----------------------------------------------------------------------------
 * Protected shop overview dashboard.
 *
 * Enforces active authentication using require_login() and prepares the
 * 4 summary card values (customers, total outstanding, low-stock items,
 * this month's collections) showing 0 for now.
 * -----------------------------------------------------------------------------
 * Rules:
 *   • Prepared statements only (when querying)
 *   • Strictly zero JavaScript
 *   • htmlspecialchars via e() on all output
 *   • Money displayed as ৳ with 2 decimals
 *   • All strings from lang files via t()
 *   • Beginner-readable code with short comments
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../helpers/format.php';

class DashboardController {
    /**
     * Display the protected shop overview dashboard.
     */
    public function index(): void {
        // Enforce active login and 30-minute inactivity timeout
        require_login();

        // Four summary cards showing 0 for now as specified
        $stats = [
            'customers'         => 0,
            'total_outstanding' => 0.0,
            'low_stock'         => 0,
            'month_collections' => 0.0,
        ];

        // Page title and active navigation slug
        $pageTitle  = t('nav_dashboard');
        $activePage = 'dashboard';

        // Render layout: header, dashboard view, footer
        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/dashboard/index.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * handleRequest() — called by the front controller.
     * Dashboard has no sub-actions; always delegates to index().
     */
    public function handleRequest(): void
    {
        $this->index();
    }
}
