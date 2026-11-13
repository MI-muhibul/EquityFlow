<?php
/**
 * TC-ROLL-01: Atomic Rollback Failure Simulation Test
 * Run from project root: php database/test_rollback.php
 */
declare(strict_types=1);

define('PROJECT_ROOT', __DIR__ . '/..');
require_once PROJECT_ROOT . '/config/Database.php';
require_once PROJECT_ROOT . '/models/Transaction.php';

$pdo    = Database::getConnection();
$custId  = 1;   // Rahim Ahmed — balance 1850.00
$ownerId = 1;

// ── SNAPSHOT BEFORE ──────────────────────────────────────────────────────────
$before = $pdo->query("SELECT balance FROM customers WHERE cust_id = {$custId}")->fetch();
$txnsBefore   = (int)$pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
$ledgerBefore = (int)$pdo->query("SELECT COUNT(*) FROM ledger_entries")->fetchColumn();

echo "=== TC-ROLL-01: Atomic Rollback Verification ===\n\n";
echo "PRE-TEST SNAPSHOT:\n";
echo "  Customer #{$custId} balance : ৳" . number_format((float)$before['balance'], 2) . "\n";
echo "  transactions rows           : {$txnsBefore}\n";
echo "  ledger_entries rows         : {$ledgerBefore}\n\n";

// ── SIMULATE FAILURE ─────────────────────────────────────────────────────────
echo "RUNNING: Transaction::record(custId=1, ownerId=1, amount=500, type='credit', simulateFailure=true)\n";
$result = Transaction::record(
    custId        : $custId,
    ownerId       : $ownerId,
    directAmount  : 500.0,
    type          : 'credit',
    note          : 'Test Rollback - should not persist',
    items         : [],
    simulateFailure: true
);
echo "RETURN VALUE: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// ── SNAPSHOT AFTER ───────────────────────────────────────────────────────────
$after  = $pdo->query("SELECT balance FROM customers WHERE cust_id = {$custId}")->fetch();
$txnsAfter   = (int)$pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
$ledgerAfter = (int)$pdo->query("SELECT COUNT(*) FROM ledger_entries")->fetchColumn();

echo "POST-TEST SNAPSHOT:\n";
echo "  Customer #{$custId} balance : ৳" . number_format((float)$after['balance'], 2) . "\n";
echo "  transactions rows           : {$txnsAfter}\n";
echo "  ledger_entries rows         : {$ledgerAfter}\n\n";

// ── ASSERTIONS ───────────────────────────────────────────────────────────────
$pass = true;

$checks = [
    'simulateFailure flag caught'    => $result['error_key'] === 'SIMULATED_TRANSACTION_FAILURE',
    'success = false'                => $result['success'] === false,
    'txn_id = null'                  => $result['txn_id'] === null,
    'balance unchanged'              => (float)$after['balance'] === (float)$before['balance'],
    'zero new transactions inserted' => $txnsAfter === $txnsBefore,
    'zero new ledger rows inserted'  => $ledgerAfter === $ledgerBefore,
];

echo "ASSERTIONS:\n";
foreach ($checks as $label => $ok) {
    echo "  [" . ($ok ? 'PASS ✅' : 'FAIL ❌') . "] {$label}\n";
    if (!$ok) $pass = false;
}

echo "\n" . ($pass ? '✅ TC-ROLL-01: ALL ASSERTIONS PASSED' : '❌ TC-ROLL-01: ONE OR MORE ASSERTIONS FAILED') . "\n";
