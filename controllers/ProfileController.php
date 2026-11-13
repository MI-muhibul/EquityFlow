<?php
declare(strict_types=1);

/**
 * controllers/ProfileController.php
 * -----------------------------------------------------------------------------
 * Shop Owner Profile Controller.
 * Displays profile details (name, email, shop name, avatar if from Google,
 * auth_provider badge), handles updating name & shop name with CSRF protection,
 * and links to change/set password.
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/format.php';
require_once __DIR__ . '/../models/User.php';

class ProfileController {
    /**
     * Handle incoming profile requests (GET view or POST update).
     */
    public function handleRequest(): void {
        require_login();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update();
        } else {
            $this->index();
        }
    }

    /**
     * Render the profile view.
     */
    public function index(): void {
        require_login();

        $userId = (int)$_SESSION['user_id'];
        $user   = User::findById($userId);

        if (!$user) {
            flash_set('error', t('wrong_credentials'));
            header('Location: index.php?page=login');
            exit;
        }

        $hasPassword = User::hasPassword($userId);
        $pageTitle   = t('profile');
        $activePage  = 'profile';

        require_once __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/auth/profile.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Process profile information updates (Name & Shop Name).
     */
    public function update(): void {
        require_login();

        $submittedToken = $_POST['csrf_token'] ?? null;
        if (!csrf_verify($submittedToken)) {
            flash_set('error', t('error_csrf_invalid'));
            header('Location: index.php?page=profile');
            exit;
        }

        $userId   = (int)$_SESSION['user_id'];
        $name     = trim((string)($_POST['name'] ?? ''));
        $shopName = trim((string)($_POST['shop_name'] ?? ''));

        if ($name === '' || $shopName === '') {
            flash_set('error', t('error_required_field'));
            header('Location: index.php?page=profile');
            exit;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET name = :name, shop_name = :shop_name WHERE user_id = :id');
        $stmt->execute([
            'name'      => $name,
            'shop_name' => $shopName,
            'id'        => $userId,
        ]);

        // Refresh active session variables
        $_SESSION['user_name'] = $name;
        $_SESSION['shop_name'] = $shopName;

        flash_set('success', t('profile_updated'));
        header('Location: index.php?page=profile');
        exit;
    }
}
