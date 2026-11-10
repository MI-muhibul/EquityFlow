<?php
declare(strict_types=1);

/**
 * controllers/DashboardController.php
 * Handles the main overview dashboard showing ledger stats,
 * top due customers, and recent transaction history.
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../helpers/format.php';

class DashboardController {
    /**
     * Display the primary shop overview dashboard.
     */
    public function index(): void {
        // Enforce active authentication and 30-minute session timeout
        require_login();
        try {
            $pdo = Database::getConnection();

            // 1. Total active customers
            $totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();

            // 2. Total outstanding market credit (Baki)
            $totalDue = (float)$pdo->query("SELECT COALESCE(SUM(balance), 0) FROM customers WHERE balance > 0")->fetchColumn();

            // 3. Total cash collected (Joma)
            $totalCollected = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'payment'")->fetchColumn();

            // 4. Low stock products alert count
            $lowStockCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock <= min_stock")->fetchColumn();

            // 5. Recent 5 ledger transactions with customer name
            $stmt = $pdo->query("
                SELECT t.txn_id, t.amount, t.type, t.note, t.created_at, c.cust_id, c.name AS customer_name
                FROM transactions t
                JOIN customers c ON t.cust_id = c.cust_id
                ORDER BY t.created_at DESC
                LIMIT 5
            ");
            $recentTxns = $stmt->fetchAll();

            // 6. Top 5 customers with highest outstanding due balance
            $stmtDue = $pdo->query("
                SELECT cust_id, name, phone, balance, credit_limit
                FROM customers
                WHERE balance > 0
                ORDER BY balance DESC
                LIMIT 5
            ");
            $topDueCustomers = $stmtDue->fetchAll();

        } catch (PDOException $e) {
            flash_set('error', 'ডাটাবেজ সংযোগে সমস্যা: ' . $e->getMessage());
            $totalCustomers = 0;
            $totalDue = 0.0;
            $totalCollected = 0.0;
            $lowStockCount = 0;
            $recentTxns = [];
            $topDueCustomers = [];
        }

        $pageTitle  = t('nav_dashboard');
        $activePage = 'dashboard';

        // Render master layout
        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/dashboard/index.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }
}
