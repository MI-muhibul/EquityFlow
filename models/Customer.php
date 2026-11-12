<?php
declare(strict_types=1);

/**
 * models/Customer.php
 * -----------------------------------------------------------------------------
 * Data-access layer for the `customers` table.
 * All queries use PDO prepared statements — no string interpolation in SQL.
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/Database.php';

class Customer
{
    // -----------------------------------------------------------------
    // create() — Insert a new customer into the database
    // -----------------------------------------------------------------
    public static function create(
        string $name,
        string $phone,
        string $address,
        float $creditLimit,
        string $joinDate
    ): int {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO customers (name, phone, address, credit_limit, join_date) " .
               "VALUES (:name, :phone, :address, :credit_limit, :join_date)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'         => $name,
            ':phone'        => $phone,
            ':address'      => $address,
            ':credit_limit' => $creditLimit,
            ':join_date'    => $joinDate,
        ]);
        return (int)$pdo->lastInsertId();
    }

    // -----------------------------------------------------------------
    // all() — Fetch every customer, ordered by name ASC
    // -----------------------------------------------------------------
    public static function all(): array {
        $pdo  = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM customers ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    // -----------------------------------------------------------------
    // findById() — Fetch a single customer by primary key
    // -----------------------------------------------------------------
    public static function findById(int $id): array|false {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE cust_id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(); // false when not found
    }

    // -----------------------------------------------------------------
    // phoneExists() — Check whether a phone number is already used.
    // $excludeId is ignored when creating a new record (default 0).
    // -----------------------------------------------------------------
    public static function phoneExists(string $phone, int $excludeId = 0): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM customers WHERE phone = :phone AND cust_id != :exclude"
        );
        $stmt->execute([
            ':phone'   => $phone,
            ':exclude' => $excludeId,
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // -----------------------------------------------------------------
    // update() — Update an existing customer (used by edit action)
    // -----------------------------------------------------------------
    public static function update(
        int $id,
        string $name,
        string $phone,
        string $address,
        float $creditLimit
    ): void {
        $pdo = Database::getConnection();
        $sql = "UPDATE customers SET name = :name, phone = :phone, address = :address, " .
               "credit_limit = :credit_limit WHERE cust_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'         => $name,
            ':phone'        => $phone,
            ':address'      => $address,
            ':credit_limit' => $creditLimit,
            ':id'           => $id,
        ]);
    }

    // -----------------------------------------------------------------
    // delete() — Remove a customer (only when balance == 0)
    // -----------------------------------------------------------------
    public static function delete(int $id): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM customers WHERE cust_id = :id");
        $stmt->execute([':id' => $id]);
    }
}
