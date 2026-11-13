<?php
declare(strict_types=1);

/**
 * controllers/ReportController.php
 * -----------------------------------------------------------------------------
 * Controller handling shop analytics and financial reports:
 *   - Monthly summary calculation & breakdown
 *   - Month/Year selector via GET parameters
 *   - Automatic saving to `reports` table
 *   - Outstanding customer balances list sorted DESC
 *   - Zero JavaScript SVG comparison bar chart
 * -----------------------------------------------------------------------------
 * Rules:
 *   - require_login() enforced
 *   - Zero JavaScript
 *   - Prepared statements only via models
 *   - htmlspecialchars on all output via e()
 *   - Money formatted as ৳ with 2 decimals
 *   - Bilingual strings from lang packs via t()
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../helpers/format.php';
require_once __DIR__ . '/../helpers/chart.php';
require_once __DIR__ . '/../models/Report.php';

class ReportController
{
    /**
     * Dispatcher method called by router.
     */
    public function handleRequest(): void
    {
        require_login();

        $action = isset($_GET['action']) && is_string($_GET['action']) ? trim($_GET['action']) : 'index';

        switch ($action) {
            case 'index':
            default:
                $this->index();
                break;
        }
    }

    /**
     * Display Monthly Report & Outstanding Balance List.
     */
    public function index(): void
    {
        // 1. Resolve selected Year and Month from GET params (defaults to current)
        $currentYear  = (int) date('Y');
        $currentMonth = (int) date('n');

        $selectedYear  = isset($_GET['year']) ? (int) $_GET['year'] : $currentYear;
        $selectedMonth = isset($_GET['month']) ? (int) $_GET['month'] : $currentMonth;

        // Validation bounds
        if ($selectedYear < 2000 || $selectedYear > 2100) {
            $selectedYear = $currentYear;
        }
        if ($selectedMonth < 1 || $selectedMonth > 12) {
            $selectedMonth = $currentMonth;
        }

        // 2. Fetch monthly aggregated summary & save snapshot
        $summary = Report::getMonthlySummary($selectedYear, $selectedMonth);

        // 3. Fetch outstanding customer balances list (sorted by balance DESC)
        $outstandingCustomers = Report::getOutstandingBalances();

        // 4. Fetch available years for selector dropdown
        $availableYears = Report::getAvailableYears();

        // 5. Fetch past saved reports
        $pastReports = Report::getPastReports();

        $pageTitle  = t('report_title');
        $activePage = 'reports';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/reports/index.php';
        require __DIR__ . '/../views/layout/footer.php';
    }
}
