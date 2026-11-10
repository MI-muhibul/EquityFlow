<?php
declare(strict_types=1);

/**
 * controllers/AuthController.php
 * Handles authentication lifecycle: login, 5-attempt account lockout,
 * 30-minute session timeout enforcement, secure logout, and change-password workflows.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../helpers/format.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    /**
     * Main route dispatcher for auth actions.
     */
    public function index(): void {
        $action = $_GET['action'] ?? ($_SERVER['REQUEST_METHOD'] === 'POST' ? 'login' : 'show');

        switch ($action) {
            case 'logout':
                $this->logout();
                break;

            case 'change_password':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->handleChangePassword();
                } else {
                    $this->showChangePasswordForm();
                }
                break;

            case 'login':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->handleLogin();
                } else {
                    $this->showLoginForm();
                }
                break;

            default:
                $this->showLoginForm();
                break;
        }
    }

    /**
     * Render the login form page.
     * Redirects to dashboard if already authenticated.
     */
    public function showLoginForm(?string $error = null): void {
        if (!empty($_SESSION['user_id'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $pageTitle = t('login_title');
        require __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Process submitted login credentials securely.
     * Enforces CSRF check, 5-attempt lockout, and generic error messaging.
     */
    public function handleLogin(): void {
        // 1. Verify CSRF token
        $submittedToken = $_POST['csrf_token'] ?? null;
        if (!csrf_verify($submittedToken)) {
            $this->showLoginForm(t('error_csrf_invalid'));
            return;
        }

        // 2. Retrieve submitted credentials
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $pin      = isset($_POST['pin']) && $_POST['pin'] !== '' ? trim((string)$_POST['pin']) : null;

        // 3. Server-side validation: fields cannot be blank
        if ($username === '' || $password === '') {
            $this->showLoginForm(t('error_invalid_login'));
            return;
        }

        // 4. Check if account is already locked
        if (User::isLocked($username)) {
            $this->showLoginForm(t('error_account_locked'));
            return;
        }

        // 5. Attempt credential verification
        $user = User::verifyLogin($username, $password, $pin);

        if ($user === null) {
            // Record failed attempt and check if lockout threshold was reached
            $failedInfo = User::recordFailedAttempt($username);

            if ($failedInfo['locked']) {
                $this->showLoginForm(t('error_account_locked'));
            } else {
                // Show generic error — never reveal which field was wrong
                $this->showLoginForm(t('error_invalid_login'));
            }
            return;
        }

        // 6. Successful login: regenerate session ID to prevent session fixation
        regenerate_session_id_safe();

        // 7. Store user identity and last_activity timestamp in session
        $_SESSION['user_id']       = (int)$user['user_id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['last_activity'] = time();

        // 8. Redirect to dashboard with welcome message
        flash_set('success', t('msg_login_success'));
        header('Location: index.php?page=dashboard');
        exit;
    }

    /**
     * Securely log out current user and clear browser cookies.
     */
    public function logout(): void {
        secure_logout();

        // Start fresh empty session to display logout flash message
        init_secure_session();
        flash_set('info', t('msg_logout_success'));

        header('Location: index.php?page=auth');
        exit;
    }

    /**
     * Render the change password form.
     * Protected by require_login().
     */
    public function showChangePasswordForm(?string $error = null): void {
        require_login();

        $pageTitle  = t('btn_change_password');
        $activePage = 'settings';

        require_once __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/auth/change_password.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Process change password form submission.
     * Verifies old password, requires min 8 characters, checks matching confirmation, and rehashes.
     */
    public function handleChangePassword(): void {
        require_login();

        // 1. Verify CSRF token
        $submittedToken = $_POST['csrf_token'] ?? null;
        if (!csrf_verify($submittedToken)) {
            $this->showChangePasswordForm(t('error_csrf_invalid'));
            return;
        }

        $userId          = (int)$_SESSION['user_id'];
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword     = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        // 2. Validate all fields filled
        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $this->showChangePasswordForm(t('error_required_field'));
            return;
        }

        // 3. Verify current password matches stored hash
        if (!User::verifyPassword($userId, $currentPassword)) {
            $this->showChangePasswordForm(t('error_current_password_invalid'));
            return;
        }

        // 4. Validate new password length (minimum 8 characters)
        if (strlen($newPassword) < 8) {
            $this->showChangePasswordForm(t('error_password_length'));
            return;
        }

        // 5. Confirm passwords match
        if ($newPassword !== $confirmPassword) {
            $this->showChangePasswordForm(t('error_password_match'));
            return;
        }

        // 6. Rehash and update password in database
        User::updatePassword($userId, $newPassword);

        // 7. Flash success notification and redirect
        flash_set('success', t('msg_password_changed'));
        header('Location: index.php?page=dashboard');
        exit;
    }
}
