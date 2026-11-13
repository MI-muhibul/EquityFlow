# Changelog

All notable changes to the **EquityFlow** project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2026-11-14

### Added
- **Authentication & Security:**
  - Secure login with bcrypt (`password_hash`) and CSRF tokens on all POST requests.
  - Hardened session cookies (`HttpOnly`, `SameSite=Lax`, strict lifetime).
  - 30-minute inactivity session timeout and lockout after 5 consecutive failed attempts.
  - Password change functionality with current password verification.
- **Customer Ledger Management:**
  - Full CRUD operations for customers (`name`, `phone`, `address`, `credit_limit`, `balance`, `join_date`).
  - Strict balance guard: customer deletion blocked if `balance > 0`.
  - Customer khata profile with chronological history and running balance calculation.
  - On-screen transaction receipt view with shop metadata and balance after transaction.
- **Transaction Engine (Credit & Payment):**
  - Multi-step atomic transactions using PDO `beginTransaction`, `commit`, and `rollBack`.
  - Concurrency safety with `SELECT ... FOR UPDATE` row-level locking.
  - Credit limit enforcement: transactions rejected if new balance exceeds `credit_limit`.
  - Payment overpay prevention: payment rejected if amount exceeds current customer balance.
  - Support for direct cash amounts as well as itemized credit sales.
- **Inventory & Stock Management:**
  - Product catalog management (`name`, `category`, `retail_price`, `cost_price`, `stock`, `min_stock`).
  - Automated status classification badges: *In Stock* (green), *Low Stock* (yellow), and *Out of Stock* (red).
  - Real-time stock decrement upon credit sale line item creation.
  - Profit per unit calculation and margin percentage with loss warning indicator.
  - Stock adjustment workflows (manual restock, set exact, or deduct loss).
  - Pure SVG profit trend sparkline generator in PHP.
- **Search, Filtering & Pagination:**
  - GET-based filter forms with input state preservation across page requests.
  - Customer search by name and phone prefix.
  - Transaction filter by date range, customer, and transaction type.
  - Product filter by name, category, and stock availability status.
  - Reusable zero-JS pagination bar (20 records per page) with query parameter carryover.
  - Database seed script (`database/seed_bulk.php`) generating 10,000 realistic customer rows with verified sub-second search times.
- **Reports & Financial Analytics:**
  - Monthly financial summaries (total credit given, total payments collected, net cashflow change, transaction count).
  - Estimated net profit aggregated from `sale_items` margins.
  - Month/Year selector with persistent snapshot recording in `reports` database table.
  - Zero-JS responsive inline SVG comparison bar breakdown chart.
  - Outstanding customer balance list sorted descending by balance with credit limit utilisation percentages.
- **Shop Settings & Backup System:**
  - Persistent settings configuration (`shop_name`, `default_credit_limit`, `default_min_stock`, `default_language`, `shop_phone`, `shop_address`).
  - Pure PDO database dump engine generating full SQL backup archives (`CREATE TABLE` + chunked `INSERT`).
  - Traversal-hardened download streaming endpoint requiring authentication.
  - Apache `.htaccess` denial protection preventing direct web access to `.sql` files in `backups/`.
- **User Experience & Internationalization:**
  - 100% bilingual interface with instantaneous toggle between English and Bangla (বাংলা).
  - High-contrast responsive dark theme styled with custom CSS properties.
  - Strictly **Zero JavaScript** architecture across the entire application.
  - Comprehensive user guide with bilingual headings (`docs/USER_GUIDE.md`).

---

## [0.9.0] - 2026-11-13
### Added
- GET-based search filters and 20-per-page pagination across customers, transactions, and inventory.
- Database bulk seeding tool for 10,000 customers with EXPLAIN execution plan benchmarks.

## [0.8.0] - 2026-11-12
### Added
- Itemized sales-to-inventory link with stock deduction and dashboard alerts.
- Product profit per unit, margin %, and SVG sparkline history.

## [0.5.0] - 2026-11-11
### Added
- Customer khata history running balance and on-screen receipt view.
- Customer CRUD operations with zero-balance deletion guard.

## [0.1.0] - 2026-11-09
### Added
- Initial project structure, front controller router, dark theme CSS, and database schema.
