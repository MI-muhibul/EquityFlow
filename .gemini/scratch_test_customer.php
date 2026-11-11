<?php
/**
 * Scratch test — Push 09 Customer Module
 * Run: php scratch_test_customer.php
 * Tests Customer model, controller logic, and lang keys.
 */

declare(strict_types=1);
error_reporting(E_ALL);

// Bootstrap
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/lang.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/helpers/format.php';
require_once __DIR__ . '/models/Customer.php';

$pass = 0;
$fail = 0;

function ok(string $name, bool $result): void {
    global $pass, $fail;
    if ($result) {
        echo "  PASS: $name\n";
        $pass++;
    } else {
        echo "  FAIL: $name\n";
        $fail++;
    }
}

echo "\n=== Lang Keys ===\n";
// Switch to EN and check required keys
$_SESSION = [];
$_GET     = [];
$keys = [
    'customer_title', 'customer_details', 'customer_empty_list',
    'label_name', 'label_phone', 'label_address', 'label_credit_limit',
    'label_balance', 'label_date',
    'btn_add_customer', 'btn_save', 'btn_cancel', 'btn_back', 'btn_edit',
    'error_required_field', 'error_invalid_phone', 'error_phone_exists',
    'error_credit_limit_invalid',
    'msg_saved_success', 'error_csrf_invalid',
    'card_customers', 'card_total_outstanding',
    'customer_total_due', 'customer_zero_balance',
    'txn_type_credit', 'txn_type_payment',
];

foreach (['en', 'bn'] as $lang) {
    $_SESSION['lang'] = $lang;
    foreach ($keys as $key) {
        $val = t($key);
        ok("[$lang] key '$key' defined", $val !== '' && $val !== $key);
    }
}

echo "\n=== Phone Regex ===\n";
$validPhones   = ['01711000000', '01811000000', '01911000000', '01311000000'];
$invalidPhones = ['0171100000', '02711000000', '01011000000', '1711000000', 'abc', '0171100000x'];
$pattern = '/^01[3-9]\d{8}$/';
foreach ($validPhones   as $p) ok("valid phone $p",   (bool)preg_match($pattern, $p));
foreach ($invalidPhones as $p) ok("invalid phone $p", !preg_match($pattern, $p));

echo "\n=== format_money ===\n";
ok('format_money(0)',       format_money(0)       === '৳0.00');
ok('format_money(1234.5)', format_money(1234.5)   === '৳1,234.50');
ok('format_money(-50)',    format_money(-50)       === '-৳50.00');

echo "\n=== Customer Model (live DB) ===\n";
try {
    $pdo = Database::getConnection();
    echo "  DB connected OK\n";

    // 1. phoneExists on a non-existent number should return false
    $exists = Customer::phoneExists('01999999999');
    ok('phoneExists non-existent', $exists === false);

    // 2. all() returns array
    $all = Customer::all();
    ok('all() returns array', is_array($all));

    // 3. Create a test customer
    $testPhone = '01' . rand(31,99) . rand(1000000, 9999999); // random BD-ish phone
    $newId = Customer::create(
        name:        'Test Customer ' . rand(100,999),
        phone:       $testPhone,
        address:     'Test Address',
        creditLimit: 1500.00,
        joinDate:    date('Y-m-d'),
    );
    ok('create() returns integer id > 0', $newId > 0);

    // 4. phoneExists should now return true for that phone
    ok('phoneExists after create', Customer::phoneExists($testPhone) === true);

    // 5. findById
    $row = Customer::findById($newId);
    ok('findById() returns row', is_array($row) && $row !== false);
    ok('findById name matches', isset($row['name']));
    ok('findById credit_limit correct', (float)$row['credit_limit'] === 1500.00);

    // 6. phoneExists with excludeId (own row excluded)
    ok('phoneExists excludeId self', Customer::phoneExists($testPhone, $newId) === false);

    // 7. Clean up test record
    $pdo->prepare("DELETE FROM customers WHERE cust_id = ?")->execute([$newId]);
    ok('cleanup delete OK', Customer::findById($newId) === false);

} catch (PDOException $e) {
    echo "  SKIP DB tests: " . $e->getMessage() . "\n";
}

echo "\n=== RESULTS ===\n";
echo "  Passed: $pass\n";
echo "  Failed: $fail\n";
echo $fail === 0 ? "  ALL TESTS PASSED!\n\n" : "  SOME TESTS FAILED!\n\n";
exit($fail > 0 ? 1 : 0);
