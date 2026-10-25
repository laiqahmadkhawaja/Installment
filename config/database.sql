-- Installment Management System Database Schema & Seed Data
-- Designed for Core PHP & MySQL (WAMP / Production)

CREATE DATABASE IF NOT EXISTS `installment_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `installment_db`;

-- 1. SETTINGS
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. BRANCHES
CREATE TABLE IF NOT EXISTS `branches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_code` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `urdu_name` VARCHAR(150) NULL,
    `phone` VARCHAR(50) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) DEFAULT 'Lahore',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. ROLES
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `permissions` TEXT NULL, -- JSON array of granted permission keys
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. USERS
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT NULL,
    `role_id` INT NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `username` VARCHAR(60) NOT NULL UNIQUE,
    `email` VARCHAR(150) NULL,
    `phone` VARCHAR(50) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. CUSTOMERS
CREATE TABLE IF NOT EXISTS `customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT NOT NULL,
    `customer_code` VARCHAR(30) NOT NULL UNIQUE,
    `full_name` VARCHAR(150) NOT NULL,
    `father_husband_name` VARCHAR(150) NULL,
    `urdu_name` VARCHAR(150) NULL,
    `cnic` VARCHAR(30) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `whatsapp` VARCHAR(30) NULL,
    `alt_phone` VARCHAR(30) NULL,
    `email` VARCHAR(100) NULL,
    `current_address` TEXT NOT NULL,
    `permanent_address` TEXT NULL,
    `city` VARCHAR(100) DEFAULT 'Lahore',
    `occupation` VARCHAR(100) NULL,
    `employer_name` VARCHAR(150) NULL,
    `monthly_income` DECIMAL(12,2) DEFAULT 0.00,
    `credit_limit` DECIMAL(12,2) DEFAULT 200000.00,
    `category` ENUM('new', 'regular', 'premium', 'high_risk', 'defaulter', 'blacklisted') DEFAULT 'new',
    `status` ENUM('active', 'inactive', 'blacklisted') DEFAULT 'active',
    `photo` VARCHAR(255) NULL,
    `cnic_front` VARCHAR(255) NULL,
    `cnic_back` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (`cnic`),
    INDEX (`phone`),
    INDEX (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. CUSTOMER GUARANTORS
CREATE TABLE IF NOT EXISTS `customer_guarantors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `father_name` VARCHAR(150) NULL,
    `cnic` VARCHAR(30) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `whatsapp` VARCHAR(30) NULL,
    `address` TEXT NOT NULL,
    `relationship` VARCHAR(100) NOT NULL,
    `occupation` VARCHAR(100) NULL,
    `monthly_income` DECIMAL(12,2) DEFAULT 0.00,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. CATEGORIES
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `urdu_name` VARCHAR(100) NULL,
    `icon` VARCHAR(50) DEFAULT 'fa-box',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. BRANDS
CREATE TABLE IF NOT EXISTS `brands` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. SUPPLIERS
CREATE TABLE IF NOT EXISTS `suppliers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `company_name` VARCHAR(150) NULL,
    `phone` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NULL,
    `address` TEXT NULL,
    `opening_balance` DECIMAL(12,2) DEFAULT 0.00,
    `current_balance` DECIMAL(12,2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. PRODUCTS
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT NULL,
    `category_id` INT NOT NULL,
    `brand_id` INT NULL,
    `product_code` VARCHAR(50) NOT NULL UNIQUE,
    `barcode` VARCHAR(50) NULL,
    `name` VARCHAR(200) NOT NULL,
    `urdu_name` VARCHAR(200) NULL,
    `model` VARCHAR(100) NULL,
    `purchase_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `cash_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `installment_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `stock_quantity` INT NOT NULL DEFAULT 0,
    `min_stock_alert` INT DEFAULT 2,
    `warranty_period` VARCHAR(50) DEFAULT '1 Year',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `image` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`),
    FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. PRODUCT SERIALS / IMEIs
CREATE TABLE IF NOT EXISTS `product_serials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `branch_id` INT NULL,
    `serial_or_imei` VARCHAR(100) NOT NULL UNIQUE,
    `status` ENUM('available', 'sold', 'returned', 'damaged') DEFAULT 'available',
    `contract_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. INSTALLMENT CONTRACTS
CREATE TABLE IF NOT EXISTS `installment_contracts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `contract_number` VARCHAR(50) NOT NULL UNIQUE,
    `branch_id` INT NOT NULL,
    `customer_id` INT NOT NULL,
    `salesperson_id` INT NULL,
    `collection_officer_id` INT NULL,
    `sale_date` DATE NOT NULL,
    `delivery_date` DATE NULL,
    `cash_price_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `installment_price_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(12,2) DEFAULT 0.00,
    `down_payment` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `financed_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00, -- (installment_price_total - discount_amount - down_payment)
    `total_payable` DECIMAL(12,2) NOT NULL DEFAULT 0.00,   -- (installment_price_total - discount_amount)
    `total_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00,      -- down payment + collected installments
    `balance_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,  -- remaining to be paid
    `installment_count` INT NOT NULL,
    `frequency` ENUM('daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'custom') DEFAULT 'monthly',
    `installment_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `first_due_date` DATE NOT NULL,
    `final_due_date` DATE NOT NULL,
    `status` ENUM('draft', 'active', 'completed', 'overdue', 'defaulted', 'cancelled', 'settled') DEFAULT 'active',
    `notes` TEXT NULL,
    `terms_agreed` TINYINT(1) DEFAULT 1,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`),
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. CONTRACT ITEMS
CREATE TABLE IF NOT EXISTS `contract_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `contract_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `serial_number` VARCHAR(100) NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `unit_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `cash_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `installment_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (`contract_id`) REFERENCES `installment_contracts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. INSTALLMENT SCHEDULES
CREATE TABLE IF NOT EXISTS `installment_schedules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `contract_id` INT NOT NULL,
    `installment_number` INT NOT NULL,
    `due_date` DATE NOT NULL,
    `installment_amount` DECIMAL(12,2) NOT NULL,
    `late_fee` DECIMAL(12,2) DEFAULT 0.00,
    `paid_amount` DECIMAL(12,2) DEFAULT 0.00,
    `remaining_amount` DECIMAL(12,2) NOT NULL,
    `status` ENUM('pending', 'partial', 'paid', 'overdue') DEFAULT 'pending',
    `paid_date` DATE NULL,
    `notes` VARCHAR(255) NULL,
    FOREIGN KEY (`contract_id`) REFERENCES `installment_contracts`(`id`) ON DELETE CASCADE,
    INDEX (`due_date`),
    INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. PAYMENTS (COLLECTIONS)
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `receipt_number` VARCHAR(50) NOT NULL UNIQUE,
    `branch_id` INT NOT NULL,
    `contract_id` INT NOT NULL,
    `customer_id` INT NOT NULL,
    `payment_date` DATE NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `payment_method` ENUM('cash', 'bank_transfer', 'bank_deposit', 'cheque', 'jazzcash', 'easypaisa', 'other') DEFAULT 'cash',
    `bank_name` VARCHAR(100) NULL,
    `transaction_reference` VARCHAR(100) NULL,
    `collected_by` INT NULL,
    `remarks` TEXT NULL,
    `is_reversed` TINYINT(1) DEFAULT 0,
    `reversal_reason` TEXT NULL,
    `reversed_by` INT NULL,
    `reversed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`contract_id`) REFERENCES `installment_contracts`(`id`),
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`),
    INDEX (`payment_date`),
    INDEX (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. PAYMENT ALLOCATIONS (Smart Allocation to Installments)
CREATE TABLE IF NOT EXISTS `payment_allocations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `payment_id` INT NOT NULL,
    `schedule_id` INT NOT NULL,
    `allocated_amount` DECIMAL(12,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`schedule_id`) REFERENCES `installment_schedules`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. CUSTOMER LEDGERS
CREATE TABLE IF NOT EXISTS `customer_ledgers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `contract_id` INT NULL,
    `branch_id` INT NOT NULL,
    `transaction_type` ENUM('sale', 'down_payment', 'installment_payment', 'discount', 'late_charge', 'refund', 'reversal', 'adjustment') NOT NULL,
    `transaction_date` DATE NOT NULL,
    `reference_id` INT NULL,
    `reference_no` VARCHAR(50) NULL,
    `description` VARCHAR(255) NOT NULL,
    `debit` DECIMAL(12,2) DEFAULT 0.00,
    `credit` DECIMAL(12,2) DEFAULT 0.00,
    `balance` DECIMAL(12,2) NOT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
    INDEX (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 18. DEFAULTER FOLLOWUPS & RECOVERY
CREATE TABLE IF NOT EXISTS `defaulter_followups` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `contract_id` INT NOT NULL,
    `officer_id` INT NOT NULL,
    `followup_date` DATE NOT NULL,
    `contact_method` ENUM('phone', 'whatsapp', 'visit', 'letter') DEFAULT 'phone',
    `promise_to_pay_date` DATE NULL,
    `promise_amount` DECIMAL(12,2) DEFAULT 0.00,
    `notes` TEXT NOT NULL,
    `status` ENUM('pending', 'fulfilled', 'broken') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`),
    FOREIGN KEY (`contract_id`) REFERENCES `installment_contracts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 19. EXPENSE CATEGORIES
CREATE TABLE IF NOT EXISTS `expense_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `urdu_name` VARCHAR(100) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 20. EXPENSES
CREATE TABLE IF NOT EXISTS `expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `expense_date` DATE NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `payment_method` ENUM('cash', 'bank') DEFAULT 'cash',
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NULL,
    `paid_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
    FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 21. PURCHASES & INVENTORY STOCK-IN
CREATE TABLE IF NOT EXISTS `purchases` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
    `branch_id` INT NOT NULL,
    `supplier_id` INT NOT NULL,
    `purchase_date` DATE NOT NULL,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `balance_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_status` ENUM('paid', 'partial', 'unpaid') DEFAULT 'paid',
    `notes` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `purchase_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `purchase_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `unit_cost` DECIMAL(12,2) NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 22. SUPPLIER LEDGERS
CREATE TABLE IF NOT EXISTS `supplier_ledgers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `supplier_id` INT NOT NULL,
    `branch_id` INT NOT NULL,
    `transaction_type` ENUM('purchase', 'payment', 'return', 'adjustment') NOT NULL,
    `transaction_date` DATE NOT NULL,
    `reference_no` VARCHAR(50) NULL,
    `debit` DECIMAL(12,2) DEFAULT 0.00,
    `credit` DECIMAL(12,2) DEFAULT 0.00,
    `balance` DECIMAL(12,2) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 23. RETURNS, CANCELLATIONS & SETTLEMENTS
CREATE TABLE IF NOT EXISTS `returns_cancellations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT NOT NULL,
    `contract_id` INT NOT NULL,
    `customer_id` INT NOT NULL,
    `return_type` ENUM('full_cancellation', 'product_return', 'early_settlement') NOT NULL,
    `return_date` DATE NOT NULL,
    `refund_amount` DECIMAL(12,2) DEFAULT 0.00,
    `cancellation_fee` DECIMAL(12,2) DEFAULT 0.00,
    `discount_given` DECIMAL(12,2) DEFAULT 0.00,
    `reason` TEXT NOT NULL,
    `processed_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`contract_id`) REFERENCES `installment_contracts`(`id`),
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 24. AUDIT LOGS
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `branch_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `module` VARCHAR(100) NOT NULL,
    `record_id` INT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`action`),
    INDEX (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 25. NOTIFICATIONS (SMS / WHATSAPP LOGS)
CREATE TABLE IF NOT EXISTS `notification_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NULL,
    `contract_id` INT NULL,
    `channel` ENUM('sms', 'whatsapp') DEFAULT 'whatsapp',
    `recipient` VARCHAR(50) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('sent', 'failed', 'queued') DEFAULT 'sent',
    `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 26. CASHBOOK ENTRIES
CREATE TABLE IF NOT EXISTS `cashbook` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT NOT NULL,
    `entry_date` DATE NOT NULL,
    `account_type` ENUM('cash', 'bank') DEFAULT 'cash',
    `type` ENUM('inflow', 'outflow') NOT NULL,
    `source` VARCHAR(100) NOT NULL, -- 'down_payment', 'installment', 'sale', 'expense', 'deposit', 'withdrawal'
    `reference_no` VARCHAR(50) NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`entry_date`),
    INDEX (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- SEED DATA
-- ==========================================

-- Default Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('company_name', 'Al-Madina Installment & Electronics'),
('company_name_urdu', 'المدینہ انسٹالمنٹ اینڈ الیکٹرانکس'),
('company_phone', '0300-1234567 / 042-35889900'),
('company_whatsapp', '923001234567'),
('company_email', 'info@almadinainstallment.com'),
('company_address', 'Main Ferozepur Road, Lahore, Pakistan'),
('currency_symbol', 'Rs.'),
('currency_symbol_urdu', 'روپے'),
('grace_period_days', '5'),
('default_late_fee', '200'),
('allow_late_fees', '1'),
('whatsapp_api_enabled', '1'),
('sms_api_enabled', '0'),
('theme_mode', 'light'),
('default_language', 'en')
ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);

-- Branches
INSERT INTO `branches` (`id`, `branch_code`, `name`, `urdu_name`, `phone`, `address`, `city`) VALUES
(1, 'BR-01', 'Main Branch - Lahore', 'مین برانچ - لاہور', '0300-1234567', 'Main Ferozepur Road, Lahore', 'Lahore'),
(2, 'BR-02', 'Faisalabad Branch', 'فیصل آباد برانچ', '0300-7654321', 'Katchery Bazar, Faisalabad', 'Faisalabad')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Roles
INSERT INTO `roles` (`id`, `role_name`, `description`, `permissions`) VALUES
(1, 'Super Admin', 'Full system access and configurations', '["all"]'),
(2, 'Branch Manager', 'Full branch operations and reports', '["customers_view","customers_manage","products_view","contracts_manage","collection_manage","defaulters_manage","accounting_view","reports_view"]'),
(3, 'Salesperson', 'Customer registration and installment sales', '["customers_view","customers_manage","products_view","contracts_manage","pos_manage"]'),
(4, 'Cashier', 'Installment collection and receipts', '["customers_view","collection_manage","receipts_print","cashbook_view"]'),
(5, 'Collection Officer', 'Field recovery and follow-ups', '["customers_view","defaulters_manage","collection_manage"]'),
(6, 'Accountant', 'Full financial, expense, and ledger reporting', '["accounting_manage","reports_view","cashbook_view"]')
ON DUPLICATE KEY UPDATE `role_name`=VALUES(`role_name`);

-- Users (Default password for all sample users: admin123)
INSERT INTO `users` (`id`, `branch_id`, `role_id`, `full_name`, `username`, `email`, `phone`, `password_hash`, `status`) VALUES
(1, 1, 1, 'Administrator', 'admin', 'admin@almadinainstallment.com', '0300-1112233', '$2y$10$yztceX220iQ1sP2K1UxxU.dv.qXfRKoohcwBtPauFrIYU0Z9eNGBu', 'active'),
(2, 1, 3, 'Muhammad Usman (Sales)', 'usman', 'usman@almadinainstallment.com', '0302-3334455', '$2y$10$yztceX220iQ1sP2K1UxxU.dv.qXfRKoohcwBtPauFrIYU0Z9eNGBu', 'active'),
(3, 1, 4, 'Ali Raza (Cashier)', 'aliraza', 'ali@almadinainstallment.com', '0301-5556677', '$2y$10$yztceX220iQ1sP2K1UxxU.dv.qXfRKoohcwBtPauFrIYU0Z9eNGBu', 'active'),
(4, 1, 5, 'Tariq Mehmood (Recovery)', 'tariq', 'tariq@almadinainstallment.com', '0304-7778899', '$2y$10$yztceX220iQ1sP2K1UxxU.dv.qXfRKoohcwBtPauFrIYU0Z9eNGBu', 'active')
ON DUPLICATE KEY UPDATE `username`=VALUES(`username`);

-- Categories
INSERT INTO `categories` (`id`, `name`, `urdu_name`, `icon`) VALUES
(1, 'Mobile Phones', 'موبائل فونز', 'fa-mobile-screen'),
(2, 'Home Appliances & Refrigerators', 'گھریلو سامان اور فریج', 'fa-snowflake'),
(3, 'Washing Machines', 'واشنگ مشین', 'fa-soap'),
(4, 'LED TVs & Audio', 'ایل ای ڈی ٹی وی', 'fa-tv'),
(5, 'Motorcycles & Scooters', 'موٹر سائیکل اور اسکوٹر', 'fa-motorcycle'),
(6, 'Solar Energy Systems', 'سولر پینل سسٹم', 'fa-solar-panel'),
(7, 'Furniture', 'فرنیچر', 'fa-couch'),
(8, 'Laptops & Computers', 'لیپ ٹاپ اور کمپیوٹر', 'fa-laptop')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Brands
INSERT INTO `brands` (`id`, `name`) VALUES
(1, 'Samsung'),
(2, 'Infinix'),
(3, 'Tecno'),
(4, 'Dawlance'),
(5, 'Haier'),
(6, 'Orient'),
(7, 'Honda'),
(8, 'Inverex Solar'),
(9, 'Super Asia'),
(10, 'Apple')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Suppliers
INSERT INTO `suppliers` (`id`, `name`, `company_name`, `phone`, `email`, `address`, `opening_balance`, `current_balance`) VALUES
(1, 'Sheikh Brothers Distribution', 'Sheikh Electronics Pvt Ltd', '0300-8451122', 'sheikh@distributors.pk', 'Hall Road, Lahore', 0.00, 150000.00),
(2, 'Haier Regional Hub', 'Haier Pakistan', '042-11142437', 'sales@haier.com.pk', 'Industrial Estate, Lahore', 0.00, 420000.00),
(3, 'Atlas Honda Wholesale', 'Atlas Honda Ltd', '0321-9988776', 'wholesale@atlashonda.pk', 'Mcleod Road, Lahore', 0.00, 0.00)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Products (with purchase cost, cash price, installment price, and stock)
INSERT INTO `products` (`id`, `branch_id`, `category_id`, `brand_id`, `product_code`, `barcode`, `name`, `urdu_name`, `model`, `purchase_cost`, `cash_price`, `installment_price`, `stock_quantity`, `min_stock_alert`, `warranty_period`) VALUES
(1, 1, 2, 4, 'PRD-REF-01', '8964000101', 'Dawlance 91999 Inverter Refrigerator 18 Cu Ft', 'ڈولنس 91999 انورٹر فریج', '91999 Chrome', 115000.00, 128000.00, 156000.00, 8, 2, '12 Years Compressor'),
(2, 1, 2, 5, 'PRD-AC-01', '8964000102', 'Haier 1.5 Ton DC Inverter Air Conditioner', 'ہائیر 1.5 ٹن ڈی سی انورٹر اے سی', 'HSU-18HFT', 135000.00, 148000.00, 180000.00, 5, 2, '10 Years Compressor'),
(3, 1, 1, 1, 'PRD-MOB-01', '8964000103', 'Samsung Galaxy A55 5G 8GB/256GB', 'سام سنگ گلیکسی اے 55', 'SM-A556E', 108000.00, 118000.00, 144000.00, 12, 3, '1 Year Official PTA'),
(4, 1, 1, 2, 'PRD-MOB-02', '8964000104', 'Infinix Note 40 Pro 8GB/256GB', 'انفینکس نوٹ 40 پرو', 'X6850', 58000.00, 65000.00, 82000.00, 15, 3, '1 Year Official PTA'),
(5, 1, 5, 7, 'PRD-BIKE-01', '8964000105', 'Honda CD 70cc 2026 Model Red', 'ہونڈا سی ڈی 70 ماڈل 2026', 'CD-70-2026', 142000.00, 155000.00, 195000.00, 6, 2, '1 Year Engine'),
(6, 1, 3, 9, 'PRD-WASH-01', '8964000106', 'Super Asia 10KG Automatic Washing Machine', 'سپر ایشیا 10 کلو آٹومیٹک واشنگ مشین', 'SA-240-Auto', 52000.00, 60000.00, 75000.00, 10, 2, '2 Years Motor'),
(7, 1, 6, 8, 'PRD-SOL-01', '8964000107', 'Inverex Nitrox 6KW Hybrid Solar Inverter', 'انوریکس نائٹروکس 6 کلوواٹ ہائبرڈ انورٹر', 'Nitrox-6KW', 280000.00, 310000.00, 375000.00, 4, 1, '5 Years Warranty')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Product Serials (For mobile phones, bikes, appliances)
INSERT INTO `product_serials` (`id`, `product_id`, `branch_id`, `serial_or_imei`, `status`, `contract_id`) VALUES
(1, 1, 1, 'DW-REF-99281726', 'sold', 1),
(2, 1, 1, 'DW-REF-99281727', 'available', NULL),
(3, 3, 1, '352899102938475', 'sold', 2),
(4, 3, 1, '352899102938476', 'available', NULL),
(5, 5, 1, 'HND-CD70-ENG-9882110', 'sold', 3),
(6, 5, 1, 'HND-CD70-ENG-9882111', 'available', NULL)
ON DUPLICATE KEY UPDATE `serial_or_imei`=VALUES(`serial_or_imei`);

-- Customers
INSERT INTO `customers` (`id`, `branch_id`, `customer_code`, `full_name`, `father_husband_name`, `urdu_name`, `cnic`, `phone`, `whatsapp`, `current_address`, `permanent_address`, `city`, `occupation`, `employer_name`, `monthly_income`, `credit_limit`, `category`, `status`) VALUES
(1, 1, 'CUST-1001', 'Muhammad Arshad', 'Abdul Ghafoor', 'محمد ارشد', '35201-1234567-1', '0300-8877665', '923008877665', 'House 42, St 5, Kot Lakhpat, Lahore', 'Village 14-GB, Dist Okara', 'Lahore', 'Govt School Teacher', 'Punjab Education Dept', 65000.00, 250000.00, 'regular', 'active'),
(2, 1, 'CUST-1002', 'Kamran Akram', 'Muhammad Akram', 'کامران اکرم', '35202-9876543-3', '0321-4455667', '923214455667', 'Flat 3B, Gulberg Heights, Lahore', 'Gulberg, Lahore', 'Lahore', 'Software Engineer', 'Tech Solutions Ltd', 120000.00, 400000.00, 'premium', 'active'),
(3, 1, 'CUST-1003', 'Zahid Hussain', 'Ghulam Rasool', 'زاہد حسین', '35201-5544332-5', '0333-1122334', '923331122334', 'Mohallah Eidgah, Shadbagh, Lahore', 'Same', 'Lahore', 'Shopkeeper', 'Self Employed (General Store)', 45000.00, 150000.00, 'defaulter', 'active'),
(4, 1, 'CUST-1004', 'Bilal Farooq', 'Farooq Ahmad', 'بلال فاروق', '35202-8877112-7', '0345-9988112', '923459988112', 'H No 12, Block C, Faisal Town, Lahore', 'Faisal Town, Lahore', 'Lahore', 'Bank Officer', 'Meezan Bank Ltd', 85000.00, 300000.00, 'new', 'active')
ON DUPLICATE KEY UPDATE `customer_code`=VALUES(`customer_code`);

-- Customer Guarantors
INSERT INTO `customer_guarantors` (`id`, `customer_id`, `full_name`, `father_name`, `cnic`, `phone`, `whatsapp`, `address`, `relationship`, `occupation`, `monthly_income`) VALUES
(1, 1, 'Tariq Mehmood', 'Abdul Ghafoor', '35201-8899001-1', '0301-2233441', '923012233441', 'House 43, St 5, Kot Lakhpat, Lahore', 'Real Brother', 'Govt Employee', 55000.00),
(2, 2, 'Farhan Akram', 'Muhammad Akram', '35202-7788992-3', '0322-1144778', '923221144778', 'Gulberg III, Lahore', 'Brother', 'Businessman', 150000.00),
(3, 3, 'Malik Aslam', 'Malik Bashir', '35201-3322114-5', '0334-6655443', '923346655443', 'Shadbagh, Lahore', 'Cousin', 'Trader', 50000.00)
ON DUPLICATE KEY UPDATE `full_name`=VALUES(`full_name`);

-- Installment Contracts
-- Contract 1: Arshad purchased Refrigerator (12 months, active, partially paid)
INSERT INTO `installment_contracts` (`id`, `contract_number`, `branch_id`, `customer_id`, `salesperson_id`, `collection_officer_id`, `sale_date`, `delivery_date`, `cash_price_total`, `installment_price_total`, `discount_amount`, `down_payment`, `financed_amount`, `total_payable`, `total_paid`, `balance_amount`, `installment_count`, `frequency`, `installment_amount`, `first_due_date`, `final_due_date`, `status`, `notes`) VALUES
(1, 'INS-2026-1001', 1, 1, 2, 4, '2026-08-01', '2026-08-02', 128000.00, 156000.00, 0.00, 36000.00, 120000.00, 156000.00, 66000.00, 90000.00, 12, 'monthly', 10000.00, '2026-09-05', '2027-08-05', 'active', 'Dawlance 18 Cu Ft Inverter Refrigerator on 12 Months');

-- Contract 2: Kamran purchased Samsung A55 5G (6 months, active, paid ahead)
INSERT INTO `installment_contracts` (`id`, `contract_number`, `branch_id`, `customer_id`, `salesperson_id`, `collection_officer_id`, `sale_date`, `delivery_date`, `cash_price_total`, `installment_price_total`, `discount_amount`, `down_payment`, `financed_amount`, `total_payable`, `total_paid`, `balance_amount`, `installment_count`, `frequency`, `installment_amount`, `first_due_date`, `final_due_date`, `status`, `notes`) VALUES
(2, 'INS-2026-1002', 1, 2, 2, 4, '2026-09-10', '2026-09-10', 118000.00, 144000.00, 4000.00, 40000.00, 100000.00, 140000.00, 60000.00, 80000.00, 5, 'monthly', 20000.00, '2026-10-10', '2027-02-10', 'active', 'Samsung Galaxy A55 5G with special Rs 4,000 discount');

-- Contract 3: Zahid purchased Honda CD 70 (12 months, overdue/defaulter)
INSERT INTO `installment_contracts` (`id`, `contract_number`, `branch_id`, `customer_id`, `salesperson_id`, `collection_officer_id`, `sale_date`, `delivery_date`, `cash_price_total`, `installment_price_total`, `discount_amount`, `down_payment`, `financed_amount`, `total_payable`, `total_paid`, `balance_amount`, `installment_count`, `frequency`, `installment_amount`, `first_due_date`, `final_due_date`, `status`, `notes`) VALUES
(3, 'INS-2026-1003', 1, 3, 2, 4, '2026-07-05', '2026-07-06', 155000.00, 195000.00, 0.00, 45000.00, 150000.00, 195000.00, 57500.00, 137500.00, 12, 'monthly', 12500.00, '2026-08-05', '2027-07-05', 'overdue', 'Honda CD 70cc 2026 model, payments delayed');

-- Contract Items
INSERT INTO `contract_items` (`id`, `contract_id`, `product_id`, `serial_number`, `quantity`, `unit_cost`, `cash_price`, `installment_price`, `subtotal`) VALUES
(1, 1, 1, 'DW-REF-99281726', 1, 115000.00, 128000.00, 156000.00, 156000.00),
(2, 2, 3, '352899102938475', 1, 108000.00, 118000.00, 144000.00, 144000.00),
(3, 3, 5, 'HND-CD70-ENG-9882110', 1, 142000.00, 155000.00, 195000.00, 195000.00)
ON DUPLICATE KEY UPDATE `contract_id`=VALUES(`contract_id`);

-- Installment Schedules for Contract 1 (12 months x Rs 10,000)
INSERT INTO `installment_schedules` (`contract_id`, `installment_number`, `due_date`, `installment_amount`, `late_fee`, `paid_amount`, `remaining_amount`, `status`, `paid_date`, `notes`) VALUES
(1, 1, '2026-09-05', 10000.00, 0.00, 10000.00, 0.00, 'paid', '2026-09-04', 'Paid on time'),
(1, 2, '2026-10-05', 10000.00, 0.00, 10000.00, 0.00, 'paid', '2026-10-05', 'Paid on time'),
(1, 3, '2026-11-05', 10000.00, 0.00, 10000.00, 0.00, 'paid', '2026-10-20', 'Paid in advance'),
(1, 4, '2026-12-05', 10000.00, 0.00, 0.00, 10000.00, 'pending', NULL, NULL),
(1, 5, '2027-01-05', 10000.00, 0.00, 0.00, 10000.00, 'pending', NULL, NULL),
(1, 6, '2027-02-05', 10000.00, 0.00, 0.00, 10000.00, 'pending', NULL, NULL),
(1, 7, '2027-03-05', 10000.00, 0.00, 0.00, 10000.00, 'pending', NULL, NULL),
(1, 8, '2027-04-05', 10000.00, 0.00, 0.00, 10000.00, 'pending', NULL, NULL),
(1, 9, '2027-05-05', 10000.00, 0.00, 0.00, 10000.00, 'pending', NULL, NULL),
(1, 10, '2027-06-05', 10000.00, 0.00, 0.00, 10000.00, 'pending', NULL, NULL),
(1, 11, '2027-07-05', 10000.00, 0.00, 0.00, 10000.00, 'pending', NULL, NULL),
(1, 12, '2027-08-05', 10000.00, 0.00, 0.00, 10000.00, 'pending', NULL, NULL);

-- Installment Schedules for Contract 2 (5 months x Rs 20,000)
INSERT INTO `installment_schedules` (`contract_id`, `installment_number`, `due_date`, `installment_amount`, `late_fee`, `paid_amount`, `remaining_amount`, `status`, `paid_date`, `notes`) VALUES
(2, 1, '2026-10-10', 20000.00, 0.00, 20000.00, 0.00, 'paid', '2026-10-09', 'Paid via JazzCash'),
(2, 2, '2026-11-10', 20000.00, 0.00, 0.00, 20000.00, 'pending', NULL, NULL),
(2, 3, '2026-12-10', 20000.00, 0.00, 0.00, 20000.00, 'pending', NULL, NULL),
(2, 4, '2027-01-10', 20000.00, 0.00, 0.00, 20000.00, 'pending', NULL, NULL),
(2, 5, '2027-02-10', 20000.00, 0.00, 0.00, 20000.00, 'pending', NULL, NULL);

-- Installment Schedules for Contract 3 (Honda Bike, missed installments)
INSERT INTO `installment_schedules` (`contract_id`, `installment_number`, `due_date`, `installment_amount`, `late_fee`, `paid_amount`, `remaining_amount`, `status`, `paid_date`, `notes`) VALUES
(3, 1, '2026-08-05', 12500.00, 0.00, 12500.00, 0.00, 'paid', '2026-08-05', 'Paid in full'),
(3, 2, '2026-09-05', 12500.00, 200.00, 0.00, 12500.00, 'overdue', NULL, 'Overdue by 50+ days'),
(3, 3, '2026-10-05', 12500.00, 200.00, 0.00, 12500.00, 'overdue', NULL, 'Overdue by 20+ days'),
(3, 4, '2026-11-05', 12500.00, 0.00, 0.00, 12500.00, 'pending', NULL, NULL),
(3, 5, '2026-12-05', 12500.00, 0.00, 0.00, 12500.00, 'pending', NULL, NULL),
(3, 6, '2027-01-05', 12500.00, 0.00, 0.00, 12500.00, 'pending', NULL, NULL),
(3, 7, '2027-02-05', 12500.00, 0.00, 0.00, 12500.00, 'pending', NULL, NULL),
(3, 8, '2027-03-05', 12500.00, 0.00, 0.00, 12500.00, 'pending', NULL, NULL),
(3, 9, '2027-04-05', 12500.00, 0.00, 0.00, 12500.00, 'pending', NULL, NULL),
(3, 10, '2027-05-05', 12500.00, 0.00, 0.00, 12500.00, 'pending', NULL, NULL),
(3, 11, '2027-06-05', 12500.00, 0.00, 0.00, 12500.00, 'pending', NULL, NULL),
(3, 12, '2027-07-05', 12500.00, 0.00, 0.00, 12500.00, 'pending', NULL, NULL);

-- Payments (Collections & Down Payments)
INSERT INTO `payments` (`id`, `receipt_number`, `branch_id`, `contract_id`, `customer_id`, `payment_date`, `amount`, `payment_method`, `transaction_reference`, `collected_by`, `remarks`) VALUES
(1, 'RCP-2026-0001', 1, 1, 1, '2026-08-01', 36000.00, 'cash', 'CASH-INIT', 3, 'Down payment for Contract INS-2026-1001'),
(2, 'RCP-2026-0002', 1, 1, 1, '2026-09-04', 10000.00, 'cash', 'CSH-0904', 3, 'Installment #1 paid'),
(3, 'RCP-2026-0003', 1, 1, 1, '2026-10-05', 10000.00, 'cash', 'CSH-1005', 3, 'Installment #2 paid'),
(4, 'RCP-2026-0004', 1, 1, 1, '2026-10-20', 10000.00, 'jazzcash', 'JC-99281726', 3, 'Installment #3 paid in advance'),
(5, 'RCP-2026-0005', 1, 2, 2, '2026-09-10', 40000.00, 'bank_transfer', 'MCB-98772', 3, 'Down payment for Contract INS-2026-1002'),
(6, 'RCP-2026-0006', 1, 2, 2, '2026-10-09', 20000.00, 'jazzcash', 'JC-7766110', 3, 'Installment #1 paid'),
(7, 'RCP-2026-0007', 1, 3, 3, '2026-07-05', 45000.00, 'cash', 'CASH-INIT', 3, 'Down payment for Contract INS-2026-1003'),
(8, 'RCP-2026-0008', 1, 3, 3, '2026-08-05', 12500.00, 'cash', 'CSH-0805', 3, 'Installment #1 paid')
ON DUPLICATE KEY UPDATE `receipt_number`=VALUES(`receipt_number`);

-- Customer Ledgers
INSERT INTO `customer_ledgers` (`customer_id`, `contract_id`, `branch_id`, `transaction_type`, `transaction_date`, `reference_no`, `description`, `debit`, `credit`, `balance`) VALUES
(1, 1, 1, 'sale', '2026-08-01', 'INS-2026-1001', 'Installment Sale: Dawlance 18 Cu Ft Inverter Refrigerator', 156000.00, 0.00, 156000.00),
(1, 1, 1, 'down_payment', '2026-08-01', 'RCP-2026-0001', 'Down payment received (Cash)', 0.00, 36000.00, 120000.00),
(1, 1, 1, 'installment_payment', '2026-09-04', 'RCP-2026-0002', 'Installment #1 received', 0.00, 10000.00, 110000.00),
(1, 1, 1, 'installment_payment', '2026-10-05', 'RCP-2026-0003', 'Installment #2 received', 0.00, 10000.00, 100000.00),
(1, 1, 1, 'installment_payment', '2026-10-20', 'RCP-2026-0004', 'Installment #3 received (Advance)', 0.00, 10000.00, 90000.00),

(2, 2, 1, 'sale', '2026-09-10', 'INS-2026-1002', 'Installment Sale: Samsung Galaxy A55 5G', 144000.00, 0.00, 144000.00),
(2, 2, 1, 'discount', '2026-09-10', 'INS-2026-1002', 'Special promotional discount', 0.00, 4000.00, 140000.00),
(2, 2, 1, 'down_payment', '2026-09-10', 'RCP-2026-0005', 'Down payment received (MCB Bank)', 0.00, 40000.00, 100000.00),
(2, 2, 1, 'installment_payment', '2026-10-09', 'RCP-2026-0006', 'Installment #1 received (JazzCash)', 0.00, 20000.00, 80000.00),

(3, 3, 1, 'sale', '2026-07-05', 'INS-2026-1003', 'Installment Sale: Honda CD 70cc 2026', 195000.00, 0.00, 195000.00),
(3, 3, 1, 'down_payment', '2026-07-05', 'RCP-2026-0007', 'Down payment received (Cash)', 0.00, 45000.00, 150000.00),
(3, 3, 1, 'installment_payment', '2026-08-05', 'RCP-2026-0008', 'Installment #1 received', 0.00, 12500.00, 137500.00);

-- Defaulter Follow-ups
INSERT INTO `defaulter_followups` (`customer_id`, `contract_id`, `officer_id`, `followup_date`, `contact_method`, `promise_to_pay_date`, `promise_amount`, `notes`, `status`) VALUES
(3, 3, 4, '2026-10-15', 'phone', '2026-10-28', 25000.00, 'Customer said shop business was slow; promised to pay both Sept & Oct installments on 28th Oct', 'pending');

-- Expense Categories
INSERT INTO `expense_categories` (`id`, `name`, `urdu_name`) VALUES
(1, 'Shop Rent', 'دکان کا کرایہ'),
(2, 'Staff Salaries & Commissions', 'تنخواہیں اور کمیشن'),
(3, 'Electricity & Utility Bills', 'بجلی اور گیس کے بل'),
(4, 'Fuel & Conveyance (Recovery)', 'پیٹرول اور فیلڈ ریکوری خرچ'),
(5, 'Office Stationery & Tea', 'چائے اور دفتری اخراجات'),
(6, 'Marketing & Advertising', 'اشتہارات اور تشہیر'),
(7, 'Shop Maintenance & Repairs', 'مرمت اور دیکھ بھال')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Sample Expenses
INSERT INTO `expenses` (`branch_id`, `category_id`, `expense_date`, `amount`, `payment_method`, `title`, `description`) VALUES
(1, 1, '2026-10-01', 45000.00, 'bank', 'October 2026 Main Branch Rent', 'Paid to Plaza Owner via Cheque #99102'),
(1, 3, '2026-10-12', 18500.00, 'cash', 'LESCO Electricity Bill', 'Paid at Bank branch'),
(1, 4, '2026-10-18', 4200.00, 'cash', 'Recovery Staff Fuel Allowance', 'Weekly fuel allowance for recovery officer Tariq'),
(1, 5, '2026-10-22', 2800.00, 'cash', 'Tea & Refreshment for customers', 'Monthly tea expenses');

-- Cashbook Inflows & Outflows
INSERT INTO `cashbook` (`branch_id`, `entry_date`, `account_type`, `type`, `source`, `reference_no`, `amount`, `description`) VALUES
(1, '2026-10-05', 'cash', 'inflow', 'installment', 'RCP-2026-0003', 10000.00, 'Installment #2 from Muhammad Arshad'),
(1, '2026-10-12', 'cash', 'outflow', 'expense', 'EXP-02', 18500.00, 'LESCO Electricity Bill'),
(1, '2026-10-18', 'cash', 'outflow', 'expense', 'EXP-03', 4200.00, 'Recovery Staff Fuel'),
(1, '2026-10-20', 'bank', 'inflow', 'installment', 'RCP-2026-0004', 10000.00, 'JazzCash installment from Muhammad Arshad'),
(1, '2026-10-22', 'cash', 'outflow', 'expense', 'EXP-04', 2800.00, 'Tea & Refreshments');

-- Audit Logs
INSERT INTO `audit_logs` (`user_id`, `branch_id`, `action`, `module`, `record_id`, `details`, `ip_address`) VALUES
(1, 1, 'SYSTEM_INIT', 'System', 1, 'System initialized with full default schemas and records.', '127.0.0.1'),
(1, 1, 'CONTRACT_CREATED', 'Contracts', 1, 'Created installment plan INS-2026-1001 for Muhammad Arshad.', '127.0.0.1');
