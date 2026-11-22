<?php
declare(strict_types=1);

/**
 * controllers/SettingController.php
 * -----------------------------------------------------------------------------
 * Controller handling shop profile and system configuration:
 *   - Shop Name
 *   - Default Credit Limit
 *   - Default Minimum Stock Alert
 *   - Default Language ('bn' or 'en')
 *   - Database Backup Creation (trigger multi-tenant PDO dump)
 *   - Secure Download of Backups (scoped to current user, path traversal protected)
 *   - Listing of user's own backup archives
 * -----------------------------------------------------------------------------
 * Rules:
 *   - require_login() enforced
 *   - All settings scoped to current_user_id() via (user_id, skey) primary key
 *   - CSRF protection on POST actions
 *   - Zero JavaScript
 *   - Prepared statements only via models
 *   - htmlspecialchars on all output via e()
 *   - Bilingual translations from lang packs via t()
 *   - Backups strictly scoped to user_id (never dumps users table)
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../helpers/format.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/Backup.php';

class SettingController
{
    /**
     * Dispatcher method called by router.
     */
    public function handleRequest(): void
    {
        require_login();

        $action = isset($_GET['action']) && is_string($_GET['action']) ? trim($_GET['action']) : 'index';

        switch ($action) {
            case 'update':
                $this->update();
                break;
            case 'backup':
                $this->backup();
                break;
            case 'download':
                $this->download();
                break;
            case 'index':
            default:
                $this->index();
                break;
        }
    }

    /**
     * Render the styled 404 view for unmatched files or resources.
     */
    private function render404(): void
    {
        http_response_code(404);
        $activePage = '404';
        $pageTitle  = t('page_not_found');
        require __DIR__ . '/../views/404.php';
        exit;
    }

    /**
     * Display Settings page and Backups list for the authenticated user.
     */
    public function index(): void
    {
        $userId   = current_user_id() ?? (int)$_SESSION['user_id'];
        $settings = Setting::all($userId);
        $backups  = Backup::listBackups($userId);

        $pageTitle  = t('settings_title');
        $activePage = 'settings';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/settings/index.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Handle POST submission to update settings for the authenticated user.
     */
    public function update(): void
    {
        $userId = current_user_id() ?? (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=settings');
            exit;
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            flash_set('error', t('error_csrf_invalid'));
            header('Location: index.php?page=settings');
            exit;
        }

        $shopName        = trim($_POST['shop_name'] ?? '');
        $defaultCredit   = (float)($_POST['default_credit_limit'] ?? 0.0);
        $defaultMinStock = (int)($_POST['default_min_stock'] ?? 5);
        $defaultLang     = trim($_POST['default_language'] ?? 'bn');
        $shopPhone       = trim($_POST['shop_phone'] ?? '');
        $shopAddress     = trim($_POST['shop_address'] ?? '');

        // Validation
        if ($shopName === '') {
            flash_set('error', t('error_required_field'));
            header('Location: index.php?page=settings');
            exit;
        }

        if ($defaultCredit < 0.0 || $defaultMinStock < 0) {
            flash_set('error', t('error_invalid_number'));
            header('Location: index.php?page=settings');
            exit;
        }

        if ($defaultLang !== 'bn' && $defaultLang !== 'en') {
            $defaultLang = 'bn';
        }

        $toSave = [
            'shop_name'            => $shopName,
            'default_credit_limit' => (string)$defaultCredit,
            'default_min_stock'    => (string)$defaultMinStock,
            'default_language'     => $defaultLang,
            'shop_phone'           => $shopPhone,
            'shop_address'         => $shopAddress,
        ];

        if (Setting::setMultiple($userId, $toSave)) {
            // Also switch language session if owner changed default language
            if (isset($_SESSION) && empty($_GET['lang'])) {
                $_SESSION['lang'] = $defaultLang;
            }
            flash_set('success', t('msg_updated_success'));
        } else {
            flash_set('error', t('error_db_error'));
        }

        header('Location: index.php?page=settings');
        exit;
    }

    /**
     * Handle trigger to create a new multi-tenant database backup for current user.
     */
    public function backup(): void
    {
        $userId = current_user_id() ?? (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=settings');
            exit;
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            flash_set('error', t('error_csrf_invalid'));
            header('Location: index.php?page=settings');
            exit;
        }

        $result = Backup::createDump($userId);
        if ($result['success']) {
            flash_set('success', t('msg_backup_created') . ' (' . e($result['filename']) . ')');
        } else {
            flash_set('error', 'Backup failed: ' . e($result['error'] ?? 'Unknown error'));
        }

        header('Location: index.php?page=settings');
        exit;
    }

    /**
     * Stream database backup file download with authentication & path security.
     */
    public function download(): void
    {
        $userId = current_user_id() ?? (int)$_SESSION['user_id'];
        $file = isset($_GET['file']) && is_string($_GET['file']) ? trim($_GET['file']) : '';
        $path = Backup::getDownloadPath($file, $userId);

        if ($path === false) {
            $this->render404();
        }

        // Send file headers for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));

        // Clear output buffer if any
        while (ob_get_level()) {
            ob_end_clean();
        }

        readfile($path);
        exit;
    }
}
