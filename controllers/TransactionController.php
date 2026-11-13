<?php
declare(strict_types=1);

/**
 * controllers/TransactionController.php
 * -----------------------------------------------------------------------------
 * Controller handling:
 *   - Listing recent transactions (?page=transactions)
 *   - Showing form to record credit sale (with 5 product rows) or payment
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
require_once __DIR__ . '/../models/Product.php';
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
     * List transactions — with optional GET filters and pagination.
     */
    public function index(): void
    {
        // --- Collect filter values -------------------------------------------
        $filters = [
            'start_date' => trim(strip_tags($_GET['start_date'] ?? '')),
            'end_date'   => trim(strip_tags($_GET['end_date']   ?? '')),
            'cust_id'    => (int) ($_GET['cust_id'] ?? 0),
            'type'       => trim(strip_tags($_GET['type'] ?? '')),
        ];

        $page = max(1, (int) ($_GET['p'] ?? 1));

        $result = Transaction::paginate($filters, $page, 20);

        $transactions = $result['items'];
        $pagination   = $result;

        // Customer dropdown list for the filter form
        $allCustomers = Customer::all();

        $pageTitle  = t('nav_transactions');
        $activePage = 'transactions';

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
            $directAmount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;
            $note   = isset($_POST['note']) && is_string($_POST['note']) ? trim($_POST['note']) : null;
            $ownerId = current_user_id() ?? 1;

            // Extract items if submitted for credit sale
            $items = [];
            if ($type === 'credit' && isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $row) {
                    $prodId = isset($row['prod_id']) ? (int)$row['prod_id'] : 0;
                    $qty    = isset($row['qty']) ? (float)$row['qty'] : 0.0;
                    if ($prodId > 0 && $qty > 0.0) {
                        $items[] = [
                            'prod_id' => $prodId,
                            'qty'     => $qty,
                        ];
                    }
                }
            }

            // Execute transactional recording in model
            $result = Transaction::record($custId, $ownerId, $directAmount, $type, $note, $items);

            if (!$result['success']) {
                $errorMsg = t($result['error_key'] ?? 'error_db_error');

                // Replace placeholders if any
                if (!empty($result['error_params'])) {
                    foreach ($result['error_params'] as $k => $v) {
                        $errorMsg = str_replace($k, $v, $errorMsg);
                    }
                }

                flash_set('error', $errorMsg);
                // Retain selected customer in query string if provided
                header('Location: index.php?page=transactions&action=create' . ($custId > 0 ? '&cust_id=' . $custId : ''));
                exit;
            }

            flash_set('success', t('msg_txn_recorded'));
            header('Location: index.php?page=transactions');
            exit;
        }

        // GET request: show form with customer and product lists
        $customers       = Customer::all();
        $products        = Product::all();
        $preselectCustId = isset($_GET['cust_id']) ? (int)$_GET['cust_id'] : 0;
        $pageTitle       = t('txn_new_title');
        $activePage      = 'transactions';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/transactions/create.php';
        require __DIR__ . '/../views/layout/footer.php';
    }
}
