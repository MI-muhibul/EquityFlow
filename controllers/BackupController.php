<?php
declare(strict_types=1);

/**
 * controllers/BackupController.php
 * -----------------------------------------------------------------------------
 * Controller alias for backup route (?page=backup).
 * Forwards to SettingController or triggers backup actions directly.
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/SettingController.php';

class BackupController extends SettingController
{
    // Inherits index(), backup(), and download() from SettingController
}
