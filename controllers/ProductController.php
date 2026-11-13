<?php
declare(strict_types=1);

/**
 * controllers/ProductController.php
 * -----------------------------------------------------------------------------
 * Controller handling inventory and product catalog:
 *   - List products (?page=inventory or ?page=products)
 *   - Add product (?page=inventory&action=create)
 *   - Edit product (?page=inventory&action=edit&id=X)
 *   - Adjust / Update stock (?page=inventory&action=stock&id=X)
 * -----------------------------------------------------------------------------
 * Rules:
 *   - require_login() enforced
 *   - Zero JavaScript
 *   - Prepared statements only via model
 *   - CSRF protection on every POST submission
 *   - htmlspecialchars on all output via e()
 *   - Money formatted as ৳ with 2 decimals
 *   - Non-negative validation for prices and stock
 *   - Product name uniqueness validated
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../helpers/format.php';
require_once __DIR__ . '/../models/Product.php';

class ProductController
{
    /**
     * Dispatcher method called by index.php router.
     */
    public function handleRequest(): void
    {
        require_login();

        $action = isset($_GET['action']) && is_string($_GET['action']) ? trim($_GET['action']) : 'index';

        switch ($action) {
            case 'view':
            case 'detail':
                $this->detail();
                break;
            case 'create':
                $this->create();
                break;
            case 'edit':
                $this->edit();
                break;
            case 'stock':
                $this->stock();
                break;
            case 'index':
            default:
                $this->index();
                break;
        }
    }

    /**
     * List all products with stock status badges and profit margins.
     */
    public function index(): void
    {
        $products   = Product::all();
        $pageTitle  = t('product_title');
        $activePage = 'inventory';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/products/index.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Product detail page with unit profit, margin %, loss warning, and sales profit sparkline trend.
     */
    public function detail(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $product = Product::findById($id);

        if (!$product) {
            flash_set('error', t('error_product_not_found'));
            header('Location: index.php?page=inventory');
            exit;
        }

        require_once __DIR__ . '/../helpers/sparkline.php';

        $saleHistory = Product::getSaleProfitHistory($id);

        // Extract trend values: total profit per sale item
        $profitTrendPoints = [];
        foreach ($saleHistory as $item) {
            $profitTrendPoints[] = (float)$item['total_profit'];
        }

        $pageTitle  = $product['name'] . ' — ' . t('product_detail_title');
        $activePage = 'inventory';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/products/detail.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Add a new product to inventory.
     */
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF Token Check
            if (!csrf_verify($_POST['csrf_token'] ?? '')) {
                flash_set('error', t('error_csrf_invalid'));
                header('Location: index.php?page=inventory&action=create');
                exit;
            }

            $name        = trim($_POST['name'] ?? '');
            $category    = trim($_POST['category'] ?? '');
            $retailPrice = isset($_POST['retail_price']) ? (float)$_POST['retail_price'] : -1.0;
            $costPrice   = isset($_POST['cost_price']) ? (float)$_POST['cost_price'] : -1.0;
            $stock       = isset($_POST['stock']) ? (int)$_POST['stock'] : -1;
            $minStock    = isset($_POST['min_stock']) ? (int)$_POST['min_stock'] : -1;

            $errors = [];
            if ($name === '') {
                $errors[] = t('error_required_field');
            }
            if ($category === '') {
                $errors[] = t('error_required_field');
            }
            if ($retailPrice < 0.0 || $costPrice < 0.0 || $stock < 0 || $minStock < 0) {
                $errors[] = t('error_invalid_number');
            }
            if ($name !== '' && Product::nameExists($name)) {
                $errors[] = t('error_product_exists');
            }

            if (!empty($errors)) {
                foreach ($errors as $e) {
                    flash_set('error', $e);
                }
                header('Location: index.php?page=inventory&action=create');
                exit;
            }

            Product::create($name, $category, $retailPrice, $costPrice, $stock, $minStock);
            flash_set('success', t('msg_product_added'));
            header('Location: index.php?page=inventory');
            exit;
        }

        $pageTitle  = t('product_add_title');
        $activePage = 'inventory';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/products/add.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Edit existing product information.
     */
    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $product = Product::findById($id);

        if (!$product) {
            flash_set('error', t('error_product_not_found'));
            header('Location: index.php?page=inventory');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF Token Check
            if (!csrf_verify($_POST['csrf_token'] ?? '')) {
                flash_set('error', t('error_csrf_invalid'));
                header("Location: index.php?page=inventory&action=edit&id={$id}");
                exit;
            }

            $name        = trim($_POST['name'] ?? '');
            $category    = trim($_POST['category'] ?? '');
            $retailPrice = isset($_POST['retail_price']) ? (float)$_POST['retail_price'] : -1.0;
            $costPrice   = isset($_POST['cost_price']) ? (float)$_POST['cost_price'] : -1.0;
            $stock       = isset($_POST['stock']) ? (int)$_POST['stock'] : -1;
            $minStock    = isset($_POST['min_stock']) ? (int)$_POST['min_stock'] : -1;

            $errors = [];
            if ($name === '') {
                $errors[] = t('error_required_field');
            }
            if ($category === '') {
                $errors[] = t('error_required_field');
            }
            if ($retailPrice < 0.0 || $costPrice < 0.0 || $stock < 0 || $minStock < 0) {
                $errors[] = t('error_invalid_number');
            }
            if ($name !== '' && Product::nameExists($name, $id)) {
                $errors[] = t('error_product_exists');
            }

            if (!empty($errors)) {
                foreach ($errors as $e) {
                    flash_set('error', $e);
                }
                header("Location: index.php?page=inventory&action=edit&id={$id}");
                exit;
            }

            Product::update($id, $name, $category, $retailPrice, $costPrice, $stock, $minStock);
            flash_set('success', t('msg_product_updated'));
            header('Location: index.php?page=inventory');
            exit;
        }

        $pageTitle  = t('product_edit_title') . ' — ' . $product['name'];
        $activePage = 'inventory';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/products/edit.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Dedicated stock adjustment page (restock, set, or deduct).
     */
    public function stock(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $product = Product::findById($id);

        if (!$product) {
            flash_set('error', t('error_product_not_found'));
            header('Location: index.php?page=inventory');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify($_POST['csrf_token'] ?? '')) {
                flash_set('error', t('error_csrf_invalid'));
                header("Location: index.php?page=inventory&action=stock&id={$id}");
                exit;
            }

            $action = trim($_POST['stock_action'] ?? 'set');
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : -1;

            if ($quantity < 0) {
                flash_set('error', t('error_invalid_number'));
                header("Location: index.php?page=inventory&action=stock&id={$id}");
                exit;
            }

            $currentStock = (int)$product['stock'];
            if ($action === 'add') {
                $newStock = $currentStock + $quantity;
            } elseif ($action === 'subtract') {
                $newStock = max(0, $currentStock - $quantity);
            } else { // 'set'
                $newStock = $quantity;
            }

            Product::updateStock($id, $newStock);
            flash_set('success', t('msg_stock_updated'));
            header('Location: index.php?page=inventory');
            exit;
        }

        $pageTitle  = t('product_stock_title') . ' — ' . $product['name'];
        $activePage = 'inventory';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/products/stock.php';
        require __DIR__ . '/../views/layout/footer.php';
    }
}
