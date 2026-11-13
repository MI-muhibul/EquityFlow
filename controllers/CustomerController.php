<?php
declare(strict_types=1);

/**
 * controllers/CustomerController.php
 * -----------------------------------------------------------------------------
 * Controller handling customer operations:
 *   - List customers (?page=customers)
 *   - Create customer (?page=customers&action=create)
 *   - Edit customer (?page=customers&action=edit&id=X)
 *   - Profile view (?page=customers&action=profile&id=X)
 *   - Delete confirmation & POST delete (?page=customers&action=deleteConfirm&id=X)
 *   - Transaction history with running balance (?page=customers&action=history&id=X)
 *   - Transaction receipt view (?page=customers&action=receipt&id=X)
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
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../config/Database.php';

class CustomerController
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
            case 'edit':
                $this->edit();
                break;
            case 'profile':
                $this->profile();
                break;
            case 'history':
                $this->history();
                break;
            case 'receipt':
                $this->receipt();
                break;
            case 'deleteConfirm':
                $this->deleteConfirm();
                break;
            case 'delete':
                $this->delete();
                break;
            case 'index':
            default:
                $this->index();
                break;
        }
    }

    /**
     * List customers — with optional GET filters (name, phone) and pagination.
     */
    public function index(): void
    {
        // --- Collect GET filter values (strip tags, never trust raw input) ---
        $filters = [
            'name'  => trim(strip_tags($_GET['name']  ?? '')),
            'phone' => trim(strip_tags($_GET['phone'] ?? '')),
        ];

        // --- Current page (default 1, minimum 1) ---
        $page = max(1, (int) ($_GET['p'] ?? 1));

        // --- Paginated query --------------------------------------------------
        $result = Customer::paginate($filters, $page, 20);

        // Unpack for the view
        $customers  = $result['items'];
        $pagination = $result;    // view receives the full pagination array
        $pageTitle  = t('customer_title');
        $activePage = 'customers';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/customers/index.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Create new customer.
     */
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify($_POST['csrf_token'] ?? '')) {
                flash_set('error', t('error_csrf_invalid'));
                header('Location: index.php?page=customers&action=create');
                exit;
            }

            $name        = trim($_POST['name'] ?? '');
            $phone       = trim($_POST['phone'] ?? '');
            $address     = trim($_POST['address'] ?? '');
            $creditLimit = (float)($_POST['credit_limit'] ?? 0.0);
            $joinDate    = date('Y-m-d');

            $errors = [];
            if ($name === '') {
                $errors[] = t('error_required_field');
            }
            if (!preg_match('/^01[3-9]\d{8}$/', $phone)) {
                $errors[] = t('error_invalid_phone');
            }
            if (Customer::phoneExists($phone)) {
                $errors[] = t('error_phone_exists');
            }
            if ($creditLimit < 0.0) {
                $errors[] = t('error_credit_limit_invalid');
            }

            if (!empty($errors)) {
                foreach ($errors as $e) {
                    flash_set('error', $e);
                }
                header('Location: index.php?page=customers&action=create');
                exit;
            }

            try {
                Customer::create($name, $phone, $address, $creditLimit, $joinDate);
                flash_set('success', t('msg_saved_success'));
                header('Location: index.php?page=customers');
                exit;
            } catch (PDOException $ex) {
                flash_set('error', t('error_db_error'));
                header('Location: index.php?page=customers&action=create');
                exit;
            }
        }

        $pageTitle  = t('customer_add_title');
        $activePage = 'customers';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/customers/add.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Edit existing customer.
     */
    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $customer = Customer::findById($id);

        if (!$customer) {
            flash_set('error', t('error_customer_not_found'));
            header('Location: index.php?page=customers');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify($_POST['csrf_token'] ?? '')) {
                flash_set('error', t('error_csrf_invalid'));
                header("Location: index.php?page=customers&action=edit&id={$id}");
                exit;
            }

            $name        = trim($_POST['name'] ?? '');
            $phone       = trim($_POST['phone'] ?? '');
            $address     = trim($_POST['address'] ?? '');
            $creditLimit = (float)($_POST['credit_limit'] ?? 0.0);

            $errors = [];
            if ($name === '') {
                $errors[] = t('error_required_field');
            }
            if (!preg_match('/^01[3-9]\d{8}$/', $phone)) {
                $errors[] = t('error_invalid_phone');
            }
            if (Customer::phoneExists($phone, $id)) {
                $errors[] = t('error_phone_exists');
            }
            if ($creditLimit < 0.0) {
                $errors[] = t('error_credit_limit_invalid');
            }

            if (!empty($errors)) {
                foreach ($errors as $e) {
                    flash_set('error', $e);
                }
                header("Location: index.php?page=customers&action=edit&id={$id}");
                exit;
            }

            Customer::update($id, $name, $phone, $address, $creditLimit);
            flash_set('success', t('msg_updated_success'));
            header("Location: index.php?page=customers&action=profile&id={$id}");
            exit;
        }

        $pageTitle  = t('btn_edit') . ' — ' . $customer['name'];
        $activePage = 'customers';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/customers/edit.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Customer profile page.
     */
    public function profile(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $customer = Customer::findById($id);

        if (!$customer) {
            flash_set('error', t('error_customer_not_found'));
            header('Location: index.php?page=customers');
            exit;
        }

        $pageTitle  = $customer['name'] . ' — ' . t('customer_profile_title');
        $activePage = 'customers';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/customers/profile.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Customer transaction history (Khata) with running balance.
     */
    public function history(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $customer = Customer::findById($id);

        if (!$customer) {
            flash_set('error', t('error_customer_not_found'));
            header('Location: index.php?page=customers');
            exit;
        }

        $transactions = Transaction::findByCustomerWithRunningBalance($id);
        $pageTitle    = $customer['name'] . ' — ' . t('customer_history_title');
        $activePage   = 'customers';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/customers/history.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Transaction receipt screen view.
     */
    public function receipt(): void
    {
        $txnId = (int)($_GET['id'] ?? 0);
        $transaction = Transaction::findById($txnId);

        if (!$transaction) {
            flash_set('error', t('receipt_not_found'));
            header('Location: index.php?page=customers');
            exit;
        }

        // Fetch shop details from settings table
        $pdo = Database::getConnection();
        $settings = $pdo->query('SELECT skey, svalue FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);

        $shopName    = $settings['shop_name'] ?? 'EquityFlow Digital Khata';
        $shopPhone   = $settings['shop_phone'] ?? '';
        $shopAddress = $settings['shop_address'] ?? '';
        $footerNote  = $settings['invoice_footer'] ?? '';

        $pageTitle  = t('receipt_title') . ' #' . $transaction['txn_id'];
        $activePage = 'customers';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/customers/receipt.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Render delete confirmation screen (Zero JS, GET).
     */
    public function deleteConfirm(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $customer = Customer::findById($id);

        if (!$customer) {
            flash_set('error', t('error_customer_not_found'));
            header('Location: index.php?page=customers');
            exit;
        }

        $pageTitle  = t('btn_delete') . ' — ' . $customer['name'];
        $activePage = 'customers';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/customers/delete_confirm.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Process delete (POST only, CSRF verified, balance guard).
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=customers');
            exit;
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            flash_set('error', t('error_csrf_invalid'));
            header('Location: index.php?page=customers');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $customer = Customer::findById($id);

        if (!$customer) {
            flash_set('error', t('error_customer_not_found'));
            header('Location: index.php?page=customers');
            exit;
        }

        // Rule: Refuse deletion unless balance = 0
        if ((float)$customer['balance'] != 0.0) {
            flash_set('error', t('error_customer_has_due'));
            header("Location: index.php?page=customers&action=profile&id={$id}");
            exit;
        }

        Customer::delete($id);
        flash_set('success', t('msg_deleted_success'));
        header('Location: index.php?page=customers');
        exit;
    }
}
