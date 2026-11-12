<?php
declare(strict_types=1);

/**
 * controllers/TransactionController.php
 * -----------------------------------------------------------------------------
 * Controller handling:
 *   - Listing recent transactions (?page=transactions)
 *   - Showing form to record credit sale or payment (?page=transactions&action=create)
 *   - Processing POST submissions with CSRF verification and PDO atomic transactions
 * -----------------------------------------------------------------------------
 * Rules:
 *   - require_login() enforced
 *   - Zero JavaScript
 *   - Prepared statements only
 *   - All output escaped with e()
 *   - Formatted money with ৳ and 2 decimals
 *   - Language strings loaded from lang files via t()
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../helpers/format.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/Transaction.php';

class TransactionController
{
    /**
     * Dispatcher method called by index.php router.
     */
    public function handleRequest(): void
    {
        require_login();

        $action = isset($_GET['action']) && is_string($_GET['action']) ? trim($_GET['action']) : 'index';

        switch ($action) {
            case 'create':
                $this->create();
                break;
            case 'index':
            default:
                $this->index();
                break;
        }
    }

    /**
     * List recent transactions with customer details.
     */
    public function index(): void
    {
        $transactions = Transaction::all(50);
        $pageTitle    = t('nav_transactions');
        $activePage   = 'transactions';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/transactions/index.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Display transaction creation form and handle POST submission.
     */
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF Verification
            $token = $_POST['csrf_token'] ?? '';
            if (!csrf_verify(is_string($token) ? $token : null)) {
                flash_set('error', t('error_csrf_invalid'));
                header('Location: index.php?page=transactions&action=create');
                exit;
            }

            // Extract and clean input parameters
            $custId = isset($_POST['cust_id']) ? (int)$_POST['cust_id'] : 0;
            $type   = isset($_POST['type']) && is_string($_POST['type']) ? trim($_POST['type']) : '';
            $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;
            $note   = isset($_POST['note']) && is_string($_POST['note']) ? trim($_POST['note']) : null;
            $ownerId = current_user_id() ?? 1;

            // Execute transactional recording in model
            $result = Transaction::record($custId, $ownerId, $amount, $type, $note);

            if (!$result['success']) {
                $errorMsg = t($result['error_key'] ?? 'error_db_error');
                flash_set('error', $errorMsg);
                // Retain selected customer in query string if provided
                header('Location: index.php?page=transactions&action=create' . ($custId > 0 ? '&cust_id=' . $custId : ''));
                exit;
            }

            flash_set('success', t('msg_txn_recorded'));
            header('Location: index.php?page=transactions');
            exit;
        }

        // GET request: show form
        $customers      = Customer::all();
        $preselectCustId = isset($_GET['cust_id']) ? (int)$_GET['cust_id'] : 0;
        $pageTitle      = t('txn_new_title');
        $activePage     = 'transactions';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/transactions/create.php';
        require __DIR__ . '/../views/layout/footer.php';
    }
}
