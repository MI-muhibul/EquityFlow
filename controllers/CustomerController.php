<?php
declare(strict_types=1);

/**
 * controllers/CustomerController.php
 * -----------------------------------------------------------------------------
 * Handles the full customer management flow:
 *   index()  — List all customers in a table
 *   create() — Show the Add Customer form (GET) + save it (POST)
 * -----------------------------------------------------------------------------
 * Rules:
 *   • require_login() on every action (30-min timeout enforced)
 *   • CSRF token verified on every POST
 *   • Prepared statements only (via Customer model)
 *   • e() / htmlspecialchars on all output
 *   • Server-side validation: name required, phone format + uniqueness,
 *     credit_limit >= 0
 *   • Flash messages for success and each validation error
 *   • Zero JavaScript
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../helpers/format.php';
require_once __DIR__ . '/../models/Customer.php';

class CustomerController
{
    // -----------------------------------------------------------------
    // index() — Display the customer list table
    // -----------------------------------------------------------------
    public function index(): void
    {
        require_login();

        // Load all customers from the database
        $customers = Customer::all();

        $pageTitle  = t('customer_title');
        $activePage = 'customers';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/customers/index.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    // -----------------------------------------------------------------
    // create() — Show the Add Customer form (GET) or save new row (POST)
    // -----------------------------------------------------------------
    public function create(): void
    {
        require_login();

        // Start with blank form values so the view always has these vars
        $old    = ['name' => '', 'phone' => '', 'address' => '', 'credit_limit' => ''];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // 1. CSRF check — reject tampered or replayed form submissions
            if (!csrf_verify($_POST['csrf_token'] ?? null)) {
                flash_set('error', t('error_csrf_invalid'));
                header('Location: index.php?page=customers&action=create');
                exit;
            }

            // 2. Collect and trim raw POST values
            $name        = trim($_POST['name']        ?? '');
            $phone       = trim($_POST['phone']       ?? '');
            $address     = trim($_POST['address']     ?? '');
            $creditLimit = trim($_POST['credit_limit'] ?? '0');

            // Keep old values so form re-fills on error
            $old = compact('name', 'phone', 'address', 'credit_limit');
            $old['credit_limit'] = $creditLimit;

            // 3. Server-side validation ---------------------------------

            // 3a. Name: required, max 100 chars
            if ($name === '') {
                $errors['name'] = t('error_required_field');
            } elseif (mb_strlen($name) > 100) {
                $errors['name'] = t('error_required_field'); // treated as invalid
            }

            // 3b. Phone: required, matches 01XXXXXXXXX (11 digits, BD mobile)
            $phonePattern = '/^01[3-9]\d{8}$/';   // BD operators: 013-019
            if ($phone === '') {
                $errors['phone'] = t('error_required_field');
            } elseif (!preg_match($phonePattern, $phone)) {
                $errors['phone'] = t('error_invalid_phone');
            } elseif (Customer::phoneExists($phone)) {
                // 3c. Phone uniqueness check via prepared statement
                $errors['phone'] = t('error_phone_exists');
            }

            // 3d. Credit limit: must be numeric and >= 0
            if (!is_numeric($creditLimit) || (float)$creditLimit < 0) {
                $errors['credit_limit'] = t('error_credit_limit_invalid');
            }

            // 4. If validation passes, save and redirect ---------------
            if (empty($errors)) {
                try {
                    Customer::create(
                        name:        $name,
                        phone:       $phone,
                        address:     $address,
                        creditLimit: (float)$creditLimit,
                        joinDate:    date('Y-m-d'),   // today's date
                    );

                    flash_set('success', t('msg_saved_success'));
                    header('Location: index.php?page=customers');
                    exit;

                } catch (PDOException $e) {
                    // Duplicate phone caught at DB level (belt-and-suspenders)
                    if ($e->getCode() === '23000') {
                        $errors['phone'] = t('error_phone_exists');
                    } else {
                        flash_set('error', 'Database error: ' . $e->getMessage());
                    }
                }
            }

            // If we reach here, validation failed — fall through to show form
        }

        $pageTitle  = t('btn_add_customer');
        $activePage = 'customers';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/customers/add.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    // -----------------------------------------------------------------
    // handleRequest() — Route sub-actions via ?action= query param
    // The front controller calls index() by default; we intercept here.
    // -----------------------------------------------------------------
    public function handleRequest(): void
    {
        $action = isset($_GET['action']) ? trim((string)$_GET['action']) : 'index';

        match ($action) {
            'create' => $this->create(),
            default  => $this->index(),
        };
    }
}
