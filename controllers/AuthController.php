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

        // If ?page=google-login
        if ($page === 'google-login') {
            $this->googleLogin();
            return;
        }

        // If ?page=google-callback
        if ($page === 'google-callback') {
            $this->googleCallback();
            return;
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

    /**
     * Initiate Google OAuth 2.0 authorization code flow.
     * Route: ?page=google-login
     *
     * Scope: openid email profile
     * State: Cryptographically random token saved in session for CSRF defense.
     */
    public function googleLogin(): void {
        redirect_if_logged_in();

        $clientId = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '';
        if ($clientId === '' || str_contains($clientId, 'YOUR_GOOGLE_CLIENT_ID')) {
            error_log('EquityFlow: GOOGLE_CLIENT_ID is not configured in config/config.php');
            flash_set('warning', t('google_failed'));
            header('Location: index.php?page=login');
            exit;
        }

        $redirectUri = defined('GOOGLE_REDIRECT_URI') ? GOOGLE_REDIRECT_URI : '';

        // Generate and persist random state token in session
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        $params = [
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ];

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * Handle the redirect back from Google OAuth 2.0.
     * Route: ?page=google-callback
     *
     * 1. Verifies state against session token (timing-safe hash_equals)
     * 2. Exchanges authorization code at https://oauth2.googleapis.com/token via cURL
     * 3. Validates id_token via Google's tokeninfo endpoint:
     *    - iss is accounts.google.com or https://accounts.google.com
     *    - aud equals my client ID
     *    - exp not passed
     *    - email_verified is true
     * 4. Finds or creates the user:
     *    - match by google_id
     *    - else match by email and link google_id (auth_provider='both')
     *    - else create a new user with password_hash NULL, auth_provider='google'
     * 5. Regenerates session ID, sets user_id, redirects to dashboard
     * 6. Catches all failures, logs diagnostics, and returns to login with translated error
     */
    public function googleCallback(): void {
        redirect_if_logged_in();

        // 1. Handle error returned by Google (e.g. user denied consent: access_denied)
        if (!empty($_GET['error'])) {
            unset($_SESSION['oauth_state']);
            error_log('EquityFlow Google OAuth Provider Error: ' . (string)$_GET['error']);
            flash_set('error', t('google_failed'));
            header('Location: index.php?page=login');
            exit;
        }

        // 2. Validate state token to defend against CSRF
        $storedState = $_SESSION['oauth_state'] ?? null;
        unset($_SESSION['oauth_state']); // One-time use

        $receivedState = $_GET['state'] ?? null;
        if (empty($storedState) || empty($receivedState) || !hash_equals((string)$storedState, (string)$receivedState)) {
            error_log('EquityFlow Google OAuth: State verification failed or state mismatch.');
            flash_set('error', t('google_failed'));
            header('Location: index.php?page=login');
            exit;
        }

        // 3. Verify authorization code is present
        $code = trim((string)($_GET['code'] ?? ''));
        if ($code === '') {
            error_log('EquityFlow Google OAuth: Missing authorization code in callback.');
            flash_set('error', t('google_failed'));
            header('Location: index.php?page=login');
            exit;
        }

        // 4. Exchange authorization code for tokens via cURL POST
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $postFields = [
            'code'          => $code,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        self::configureSslBundle($ch);

        $response  = curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $curlError = curl_error($ch);
        $httpCode  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErrNo !== 0) {
            self::logCurlError($curlErrNo, $curlError, 'Token Exchange');
            flash_set('error', t('google_failed'));
            header('Location: index.php?page=login');
            exit;
        }

        $tokenData = json_decode((string)$response, true);
        if ($httpCode !== 200 || empty($tokenData['id_token'])) {
            error_log("EquityFlow Google OAuth token exchange failed (HTTP {$httpCode}): " . ($tokenData['error_description'] ?? $response));
            flash_set('error', t('google_failed'));
            header('Location: index.php?page=login');
            exit;
        }

        $idToken = (string)$tokenData['id_token'];

        // 5. Validate id_token via Google's tokeninfo endpoint
        $infoUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
        $chInfo  = curl_init($infoUrl);
        curl_setopt($chInfo, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chInfo, CURLOPT_TIMEOUT, 15);
        self::configureSslBundle($chInfo);

        $infoResponse = curl_exec($chInfo);
        $infoErrNo    = curl_errno($chInfo);
        $infoError    = curl_error($chInfo);
        $infoHttpCode = (int)curl_getinfo($chInfo, CURLINFO_HTTP_CODE);
        curl_close($chInfo);

        if ($infoErrNo !== 0 || $infoHttpCode !== 200) {
            self::logCurlError($infoErrNo, $infoError, 'Tokeninfo Validation');
            flash_set('error', t('google_failed'));
            header('Location: index.php?page=login');
            exit;
        }

        $payload = json_decode((string)$infoResponse, true);
        if (!is_array($payload)) {
            error_log('EquityFlow Google OAuth: Malformed tokeninfo response.');
            flash_set('error', t('google_failed'));
            header('Location: index.php?page=login');
            exit;
        }

        // Validate Issuer: iss is accounts.google.com or https://accounts.google.com
        $iss = (string)($payload['iss'] ?? '');
        if ($iss !== 'accounts.google.com' && $iss !== 'https://accounts.google.com') {
            error_log("EquityFlow Google OAuth: Invalid token issuer '{$iss}'.");
            flash_set('error', t('google_failed'));
            header('Location: index.php?page=login');
            exit;
        }

        // Validate Audience: aud equals my client ID
        $aud = (string)($payload['aud'] ?? '');
        if ($aud !== GOOGLE_CLIENT_ID) {
            error_log("EquityFlow Google OAuth: Audience mismatch ('{$aud}' vs expected '" . GOOGLE_CLIENT_ID . "').");
            flash_set('error', t('google_failed'));
            header('Location: index.php?page=login');
            exit;
        }

        // Validate Expiration: exp not passed
        $exp = isset($payload['exp']) ? (int)$payload['exp'] : 0;
        if ($exp <= time()) {
            error_log("EquityFlow Google OAuth: Token has expired (exp: {$exp}, current: " . time() . ").");
            flash_set('error', t('google_failed'));
            header('Location: index.php?page=login');
            exit;
        }

        // Validate Email Verification: email_verified is true
        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if (!$emailVerified) {
            error_log("EquityFlow Google OAuth: Google account email is not verified.");
            flash_set('error', t('google_failed'));
            header('Location: index.php?page=login');
            exit;
        }

        $googleId  = trim((string)($payload['sub'] ?? ''));
        $email     = strtolower(trim((string)($payload['email'] ?? '')));
        $name      = trim((string)($payload['name'] ?? ''));
        $avatarUrl = !empty($payload['picture']) ? trim((string)$payload['picture']) : null;

        if ($googleId === '' || $email === '') {
            error_log('EquityFlow Google OAuth: Google profile missing sub or email.');
            flash_set('error', t('google_failed'));
            header('Location: index.php?page=login');
            exit;
        }

        // 6. Account Provisioning Strategy:
        //    a) Match by google_id
        $user = User::findByGoogleId($googleId);

        //    b) Else match by email and link google_id (set auth_provider = 'both')
        if (!$user) {
            $userByEmail = User::findByEmail($email);
            if ($userByEmail) {
                User::linkGoogleAccount((int)$userByEmail['user_id'], $googleId, $avatarUrl);
                $user = User::findById((int)$userByEmail['user_id']);
            }
        }

        //    c) Else create new user (password_hash NULL, auth_provider 'google', name & avatar from Google)
        //       No extra email verification step
        if (!$user) {
            $shopName  = ($name !== '' ? $name : 'Shop') . ' স্টোর';
            $newUserId = User::createFromGoogle([
                'name'       => $name !== '' ? $name : explode('@', $email)[0],
                'email'      => $email,
                'google_id'  => $googleId,
                'avatar_url' => $avatarUrl,
                'shop_name'  => $shopName,
            ]);
            $user = User::findById($newUserId);
        }

        if (!$user) {
            error_log('EquityFlow Google OAuth: Failed to load user account after provisioning.');
            flash_set('error', t('google_failed'));
            header('Location: index.php?page=login');
            exit;
        }

        // Update last_login timestamp
        User::touchLastLogin((int)$user['user_id']);

        // 7. Regenerate session ID, store user identity and last_activity, redirect to dashboard
        regenerate_session_id_safe();
        $_SESSION['user_id']       = (int)$user['user_id'];
        $_SESSION['user_name']     = $user['name'];
        $_SESSION['email']         = $user['email'];
        $_SESSION['shop_name']     = $user['shop_name'];
        $_SESSION['auth_provider'] = $user['auth_provider'];
        $_SESSION['avatar_url']    = $user['avatar_url'] ?? null;
        $_SESSION['last_activity'] = time();

        flash_set('success', t('msg_login_success'));
        header('Location: index.php?page=dashboard');
        exit;
    }

    /**
     * Auto-detect and configure local CA bundle if php.ini curl.cainfo is missing in XAMPP.
     *
     * @param \CurlHandle $ch
     */
    private static function configureSslBundle(\CurlHandle $ch): void {
        // If curl.cainfo is already configured in php.ini, use it
        $configured = ini_get('curl.cainfo');
        if (!empty($configured) && file_exists($configured)) {
            return;
        }

        // Probe common XAMPP / Windows CA certificate bundle locations
        $candidates = [
            'C:\\xampp\\php\\extras\\ssl\\cacert.pem',
            'C:\\xampp\\perl\\vendor\\lib\\Mozilla\\CA\\cacert.pem',
            'C:\\xampp\\phpMyAdmin\\vendor\\composer\\ca-bundle\\res\\cacert.pem',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                curl_setopt($ch, CURLOPT_CAINFO, $path);
                return;
            }
        }
    }

    /**
     * Log cURL errors with detailed diagnostics, specifically explaining error 60 for XAMPP.
     *
     * @param int $errno
     * @param string $error
     * @param string $context
     */
    private static function logCurlError(int $errno, string $error, string $context): void {
        if ($errno === 60 /* CURLE_SSL_CACERT */) {
            error_log(
                "EquityFlow Google OAuth [{$context}] - cURL error 60: SSL certificate problem: unable to get local issuer certificate.\n" .
                "--- XAMPP Windows Fix ---\n" .
                "1. Download the latest CA certificate bundle from https://curl.se/ca/cacert.pem\n" .
                "2. Save it to C:\\xampp\\php\\extras\\ssl\\cacert.pem\n" .
                "3. Open C:\\xampp\\php\\php.ini and configure:\n" .
                "     curl.cainfo = \"C:\\xampp\\php\\extras\\ssl\\cacert.pem\"\n" .
                "     openssl.cafile = \"C:\\xampp\\php\\extras\\ssl\\cacert.pem\"\n" .
                "4. Restart the Apache server from the XAMPP Control Panel.\n" .
                "-------------------------"
            );
        } else {
            error_log("EquityFlow Google OAuth [{$context}] - cURL error ({$errno}): {$error}");
        }
    }
}
