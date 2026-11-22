<?php
declare(strict_types=1);

/**
 * test_backup_isolation.php
 * Automated verification of multi-tenant backup scoping and security in EquityFlow.
 */

function curlCmd(string $args): string {
    $cmd = 'curl.exe -s -L ' . $args;
    return (string)shell_exec($cmd);
}

function curlWithHeaders(string $args): array {
    $cmd = 'curl.exe -s -i ' . $args;
    $output = (string)shell_exec($cmd);
    $parts = explode("\r\n\r\n", $output, 2);
    $headers = $parts[0] ?? '';
    $body = $parts[1] ?? '';
    return ['headers' => $headers, 'body' => $body];
}

$cookie1 = __DIR__ . '/cookie1.txt';
$cookie2 = __DIR__ . '/cookie2.txt';
@unlink($cookie1);
@unlink($cookie2);

echo "=== 1. Logging in User 1 (demo@equityflow.test) ===\n";
$loginPage1 = curlCmd("-c \"{$cookie1}\" \"http://localhost/EquityFlow/index.php?page=login\"");
preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage1, $m1);
$token1 = $m1[1] ?? '';
$res1 = curlCmd("-b \"{$cookie1}\" -c \"{$cookie1}\" -d \"csrf_token={$token1}&email=demo@equityflow.test&password=Admin@123\" \"http://localhost/EquityFlow/index.php?page=login\"");
if (strpos($res1, 'Dashboard') !== false || strpos($res1, 'ড্যাশবোর্ড') !== false) {
    echo "[PASS] User 1 logged in successfully.\n";
} else {
    echo "[FAIL] User 1 login failed.\n";
}

echo "\n=== 2. Logging in User 2 (shop2@equityflow.test) ===\n";
$loginPage2 = curlCmd("-c \"{$cookie2}\" \"http://localhost/EquityFlow/index.php?page=login\"");
preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage2, $m2);
$token2 = $m2[1] ?? '';
$res2 = curlCmd("-b \"{$cookie2}\" -c \"{$cookie2}\" -d \"csrf_token={$token2}&email=shop2@equityflow.test&password=Admin@123\" \"http://localhost/EquityFlow/index.php?page=login\"");
if (strpos($res2, 'Dashboard') !== false || strpos($res2, 'ড্যাশবোর্ড') !== false) {
    echo "[PASS] User 2 logged in successfully.\n";
} else {
    echo "[FAIL] User 2 login failed.\n";
}

echo "\n=== 3. User 1 triggers backup creation via POST + CSRF ===\n";
$settings1 = curlCmd("-b \"{$cookie1}\" \"http://localhost/EquityFlow/index.php?page=settings\"");
preg_match('/action="index\.php\?page=settings&amp;action=backup"[\s\S]*?name="csrf_token" value="([^"]+)"/', $settings1, $mCsrf1);
$csrfBackup1 = $mCsrf1[1] ?? '';
$backupCreateRes1 = curlCmd("-b \"{$cookie1}\" -c \"{$cookie1}\" -d \"csrf_token={$csrfBackup1}\" \"http://localhost/EquityFlow/index.php?page=settings&action=backup\"");
echo "User 1 backup creation submitted.\n";

sleep(1);

echo "\n=== 4. User 2 triggers backup creation via POST + CSRF ===\n";
$settings2 = curlCmd("-b \"{$cookie2}\" \"http://localhost/EquityFlow/index.php?page=settings\"");
preg_match('/action="index\.php\?page=settings&amp;action=backup"[\s\S]*?name="csrf_token" value="([^"]+)"/', $settings2, $mCsrf2);
$csrfBackup2 = $mCsrf2[1] ?? '';
$backupCreateRes2 = curlCmd("-b \"{$cookie2}\" -c \"{$cookie2}\" -d \"csrf_token={$csrfBackup2}\" \"http://localhost/EquityFlow/index.php?page=settings&action=backup\"");
echo "User 2 backup creation submitted.\n";

echo "\n=== 5. Listing check: User 1 settings page ===\n";
$settingsView1 = curlCmd("-b \"{$cookie1}\" \"http://localhost/EquityFlow/index.php?page=settings\"");
preg_match_all('/equityflow_backup_user_(\d+)_[\d\-_]+\.sql/', $settingsView1, $list1);
$user1Files = array_values(array_unique($list1[0]));
echo "User 1 sees files: " . json_encode($user1Files) . "\n";
$isolationPass1 = true;
foreach ($list1[1] as $uid) {
    if ($uid !== '1') {
        echo "[FAIL] User 1 sees backup belonging to user {$uid}!\n";
        $isolationPass1 = false;
    }
}
if ($isolationPass1 && !empty($user1Files)) {
    echo "[PASS] User 1 only sees their own backups.\n";
}
$user1BackupFile = $user1Files[0] ?? '';
echo "User 1's backup file: {$user1BackupFile}\n";

echo "\n=== 6. Listing check: User 2 settings page ===\n";
$settingsView2 = curlCmd("-b \"{$cookie2}\" \"http://localhost/EquityFlow/index.php?page=settings\"");
preg_match_all('/equityflow_backup_user_(\d+)_[\d\-_]+\.sql/', $settingsView2, $list2);
$user2Files = array_values(array_unique($list2[0]));
echo "User 2 sees files: " . json_encode($user2Files) . "\n";
$isolationPass2 = true;
foreach ($list2[1] as $uid) {
    if ($uid !== '2') {
        echo "[FAIL] User 2 sees backup belonging to user {$uid}!\n";
        $isolationPass2 = false;
    }
}
if ($isolationPass2 && !empty($user2Files)) {
    echo "[PASS] User 2 only sees their own backups.\n";
}
$user2BackupFile = $user2Files[0] ?? '';
echo "User 2's backup file: {$user2BackupFile}\n";

echo "\n=== 7. User 1 downloads their own backup ===\n";
$down1 = curlCmd("-b \"{$cookie1}\" \"http://localhost/EquityFlow/index.php?page=settings&action=download&file={$user1BackupFile}\"");
echo "Downloaded size: " . strlen($down1) . " bytes\n";
if (strpos($down1, 'CREATE TABLE `users`') !== false || strpos($down1, 'INSERT INTO `users`') !== false) {
    echo "[CRITICAL FAIL] Dump contains users table or password hashes!\n";
} else {
    echo "[PASS] No users table in dump (passwords/hashes protected).\n";
}
if (strpos($down1, 'Scoped User ID : 1') !== false) {
    echo "[PASS] Backup has correct scoped user header for User 1.\n";
}
if (strpos($down1, '`customers`') !== false && strpos($down1, '`products`') !== false) {
    echo "[PASS] Dump contains user's data tables (customers, products, etc.).\n";
}

echo "\n=== 8. Cross-Tenant Download Attack: User 2 tries to download User 1's backup ===\n";
$attackRes = curlWithHeaders("-b \"{$cookie2}\" \"http://localhost/EquityFlow/index.php?page=settings&action=download&file={$user1BackupFile}\"");
if (strpos($attackRes['headers'], '404 Not Found') !== false) {
    echo "[PASS] User 2 blocked with 404 Not Found when requesting User 1's backup!\n";
} else {
    echo "[FAIL] User 2 got response: " . substr($attackRes['headers'], 0, 100) . "\n";
}

echo "\n=== 9. Path Traversal Attack: User 1 attempts directory traversal ===\n";
$traversal1 = curlWithHeaders("-b \"{$cookie1}\" \"http://localhost/EquityFlow/index.php?page=settings&action=download&file=../../config/Database.php\"");
if (strpos($traversal1['headers'], '404 Not Found') !== false) {
    echo "[PASS] Path traversal attack rejected with 404 Not Found!\n";
} else {
    echo "[FAIL] Path traversal was not rejected with 404!\n";
}

$traversal2 = curlWithHeaders("-b \"{$cookie1}\" \"http://localhost/EquityFlow/index.php?page=settings&action=download&file=..%2F..%2Fconfig%2FDatabase.php\"");
if (strpos($traversal2['headers'], '404 Not Found') !== false) {
    echo "[PASS] Encoded path traversal attack rejected with 404 Not Found!\n";
} else {
    echo "[FAIL] Encoded path traversal was not rejected with 404!\n";
}

echo "\n=== 10. CSRF Protection Check: POST to backup action without CSRF ===\n";
$noCsrf = curlCmd("-b \"{$cookie1}\" -d \"some_param=1\" \"http://localhost/EquityFlow/index.php?page=settings&action=backup\"");
if (strpos($noCsrf, 'Invalid CSRF token') !== false || strpos($noCsrf, 'অবৈধ সিএসআরএফ') !== false || strpos($noCsrf, 'CSRF') !== false) {
    echo "[PASS] Backup POST rejected when CSRF token is missing/invalid!\n";
} else {
    echo "[FAIL] Backup POST did not reject missing CSRF token!\n";
}

@unlink($cookie1);
@unlink($cookie2);
echo "\n=== All Tests Completed ===\n";
