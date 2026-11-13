<?php
declare(strict_types=1);

/**
 * controllers/CustomerController.php
 * -----------------------------------------------------------------------------
 * Handles all customer CRUD operations for the logged-in shop owner.
 *
 * Routes:
 *   ?page=customers                           – list (index)
 *   ?page=customers&action=create             – add form + POST handler
 *   ?page=customers&action=edit&id=X          – edit form + POST handler
 *   ?page=customers&action=profile&id=X       – customer profile page
 *   ?page=customers&action=deleteConfirm&id=X – server-rendered confirm page
 *   ?page=customers&action=delete  (POST)     – perform deletion
 *
 * Security rules enforced here:
 *   • require_login()  — session + 30-min timeout
 *   • user_id scoping  — every Customer model call receives $userId
 *   • CSRF token       — verified on every POST
 *   • 404 on wrong owner — findById(id, userId) returns null → 404 view
 *   • Balance guard    — refuse delete unless balance == 0
 *
 * Output rules:
 *   • All dynamic values escaped with e() / htmlspecialchars
 *   • Money formatted as ৳ with 2 decimals via format_money()
 *   • All UI strings from lang files via t()
 *   • Zero JavaScript
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../helpers/format.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../config/Database.php';

class CustomerController
{
    // Logged-in user's ID — set once in handleRequest()
    private int $userId;

    // =========================================================================
    // handleRequest() — router entry point
    // =========================================================================
    public function handleRequest(): void
    {
        require_login(); // redirect to login if unauthenticated or timed-out

        $this->userId = (int)$_SESSION['user_id'];

        $action = isset($_GET['action']) && is_string($_GET['action'])
            ? trim($_GET['action'])
            : 'index';

        match ($action) {
            'create'        => $this->create(),
            'edit'          => $this->edit(),
            'profile'       => $this->profile(),
            'deleteConfirm' => $this->deleteConfirm(),
            'delete'        => $this->delete(),
            default         => $this->index(),
        };
    }

    // =========================================================================
    // index() — customer list with filter + pagination
    // =========================================================================
    public function index(): void
    {
        // Read GET filter values (strip tags, never trust raw input)
        $filters = [
            'name'  => trim(strip_tags($_GET['name']  ?? '')),
            'phone' => trim(strip_tags($_GET['phone'] ?? '')),
        ];

        $page = max(1, (int)($_GET['p'] ?? 1));

        // All queries inside paginate() are scoped to $this->userId
        $result = Customer::paginate($this->userId, $filters, $page, 20);

        $customers  = $result['items'];
        $pagination = $result;
        $pageTitle  = t('customer_title');
        $activePage = 'customers';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/customers/index.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    // =========================================================================
    // create() — add new customer
    // =========================================================================
    public function create(): void
    {
        $old    = [];   // repopulate form on error
        $errors = [];   // field-keyed error messages

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. CSRF check
            if (!csrf_verify($_POST['csrf_token'] ?? '')) {
                flash_set('error', t('error_csrf_invalid'));
                header('Location: index.php?page=customers&action=create');
                exit;
            }

            // 2. Gather & sanitise inputs
            $old         = $_POST;
            $name        = trim($_POST['name']         ?? '');
            $phone       = trim($_POST['phone']        ?? '');
            $address     = trim($_POST['address']      ?? '');
            $creditLimit = (float)($_POST['credit_limit'] ?? 0);
            $joinDate    = date('Y-m-d');

            // 3. Server-side validation
            if ($name === '') {
                $errors['name'] = t('error_required_field');
            }
            // Phone: must be 11 digits starting 013-019
            if (!preg_match('/^01[3-9]\d{8}$/', $phone)) {
                $errors['phone'] = t('error_invalid_phone');
            } elseif (Customer::phoneExists($phone, $this->userId, 0)) {
                // unique per this user
                $errors['phone'] = t('error_phone_exists');
            }
            if ($creditLimit < 0) {
                $errors['credit_limit'] = t('error_credit_limit_invalid');
            }

            if (empty($errors)) {
                try {
                    $custId = Customer::create(
                        $this->userId, $name, $phone, $address, $creditLimit, $joinDate
                    );
                    flash_set('success', t('msg_saved_success'));
                    header("Location: index.php?page=customers&action=profile&id={$custId}");
                    exit;
                } catch (PDOException) {
                    flash_set('error', t('error_db_error'));
                }
            }
        }

        $pageTitle  = t('customer_add_title');
        $activePage = 'customers';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/customers/add.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    // =========================================================================
    // edit() — edit existing customer (must belong to this user)
    // =========================================================================
    public function edit(): void
    {
        $id       = (int)($_GET['id'] ?? 0);
        $customer = Customer::findById($id, $this->userId); // null if wrong owner

        // 404 if the customer doesn't exist or belongs to another user
        if ($customer === null) {
            $this->render404();
            return;
        }

        $old    = $customer; // pre-fill form with current data
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. CSRF check
            if (!csrf_verify($_POST['csrf_token'] ?? '')) {
                flash_set('error', t('error_csrf_invalid'));
                header("Location: index.php?page=customers&action=edit&id={$id}");
                exit;
            }

            // 2. Gather & sanitise
            $old         = $_POST;
            $name        = trim($_POST['name']         ?? '');
            $phone       = trim($_POST['phone']        ?? '');
            $address     = trim($_POST['address']      ?? '');
            $creditLimit = (float)($_POST['credit_limit'] ?? 0);

            // 3. Validation
            if ($name === '') {
                $errors['name'] = t('error_required_field');
            }
            if (!preg_match('/^01[3-9]\d{8}$/', $phone)) {
                $errors['phone'] = t('error_invalid_phone');
            } elseif (Customer::phoneExists($phone, $this->userId, $id)) {
                // excludeId = $id so the existing record is not flagged
                $errors['phone'] = t('error_phone_exists');
            }
            if ($creditLimit < 0) {
                $errors['credit_limit'] = t('error_credit_limit_invalid');
            }

            if (empty($errors)) {
                Customer::update($id, $this->userId, $name, $phone, $address, $creditLimit);
                flash_set('success', t('msg_updated_success'));
                header("Location: index.php?page=customers&action=profile&id={$id}");
                exit;
            }
        }

        $pageTitle  = t('btn_edit') . ' — ' . e($customer['name']);
        $activePage = 'customers';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/customers/edit.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    // =========================================================================
    // profile() — customer profile details + balance
    // =========================================================================
    public function profile(): void
    {
        $id       = (int)($_GET['id'] ?? 0);
        $customer = Customer::findById($id, $this->userId);

        if ($customer === null) {
            $this->render404();
            return;
        }

        $pageTitle  = e($customer['name']) . ' — ' . t('customer_profile_title');
        $activePage = 'customers';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/customers/profile.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    // =========================================================================
    // deleteConfirm() — server-rendered confirmation page (GET, no JS)
    // =========================================================================
    public function deleteConfirm(): void
    {
        $id       = (int)($_GET['id'] ?? 0);
        $customer = Customer::findById($id, $this->userId);

        if ($customer === null) {
            $this->render404();
            return;
        }

        // Show reason why deletion is blocked if balance != 0
        $canDelete   = (float)$customer['balance'] == 0.0;
        $pageTitle   = t('btn_delete') . ' — ' . e($customer['name']);
        $activePage  = 'customers';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/customers/delete_confirm.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    // =========================================================================
    // delete() — POST-only, CSRF + balance guard + user scope
    // =========================================================================
    public function delete(): void
    {
        // Only accept POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=customers');
            exit;
        }

        // 1. CSRF check
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            flash_set('error', t('error_csrf_invalid'));
            header('Location: index.php?page=customers');
            exit;
        }

        $id       = (int)($_POST['id'] ?? 0);
        $customer = Customer::findById($id, $this->userId);

        // 404 if wrong owner
        if ($customer === null) {
            $this->render404();
            return;
        }

        // 2. Refuse deletion unless balance = 0 (show translated reason)
        if ((float)$customer['balance'] != 0.0) {
            flash_set('error', t('error_customer_has_due'));
            header("Location: index.php?page=customers&action=profile&id={$id}");
            exit;
        }

        // 3. Delete — model also enforces user_id + balance = 0 in SQL
        $deleted = Customer::delete($id, $this->userId);

        if ($deleted) {
            flash_set('success', t('msg_deleted_success'));
            header('Location: index.php?page=customers');
        } else {
            flash_set('error', t('error_db_error'));
            header("Location: index.php?page=customers&action=profile&id={$id}");
        }
        exit;
    }

    // =========================================================================
    // render404() — load the styled 404 view (no JS, dark theme)
    // =========================================================================
    private function render404(): void
    {
        http_response_code(404);
        $activePage = '404';
        $pageTitle  = t('page_not_found');
        $errorMsg   = t('page_not_found_desc');
        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/404.php';
        require __DIR__ . '/../views/layout/footer.php';
        exit;
    }
}
