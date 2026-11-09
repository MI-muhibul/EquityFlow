# EquityFlow

> **Where Financial Records Flow with Accuracy**

EquityFlow is a lightweight, high-performance, bilingual (Bangla/English) digital Khata ledger built specifically for small and medium retail shops in Bangladesh. It digitizes daily credit (*Baki*), debit (*Joma*), customer balances, and supplier transactions with bank-grade integrity.

---

## 🛠 Tech Stack

- **Backend:** PHP 8.x (Strict OOP + MVC Architecture)
- **Database:** MySQL via PDO (100% Prepared Statements only)
- **Frontend:** Pure HTML5, Semantic CSS3 (Dark, High-Contrast & Mobile-Responsive)
- **Scripting:** Zero JavaScript (**Strictly NO JS** for maximum device compatibility and stability)
- **Environment:** XAMPP (Apache + MySQL on Windows/Linux/macOS)

---

## 🚀 XAMPP Setup Guide

Follow these steps to run EquityFlow locally with XAMPP:

1. **Clone or Copy Repository:**
   Place the `EquityFlow` directory inside your XAMPP web root:
   ```text
   C:\xampp\htdocs\EquityFlow
   ```

2. **Start XAMPP Services:**
   - Launch the **XAMPP Control Panel**.
   - Start **Apache** and **MySQL**.

3. **Configure Environment:**
   - Create your local configuration file by copying `config/config.example.php` (once created) to `config/config.php`.
   - Update your MySQL credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'equityflow');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

4. **Import Database:**
   - Open phpMyAdmin (`http://localhost/phpmyadmin`).
   - Create a database named `equityflow` with collation `utf8mb4_unicode_ci`.
   - Import the schema file from `config/database.sql` (in upcoming push).

5. **Access the Application:**
   Open your browser and navigate to:
   ```text
   http://localhost/EquityFlow/index.php
   ```

---

## 📂 Project Folder Tree

```text
EquityFlow/
├── config/                  # Database credentials & app configuration
│   └── .gitkeep
├── models/                  # Business logic & PDO database operations
│   └── .gitkeep
├── controllers/             # Request handlers & page controllers
│   └── .gitkeep
├── views/                   # HTML5 presentation templates
│   └── .gitkeep
├── assets/                  # High-contrast CSS styles & static assets
│   └── .gitkeep
├── lang/                    # Bilingual localization dictionaries
│   ├── .gitkeep
│   ├── bn.php               # বাংলা (Bangla translation pack)
│   └── en.php               # English translation pack
├── backups/                 # Exported SQL database backups
│   └── .gitkeep
├── .gitignore               # Ignored local files & backups
├── index.php                # Front controller & request router
└── README.md                # Project documentation & roadmap
```

---

## 📋 Status Checklist (20-Push Roadmap)

- [x] **Push 01:** Project Skeleton, Directory Structure & Front Controller Router
- [ ] **Push 02:** Database Schema & Migration SQL Scripts
- [ ] **Push 03:** Database Configuration & PDO Singleton Connection
- [ ] **Push 04:** Bilingual Localization Engine (Bangla `bn` / English `en`)
- [ ] **Push 05:** Security Core: CSRF Token Engine & Output Escaper
- [ ] **Push 06:** Authentication System: Shop Owner Login, Session & Logout
- [ ] **Push 07:** Dark High-Contrast Responsive CSS & Master Layout
- [ ] **Push 08:** Customer Model, Controller & Management Views
- [ ] **Push 09:** Supplier Model, Controller & Management Views
- [ ] **Push 10:** Transaction Ledger Core: Debit, Credit & Due Logic
- [ ] **Push 11:** Daily Khata (Cashbook) Entry for Sales & Purchases
- [ ] **Push 12:** Customer Individual Ledger & Outstanding Balance Calculation
- [ ] **Push 13:** Supplier Individual Ledger & Payment Records
- [ ] **Push 14:** Due Collection Voucher & Payment Entry Forms
- [ ] **Push 15:** Printable Slip / Money Receipt Generator (CSS Print-friendly, No JS)
- [ ] **Push 16:** Ledger Search, Filter & Date Range Pagination
- [ ] **Push 17:** Financial Reports: Daily Summary, Cash Flow & Profit/Loss Overview
- [ ] **Push 18:** One-Click Database Backup & SQL Export Engine
- [ ] **Push 19:** Comprehensive Validation, Error Boundary & Edge-case Hardening
- [ ] **Push 20:** System Verification, Seed Data Demo & Final Production Handover
