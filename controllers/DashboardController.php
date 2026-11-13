<?php
declare(strict_types=1);

/**
 * controllers/DashboardController.php
 * -----------------------------------------------------------------------------
 * Protected shop overview dashboard.
 *
 * Enforces active authentication using require_login() and prepares the
 * 4 summary card values using real database queries scoped to the logged-in user:
 *   1. Total Customers: SELECT COUNT(*) FROM customers WHERE user_id = :uid
 *   2. Total Outstanding (Due): SELECT COALESCE(SUM(balance), 0.00) FROM customers WHERE user_id = :uid AND balance > 0
 *   3. Low-Stock Items: SELECT COUNT(*) FROM products WHERE user_id = :uid AND stock <= min_stock
 *   4. This Month's Collections: SELECT COALESCE(SUM(t.amount), 0.00) FROM transactions t
 *      JOIN customers c ON t.cust_id = c.cust_id
 *      WHERE c.user_id = :uid AND t.type = 'payment'
 *        AND YEAR(t.created_at) = YEAR(CURDATE()) AND MONTH(t.created_at) = MONTH(CURDATE())
 *
 * All queries are strictly scoped to user_id via PDO prepared statements.
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

        $userId = (int)$_SESSION['user_id'];
        $pdo = Database::getConnection();

        // 1. Total Customers Count (scoped to user_id)
        $stmtCust = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE user_id = :uid');
        $stmtCust->execute(['uid' => $userId]);
        $customerCount = (int)$stmtCust->fetchColumn();

        // 2. Total Outstanding Balance (sum of positive customer balances scoped to user_id)
        $stmtDue = $pdo->prepare('SELECT COALESCE(SUM(balance), 0.00) FROM customers WHERE user_id = :uid AND balance > 0');
        $stmtDue->execute(['uid' => $userId]);
        $totalOutstanding = (float)$stmtDue->fetchColumn();

        // 3. Low-Stock & Out-of-Stock Items Count (scoped to user_id)
        $stmtLowStockCount = $pdo->prepare('SELECT COUNT(*) FROM products WHERE user_id = :uid AND stock <= min_stock');
        $stmtLowStockCount->execute(['uid' => $userId]);
        $lowStockCount = (int)$stmtLowStockCount->fetchColumn();

        // 4. This Month's Collections (Payments scoped to user_id)
        $stmtMonthCollections = $pdo->prepare(
            "SELECT COALESCE(SUM(t.amount), 0.00)
             FROM transactions t
             JOIN customers c ON t.cust_id = c.cust_id
             WHERE c.user_id = :uid
               AND t.type = 'payment'
               AND YEAR(t.created_at) = YEAR(CURDATE())
               AND MONTH(t.created_at) = MONTH(CURDATE())"
        );
        $stmtMonthCollections->execute(['uid' => $userId]);
        $monthCollections = (float)$stmtMonthCollections->fetchColumn();

        $stats = [
            'customers'         => $customerCount,
            'total_outstanding' => $totalOutstanding,
            'low_stock'         => $lowStockCount,
            'month_collections' => $monthCollections,
        ];

        // Fetch low-stock and out-of-stock items for the dashboard panel
        $stmtLowStockItems = $pdo->prepare(
            'SELECT * FROM products WHERE user_id = :uid AND stock <= min_stock ORDER BY stock ASC, name ASC LIMIT 10'
        );
        $stmtLowStockItems->execute(['uid' => $userId]);
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
    public function handleRequest(): void {
        $this->index();
    }
}
