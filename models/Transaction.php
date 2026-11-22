<?php
declare(strict_types=1);

/**
 * models/Transaction.php
 * -----------------------------------------------------------------------------
 * Model handling Khata transactions (credit sale / payment).
 *
 * Rules:
 *   - Strictly scoped to the logged-in shop owner (user_id).
 *   - Uses PDO beginTransaction(), commit(), rollBack().
 *   - Runs SELECT ... FOR UPDATE on customers row joined/filtered by user_id.
 *   - If products are provided for a credit sale:
 *       • SELECT prod_id, name, retail_price, cost_price, stock FROM products
 *         WHERE prod_id = :id AND user_id = :uid FOR UPDATE
 *       • Rejects if requested quantity > available stock.
 *       • Deducts stock: UPDATE products SET stock = stock - qty WHERE prod_id = :id AND user_id = :uid
 *       • Computes total transaction amount = sum(qty * retail_price).
 *       • Inserts line items into `sale_items`.
 *   - Validates:
 *       1. amount > 0
 *       2. type is either 'credit' or 'payment'
 *       3. customer must exist and belong to user_id
 *       4. credit sale rejected if (balance + amount) > credit_limit
 *       5. payment rejected if amount > current balance
 *   - Inserts row into `transactions`.
 *   - Updates `customers.balance` scoped to user_id.
 *   - Inserts audit trail row into `ledger_entries` (owner_id = user_id, txn_id).
 *   - All SQL uses PDO prepared statements.
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/Database.php';

class Transaction
{
    /**
     * Record a transaction (credit sale or payment) with full atomicity.
     * Supports optional itemized product sales for credit transactions.
     *
     * @param int $custId
     * @param int $userId (user_id recording the transaction and owning the customer & products)
     * @param float $directAmount Direct amount entered (ignored if items are provided)
     * @param string $type ('credit' or 'payment')
     * @param string|null $note
     * @param array<int, array{prod_id: int, qty: float}> $items Optional product lines for credit sales
     * @param bool $simulateFailure If true, throws exception before commit to test rollback
     * @return array{success: bool, txn_id: ?int, new_balance: ?float, error_key: ?string, error_params?: array}
     */
    public static function record(
        int $custId,
        int $userId,
        float $directAmount,
        string $type,
        ?string $note = null,
        array $items = [],
        bool $simulateFailure = false
    ): array {
        // Basic type validation
        if ($type !== 'credit' && $type !== 'payment') {
            return [
                'success'     => false,
                'txn_id'      => null,
                'new_balance' => null,
                'error_key'   => 'error_invalid_txn_type',
            ];
        }

        $pdo = Database::getConnection();

        try {
            // Begin transactional boundary
            $pdo->beginTransaction();

            // 1. Lock and validate customer record for atomic update, strictly scoped to user_id
            $stmtCust = $pdo->prepare(
                'SELECT cust_id, balance, credit_limit FROM customers WHERE cust_id = :id AND user_id = :uid FOR UPDATE'
            );
            $stmtCust->execute([':id' => $custId, ':uid' => $userId]);
            $customer = $stmtCust->fetch();

            if (!$customer) {
                $pdo->rollBack();
                return [
                    'success'     => false,
                    'txn_id'      => null,
                    'new_balance' => null,
                    'error_key'   => 'error_customer_not_found',
                ];
            }

            $currentBalance = (float)$customer['balance'];
            $creditLimit    = (float)$customer['credit_limit'];

            // 2. Process itemized products if provided for credit sale
            $processedItems = [];
            $computedAmount = 0.0;

            if ($type === 'credit' && !empty($items)) {
                // Prepare product lock statement scoped to user_id
                $stmtProd = $pdo->prepare(
                    'SELECT prod_id, name, retail_price, cost_price, stock FROM products WHERE prod_id = :id AND user_id = :uid FOR UPDATE'
                );
                $stmtStockReduce = $pdo->prepare(
                    'UPDATE products SET stock = stock - :qty WHERE prod_id = :id AND user_id = :uid'
                );

                foreach ($items as $item) {
                    $prodId = (int)($item['prod_id'] ?? 0);
                    $qty    = (float)($item['qty'] ?? 0.0);

                    // Skip empty rows
                    if ($prodId <= 0 || $qty <= 0.0) {
                        continue;
                    }

                    $stmtProd->execute([':id' => $prodId, ':uid' => $userId]);
                    $product = $stmtProd->fetch();

                    // If product does not exist or does not belong to user_id, fail
                    if (!$product) {
                        $pdo->rollBack();
                        return [
                            'success'     => false,
                            'txn_id'      => null,
                            'new_balance' => null,
                            'error_key'   => 'error_product_not_found',
                        ];
                    }

                    $availableStock = (float)$product['stock'];

                    // Rule: Reject quantities above available stock
                    if ($qty > $availableStock) {
                        $pdo->rollBack();
                        return [
                            'success'      => false,
                            'txn_id'       => null,
                            'new_balance'  => null,
                            'error_key'    => 'error_stock_insufficient_item',
                            'error_params' => [
                                ':product'   => $product['name'],
                                ':stock'     => (string)$availableStock,
                                ':requested' => (string)$qty,
                            ],
                        ];
                    }

                    $unitPrice  = (float)$product['retail_price'];
                    $unitCost   = (float)$product['cost_price'];
                    $lineTotal  = round($unitPrice * $qty, 2);
                    $computedAmount += $lineTotal;

                    // Reduce stock in DB
                    $stmtStockReduce->execute([
                        ':qty' => $qty,
                        ':id'  => $prodId,
                        ':uid' => $userId,
                    ]);

                    $processedItems[] = [
                        'prod_id'    => $prodId,
                        'qty'        => $qty,
                        'unit_price' => $unitPrice,
                        'unit_cost'  => $unitCost,
                    ];
                }
            }

            // Determine final transaction amount to add to customer balance (baki)
            // If items are chosen and directAmount > 0 was also specified as the credited portion,
            // use directAmount as the credit amount (capped at computed total).
            // If directAmount is 0/empty, the full computed items amount is credited.
            if (!empty($processedItems)) {
                if ($directAmount > 0.0) {
                    $finalAmount = round($directAmount, 2);
                } else {
                    $finalAmount = round($computedAmount, 2);
                }
            } else {
                $finalAmount = round($directAmount, 2);
            }

            // Rule: amount > 0
            if ($finalAmount <= 0.0) {
                $pdo->rollBack();
                return [
                    'success'     => false,
                    'txn_id'      => null,
                    'new_balance' => null,
                    'error_key'   => 'error_amount_positive',
                ];
            }

            // 3. Validate credit limits or payment limits
            if ($type === 'credit') {
                $newBalance = round($currentBalance + $finalAmount, 2);

                // Rule: credit sale rejected if new balance would exceed credit_limit
                if ($creditLimit > 0.0 && $newBalance > $creditLimit) {
                    $pdo->rollBack();
                    return [
                        'success'     => false,
                        'txn_id'      => null,
                        'new_balance' => null,
                        'error_key'   => 'error_credit_limit',
                    ];
                }
            } else { // payment
                // Rule: payment cannot exceed current balance
                if ($finalAmount > $currentBalance) {
                    $pdo->rollBack();
                    return [
                        'success'     => false,
                        'txn_id'      => null,
                        'new_balance' => null,
                        'error_key'   => 'error_payment_exceeds_balance',
                    ];
                }

                $newBalance = round($currentBalance - $finalAmount, 2);
            }

            // 4. Insert transaction row
            $sqlTxn = 'INSERT INTO transactions (cust_id, amount, type, note, created_at)
                       VALUES (:cust_id, :amount, :type, :note, NOW())';
            $stmtTxn = $pdo->prepare($sqlTxn);
            $stmtTxn->execute([
                ':cust_id' => $custId,
                ':amount'  => $finalAmount,
                ':type'    => $type,
                ':note'    => $note !== null && trim($note) !== '' ? trim($note) : null,
            ]);

            $txnId = (int)$pdo->lastInsertId();

            // 5. Insert sale_items if items were processed
            if (!empty($processedItems)) {
                $sqlSaleItem = 'INSERT INTO sale_items (txn_id, prod_id, qty, unit_price, unit_cost)
                                VALUES (:txn_id, :prod_id, :qty, :unit_price, :unit_cost)';
                $stmtSaleItem = $pdo->prepare($sqlSaleItem);

                foreach ($processedItems as $pItem) {
                    $stmtSaleItem->execute([
                        ':txn_id'     => $txnId,
                        ':prod_id'    => $pItem['prod_id'],
                        ':qty'        => $pItem['qty'],
                        ':unit_price' => $pItem['unit_price'],
                        ':unit_cost'  => $pItem['unit_cost'],
                    ]);
                }
            }

            // 6. Update customer balance (scoped to user_id)
            $sqlUpd = 'UPDATE customers SET balance = :new_balance WHERE cust_id = :id AND user_id = :uid';
            $stmtUpd = $pdo->prepare($sqlUpd);
            $stmtUpd->execute([
                ':new_balance' => $newBalance,
                ':id'          => $custId,
                ':uid'         => $userId,
            ]);

            // 7. Insert audit ledger_entries row
            $sqlLedger = 'INSERT INTO ledger_entries (owner_id, txn_id, created_at)
                          VALUES (:owner_id, :txn_id, NOW())';
            $stmtLedger = $pdo->prepare($sqlLedger);
            $stmtLedger->execute([
                ':owner_id' => $userId,
                ':txn_id'   => $txnId,
            ]);

            // Simulation check for rollback testing
            if ($simulateFailure) {
                throw new RuntimeException('SIMULATED_TRANSACTION_FAILURE');
            }

            // Commit all changes atomically
            $pdo->commit();

            return [
                'success'     => true,
                'txn_id'      => $txnId,
                'new_balance' => $newBalance,
                'error_key'   => null,
            ];

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [
                'success'     => false,
                'txn_id'      => null,
                'new_balance' => null,
                'error_key'   => $e->getMessage() === 'SIMULATED_TRANSACTION_FAILURE'
                                    ? 'SIMULATED_TRANSACTION_FAILURE'
                                    : 'error_db_error',
            ];
        }
    }

    // =========================================================================
    // paginate() — filtered list with 20-per-page pagination, scoped to user
    // =========================================================================
    /**
     * Return a page of transactions matching optional filters, scoped to user_id.
     *
     * @param int   $userId
     * @param array $filters
     * @param int   $page
     * @param int   $perPage
     * @return array{items: array, total: int, page: int, per_page: int, total_pages: int}
     */
    public static function paginate(int $userId, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $pdo    = Database::getConnection();
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where  = ['c.user_id = :uid'];
        $params = [':uid' => $userId];

        $startDate = trim($filters['start_date'] ?? '');
        $endDate   = trim($filters['end_date']   ?? '');
        $custId    = (int) ($filters['cust_id'] ?? 0);
        $type      = trim($filters['type'] ?? '');

        if ($startDate !== '') {
            $where[]              = 't.created_at >= :start_date';
            $params[':start_date'] = $startDate . ' 00:00:00';
        }
        if ($endDate !== '') {
            $where[]             = 't.created_at <= :end_date';
            $params[':end_date'] = $endDate . ' 23:59:59';
        }
        if ($custId > 0) {
            $where[]            = 't.cust_id = :cust_id';
            $params[':cust_id'] = $custId;
        }
        if ($type === 'credit' || $type === 'payment') {
            $where[]        = 't.type = :type';
            $params[':type'] = $type;
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);

        // COUNT scoped through customers.user_id
        $countStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM transactions t
             JOIN customers c ON t.cust_id = c.cust_id
             {$whereSQL}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Page of rows — JOIN customers for display, scoped to user_id
        $listStmt = $pdo->prepare(
            "SELECT t.*, c.name AS customer_name, c.phone AS customer_phone
             FROM transactions t
             JOIN customers c ON t.cust_id = c.cust_id
             {$whereSQL}
             ORDER BY t.txn_id DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $listStmt->execute($params);
        $items = $listStmt->fetchAll();

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 1,
        ];
    }

    /**
     * Get recent transactions with customer details scoped to user_id.
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public static function all(int $userId, int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT t.*, c.name AS customer_name, c.phone AS customer_phone
             FROM transactions t
             JOIN customers c ON t.cust_id = c.cust_id
             WHERE c.user_id = :uid
             ORDER BY t.txn_id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get transactions for a specific customer with running balance computed,
     * verifying that the customer belongs to the user.
     * Returned newest first.
     *
     * @param int $custId
     * @param int $userId
     * @return array
     */
    public static function findByCustomerWithRunningBalance(int $custId, int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT t.*
             FROM transactions t
             JOIN customers c ON t.cust_id = c.cust_id
             WHERE t.cust_id = :id AND c.user_id = :uid
             ORDER BY t.txn_id ASC'
        );
        $stmt->execute([':id' => $custId, ':uid' => $userId]);
        $rows = $stmt->fetchAll();

        $running = 0.0;
        foreach ($rows as &$row) {
            $amt = (float)$row['amount'];
            if ($row['type'] === 'credit') {
                $running += $amt;
            } else {
                $running -= $amt;
            }
            $row['running_balance'] = round($running, 2);
        }
        unset($row);

        // Reverse to display newest first
        return array_reverse($rows);
    }

    /**
     * Find a single transaction by primary ID, scoped to user_id via customers.
     *
     * @param int $txnId
     * @param int $userId
     * @return array|false
     */
    public static function findById(int $txnId, int $userId): array|false
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT t.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address, c.balance, c.credit_limit
             FROM transactions t
             JOIN customers c ON t.cust_id = c.cust_id
             WHERE t.txn_id = :id AND c.user_id = :uid
             LIMIT 1'
        );
        $stmt->execute([':id' => $txnId, ':uid' => $userId]);
        $txn = $stmt->fetch();

        if (!$txn) {
            return false;
        }

        // Calculate balance after this transaction occurred
        $stmtBal = $pdo->prepare(
            "SELECT 
                COALESCE(SUM(CASE WHEN t2.type = 'credit' THEN t2.amount ELSE -t2.amount END), 0.00) AS balance_after
             FROM transactions t2
             JOIN customers c ON t2.cust_id = c.cust_id
             WHERE t2.cust_id = :cust_id AND c.user_id = :uid AND t2.txn_id <= :txn_id"
        );
        $stmtBal->execute([
            ':cust_id' => $txn['cust_id'],
            ':uid'     => $userId,
            ':txn_id'  => $txn['txn_id'],
        ]);
        $txn['balance_after'] = (float)$stmtBal->fetchColumn();

        return $txn;
    }
}
