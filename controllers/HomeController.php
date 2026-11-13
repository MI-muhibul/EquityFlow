<?php
declare(strict_types=1);

/**
 * controllers/HomeController.php
 * -----------------------------------------------------------------------------
 * Public Landing Page Controller.
 * Displays application overview, value proposition, and guest actions (Sign in, Sign up).
 * Authenticated shop owners are automatically redirected to the dashboard.
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../helpers/format.php';

class HomeController {
    /**
     * Display the public home landing page.
     */
    public function index(): void {
        redirect_if_logged_in();

        $pageTitle  = t('home');
        $activePage = 'home';

        require_once __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/home.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Dispatch router requests.
     */
    public function handleRequest(): void {
        $this->index();
    }
}
