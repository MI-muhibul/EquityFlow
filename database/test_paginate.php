<?php
/**
 * Quick integration test: paginate() methods on all three models.
 * Run: php database/test_paginate.php
 */
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Product.php';

$pass = 0;
$fail = 0;
$userId = 1;

function assert_eq(mixed $expected, mixed $actual, string $label): void {
    global $pass, $fail;
    if ($expected == $actual) {
        echo "  ✅ PASS  {$label}\n";
        $pass++;
    } else {
        echo "  ❌ FAIL  {$label} — expected " . var_export($expected, true)
           . ", got " . var_export($actual, true) . "\n";
        $fail++;
    }
}

echo "\n=== Customer::paginate() ===\n";

// 1. No filters — page 1 of many
$r = Customer::paginate($userId, [], 1, 20);
assert_eq(true, $r['total'] > 0, 'total > 0');
assert_eq(true, count($r['items']) > 0 && count($r['items']) <= 20, 'page 1 returns <= 20 rows');
assert_eq(1,    $r['page'],           'page is 1');
assert_eq(20,   $r['per_page'],       'per_page is 20');
assert_eq(true, $r['total_pages'] >= 1, 'total_pages >= 1');

// 2. Filter by name
$sampleName = $r['items'][0]['name'] ?? 'Rahim';
$r2 = Customer::paginate($userId, ['name' => $sampleName], 1, 20);
assert_eq(true, $r2['total'] > 0, 'name filter finds rows');
assert_eq(true, str_contains(strtolower($r2['items'][0]['name']), strtolower($sampleName)), 'row contains search term');

// 3. Filter by phone prefix
$r3 = Customer::paginate($userId, ['phone' => '01'], 1, 20);
assert_eq(true, $r3['total'] > 0, 'phone filter finds rows');

// 4. No results — made-up name
$r4 = Customer::paginate($userId, ['name' => 'ZZZZZNOEXIST'], 1, 20);
assert_eq(0, $r4['total'], 'zero results for unmatched name');
assert_eq(0, count($r4['items']), 'items array empty when no results');
assert_eq(1, $r4['total_pages'], 'total_pages is 1 even with zero results');

// 5. Page 2
$r5 = Customer::paginate($userId, [], 2, 20);
assert_eq(true, count($r5['items']) <= 20, 'page 2 returns <= 20 rows');
assert_eq(2, $r5['page'], 'page is 2');

echo "\n=== Transaction::paginate() ===\n";

// 6. No filters
$rt = Transaction::paginate($userId, [], 1, 20);
assert_eq(true, $rt['total'] > 0, 'transaction total > 0');
assert_eq(true, count($rt['items']) <= 20, 'at most 20 items returned');
// Every row must have customer_name from JOIN
if (!empty($rt['items'])) {
    assert_eq(true, isset($rt['items'][0]['customer_name']), 'customer_name JOIN present');
}

// 7. Type filter: credit
$rtc = Transaction::paginate($userId, ['type' => 'credit'], 1, 20);
assert_eq(true, $rtc['total'] > 0, 'credit filter finds rows');
if (!empty($rtc['items'])) {
    assert_eq('credit', $rtc['items'][0]['type'], 'filtered row has type=credit');
}

// 8. Type filter: payment
$rtp = Transaction::paginate($userId, ['type' => 'payment'], 1, 20);
assert_eq(true, $rtp['total'] >= 0, 'payment filter runs without error');

// 9. Date range
$today = date('Y-m-d');
$rdate = Transaction::paginate($userId, ['start_date' => '2020-01-01', 'end_date' => $today], 1, 20);
assert_eq(true, $rdate['total'] > 0, 'date range filter finds rows');

// 10. Invalid type — should return all (no filter applied)
$rinv = Transaction::paginate($userId, ['type' => 'bogus'], 1, 20);
assert_eq($rt['total'], $rinv['total'], 'invalid type defaults to all rows');

echo "\n=== Product::paginate() ===\n";

// 11. No filters
$rp = Product::paginate($userId, [], 1, 20);
assert_eq(true, $rp['total'] > 0, 'product total > 0');
assert_eq(true, count($rp['items']) <= 20, 'products on page 1 <= 20');
assert_eq(true, $rp['total_pages'] >= 1, 'at least one page for products');

// 12. Name filter
$sampleProd = $rp['items'][0]['name'] ?? 'তেল';
$rpn = Product::paginate($userId, ['name' => $sampleProd], 1, 20);
assert_eq(true, $rpn['total'] > 0, 'name filter matches product name');

// 13. Status: out_of_stock
$rpos = Product::paginate($userId, ['status' => 'out_of_stock'], 1, 20);
assert_eq(true, $rpos['total'] >= 0, 'out_of_stock filter runs');

// 14. Status: in_stock
$rpis = Product::paginate($userId, ['status' => 'in_stock'], 1, 20);
assert_eq(true, $rpis['total'] >= 0, 'in_stock filter runs');

// 15. allCategories() returns array
$cats = Product::allCategories($userId);
assert_eq(true, count($cats) >= 1, 'allCategories returns >= 1 item');

echo "\n=== Product metrics attached ===\n";
if (!empty($rp['items'])) {
    $item = $rp['items'][0];
    assert_eq(true, isset($item['status_info']),    'status_info attached');
    assert_eq(true, isset($item['profit_metrics']), 'profit_metrics attached');
}

echo "\n=====================================\n";
echo "PASS: {$pass}   FAIL: {$fail}\n\n";
