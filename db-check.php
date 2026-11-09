<?php
declare(strict_types=1);

/**
 * EquityFlow - Database Connection Verification
 *
 * Direct throwaway diagnostic page to verify PDO connection to MySQL/MariaDB.
 * Outputs "Connected" with environment details or a user-friendly error message.
 * Strict rules: HTML5 + CSS3 only, Zero JavaScript, Dark High-Contrast Theme.
 */

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/constants.php';

$status = 'unknown';
$message = '';
$details = [];

try {
    // Attempt singleton PDO connection
    $pdo = Database::getConnection();
    $status = 'connected';
    $message = 'Connected';

    // Retrieve database engine version and table statistics
    $versionQuery = $pdo->query("SELECT VERSION() AS db_version");
    $dbVersion = $versionQuery->fetch()['db_version'] ?? 'Unknown';

    $tableQuery = $pdo->query("SHOW TABLES");
    $tables = $tableQuery->fetchAll(PDO::FETCH_COLUMN);

    $details = [
        'Status'           => 'Connected successfully',
        'Database Engine'  => 'MySQL / MariaDB ' . $dbVersion,
        'Database Name'    => defined('DB_NAME') ? DB_NAME : 'equityflow',
        'Charset'          => defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4',
        'Tables Found'     => count($tables) . ' tables (' . implode(', ', $tables) . ')',
        'Prepared Stmt'    => 'Native PDO (Emulation Disabled)',
    ];
} catch (Throwable $e) {
    $status = 'error';
    $message = 'Connection Failed';
    $details = [
        'Error Summary'    => 'Unable to connect to the database server.',
        'Friendly Hint'    => 'Please ensure MySQL is running in your XAMPP Control Panel and config/config.php contains valid credentials.',
        'Technical Reason' => $e->getMessage(),
    ];
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ডাটাবেজ সংযোগ যাচাই (DB Check) - <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            --bg-base: #0b0f19;
            --bg-surface: #151d2e;
            --border-color: #2a3754;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-success: #10b981;
            --accent-danger: #ef4444;
            --code-bg: #030712;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            background-color: var(--bg-base);
            color: var(--text-primary);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2.5rem;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.6);
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .badge {
            display: inline-block;
            font-weight: 700;
            font-size: 1.5rem;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .badge-success {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--accent-success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .badge-error {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--accent-danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        p.subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
        }
        .info-table th, .info-table td {
            text-align: left;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
        }
        .info-table th {
            color: var(--text-secondary);
            width: 35%;
            font-weight: 600;
        }
        .info-table td {
            color: var(--text-primary);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            word-break: break-all;
        }
        .actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        .btn {
            text-decoration: none;
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .btn-primary {
            background-color: var(--accent-success);
            color: #ffffff;
        }
        .footer-tag {
            margin-top: 2rem;
            font-size: 0.75rem;
            color: #64748b;
            text-align: center;
            border-top: 1px solid #1e293b;
            padding-top: 1rem;
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="header">
            <?php if ($status === 'connected'): ?>
                <div class="badge badge-success">✓ <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                <h1>ডাটাবেজ সংযোগ সফল (Database Online)</h1>
                <p class="subtitle">PDO Singleton সংযোগ সফলভাবে প্রতিষ্ঠিত হয়েছে।</p>
            <?php else: ?>
                <div class="badge badge-error">✕ <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                <h1>ডাটাবেজ সংযোগে সমস্যা (Database Offline)</h1>
                <p class="subtitle">সার্ভারের সাথে সংযোগ স্থাপন করা যায়নি।</p>
            <?php endif; ?>
        </div>

        <table class="info-table">
            <tbody>
                <?php foreach ($details as $label => $val): ?>
                    <tr>
                        <th><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></th>
                        <td><?= htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="actions">
            <a href="index.php?page=dashboard" class="btn btn-primary">ড্যাশবোর্ডে ফিরে যান / Dashboard</a>
        </div>

        <div class="footer-tag"><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars(APP_TAGLINE, ENT_QUOTES, 'UTF-8') ?></div>
    </main>
</body>
</html>
