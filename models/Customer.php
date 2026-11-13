<?php
declare(strict_types=1);

/**
 * models/Customer.php
 * -----------------------------------------------------------------------------
 * Data-access layer for the `customers` table.
 *
 * SECURITY RULE: Every single query is scoped to `user_id` so that shop
 * owners can only ever see their own customers. No cross-tenant data leaks.
 *
 * All queries use PDO prepared statements — no string interpolation in SQL.
 *
 * Public methods:
 *   paginate()     – filtered, paginated list (20 per page), scoped to user
 *   all()          – full list for dropdowns, scoped to user
 *   findById()     – single customer by PK AND user_id (returns null if wrong owner)
 *   create()       – insert new customer for a user
 *   update()       – update existing customer (only if same user owns it)
 *   delete()       – remove customer (only if same user owns it)
 *   phoneExists()  – unique-phone check per user (excludes the same record on edit)
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/Database.php';

class Customer
{
    // =========================================================================
    // paginate() — filtered, paginated list scoped to user
    // =========================================================================
    /**
     * Return one page of customers belonging to $userId, with optional filters.
     *
     * @param int    $userId  The logged-in shop owner's ID
     * @param array  $filters Optional: ['name' => '', 'phone' => '']
     * @param int    $page    1-based page number
     * @param int    $perPage Rows per page (default 20)
     * @return array{items: array, total: int, page: int, per_page: int, total_pages: int}
     */
    public static function paginate(int $userId, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $pdo    = Database::getConnection();
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;

        // Always start with the ownership scope
        $where  = ['user_id = :uid'];
        $params = [':uid' => $userId];

        $name  = trim($filters['name']  ?? '');
        $phone = trim($filters['phone'] ?? '');

        if ($name !== '') {
            $where[]         = 'name LIKE :name';
            $params[':name'] = '%' . $name . '%';
        }
        if ($phone !== '') {
            $where[]          = 'phone LIKE :phone';
            $params[':phone'] = '%' . $phone . '%';
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);

        // Count total matching rows
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM customers {$whereSQL}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Fetch the page (LIMIT/OFFSET are cast to int — safe to interpolate)
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
            'total_pages' => $total > 0 ? (int)ceil($total / $perPage) : 1,
        ];
    }

    // =========================================================================
    // all() — full list scoped to user (for dropdowns, exports)
    // =========================================================================
    /**
     * @return array All customer rows for this shop owner, ordered by name.
     */
    public static function all(int $userId): array
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE user_id = :uid ORDER BY name ASC');
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    // =========================================================================
    // findById() — ownership check built in
    // =========================================================================
    /**
     * Find a single customer by PK, but only if it belongs to $userId.
     * Returns null (not false) so the controller can safely pass it to 404.
     *
     * @param int $id     The cust_id to look up
     * @param int $userId The logged-in shop owner's ID
     * @return array<string,mixed>|null
     */
    public static function findById(int $id, int $userId): ?array
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM customers WHERE cust_id = :id AND user_id = :uid LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // =========================================================================
    // phoneExists() — unique per user, excluding the same record on edit
    // =========================================================================
    /**
     * @param string $phone     Bangladeshi mobile number to check
     * @param int    $userId    The shop owner's user_id
     * @param int    $excludeId cust_id to skip (pass 0 on create, actual ID on edit)
     */
    public static function phoneExists(string $phone, int $userId, int $excludeId = 0): bool
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM customers
             WHERE phone = :phone AND user_id = :uid AND cust_id != :excl'
        );
        $stmt->execute([
            ':phone' => $phone,
            ':uid'   => $userId,
            ':excl'  => $excludeId,
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // =========================================================================
    // create()
    // =========================================================================
    /**
     * Insert a new customer row.
     *
     * @return int The new cust_id
     */
    public static function create(
        int    $userId,
        string $name,
        string $phone,
        string $address,
        float  $creditLimit,
        string $joinDate
    ): int {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO customers (user_id, name, phone, address, credit_limit, join_date)
             VALUES (:uid, :name, :phone, :address, :credit_limit, :join_date)'
        );
        $stmt->execute([
            ':uid'          => $userId,
            ':name'         => $name,
            ':phone'        => $phone,
            ':address'      => $address,
            ':credit_limit' => $creditLimit,
            ':join_date'    => $joinDate,
        ]);
        return (int)$pdo->lastInsertId();
    }

    // =========================================================================
    // update() — scoped to user so no row from another account is ever modified
    // =========================================================================
    /**
     * @return bool True on success
     */
    public static function update(
        int    $id,
        int    $userId,
        string $name,
        string $phone,
        string $address,
        float  $creditLimit
    ): bool {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE customers
             SET name = :name, phone = :phone, address = :address,
                 credit_limit = :credit_limit
             WHERE cust_id = :id AND user_id = :uid'
        );
        return $stmt->execute([
            ':name'         => $name,
            ':phone'        => $phone,
            ':address'      => $address,
            ':credit_limit' => $creditLimit,
            ':id'           => $id,
            ':uid'          => $userId,
        ]);
    }

    // =========================================================================
    // delete() — balance guard + user scope (controller checks balance first)
    // =========================================================================
    /**
     * Remove the customer row only if balance = 0 and user_id matches.
     *
     * @return bool True on success, false if no row was deleted (wrong owner / balance != 0)
     */
    public static function delete(int $id, int $userId): bool
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare(
            'DELETE FROM customers
             WHERE cust_id = :id AND user_id = :uid AND balance = 0'
        );
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }
}
