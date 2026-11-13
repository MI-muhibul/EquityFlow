<?php
declare(strict_types=1);

/**
 * controllers/DashboardController.php
 * -----------------------------------------------------------------------------
 * Protected shop overview dashboard.
 *
 * Enforces active authentication using require_login() and prepares the
 * 4 summary card values using real database queries:
 *   1. Total Customers: SELECT COUNT(*) FROM customers
 *   2. Total Outstanding (Due): SELECT COALESCE(SUM(balance), 0.00) FROM customers WHERE balance > 0
 *   3. Low-Stock Items: SELECT COUNT(*) FROM products WHERE stock <= min_stock
 *   4. This Month's Collections: SELECT COALESCE(SUM(amount), 0.00) FROM transactions
 *      WHERE type = 'payment' AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())
 *
 * Also queries low-stock and out-of-stock items for the dedicated alert panel.
 * -----------------------------------------------------------------------------
 * Rules:
 *   • Prepared statements only
 *   • Strictly zero JavaScript
 *   • htmlspecialchars via e() on all output
 *   • Money displayed as ৳ with 2 decimals
 *   • All strings from lang files via t()
 *   • Beginner-readable code with short comments
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/format.php';
require_once __DIR__ . '/../models/Product.php';

class DashboardController {
    /**
     * Display the protected shop overview dashboard.
     */
    public function index(): void {
        // Enforce active login and 30-minute inactivity timeout
        require_login();

        $pdo = Database::getConnection();

        // 1. Total Customers Count
        $stmtCust = $pdo->query('SELECT COUNT(*) FROM customers');
        $customerCount = (int)$stmtCust->fetchColumn();

        // 2. Total Outstanding Balance (sum of positive customer balances)
        $stmtDue = $pdo->query('SELECT COALESCE(SUM(balance), 0.00) FROM customers WHERE balance > 0');
        $totalOutstanding = (float)$stmtDue->fetchColumn();

        // 3. Low-Stock & Out-of-Stock Items Count
        $stmtLowStockCount = $pdo->query('SELECT COUNT(*) FROM products WHERE stock <= min_stock');
        $lowStockCount = (int)$stmtLowStockCount->fetchColumn();

        // 4. This Month's Collections (Payments)
        $stmtMonthCollections = $pdo->query(
            "SELECT COALESCE(SUM(amount), 0.00) FROM transactions 
             WHERE type = 'payment' 
               AND YEAR(created_at) = YEAR(CURDATE()) 
               AND MONTH(created_at) = MONTH(CURDATE())"
        );
        $monthCollections = (float)$stmtMonthCollections->fetchColumn();

        $stats = [
            'customers'         => $customerCount,
            'total_outstanding' => $totalOutstanding,
            'low_stock'         => $lowStockCount,
            'month_collections' => $monthCollections,
        ];

        // Fetch low-stock and out-of-stock items for the dashboard panel
        $stmtLowStockItems = $pdo->query(
            'SELECT * FROM products WHERE stock <= min_stock ORDER BY stock ASC, name ASC'
        );
        $lowStockProducts = $stmtLowStockItems->fetchAll();

        foreach ($lowStockProducts as &$item) {
            $item['status_info'] = Product::computeStatus((int)$item['stock'], (int)$item['min_stock']);
        }
        unset($item);

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
