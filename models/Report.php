<?php
declare(strict_types=1);

/**
 * models/Report.php
 * =============================================================================
 * Data-access layer for the `reports` table and all report aggregations.
 *
 * Rules:
 *   - Strictly scoped to the logged-in shop owner (user_id).
 *   - 100% PDO prepared statements; no string interpolation on user data.
 *   - Money arithmetic uses DECIMAL; PHP rounds to 2 d.p. before display.
 *   - Profit = sum((unit_price - unit_cost) * qty) for sale_items in period.
 *   - Net change = total payments received − total credit given.
 *   - Snapshot records in `reports` table store user_id on INSERT and DELETE.
 * =============================================================================
 */

require_once __DIR__ . '/../config/Database.php';

class Report
{
    /**
     * Aggregate all financial metrics for the requested month and year scoped to user_id.
     * Saves a snapshot to `reports` table with user_id.
     *
     * @param int $userId
     * @param int $year  e.g. 2026
     * @param int $month 1-12
     * @return array{
     *     year:              int,
     *     month:             int,
     *     period:            string,
     *     period_label:      string,
     *     total_credit:      float,
     *     total_payments:    float,
     *     net_change:        float,
     *     txn_count:         int,
     *     credit_count:      int,
     *     payment_count:     int,
     *     estimated_profit:  float,
     *     total_outstanding: float,
     *     daily_credits:     array,
     *     daily_payments:    array
     * }
     */
    public static function getMonthlySummary(int $userId, int $year, int $month): array
    {
        $pdo = Database::getConnection();

        // Clamp to valid range
        $year  = max(2000, min(2100, $year));
        $month = max(1, min(12, $month));

        // Date range for this month (inclusive)
        $dateStart   = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        $dateEnd     = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $daysInMonth);
        $periodKey   = sprintf('%04d-%02d', $year, $month);

        // 1. Main transaction aggregations joined through customers.user_id
        $stmtAgg = $pdo->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN t.type = 'credit'  THEN t.amount ELSE 0 END), 0.00) AS total_credit,
                COALESCE(SUM(CASE WHEN t.type = 'payment' THEN t.amount ELSE 0 END), 0.00) AS total_payments,
                COUNT(*)                                                                    AS txn_count,
                SUM(CASE WHEN t.type = 'credit'  THEN 1 ELSE 0 END)                          AS credit_count,
                SUM(CASE WHEN t.type = 'payment' THEN 1 ELSE 0 END)                          AS payment_count
             FROM transactions t
             JOIN customers c ON t.cust_id = c.cust_id
             WHERE c.user_id = :uid AND t.created_at BETWEEN :start AND :end"
        );
        $stmtAgg->execute([':uid' => $userId, ':start' => $dateStart, ':end' => $dateEnd]);
        $agg = $stmtAgg->fetch();

        $totalCredit   = round((float) ($agg['total_credit']   ?? 0), 2);
        $totalPayments = round((float) ($agg['total_payments'] ?? 0), 2);
        $txnCount      = (int) ($agg['txn_count']      ?? 0);
        $creditCount   = (int) ($agg['credit_count']   ?? 0);
        $paymentCount  = (int) ($agg['payment_count']  ?? 0);
        $netChange     = round($totalPayments - $totalCredit, 2);

        // 2. Estimated profit from sale_items joined through transactions and customers
        $stmtProfit = $pdo->prepare(
            "SELECT COALESCE(
                SUM((si.unit_price - si.unit_cost) * si.qty),
                0.00
             ) AS estimated_profit
             FROM sale_items si
             JOIN transactions t ON si.txn_id = t.txn_id
             JOIN customers c ON t.cust_id = c.cust_id
             WHERE c.user_id = :uid
               AND t.type = 'credit'
               AND t.created_at BETWEEN :start AND :end"
        );
        $stmtProfit->execute([':uid' => $userId, ':start' => $dateStart, ':end' => $dateEnd]);
        $estimatedProfit = round((float) $stmtProfit->fetchColumn(), 2);

        // 3. Live total outstanding across current user's customers
        $stmtOut = $pdo->prepare("SELECT COALESCE(SUM(balance), 0.00) FROM customers WHERE user_id = :uid AND balance > 0");
        $stmtOut->execute([':uid' => $userId]);
        $totalOutstanding = round((float) $stmtOut->fetchColumn(), 2);

        // 4. Daily breakdown for charts, scoped to user
        $stmtDaily = $pdo->prepare(
            "SELECT
                DATE(t.created_at) AS day,
                SUM(CASE WHEN t.type = 'credit'  THEN t.amount ELSE 0 END) AS daily_credit,
                SUM(CASE WHEN t.type = 'payment' THEN t.amount ELSE 0 END) AS daily_payment
             FROM transactions t
             JOIN customers c ON t.cust_id = c.cust_id
             WHERE c.user_id = :uid AND t.created_at BETWEEN :start AND :end
             GROUP BY DATE(t.created_at)
             ORDER BY day ASC"
        );
        $stmtDaily->execute([':uid' => $userId, ':start' => $dateStart, ':end' => $dateEnd]);
        $dailyRows = $stmtDaily->fetchAll();

        $dailyCredits  = [];
        $dailyPayments = [];
        foreach ($dailyRows as $row) {
            $dayNum = (int) date('j', strtotime((string) $row['day']));
            $dailyCredits[$dayNum]  = round((float) $row['daily_credit'], 2);
            $dailyPayments[$dayNum] = round((float) $row['daily_payment'], 2);
        }

        // 5. Month name string
        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March',    4 => 'April',
            5 => 'May',     6 => 'June',      7 => 'July',     8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
        $periodLabel = $monthNames[$month] . ' ' . $year;

        // 6. Save snapshot into reports table, strictly filtered and inserted with user_id
        $records = [
            ['credit',  $totalCredit],
            ['payment', $totalPayments],
            ['profit',  $estimatedProfit],
            ['net',     $netChange],
        ];

        $stmtDel = $pdo->prepare("DELETE FROM reports WHERE user_id = :uid AND period = :period AND type = :type");
        $stmtIns = $pdo->prepare("INSERT INTO reports (user_id, period, type, total, generated_at) VALUES (:uid, :period, :type, :total, NOW())");

        foreach ($records as [$rtype, $rtotal]) {
            try {
                $stmtDel->execute([':uid' => $userId, ':period' => $periodKey, ':type' => $rtype]);
                $stmtIns->execute([':uid' => $userId, ':period' => $periodKey, ':type' => $rtype, ':total' => $rtotal]);
            } catch (\Throwable $e) {
                // Do not block page render on snapshot save failure
            }
        }

        return [
            'year'             => $year,
            'month'            => $month,
            'period'           => $periodKey,
            'period_label'     => $periodLabel,
            'total_credit'     => $totalCredit,
            'total_payments'   => $totalPayments,
            'net_change'       => $netChange,
            'txn_count'        => $txnCount,
            'credit_count'     => $creditCount,
            'payment_count'    => $paymentCount,
            'estimated_profit' => $estimatedProfit,
            'total_outstanding'=> $totalOutstanding,
            'daily_credits'    => $dailyCredits,
            'daily_payments'   => $dailyPayments,
        ];
    }

    /**
     * Return all customers of the user with balance > 0, sorted descending by balance.
     *
     * @param int $userId
     * @return array
     */
    public static function getOutstandingBalances(int $userId): array
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare(
            "SELECT
                c.cust_id,
                c.name,
                c.phone,
                c.balance,
                c.credit_limit,
                MAX(t.created_at) AS last_txn_date
             FROM customers c
             LEFT JOIN transactions t ON c.cust_id = t.cust_id
             WHERE c.user_id = :uid AND c.balance > 0
             GROUP BY c.cust_id, c.name, c.phone, c.balance, c.credit_limit
             ORDER BY c.balance DESC"
        );
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $balance = (float) $row['balance'];
            $limit   = (float) $row['credit_limit'];
            $row['balance']      = $balance;
            $row['credit_limit'] = $limit;
            $row['util_pct']     = ($limit > 0)
                ? round(($balance / $limit) * 100.0, 1)
                : 0.0;
        }
        unset($row);

        return $rows;
    }

    /**
     * Fetch all previously saved report rows for the user, grouped by period.
     *
     * @param int $userId
     * @return array<string, array{credit: float, payment: float, profit: float, net: float, generated_at: string}>
     */
    public static function getPastReports(int $userId): array
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT period, type, total, generated_at
             FROM reports
             WHERE user_id = :uid
             ORDER BY period DESC, generated_at DESC"
        );
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $period = (string) $row['period'];
            if (!isset($grouped[$period])) {
                $grouped[$period] = [
                    'credit'       => 0.0,
                    'payment'      => 0.0,
                    'profit'       => 0.0,
                    'net'          => 0.0,
                    'generated_at' => (string) $row['generated_at'],
                ];
            }
            $grouped[$period][$row['type']] = (float) $row['total'];
        }

        return $grouped;
    }

    /**
     * Returns an array of years that contain transactions for this user.
     *
     * @param int $userId
     * @return array<int>
     */
    public static function getAvailableYears(int $userId): array
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT DISTINCT YEAR(t.created_at) AS yr
             FROM transactions t
             JOIN customers c ON t.cust_id = c.cust_id
             WHERE c.user_id = :uid
             ORDER BY yr DESC"
        );
        $stmt->execute([':uid' => $userId]);
        $dbYears = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $currentYear = (int) date('Y');
        $years = array_map('intval', $dbYears);

        if (!in_array($currentYear, $years, true)) {
            array_unshift($years, $currentYear);
        }

        return $years;
    }
}
