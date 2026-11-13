<?php
declare(strict_types=1);

/**
 * models/Customer.php
 * -----------------------------------------------------------------------------
 * Data-access layer for the `customers` table.
 * All queries use PDO prepared statements — no string interpolation in SQL.
 *
 * Key public methods:
 *   paginate()   – filtered, paginated list (20 per page)
 *   all()        – full list (kept for dropdowns / receipts)
 *   findById()   – single customer by PK
 *   create()     – insert new customer
 *   update()     – update existing customer
 *   delete()     – remove customer (guarded by zero-balance check)
 *   phoneExists()
 *   nameExists()
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/Database.php';

class Customer
{
    // =========================================================================
    // paginate() — filtered list with 20-per-page pagination
    // =========================================================================
    /**
     * Return a page of customers matching optional name / phone filters.
     *
     * Filters accepted (all optional, pass empty string to skip):
     *   'name'  – partial match on customers.name  (uses idx_customers_name)
     *   'phone' – partial match on customers.phone (uses idx_customers_phone)
     *
     * Returns array{
     *   items:       array,   // the customer rows for this page
     *   total:       int,     // total matching rows (for pagination maths)
     *   page:        int,     // current page number (1-based)
     *   per_page:    int,     // rows per page (always 20)
     *   total_pages: int      // ceil(total / per_page)
     * }
     */
    public static function paginate(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $pdo    = Database::getConnection();
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;

        // --- Build WHERE clause dynamically --------------------------------
        $where  = [];   // SQL fragments, e.g. "name LIKE :name"
        $params = [];   // bound parameter values

        $name  = trim($filters['name']  ?? '');
        $phone = trim($filters['phone'] ?? '');

        if ($name !== '') {
            // LIKE on indexed name column — stays fast with 10 k rows
            $where[]          = 'name LIKE :name';
            $params[':name']  = '%' . $name . '%';
        }
        if ($phone !== '') {
            $where[]          = 'phone LIKE :phone';
            $params[':phone'] = '%' . $phone . '%';
        }

        $whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // --- COUNT total matching rows -------------------------------------
        $countStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM customers {$whereSQL}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // --- Fetch the page -----------------------------------------------
        // LIMIT and OFFSET cannot be bound as named params in all drivers,
        // so we cast them to int and interpolate safely.
        $listStmt = $pdo->prepare(
            "SELECT * FROM customers {$whereSQL}
             ORDER BY name ASC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $listStmt->execute($params);
        $items = $listStmt->fetchAll();

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 1,
        ];
    }

    // =========================================================================
    // all() — full list (used by dropdowns and exports)
    // =========================================================================
    public static function all(): array
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM customers ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    // =========================================================================
    // findById()
    // =========================================================================
    public static function findById(int $id): array|false
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE cust_id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // =========================================================================
    // phoneExists()
    // =========================================================================
    public static function phoneExists(string $phone, int $excludeId = 0): bool
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM customers WHERE phone = :phone AND cust_id != :id'
        );
        $stmt->execute([':phone' => $phone, ':id' => $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // =========================================================================
    // nameExists() — not unique in schema but useful for soft warning
    // =========================================================================
    public static function nameExists(string $name, int $excludeId = 0): bool
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM customers WHERE name = :name AND cust_id != :id'
        );
        $stmt->execute([':name' => $name, ':id' => $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // =========================================================================
    // create()
    // =========================================================================
    public static function create(
        string $name,
        string $phone,
        string $address,
        float  $creditLimit,
        string $joinDate
    ): int {
        $pdo  = Database::getConnection();
        $sql  = 'INSERT INTO customers (name, phone, address, credit_limit, join_date)
                 VALUES (:name, :phone, :address, :credit_limit, :join_date)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'         => $name,
            ':phone'        => $phone,
            ':address'      => $address,
            ':credit_limit' => $creditLimit,
            ':join_date'    => $joinDate,
        ]);
        return (int) $pdo->lastInsertId();
    }

    // =========================================================================
    // update()
    // =========================================================================
    public static function update(
        int    $id,
        string $name,
        string $phone,
        string $address,
        float  $creditLimit
    ): bool {
        $pdo  = Database::getConnection();
        $sql  = 'UPDATE customers
                 SET name = :name, phone = :phone, address = :address,
                     credit_limit = :credit_limit
                 WHERE cust_id = :id';
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':name'         => $name,
            ':phone'        => $phone,
            ':address'      => $address,
            ':credit_limit' => $creditLimit,
            ':id'           => $id,
        ]);
    }

    // =========================================================================
    // delete() — only allowed when balance = 0
    // =========================================================================
    public static function delete(int $id): bool
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM customers WHERE cust_id = :id');
        return $stmt->execute([':id' => $id]);
    }
}
