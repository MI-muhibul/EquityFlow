-- =============================================================================
-- EquityFlow - Database Schema
-- Project: Digital Khata Ledger for Small Retail Shops in Bangladesh
-- Engine: MySQL / MariaDB (InnoDB, utf8mb4)
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. Table: users
-- Stores authenticated shop owners and staff members
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `user_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `pin_hash` VARCHAR(255) NULL,
    `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    `failed_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `locked` TINYINT(1) NOT NULL DEFAULT 0,
    `last_login` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Table: customers
-- Stores customer profiles, balances (due/baki), and credit limits
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
    `cust_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL UNIQUE,
    `address` TEXT NULL,
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `credit_limit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `join_date` DATE NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_customers_name` (`name`),
    INDEX `idx_customers_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Table: transactions
-- Records daily Khata entries (credit/baki vs payment/joma)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
    `txn_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cust_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `type` ENUM('credit', 'payment') NOT NULL,
    `note` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_transactions_cust_id` (`cust_id`),
    INDEX `idx_transactions_created_at` (`created_at`),
    CONSTRAINT `fk_transactions_customer`
        FOREIGN KEY (`cust_id`) REFERENCES `customers` (`cust_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Table: products
-- Inventory items commonly sold in grocery/general shops
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
    `prod_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL UNIQUE,
    `category` VARCHAR(100) NOT NULL,
    `retail_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `cost_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `stock` INT NOT NULL DEFAULT 0,
    `min_stock` INT NOT NULL DEFAULT 5,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. Table: sale_items
-- Line items associated with credit/sales transactions
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `sale_items`;
CREATE TABLE `sale_items` (
    `item_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `txn_id` INT UNSIGNED NOT NULL,
    `prod_id` INT UNSIGNED NOT NULL,
    `qty` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `unit_cost` DECIMAL(12,2) NOT NULL,
    INDEX `idx_sale_items_txn_id` (`txn_id`),
    INDEX `idx_sale_items_prod_id` (`prod_id`),
    CONSTRAINT `fk_sale_items_transaction`
        FOREIGN KEY (`txn_id`) REFERENCES `transactions` (`txn_id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_sale_items_product`
        FOREIGN KEY (`prod_id`) REFERENCES `products` (`prod_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. Table: ledger_entries
-- Audit ledger trail mapping each transaction to an authorized user/owner
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `ledger_entries`;
CREATE TABLE `ledger_entries` (
    `ledger_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `owner_id` INT UNSIGNED NOT NULL,
    `txn_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ledger_entries_owner_id` (`owner_id`),
    INDEX `idx_ledger_entries_txn_id` (`txn_id`),
    CONSTRAINT `fk_ledger_entries_owner`
        FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_ledger_entries_transaction`
        FOREIGN KEY (`txn_id`) REFERENCES `transactions` (`txn_id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. Table: reports
-- Pre-computed financial aggregations and periodic summaries
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
    `report_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `period` VARCHAR(50) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `generated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_reports_period` (`period`),
    INDEX `idx_reports_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. Table: settings
-- Key-value storage for shop configuration, business info & locale settings
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
    `skey` VARCHAR(64) PRIMARY KEY,
    `svalue` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
