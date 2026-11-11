<?php
declare(strict_types=1);

/**
 * models/Customer.php
 * -----------------------------------------------------------------------------
 * Data-access layer for the `customers` table.
 *
 * All queries use PDO prepared statements — no string interpolation in SQL.
 * Every method works with the singleton Database connection.
 * -----------------------------------------------------------------------------
 * Public API:
 *   Customer::create()      — Insert a new customer row
 *   Customer::all()         — Return all customers ordered by name
 *   Customer::findById()    — Fetch one customer by primary key
 *   Customer::phoneExists() — Check if a phone number is already registered
 */

require_once __DIR__ . '/../config/Database.php';

class Customer
{
    // -----------------------------------------------------------------
    // create() — Insert a new customer into the database
    // Returns the new cust_id on success, throws PDOException on failure.
    // -----------------------------------------------------------------
    public static function create(
        string $name,
        string $phone,
        string $address,
        float  $creditLimit,
        string $joinDate
    ): int {
        $pdo = Database::getConnection();

        // Prepared statement — values never touch the SQL string directly
        $sql = "INSERT INTO customers (name, phone, address, credit_limit, join_date)
                VALUES (:name, :phone, :address, :credit_limit, :join_date)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'         => $name,
            ':phone'        => $phone,
            ':address'      => $address,
            ':credit_limit' => $creditLimit,
            ':join_date'    => $joinDate,
        ]);

        // Return the auto-incremented ID of the new row
        return (int)$pdo->lastInsertId();
    }

    // -----------------------------------------------------------------
    // all() — Fetch every customer, newest join date first.
    // Returns an array of associative arrays (each row = one customer).
    // -----------------------------------------------------------------
    public static function all(): array
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM customers ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    // -----------------------------------------------------------------
    // findById() — Fetch a single customer by their primary key.
    // Returns the row array, or false if not found.
    // -----------------------------------------------------------------
    public static function findById(int $id): array|false
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE cust_id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(); // returns false when no row found
    }

    // -----------------------------------------------------------------
    // phoneExists() — Check whether a phone number is already in use.
    // $excludeId: when editing, ignore the current customer's own row.
    // Returns true if phone is taken by a DIFFERENT customer.
    // -----------------------------------------------------------------
    public static function phoneExists(string $phone, int $excludeId = 0): bool
    {
        $pdo = Database::getConnection();

        // Exclude the current customer's own row during an edit
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM customers
              WHERE phone = :phone AND cust_id != :exclude"
        );
        $stmt->execute([
            ':phone'   => $phone,
            ':exclude' => $excludeId,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }
}
