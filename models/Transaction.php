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
     *
     * @param int $custId
     * @param int $ownerId (user_id recording the transaction)
     * @param float $amount
     * @param string $type ('credit' or 'payment')
     * @param string|null $note
     * @param bool $simulateFailure If true, throws exception before commit to test rollback
     * @return array{success: bool, txn_id: ?int, new_balance: ?float, error_key: ?string}
     */
    public static function record(
        int $custId,
        int $ownerId,
        float $amount,
        string $type,
        ?string $note = null,
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

        // Rule 1: amount > 0
        if ($amount <= 0.0) {
            return [
                'success'     => false,
                'txn_id'      => null,
                'new_balance' => null,
                'error_key'   => 'error_amount_positive',
            ];
        }

        $pdo = Database::getConnection();

        try {
            // Begin transactional boundary
            $pdo->beginTransaction();

            // Lock customer record for atomic update
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

            // Validate business rules based on transaction type
            if ($type === 'credit') {
                $newBalance = round($currentBalance + $amount, 2);

                // Rule 2: credit sale rejected if new balance would exceed credit_limit
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
                // Rule 3: payment cannot exceed current balance
                if ($amount > $currentBalance) {
                    $pdo->rollBack();
                    return [
                        'success'     => false,
                        'txn_id'      => null,
                        'new_balance' => null,
                        'error_key'   => 'error_payment_exceeds_balance',
                    ];
                }

                $newBalance = round($currentBalance - $amount, 2);
            }

            // 1. Insert transaction row
            $sqlTxn = 'INSERT INTO transactions (cust_id, amount, type, note, created_at)
                       VALUES (:cust_id, :amount, :type, :note, NOW())';
            $stmtTxn = $pdo->prepare($sqlTxn);
            $stmtTxn->execute([
                ':cust_id' => $custId,
                ':amount'  => $amount,
                ':type'    => $type,
                ':note'    => $note !== null && trim($note) !== '' ? trim($note) : null,
            ]);

            $txnId = (int)$pdo->lastInsertId();

            // 2. Update customer balance
            $sqlUpd = 'UPDATE customers SET balance = :new_balance WHERE cust_id = :id';
            $stmtUpd = $pdo->prepare($sqlUpd);
            $stmtUpd->execute([
                ':new_balance' => $newBalance,
                ':id'          => $custId,
            ]);

            // 3. Insert audit ledger_entries row
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
     * Get transactions for a specific customer.
     *
     * @param int $custId
     * @return array
     */
    public static function findByCustomer(int $custId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM transactions WHERE cust_id = :id ORDER BY txn_id DESC'
        );
        $stmt->execute([':id' => $custId]);
        return $stmt->fetchAll();
    }

    /**
     * Find a single transaction by primary ID with customer info.
     *
     * @param int $txnId
     * @return array|false
     */
    public static function findById(int $txnId): array|false
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT t.*, c.name AS customer_name, c.phone AS customer_phone, c.balance, c.credit_limit
             FROM transactions t
             JOIN customers c ON t.cust_id = c.cust_id
             WHERE t.txn_id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $txnId]);
        return $stmt->fetch();
    }
}
