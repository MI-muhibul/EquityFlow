<?php
declare(strict_types=1);

/**
 * controllers/AuthController.php
 * Handles shop owner authentication, login form rendering, credential validation,
 * secure session lifecycle management, and logout.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../helpers/format.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    /**
     * Main dispatch method called by index.php router.
     */
    public function index(): void {
        $action = $_GET['action'] ?? ($_SERVER['REQUEST_METHOD'] === 'POST' ? 'login' : 'show');

        if ($action === 'logout') {
            $this->logout();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $action === 'login') {
            $this->handleLogin();
            return;
        }

        $this->showLoginForm();
    }

    /**
     * Render the login form page.
     * Redirects to dashboard if already authenticated.
     */
    public function showLoginForm(?string $error = null): void {
        // Redirect to dashboard if user is already logged in
        if (!empty($_SESSION['user_id'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $pageTitle = t('login_title');
        require __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Process submitted login credentials securely.
     */
    public function handleLogin(): void {
        // 1. Verify CSRF security token
        $submittedToken = $_POST['csrf_token'] ?? null;
        if (!csrf_verify($submittedToken)) {
            $this->showLoginForm(t('error_csrf_invalid'));
            return;
        }

        // 2. Retrieve submitted inputs
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $pin      = isset($_POST['pin']) && $_POST['pin'] !== '' ? trim((string)$_POST['pin']) : null;

        // 3. Server-side validation: username and password cannot be empty
        if ($username === '' || $password === '') {
            // Show one generic translated error — never reveal which field was wrong
            $this->showLoginForm(t('error_invalid_login'));
            return;
        }

        // 4. Verify credentials against User model (password_verify + optional pin_hash)
        $user = User::verifyLogin($username, $password, $pin);

        if ($user === null) {
            // Show one generic translated error on any failure
            $this->showLoginForm(t('error_invalid_login'));
            return;
        }

        // 5. Successful login: regenerate session ID to prevent session fixation
        regenerate_session_id_safe();

        // 6. Store user details and last_activity timestamp in session
        $_SESSION['user_id']       = (int)$user['user_id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['last_activity'] = time();

        // 7. Queue welcome flash alert and redirect to dashboard
        flash_set('success', t('msg_login_success'));
        header('Location: index.php?page=dashboard');
        exit;
    }

    /**
     * Log out current user and terminate session.
     */
    public function logout(): void {
        terminate_session();

        // Start fresh empty session to display logout flash message
        init_secure_session();
        flash_set('info', t('msg_logout_success'));

        header('Location: index.php?page=auth');
        exit;
    }
}
