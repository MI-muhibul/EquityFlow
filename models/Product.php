<?php
declare(strict_types=1);

/**
 * models/Product.php
 * -----------------------------------------------------------------------------
 * Data-access layer for the `products` table.
 *
 * Rules:
 *   - 100% PDO prepared statements — no string interpolation in SQL queries.
 *   - Non-negative validation for prices, stock, and min_stock.
 *   - Unique product name checking (supports excluding self during update).
 *   - Status calculation in PHP:
 *       • stock = 0           => 'out_of_stock' (Out of Stock)
 *       • stock <= min_stock  => 'low_stock'    (Low Stock)
 *       • otherwise           => 'in_stock'     (In Stock)
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/Database.php';

class Product
{
    /**
     * Compute stock status for a product row in PHP.
     *
     * @param int $stock
     * @param int $minStock
     * @return array{key: string, badge_class: string}
     */
    public static function computeStatus(int $stock, int $minStock): array
    {
        if ($stock <= 0) {
            return [
                'key'         => 'status_out_of_stock',
                'badge_class' => 'badge-red',
            ];
        }

        if ($stock <= $minStock) {
            return [
                'key'         => 'status_low_stock',
                'badge_class' => 'badge-yellow',
            ];
        }

        return [
            'key'         => 'status_in_stock',
            'badge_class' => 'badge-green',
        ];
    }

    /**
     * Compute profit per unit (retail - cost) and margin percentage.
     *
     * @param float $retailPrice
     * @param float $costPrice
     * @return array{
     *     profit_per_unit: float,
     *     margin_pct: float,
     *     is_loss: bool
     * }
     */
    public static function computeProfitMetrics(float $retailPrice, float $costPrice): array
    {
        $profit = round($retailPrice - $costPrice, 2);
        $isLoss = ($retailPrice < $costPrice);

        // Margin calculation: (Profit / Retail Price) * 100
        // When retailPrice <= 0, default margin to 0.0%
        if ($retailPrice > 0.0) {
            $margin = round(($profit / $retailPrice) * 100.0, 2);
        } else {
            $margin = 0.0;
        }

        return [
            'profit_per_unit' => $profit,
            'margin_pct'      => $margin,
            'is_loss'         => $isLoss,
        ];
    }

    /**
     * Fetch all products ordered by category ASC, name ASC.
     * Attaches computed status array and profit metrics to each record.
     *
     * @return array
     */
    public static function all(): array
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM products ORDER BY category ASC, name ASC');
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['status_info']    = self::computeStatus((int)$row['stock'], (int)$row['min_stock']);
            $row['profit_metrics'] = self::computeProfitMetrics((float)$row['retail_price'], (float)$row['cost_price']);
        }
        unset($row);

        return $rows;
    }

    /**
     * Find a single product by primary key prod_id.
     *
     * @param int $prodId
     * @return array|false
     */
    public static function findById(int $prodId): array|false
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM products WHERE prod_id = :id LIMIT 1');
        $stmt->execute([':id' => $prodId]);
        $row = $stmt->fetch();

        if ($row) {
            $row['status_info']    = self::computeStatus((int)$row['stock'], (int)$row['min_stock']);
            $row['profit_metrics'] = self::computeProfitMetrics((float)$row['retail_price'], (float)$row['cost_price']);
        }

        return $row;
    }

    /**
     * Fetch profit history from sale_items data for this product, ordered chronologically.
     * Computes profit = (unit_price - unit_cost) * qty for each sale item.
     *
     * @param int $prodId
     * @return array<int, array{
     *     item_id: int,
     *     txn_id: int,
     *     qty: float,
     *     unit_price: float,
     *     unit_cost: float,
     *     unit_profit: float,
     *     total_profit: float,
     *     created_at: string
     * }>
     */
    public static function getSaleProfitHistory(int $prodId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT s.item_id, s.txn_id, s.qty, s.unit_price, s.unit_cost, t.created_at
             FROM sale_items s
             JOIN transactions t ON s.txn_id = t.txn_id
             WHERE s.prod_id = :prod_id
             ORDER BY t.created_at ASC, s.item_id ASC'
        );
        $stmt->execute([':prod_id' => $prodId]);
        $rows = $stmt->fetchAll();

        $history = [];
        foreach ($rows as $r) {
            $qty = (float)$r['qty'];
            $price = (float)$r['unit_price'];
            $cost = (float)$r['unit_cost'];
            $unitProfit = round($price - $cost, 2);
            $totalProfit = round($unitProfit * $qty, 2);

            $history[] = [
                'item_id'      => (int)$r['item_id'],
                'txn_id'       => (int)$r['txn_id'],
                'qty'          => $qty,
                'unit_price'   => $price,
                'unit_cost'    => $cost,
                'unit_profit'  => $unitProfit,
                'total_profit' => $totalProfit,
                'created_at'   => (string)$r['created_at'],
            ];
        }

        return $history;
    }

    /**
     * Check whether a product name is already taken.
     * $excludeId is ignored when adding, or set to current prod_id when editing.
     *
     * @param string $name
     * @param int $excludeId
     * @return bool True if name exists on a different record
     */
    public static function nameExists(string $name, int $excludeId = 0): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM products WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name)) AND prod_id != :exclude'
        );
        $stmt->execute([
            ':name'    => $name,
            ':exclude' => $excludeId,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Insert a new product into inventory.
     *
     * @param string $name
     * @param string $category
     * @param float $retailPrice
     * @param float $costPrice
     * @param int $stock
     * @param int $minStock
     * @return int Inserted prod_id
     */
    public static function create(
        string $name,
        string $category,
        float  $retailPrice,
        float  $costPrice,
        int    $stock,
        int    $minStock
    ): int {
        $pdo = Database::getConnection();
        $sql = 'INSERT INTO products (name, category, retail_price, cost_price, stock, min_stock, created_at)
                VALUES (:name, :category, :retail_price, :cost_price, :stock, :min_stock, NOW())';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'         => trim($name),
            ':category'     => trim($category),
            ':retail_price' => $retailPrice,
            ':cost_price'   => $costPrice,
            ':stock'        => $stock,
            ':min_stock'    => $minStock,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Update an existing product's details.
     *
     * @param int $prodId
     * @param string $name
     * @param string $category
     * @param float $retailPrice
     * @param float $costPrice
     * @param int $stock
     * @param int $minStock
     * @return void
     */
    public static function update(
        int    $prodId,
        string $name,
        string $category,
        float  $retailPrice,
        float  $costPrice,
        int    $stock,
        int    $minStock
    ): void {
        $pdo = Database::getConnection();
        $sql = 'UPDATE products
                SET name = :name,
                    category = :category,
                    retail_price = :retail_price,
                    cost_price = :cost_price,
                    stock = :stock,
                    min_stock = :min_stock
                WHERE prod_id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'         => trim($name),
            ':category'     => trim($category),
            ':retail_price' => $retailPrice,
            ':cost_price'   => $costPrice,
            ':stock'        => $stock,
            ':min_stock'    => $minStock,
            ':id'           => $prodId,
        ]);
    }

    /**
     * Update only the stock quantity for a product.
     *
     * @param int $prodId
     * @param int $newStock
     * @return void
     */
    public static function updateStock(int $prodId, int $newStock): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE products SET stock = :stock WHERE prod_id = :id');
        $stmt->execute([
            ':stock' => max(0, $newStock),
            ':id'    => $prodId,
        ]);
    }
}
