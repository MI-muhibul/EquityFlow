<?php
declare(strict_types=1);

/**
 * EquityFlow - Database Check Controller
 *
 * Throwaway diagnostic controller to test PDO singleton connection.
 */

class DbCheckController {
    public function index(): void {
        require_once __DIR__ . '/../db-check.php';
    }
}
