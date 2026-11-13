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
 *   - Database Backup Creation (trigger PDO dump)
 *   - Secure Download of Backups
 *   - Listing of all existing backup archives
 * -----------------------------------------------------------------------------
 * Rules:
 *   - require_login() enforced
 *   - CSRF protection on POST actions
 *   - Zero JavaScript
 *   - Prepared statements only via models
 *   - htmlspecialchars on all output via e()
 *   - Bilingual translations from lang packs via t()
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
     * Display Settings page and Backups list.
     */
    public function index(): void
    {
        $settings = Setting::all();
        $backups  = Backup::listBackups();

        $pageTitle  = t('settings_title');
        $activePage = 'settings';

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/settings/index.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Handle POST submission to update settings.
     */
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=settings');
            exit;
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            flash_set('error', t('error_csrf_invalid'));
            header('Location: index.php?page=settings');
            exit;
        }

        $shopName       = trim($_POST['shop_name'] ?? '');
        $defaultCredit  = (float)($_POST['default_credit_limit'] ?? 0.0);
        $defaultMinStock = (int)($_POST['default_min_stock'] ?? 5);
        $defaultLang    = trim($_POST['default_language'] ?? 'bn');
        $shopPhone      = trim($_POST['shop_phone'] ?? '');
        $shopAddress    = trim($_POST['shop_address'] ?? '');

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

        if (Setting::setMultiple($toSave)) {
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
     * Handle trigger to create a new database backup.
     */
    public function backup(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=settings');
            exit;
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            flash_set('error', t('error_csrf_invalid'));
            header('Location: index.php?page=settings');
            exit;
        }

        $result = Backup::createDump();
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
        $file = isset($_GET['file']) && is_string($_GET['file']) ? trim($_GET['file']) : '';
        $path = Backup::getDownloadPath($file);

        if ($path === false) {
            flash_set('error', t('page_not_found'));
            header('Location: index.php?page=settings');
            exit;
        }

        // Send file headers for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));

        // Read directly to output buffer
        readfile($path);
        exit;
    }
}
