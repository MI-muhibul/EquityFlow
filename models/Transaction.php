<?php
declare(strict_types=1);

/**
 * models/Transaction.php
 * -----------------------------------------------------------------------------
 * Model handling Khata transactions (credit sale / payment).
 *
 * Rules:
 *   - Uses PDO beginTransaction(), commit(), rollBack().
 *   - Runs SELECT ... FOR UPDATE on customers row to lock and fetch fresh state.
 *   - If products are provided for a credit sale:
 *       • SELECT prod_id, name, retail_price, cost_price, stock FROM products FOR UPDATE
 *       • Rejects if requested quantity > available stock.
 *       • Deducts stock: UPDATE products SET stock = stock - qty.
 *       • Computes total transaction amount = sum(qty * retail_price).
 *       • Inserts line items into `sale_items`.
 *   - Validates:
 *       1. amount > 0
 *       2. type is either 'credit' or 'payment'
 *       3. customer must exist
 *       4. credit sale rejected if (balance + amount) > credit_limit
 *       5. payment rejected if amount > current balance
 *   - Inserts row into `transactions`.
 *   - Updates `customers.balance` (+ amount for credit, - amount for payment).
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
     * @param int $ownerId (user_id recording the transaction)
     * @param float $directAmount Direct amount entered (ignored if items are provided)
     * @param string $type ('credit' or 'payment')
     * @param string|null $note
     * @param array<int, array{prod_id: int, qty: float}> $items Optional product lines for credit sales
     * @param bool $simulateFailure If true, throws exception before commit to test rollback
     * @return array{success: bool, txn_id: ?int, new_balance: ?float, error_key: ?string, error_params?: array}
     */
    public static function record(
        int $custId,
        int $ownerId,
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

            // 1. Lock and validate customer record for atomic update
            $stmtCust = $pdo->prepare('SELECT cust_id, balance, credit_limit FROM customers WHERE cust_id = :id FOR UPDATE');
            $stmtCust->execute([':id' => $custId]);
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
                // Prepare product lock statement
                $stmtProd = $pdo->prepare('SELECT prod_id, name, retail_price, cost_price, stock FROM products WHERE prod_id = :id FOR UPDATE');
                $stmtStockReduce = $pdo->prepare('UPDATE products SET stock = stock - :qty WHERE prod_id = :id');

                foreach ($items as $item) {
                    $prodId = (int)($item['prod_id'] ?? 0);
                    $qty    = (float)($item['qty'] ?? 0.0);

                    // Skip empty rows
                    if ($prodId <= 0 || $qty <= 0.0) {
                        continue;
                    }

                    $stmtProd->execute([':id' => $prodId]);
                    $product = $stmtProd->fetch();

                    if (!$product) {
                        $pdo->rollBack();
                        return [
                            'success'     => false,
                            'txn_id'      => null,
                            'new_balance' => null,
                            'error_key'   => 'error_product_not_found',
                        ];
                    }

                    $availableStock = (int)$product['stock'];

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
                        ':qty' => (int)$qty,
                        ':id'  => $prodId,
                    ]);

                    $processedItems[] = [
                        'prod_id'    => $prodId,
                        'qty'        => $qty,
                        'unit_price' => $unitPrice,
                        'unit_cost'  => $unitCost,
                    ];
                }
            }

            // Determine final transaction amount:
            // Use computed amount from products if any products were validly selected,
            // otherwise use direct amount.
            $finalAmount = !empty($processedItems) ? round($computedAmount, 2) : round($directAmount, 2);

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
                if ($newBalance > $creditLimit) {
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

            // 6. Update customer balance
            $sqlUpd = 'UPDATE customers SET balance = :new_balance WHERE cust_id = :id';
            $stmtUpd = $pdo->prepare($sqlUpd);
            $stmtUpd->execute([
                ':new_balance' => $newBalance,
                ':id'          => $custId,
            ]);

            // 7. Insert audit ledger_entries row
            $sqlLedger = 'INSERT INTO ledger_entries (owner_id, txn_id, created_at)
                          VALUES (:owner_id, :txn_id, NOW())';
            $stmtLedger = $pdo->prepare($sqlLedger);
            $stmtLedger->execute([
                ':owner_id' => $ownerId,
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

    /**
     * Get recent transactions with customer details.
     *
     * @param int $limit
     * @return array
     */
    public static function all(int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT t.*, c.name AS customer_name, c.phone AS customer_phone
             FROM transactions t
             JOIN customers c ON t.cust_id = c.cust_id
             ORDER BY t.txn_id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get transactions for a specific customer with running balance computed.
     * Returned newest first.
     *
     * @param int $custId
     * @return array
     */
    public static function findByCustomerWithRunningBalance(int $custId): array
    {
        $pdo = Database::getConnection();
        // Fetch oldest first to compute running balance from the start
        $stmt = $pdo->prepare(
            'SELECT * FROM transactions WHERE cust_id = :id ORDER BY txn_id ASC'
        );
        $stmt->execute([':id' => $custId]);
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
     * Find a single transaction by primary ID with customer info and balance after.
     *
     * @param int $txnId
     * @return array|false
     */
    public static function findById(int $txnId): array|false
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT t.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address, c.balance, c.credit_limit
             FROM transactions t
             JOIN customers c ON t.cust_id = c.cust_id
             WHERE t.txn_id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $txnId]);
        $txn = $stmt->fetch();

        if (!$txn) {
            return false;
        }

        // Calculate balance after this transaction occurred:
        // Sum of all credits up to this txn_id minus sum of all payments up to this txn_id
        $stmtBal = $pdo->prepare(
            "SELECT 
                COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END), 0.00) AS balance_after
             FROM transactions 
             WHERE cust_id = :cust_id AND txn_id <= :txn_id"
        );
        $stmtBal->execute([
            ':cust_id' => $txn['cust_id'],
            ':txn_id'  => $txn['txn_id'],
        ]);
        $txn['balance_after'] = (float)$stmtBal->fetchColumn();

        return $txn;
    }
}
