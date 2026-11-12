<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../helpers/flash.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../helpers/format.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../config/auth_guard.php';

class CustomerController
{
    // List customers – already existed
    public function index(): void {
        $customers = Customer::all();
        require __DIR__ . '/../views/customers/index.php';
    }

    // Add new customer – already existed
    public function create(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify($_POST['csrf_token'] ?? '')) {
                flash_set('error', t('error_csrf_invalid'));
                header('Location: ?page=customers&action=create');
                exit;
            }
            $name        = trim($_POST['name'] ?? '');
            $phone       = trim($_POST['phone'] ?? '');
            $address     = trim($_POST['address'] ?? '');
            $creditLimit = floatval($_POST['credit_limit'] ?? 0);
            $joinDate    = date('Y-m-d');
            $errors = [];
            if ($name === '') $errors[] = t('error_name_required');
            if (!preg_match('/^01[3-9]\d{8}$/', $phone)) $errors[] = t('error_phone_invalid');
            if (Customer::phoneExists($phone)) $errors[] = t('error_phone_exists');
            if ($creditLimit < 0) $errors[] = t('error_credit_limit_invalid');
            if ($errors) {
                foreach ($errors as $e) flash_set('error', $e);
                header('Location: ?page=customers&action=create');
                exit;
            }
            try {
                Customer::create($name, $phone, $address, $creditLimit, $joinDate);
                flash_set('success', t('msg_customer_added'));
                header('Location: ?page=customers');
                exit;
            } catch (PDOException $ex) {
                flash_set('error', t('error_db_error'));
                header('Location: ?page=customers&action=create');
                exit;
            }
        } else {
            require __DIR__ . '/../views/customers/add.php';
        }
    }

    // Edit existing customer
    public function edit(): void {
        $id = (int)($_GET['id'] ?? 0);
        $customer = Customer::findById($id);
        if (!$customer) {
            flash_set('error', t('error_customer_not_found'));
            header('Location: ?page=customers');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify($_POST['csrf_token'] ?? '')) {
                flash_set('error', t('error_csrf_invalid'));
                header("Location: ?page=customers&action=edit&id=$id");
                exit;
            }
            $name        = trim($_POST['name'] ?? '');
            $phone       = trim($_POST['phone'] ?? '');
            $address     = trim($_POST['address'] ?? '');
            $creditLimit = floatval($_POST['credit_limit'] ?? 0);
            $errors = [];
            if ($name === '') $errors[] = t('error_name_required');
            if (!preg_match('/^01[3-9]\d{8}$/', $phone)) $errors[] = t('error_phone_invalid');
            if (Customer::phoneExists($phone, $id)) $errors[] = t('error_phone_exists');
            if ($creditLimit < 0) $errors[] = t('error_credit_limit_invalid');
            if ($errors) {
                foreach ($errors as $e) flash_set('error', $e);
                header("Location: ?page=customers&action=edit&id=$id");
                exit;
            }
            Customer::update($id, $name, $phone, $address, $creditLimit);
            flash_set('success', t('msg_customer_updated'));
            header('Location: ?page=customers&action=profile&id=' . $id);
            exit;
        } else {
            require __DIR__ . '/../views/customers/edit.php';
        }
    }

    // Show profile page
    public function profile(): void {
        $id = (int)($_GET['id'] ?? 0);
        $customer = Customer::findById($id);
        if (!$customer) {
            flash_set('error', t('error_customer_not_found'));
            header('Location: ?page=customers');
            exit;
        }
        require __DIR__ . '/../views/customers/profile.php';
    }

    // Render delete confirmation page
    public function deleteConfirm(): void {
        $id = (int)($_GET['id'] ?? 0);
        $customer = Customer::findById($id);
        if (!$customer) {
            flash_set('error', t('error_customer_not_found'));
            header('Location: ?page=customers');
            exit;
        }
        require __DIR__ . '/../views/customers/delete_confirm.php';
    }

    // Perform deletion (POST only, CSRF, balance guard)
    public function delete(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=customers');
            exit;
        }
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            flash_set('error', t('error_csrf_invalid'));
            header('Location: ?page=customers');
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $customer = Customer::findById($id);
        if (!$customer) {
            flash_set('error', t('error_customer_not_found'));
            header('Location: ?page=customers');
            exit;
        }
        if ((float)$customer['balance'] != 0.0) {
            flash_set('error', t('error_customer_has_due'));
            header("Location: ?page=customers&action=profile&id=$id");
            exit;
        }
        Customer::delete($id);
        flash_set('success', t('msg_customer_deleted'));
        header('Location: ?page=customers');
        exit;
    }
}
