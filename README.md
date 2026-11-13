<div align="center">

# EquityFlow

### Where Financial Records Flow with Accuracy

A bilingual (বাংলা / English) digital **Khata** ledger for small shop owners in Bangladesh, built with PHP, MySQL, HTML and CSS. No JavaScript, no paid hosting, no internet needed for daily use.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?logo=css3&logoColor=white)
![XAMPP](https://img.shields.io/badge/Server-XAMPP-FB7A24?logo=xampp&logoColor=white)
![Status](https://img.shields.io/badge/status-released-success)
![Version](https://img.shields.io/badge/version-1.0.0-blue)

</div>

---

## Table of Contents

1. [About the Project](#about-the-project)
2. [The Problem](#the-problem)
3. [Features (v1.0)](#features-v10)
4. [Screenshots](#screenshots)
5. [Tech Stack](#tech-stack)
6. [Architecture](#architecture)
7. [Folder Structure](#folder-structure)
8. [Database Design](#database-design)
9. [Getting Started](#getting-started)
10. [Security](#security)
11. [Bilingual Interface](#bilingual-interface)
12. [Testing & Quality Assurance](#testing--quality-assurance)
13. [Known Limitations from Testing](#known-limitations-from-testing)
14. [Development Roadmap](#development-roadmap)
15. [Out of Scope and Future Plans](#out-of-scope-and-future-plans)
16. [Author](#author)
17. [License](#license)

---

## About the Project

EquityFlow replaces the handwritten credit book (the *Khata*) used by grocery stores, pharmacies, tailors, and repair shops across Bangladesh with a secure, searchable, and automated local system. It tracks customer credit, manages product inventory, and produces comprehensive monthly financial reports.

It runs locally on a standard **XAMPP** environment (Apache + PHP + MySQL), eliminating cloud subscription costs and internet dependency. Devices on the same local Wi-Fi network (such as smartphones or tablets) can open and operate the ledger directly from any web browser.

## The Problem

The traditional paper Khata book is used by millions of merchants, but presents severe operational and financial vulnerabilities:

- Arithmetic mistakes in balances cause unnoticed financial loss.
- Entries can be illegible, altered, damaged, or lost.
- There is no instant way to see a customer's total outstanding balance or chronological transaction history.
- Credit disputes cannot be settled with objective, timestamped evidence.
- Stock is tracked by eye, leading to stockouts and lost revenue.
- Retail price, cost price, and gross profit margins are rarely calculated or monitored.

EquityFlow solves these problems through automatic arithmetic calculation, timestamped transaction history, credit-limit enforcement, and an accessible interface designed for users with limited digital literacy.

## Features (v1.0)

| Module | What it does |
|---|---|
| **Login and Access** | Salted passwords (`password_hash`), 30-minute inactivity session timeout, lockout after 5 consecutive failed attempts, password change screen. |
| **Customer Ledger** | Add, edit, view, and delete customers (deletion strictly guarded when balance > 0), individual credit limits. |
| **Credit Transactions** | Atomic credit sales and payments via PDO transactions, automatic running balance, credit-limit check, payment overpay prevention, on-screen receipts. |
| **Sales to Inventory** | Itemized product sales deducting catalog stock in real time, calculating sale totals from catalog retail prices. |
| **Inventory & Stock** | Product catalog, stock updates/adjustments, minimum alert stock thresholds, automated badges (*In Stock* / *Low Stock* / *Out of Stock*), dashboard alert panel. |
| **Pricing and Profit** | Retail price and cost price tracking per product, automated unit profit, margin %, loss warnings, and profit trend history via inline SVG sparklines. |
| **Search and Filter** | GET-based zero-JS filters: customers by name/phone; transactions by date range, customer, and type; products by name, category, and status. 20-per-page pagination with preserved inputs. |
| **Reports** | Monthly financial summary (credit given, payments received, net cashflow, estimated profit), pure SVG visual comparison chart, and debtors list sorted descending by balance. |
| **Settings & Backups** | Shop profile and defaults management, one-click pure PDO database backup dump (`.sql`), secure authenticated download, and `.htaccess` file protection. |

All amounts are formatted in Bangladeshi Taka (৳) with two decimal places.

## Screenshots

| 1. Shop Dashboard | 2. Customer Ledger Profile |
|---|---|
| ![Dashboard](assets/images/screenshots/dashboard.png) | ![Customer Ledger](assets/images/screenshots/customer_ledger.png) |

| 3. Record Credit Sale & Stock Deduction | 4. Inventory Catalog & Profit Margins |
|---|---|
| ![New Transaction](assets/images/screenshots/transaction_create.png) | ![Inventory](assets/images/screenshots/inventory.png) |

| 5. Monthly Financial Report & SVG Chart | 6. Settings & Database Backup |
|---|---|
| ![Monthly Report](assets/images/screenshots/reports.png) | ![Settings](assets/images/screenshots/settings.png) |

> *Note: Place your PNG screenshots in `assets/images/screenshots/` with the filenames shown above.*

## Tech Stack

| Layer | Technology |
|---|---|
| Presentation | HTML5 + CSS3 (responsive, high-contrast dark theme) |
| Business Logic | PHP 8.x OOP + MVC (validation, balance updates, stock deductions, profit calculation) |
| Data Persistence | MySQL / MariaDB via PDO prepared statements |
| Local Server | Apache via XAMPP |
| Development Tools | VS Code, phpMyAdmin, Git/GitHub |

**Strictly Zero JavaScript.** All form validation runs through HTML5 attributes and server-side PHP. Language switching is handled through PHP session dictionaries, and charts are rendered as pure, scalable inline SVG markup.

## Architecture

EquityFlow follows a standard **MVC (Model-View-Controller)** pattern:

- **Model**: Data-access classes (`User`, `Customer`, `Transaction`, `Product`, `Report`, `Setting`, `Backup`) executing parameterized queries via PDO.
- **View**: Server-rendered HTML templates with consistent layout wrappers (`header.php`, `footer.php`, `pagination.php`).
- **Controller**: Request handlers validating inputs, enforcing authentication, and orchestrating models and views.
- **Database**: Normalized relational MySQL tables with foreign keys. Multi-step operations employ `BEGIN / COMMIT / ROLLBACK` transactions with row-level locks (`SELECT ... FOR UPDATE`) to guarantee ACID compliance.

## Folder Structure

```
equityflow/
├── assets/
│   ├── css/
│   │   └── style.css            # Dark high-contrast responsive styling
│   └── images/
│       └── screenshots/         # Application UI preview captures
├── backups/                     # Generated SQL dumps (.htaccess protected)
│   ├── .htaccess
│   └── .gitkeep
├── config/
│   ├── auth_guard.php           # require_login() enforcement
│   ├── constants.php            # Currency, pagination, session constants
│   ├── csrf.php                 # CSRF token generation & verification
│   ├── Database.php             # PDO singleton database connection
│   ├── lang.php                 # Bilingual translation helper t()
│   └── session.php              # Hardened session initialization
├── controllers/                 # Route controllers (Auth, Customer, Product, etc.)
├── database/
│   ├── schema.sql               # Database schema (utf8mb4, InnoDB)
│   ├── seed.sql                 # Initial seed dataset (1 admin, 5 customers, 8 products)
│   ├── seed_bulk.php            # 10,000 customer benchmark generator
│   └── test_paginate.php        # 30-point integration test suite
├── docs/
│   └── USER_GUIDE.md            # Plain-language user guide (Bangla & English)
├── helpers/
│   ├── chart.php                # Pure inline SVG bar comparison generator
│   ├── format.php               # e() escaping, format_money(), flash alerts
│   ├── sparkline.php            # Pure inline SVG sparkline polyline generator
│   └── validation.php           # BD phone, positive amount, string sanitizers
├── lang/
│   ├── bn.php                   # Bangla translation dictionary
│   └── en.php                   # English translation dictionary
├── models/                      # Business & data-access models
├── views/                       # Server-rendered view templates
├── CHANGELOG.md                 # Release version history
├── index.php                    # Front controller & URL whitelist router
└── README.md                    # Project documentation
```

## Database Design

| Table | Key Columns | Description |
|---|---|---|
| `users` | `user_id`, `username`, `password_hash`, `pin_hash`, `role`, `failed_attempts`, `locked`, `last_login` | Authenticated merchants & staff |
| `customers` | `cust_id`, `name`, `phone`, `address`, `balance`, `credit_limit`, `join_date` | Customer profiles & balances |
| `transactions` | `txn_id`, `cust_id` (FK), `amount`, `type` (`credit`/`payment`), `note`, `created_at` | Khata ledger transactions |
| `products` | `prod_id`, `name`, `category`, `retail_price`, `cost_price`, `stock`, `min_stock` | Inventory catalog |
| `sale_items` | `item_id`, `txn_id` (FK), `prod_id` (FK), `qty`, `unit_price`, `unit_cost` | Itemized credit sale line items |
| `ledger_entries` | `ledger_id`, `owner_id` (FK), `txn_id` (FK), `created_at` | Audit trail linking owner to transaction |
| `reports` | `report_id`, `period`, `type`, `total`, `generated_at` | Monthly snapshot financial aggregations |
| `settings` | `skey` (PK), `svalue` | Key-value shop preferences & defaults |

## Getting Started

### Requirements

- Computer running Windows 10/11 or Linux with 4 GB RAM or more
- [XAMPP](https://www.apachefriends.org/) (Apache 2.4+, PHP 8.1+, MySQL/MariaDB 10.4+)
- Modern web browser (Google Chrome, Mozilla Firefox, or Microsoft Edge)

### Installation

1. **Clone or copy the repository** into your XAMPP `htdocs` directory:
   ```bash
   cd C:/xampp/htdocs
   git clone https://github.com/MI-muhibul/EquityFlow.git equityflow
   ```
2. **Start Apache and MySQL** from the XAMPP Control Panel.
3. **Import Database Schema & Seeds:**
   - Open `http://localhost/phpmyadmin` in your browser.
   - Create a database named `equityflow` with collation `utf8mb4_unicode_ci`.
   - Import `database/schema.sql`.
   - Import `database/seed.sql`.
4. **Open the Application:**
   - Navigate to `http://localhost/equityflow` in your web browser.

### First Login

- **Username:** `admin`
- **Password:** `Admin@123`

> [!IMPORTANT]
> Immediately upon your first login, navigate to the **Change Password** page (click the 🔑 key icon in the navigation bar) and set a new, secure password.

### Local Network Access (Using from Mobile Devices)

To access EquityFlow from a smartphone or tablet connected to the same shop Wi-Fi network:
1. Find your host computer's local IP address (e.g. run `ipconfig` in Command Prompt to find your IPv4 address, such as `192.168.0.105`).
2. Open the browser on your phone and navigate to `http://192.168.0.105/equityflow`.

## Security

- **Password Storage:** Salted, hashed passwords using PHP `password_hash()` with `PASSWORD_BCRYPT`.
- **Brute-Force Lockout:** Automatic account locking after 5 consecutive failed login attempts.
- **Session Hardening:** 30-minute inactivity session timeout, session ID regeneration upon login, and secure cookie attributes (`HttpOnly`, `SameSite=Lax`).
- **SQL Injection Prevention:** 100% of database queries use PDO prepared statements with strict parameter binding.
- **Cross-Site Scripting (XSS) Mitigation:** All dynamic view output is sanitized using `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
- **Cross-Site Request Forgery (CSRF):** Cryptographically secure one-time CSRF tokens validated on every POST request.
- **Transactional Atomicity:** Multi-table operations wrapped in ACID PDO transactions (`beginTransaction`, `commit`, `rollBack`).
- **Direct Directory Denial:** Apache configuration in `backups/.htaccess` strictly denies direct HTTP requests to database dump files.

## Bilingual Interface

Every UI string, button, badge, and validation message is loaded dynamically from `lang/en.php` or `lang/bn.php`. Users can switch languages instantly by clicking the **🌐 বাংলা** / **🌐 English** button in the header navigation.

## Testing & Quality Assurance

EquityFlow includes comprehensive automated and manual test verification:
- **30-Point Pagination & Filter Test Suite (`database/test_paginate.php`):** Validates query building, bounds checking, and input retention across all models (**30/30 PASS**).
- **Atomic Rollback Verification (`database/test_rollback.php`):** Proves that simulated failures roll back database state cleanly with zero orphaned rows.
- **High-Volume Benchmark (`database/seed_bulk.php`):** Populates 10,000 customer records in under 1 second (0.46s benchmarked) with verified sub-50ms search latency using B-Tree indexes (`idx_customers_name`, `idx_customers_phone`).

## Known Limitations from Testing

Through rigorous QA testing, the following architectural and environment constraints are documented for v1.0:

1. **Leading Wildcard Search Scans:** While prefix searches (`name LIKE 'Mohammad%'`) hit the B-Tree index with sub-millisecond range scans, search terms entered with leading wildcards (`%keyword%`) perform full table scans. (With 10,000 rows and `LIMIT 20`, latency remains $< 50\text{ ms}$ on local XAMPP).
2. **Server-Side Pagination Workflow:** Because EquityFlow strictly avoids client-side JavaScript, changing page numbers or applying filters triggers a standard HTTP GET request reload rather than an asynchronous DOM replacement.
3. **Local Network Scope:** The application is architected for local shop intranet use via Apache/XAMPP. Access from outside the shop Wi-Fi network requires a VPN or reverse proxy (e.g., Cloudflare Tunnel / ngrok), which is not part of default setup.
4. **Thermal Printer Direct Spooling:** Receipts and reports are designed for on-screen viewing and standard browser printing (`Ctrl + P`); raw ESC/POS thermal printer driver integration is not supported in v1.0.

## Development Roadmap

- [x] 1. Repository skeleton, router, and documentation
- [x] 2. Database schema and seed data
- [x] 3. PDO connection, constants, secure session
- [x] 4. Bangla / English bilingual language system
- [x] 5. Base layout and dark responsive theme
- [x] 6. Login with `password_hash`
- [x] 7. Lockout, timeout, CSRF, password change
- [x] 8. Dashboard shell and navigation
- [x] 9. Customers: add and list
- [x] 10. Customers: edit, view profile, delete guard
- [x] 11. Credit sales and payments with rollback safety
- [x] 12. History with running balance and on-screen receipt
- [x] 13. Products: add, edit, stock status computation
- [x] 14. Profit, margin %, loss warning, and SVG sparkline
- [x] 15. Stock reduction and low-stock alerts
- [x] 16. Search and GET-based filters (20 per page)
- [x] 17. Monthly reports and inline SVG bar breakdown
- [x] 18. Settings and pure PDO SQL database backup
- [x] 19. Testing, bug fixes, and performance benchmarking
- [x] 20. Final documentation and v1.0 release

## Out of Scope and Future Plans

**Not in v1.0:** Online mobile payment APIs (bKash / Nagad merchant gateway), native Android/iOS mobile apps, automated SMS/WhatsApp gateways, receipt OCR/voice input.

**Planned for Future Releases:**
- Native 58mm / 80mm ESC/POS thermal printer direct output
- SMS and WhatsApp digital slip sharing via webhook integrations
- bKash / Nagad QR payment collection
- Multi-user role-based permissions (Cashier vs Manager vs Owner)
- Progressive Web App (PWA) offline service workers

## Author

**Md Muhibul Islam Abir** (ID: 242-134-042)  
Department of Software Engineering, Metropolitan University, Sylhet  

Submitted to: **Lukman Nakib**, Guest Lecturer, Department of Software Engineering, Metropolitan University, Sylhet.

GitHub: [@MI-muhibul](https://github.com/MI-muhibul)

## License

This project is licensed under the [MIT License](LICENSE).
