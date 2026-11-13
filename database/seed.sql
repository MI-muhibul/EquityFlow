-- =============================================================================
-- EquityFlow - Seed Data (Multi-Tenant Demo Dataset)
-- Demo Shop Owner:
--   Name:        আব্দুর রহিম (Abdur Rahim)
--   Email:       demo@equityflow.test
--   Password:    Admin@123
--   Shop Name:   রহিম স্টোর (Rahim General Store)
-- Real bcrypt hash for 'Admin@123': $2y$10$W.c0Su14eb/vtiRyPOQkue4P1PDeSAXoOgrKZNlAVpk3VyNzAbTKa
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. Seed: users
-- 1 Demo Shop Owner with local email+password authentication
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `users`;
INSERT INTO `users` (
    `user_id`, `name`, `email`, `password_hash`, `google_id`, `avatar_url`,
    `auth_provider`, `pin_hash`, `shop_name`, `failed_attempts`, `locked`,
    `created_at`, `last_login`
) VALUES (
    1,
    'আব্দুর রহিম (Abdur Rahim)',
    'demo@equityflow.test',
    '$2y$10$W.c0Su14eb/vtiRyPOQkue4P1PDeSAXoOgrKZNlAVpk3VyNzAbTKa',
    NULL,
    NULL,
    'local',
    NULL,
    'রহিম জেনারেল স্টোর (Rahim General Store)',
    0,
    0,
    '2026-11-01 08:00:00',
    NULL
);

-- -----------------------------------------------------------------------------
-- 2. Seed: customers
-- 5 retail customers owned by user_id = 1
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `customers`;
INSERT INTO `customers` (`cust_id`, `user_id`, `name`, `phone`, `address`, `balance`, `credit_limit`, `join_date`, `created_at`) VALUES
(1, 1, 'মোঃ করিম উল্লাহ (Md. Karim Ullah)', '01711223344', 'বাড়ি #১২, রোড #৪, মিরপুর-১০, ঢাকা', 1850.00, 10000.00, '2026-01-15', '2026-01-15 10:00:00'),
(2, 1, 'ফারহানা হক (Farhana Huq)', '01819556677', 'দোকান #৫, চকবাজার, চট্টগ্রাম', 3400.00, 15000.00, '2026-02-01', '2026-02-01 11:30:00'),
(3, 1, 'তাহমিনা আক্তার (Tahmina Akter)', '01912334455', 'হাউজিং এস্টেট, উপশহর, সিলেট', 0.00, 8000.00, '2026-02-20', '2026-02-20 14:15:00'),
(4, 1, 'মোঃ রফিকুল ইসলাম (Md. Rafiqul Islam)', '01552445566', 'সাহেব বাজার মেইন রোড, বোয়ালিয়া, রাজশাহী', 520.00, 5000.00, '2026-03-10', '2026-03-10 09:45:00'),
(5, 1, 'সুমন চন্দ্র দাস (Sumon Chandra Das)', '01671889900', 'রেলগেট মোড়, দৌলতপুর, খুলনা', 2150.00, 12000.00, '2026-04-05', '2026-04-05 16:00:00');

-- -----------------------------------------------------------------------------
-- 3. Seed: products
-- 8 staple inventory items owned by user_id = 1
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `products`;
INSERT INTO `products` (`prod_id`, `user_id`, `name`, `category`, `retail_price`, `cost_price`, `stock`, `min_stock`, `created_at`) VALUES
(1, 1, 'মিনিকেট চাল (Miniket Rice 5kg)', 'মুদি / Grocery', 380.00, 350.00, 45, 10, '2026-01-10 08:00:00'),
(2, 1, 'রূপচাঁদা সয়াবিন তেল (Rupchanda Soybean Oil 2L)', 'তেল / Cooking Oil', 370.00, 345.00, 30, 8, '2026-01-10 08:00:00'),
(3, 1, 'দেশি মসুর ডাল (Deshi Red Lentils 1kg)', 'ডাল / Pulses', 140.00, 125.00, 60, 15, '2026-01-10 08:00:00'),
(4, 1, 'তীর রিফাইন্ড চিনি (Teer Refined Sugar 1kg)', 'মুদি / Grocery', 135.00, 122.00, 50, 10, '2026-01-10 08:00:00'),
(5, 1, 'ইস্পাহানি মির্জাপুর চা পাতা (Ispahani Tea 400g)', 'চা ও পানীয় / Beverage', 220.00, 195.00, 25, 5, '2026-01-10 08:00:00'),
(6, 1, 'লাক্স সাবান (Lux Beauty Soap 150g)', 'প্রসাধন / Toiletries', 75.00, 62.00, 80, 20, '2026-01-10 08:00:00'),
(7, 1, 'হুইল ডিটারজেন্ট পাউডার (Wheel Detergent 1kg)', 'পরিচ্ছন্নতা / Cleaning', 130.00, 110.00, 40, 10, '2026-01-10 08:00:00'),
(8, 1, 'প্রাণ গুড়া দুধ (Pran Milk Powder 500g)', 'দুগ্ধজাত / Dairy', 430.00, 390.00, 20, 6, '2026-01-10 08:00:00');

-- -----------------------------------------------------------------------------
-- 4. Seed: transactions
-- Sample credit (baki) and payment (joma) records for customers
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `transactions`;
INSERT INTO `transactions` (`txn_id`, `cust_id`, `amount`, `type`, `note`, `created_at`) VALUES
-- Txn 1: Karim purchases rice and oil on credit (Total: ৳1,130)
(1, 1, 1130.00, 'credit', 'মিনিকেট চাল ২ বস্তা ও তেল বাকিতে ক্রয়', '2026-11-05 10:15:00'),
-- Txn 2: Karim pays cash towards balance (Payment: ৳500)
(2, 1, 500.00, 'payment', 'নগদ কিস্তি জমা (Cash payment)', '2026-11-06 17:30:00'),
-- Txn 3: Karim purchases sugar and tea on credit (Total: ৳1,220)
(3, 1, 1220.00, 'credit', 'চিনি ও চা পাতা বাকিতে ক্রয়', '2026-11-07 11:00:00'),
-- Txn 4: Farhana purchases grocery items on credit (Total: ৳3,400)
(4, 2, 3400.00, 'credit', 'দোকানের জন্য পাইকারি মুদি মালামাল বাকি', '2026-11-07 14:45:00'),
-- Txn 5 & 6: Tahmina purchases items and clears payment immediately
(5, 3, 1000.00, 'credit', 'সাবান ও ডিটারজেন্ট ক্রয়', '2026-11-08 12:00:00'),
(6, 3, 1000.00, 'payment', 'সম্পূর্ণ বাকি পরিশোধ (Full balance cleared)', '2026-11-08 12:05:00'),
-- Txn 7: Rafiqul purchases milk powder & lentils (Total: ৳520)
(7, 4, 520.00, 'credit', 'ডাল ও সাবান বাকি', '2026-11-08 15:20:00'),
-- Txn 8: Sumon purchases goods on credit (Total: ৳2,150)
(8, 5, 2150.00, 'credit', 'মাসিক মুদি বাজার বাকি', '2026-11-09 09:10:00');

-- -----------------------------------------------------------------------------
-- 5. Seed: sale_items
-- Line items associated with credit sales
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `sale_items`;
INSERT INTO `sale_items` (`item_id`, `txn_id`, `prod_id`, `qty`, `unit_price`, `unit_cost`) VALUES
(1, 1, 1, 2.00, 380.00, 350.00),
(2, 1, 2, 1.00, 370.00, 345.00),
(3, 3, 4, 4.00, 135.00, 122.00),
(4, 3, 5, 3.00, 220.00, 195.00),
(5, 3, 6, 1.00, 20.00, 18.00),
(6, 4, 1, 5.00, 380.00, 350.00),
(7, 4, 2, 4.00, 375.00, 345.00),
(8, 7, 3, 2.00, 140.00, 125.00),
(9, 7, 6, 2.00, 75.00, 62.00),
(10, 7, 7, 1.00, 90.00, 80.00),
(11, 8, 8, 5.00, 430.00, 390.00);

-- -----------------------------------------------------------------------------
-- 6. Seed: ledger_entries
-- Audit ledger trail mapping each transaction to Abdur Rahim (user_id = 1)
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `ledger_entries`;
INSERT INTO `ledger_entries` (`ledger_id`, `owner_id`, `txn_id`, `created_at`) VALUES
(1, 1, 1, '2026-11-05 10:15:00'),
(2, 1, 2, '2026-11-06 17:30:00'),
(3, 1, 3, '2026-11-07 11:00:00'),
(4, 1, 4, '2026-11-07 14:45:00'),
(5, 1, 5, '2026-11-08 12:00:00'),
(6, 1, 6, '2026-11-08 12:05:00'),
(7, 1, 7, '2026-11-08 15:20:00'),
(8, 1, 8, '2026-11-09 09:10:00');

-- -----------------------------------------------------------------------------
-- 7. Seed: reports
-- Baseline report summary owned by user_id = 1
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `reports`;
INSERT INTO `reports` (`report_id`, `user_id`, `period`, `type`, `total`, `generated_at`) VALUES
(1, 1, '2026-11-08', 'daily_summary', 2000.00, '2026-11-08 23:59:59'),
(2, 1, '2026-11', 'monthly_credit_aging', 7920.00, '2026-11-09 08:00:00');

-- -----------------------------------------------------------------------------
-- 8. Seed: settings
-- Default shop configuration owned by user_id = 1
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `settings`;
INSERT INTO `settings` (`user_id`, `skey`, `svalue`) VALUES
(1, 'shop_name', 'রহিম জেনারেল স্টোর (Rahim General Store)'),
(1, 'tagline', 'হিসাবের স্বচ্ছতায় ব্যবসার অগ্রগতি'),
(1, 'shop_phone', '+8801700112233'),
(1, 'shop_address', 'দোকান নং #১২, নিউ মার্কেট, ঢাকা-১২০৫'),
(1, 'currency_symbol', '৳'),
(1, 'default_language', 'bn'),
(1, 'default_credit_limit', '10000.00'),
(1, 'default_min_stock', '5'),
(1, 'invoice_footer', 'বাকি লেনদেনের সময়সীমা সর্বোচ্চ ৩০ দিন। নগদ কেনাকাটায় ধন্যবাদ।');

SET FOREIGN_KEY_CHECKS = 1;
