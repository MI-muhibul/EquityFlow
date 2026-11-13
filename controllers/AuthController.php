<?php
declare(strict_types=1);

/**
 * controllers/AuthController.php
 * -----------------------------------------------------------------------------
 * Shop Owner Authentication Controller.
 * Handles:
 *  - Email & Password registration (?page=signup)
 *  - Email & Password login (?page=login)
 *  - 5-attempt account lockout tracking
 *  - Generic translated error messages (never exposing which field failed)
 *  - Redirection of authenticated users away from auth forms to dashboard
 *  - Secure session ID regeneration on login
 *  - Multi-lingual UI strings (Bangla / English)
 *  - Zero JavaScript, pure HTML5/CSS3 with CSRF defense
 * -----------------------------------------------------------------------------
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
     * Dispatch incoming requests by routing parameters (?page= and ?action=).
     */
    public function handleRequest(): void {
        $page   = isset($_GET['page']) ? trim((string)$_GET['page']) : 'login';
        $action = isset($_GET['action']) ? trim((string)$_GET['action']) : '';

        // If ?page=signup or ?action=signup
        if ($page === 'signup' || $action === 'signup') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->handleSignup();
            } else {
                $this->showSignupForm();
            }
            return;
        }

        // If ?page=logout or ?action=logout
        if ($page === 'logout' || $action === 'logout') {
            $this->logout();
            return;
        }

        // If ?page=google-login (placeholder for next push)
        if ($page === 'google-login') {
            flash_set('info', t('google_failed'));
            header('Location: index.php?page=login');
            exit;
        }

        // If ?action=change_password
        if ($action === 'change_password') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->handleChangePassword();
            } else {
                $this->showChangePasswordForm();
            }
            return;
        }

        // If ?page=home
        if ($page === 'home') {
            if (!empty($_SESSION['user_id'])) {
                header('Location: index.php?page=dashboard');
                exit;
            }
            $this->showLoginForm();
            return;
        }

        // Default: Login flow (?page=login or ?page=auth)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleLogin();
        } else {
            $this->showLoginForm();
        }
    }

    /**
     * Fallback for router compatibility when index() is called directly.
     */
    public function index(): void {
        $this->handleRequest();
    }

    /**
     * Render the Sign In form.
     * Redirects authenticated shop owners to dashboard.
     *
     * @param string|null $error Translated error message
     * @param string $email Retained email input on error
     */
    public function showLoginForm(?string $error = null, string $email = ''): void {
        if (!empty($_SESSION['user_id'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $pageTitle  = t('sign_in');
        $activePage = 'login';

        require __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Process Sign In form submission.
     * - Verifies CSRF token
     * - Enforces 5-attempt lockout
     * - Uses generic translated error message that never reveals which field was wrong
     * - Regenerates session ID on success to prevent fixation
     * - Stores user_id and last_activity timestamp in session
     * - Redirects to dashboard
     */
    public function handleLogin(): void {
        if (!empty($_SESSION['user_id'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        // 1. Verify CSRF token
        $submittedToken = $_POST['csrf_token'] ?? null;
        if (!csrf_verify($submittedToken)) {
            $this->showLoginForm(t('error_csrf_invalid'));
            return;
        }

        // 2. Retrieve submitted credentials
        $email    = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        // 3. Validation: fields cannot be empty
        if ($email === '' || $password === '') {
            $this->showLoginForm(t('wrong_credentials'), $email);
            return;
        }

        // 4. Check if account is locked
        if (User::isLocked($email)) {
            $this->showLoginForm(t('account_locked'), $email);
            return;
        }

        // 5. Verify credentials against User model
        $user = User::verifyLogin($email, $password);

        if ($user === null) {
            // Record failed attempt
            $failedInfo = User::recordFailedAttempt($email);

            if ($failedInfo['locked']) {
                $this->showLoginForm(t('account_locked'), $email);
            } else {
                // Generic error: never reveal whether email or password was incorrect
                $this->showLoginForm(t('wrong_credentials'), $email);
            }
            return;
        }

        // 6. Regenerate session ID safely
        regenerate_session_id_safe();

        // 7. Store user identity and last_activity timestamp in session
        $_SESSION['user_id']       = (int)$user['user_id'];
        $_SESSION['user_name']     = $user['name'];
        $_SESSION['email']         = $user['email'];
        $_SESSION['shop_name']     = $user['shop_name'];
        $_SESSION['auth_provider'] = $user['auth_provider'];
        $_SESSION['last_activity'] = time();

        // 8. Redirect to dashboard
        flash_set('success', t('msg_login_success'));
        header('Location: index.php?page=dashboard');
        exit;
    }

    /**
     * Render the Sign Up form.
     * Redirects authenticated shop owners to dashboard.
     *
     * @param string|null $error Translated error message
     * @param array<string, mixed> $old Retained input values
     */
    public function showSignupForm(?string $error = null, array $old = []): void {
        if (!empty($_SESSION['user_id'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $pageTitle  = t('sign_up');
        $activePage = 'signup';

        require __DIR__ . '/../views/auth/signup.php';
    }

    /**
     * Process Sign Up form submission.
     * - Validates: name, email, shop_name, password (min 8) + confirm, optional 4-6 digit PIN
     * - Validates duplicate email:
     *     - If existing account is Google-only, displays translated message to use Google Sign-In
     *     - Otherwise displays translated account exists message
     * - On success: creates user, sets flash message, redirects to ?page=login
     */
    public function handleSignup(): void {
        if (!empty($_SESSION['user_id'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        // 1. Verify CSRF token
        $submittedToken = $_POST['csrf_token'] ?? null;
        if (!csrf_verify($submittedToken)) {
            $this->showSignupForm(t('error_csrf_invalid'), $_POST);
            return;
        }

        // 2. Retrieve submitted fields
        $name            = trim((string)($_POST['name'] ?? ''));
        $email           = trim((string)($_POST['email'] ?? ''));
        $shopName        = trim((string)($_POST['shop_name'] ?? ''));
        $password        = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        $pin             = trim((string)($_POST['pin'] ?? ''));

        // 3. Required fields validation
        if ($name === '' || $email === '' || $shopName === '' || $password === '' || $confirmPassword === '') {
            $this->showSignupForm(t('error_required_field'), $_POST);
            return;
        }

        // 4. Email format validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->showSignupForm(t('error_invalid_email') ?? 'Invalid email address.', $_POST);
            return;
        }

        // 5. Password minimum length (min 8 characters)
        if (strlen($password) < 8) {
            $this->showSignupForm(t('error_password_length'), $_POST);
            return;
        }

        // 6. Passwords confirmation match
        if ($password !== $confirmPassword) {
            $this->showSignupForm(t('error_password_match'), $_POST);
            return;
        }

        // 7. Optional PIN validation (4 to 6 digits if provided)
        if ($pin !== '' && !preg_match('/^\d{4,6}$/', $pin)) {
            $this->showSignupForm(t('pin_invalid') ?? 'Security PIN must be 4 to 6 digits.', $_POST);
            return;
        }

        // 8. Check if email already exists
        $existing = User::findByEmail($email);
        if ($existing !== null) {
            // If that account is Google-only (no password_hash or auth_provider=google), prompt to use Google
            if ($existing['auth_provider'] === 'google' || empty($existing['password_hash'])) {
                $this->showSignupForm(t('use_google_for_this_account'), $_POST);
            } else {
                $this->showSignupForm(t('account_exists'), $_POST);
            }
            return;
        }

        // 9. Create user account
        User::create([
            'name'          => $name,
            'email'         => $email,
            'password'      => $password,
            'shop_name'     => $shopName,
            'pin'           => $pin !== '' ? $pin : null,
            'auth_provider' => 'local',
        ]);

        // 10. Redirect to login with translated flash success message
        flash_set('success', t('signup_success'));
        header('Location: index.php?page=login');
        exit;
    }

    /**
     * Terminate active session and log out user.
     */
    public function logout(): void {
        secure_logout();

        // Start clean empty session for displaying flash notice
        init_secure_session();
        flash_set('info', t('msg_logout_success'));

        header('Location: index.php?page=login');
        exit;
    }

    /**
     * Render the change password form.
     */
    public function showChangePasswordForm(?string $error = null): void {
        require_login();

        $userId      = (int)$_SESSION['user_id'];
        $hasPassword = User::hasPassword($userId);

        $pageTitle  = $hasPassword ? t('btn_change_password') : t('set_password');
        $activePage = 'settings';

        require_once __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/auth/change_password.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Process change password form submission.
     * Verifies old password if existing, min 8 chars, rehashes.
     * For Google-only accounts, allows setting a password without verifying an old one.
     */
    public function handleChangePassword(): void {
        require_login();

        $submittedToken = $_POST['csrf_token'] ?? null;
        if (!csrf_verify($submittedToken)) {
            $this->showChangePasswordForm(t('error_csrf_invalid'));
            return;
        }

        $userId          = (int)$_SESSION['user_id'];
        $hasPassword     = User::hasPassword($userId);
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword     = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        // 1. If user has an existing password, current_password is required and verified
        if ($hasPassword) {
            if ($currentPassword === '') {
                $this->showChangePasswordForm(t('error_required_field'));
                return;
            }

            if (!User::verifyPassword($userId, $currentPassword)) {
                $this->showChangePasswordForm(t('error_current_password_invalid'));
                return;
            }
        }

        // 2. Validate new password
        if ($newPassword === '' || $confirmPassword === '') {
            $this->showChangePasswordForm(t('error_required_field'));
            return;
        }

        if (strlen($newPassword) < 8) {
            $this->showChangePasswordForm(t('error_password_length'));
            return;
        }

        if ($newPassword !== $confirmPassword) {
            $this->showChangePasswordForm(t('error_password_match'));
            return;
        }

        // 3. Rehash and save password (elevates auth_provider to 'both' if was 'google')
        User::updatePassword($userId, $newPassword);

        $successMsg = $hasPassword ? t('msg_password_changed') : t('msg_password_set');
        flash_set('success', $successMsg);
        header('Location: index.php?page=dashboard');
        exit;
    }
}
