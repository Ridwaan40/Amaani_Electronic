<?php
// Database configuration for CAALAMI ELECTRONICS ERP
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'caalami_erp');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // First connect without dbname to ensure database exists
            $pdoInit = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdoInit->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Now connect to specific db
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            ensureTablesAndSeed($pdo);
            ensureExtraProductColumns($pdo);
            ensurePurchaseTablesAndSeed($pdo);
            ensureSupplierTableAndSeed($pdo);
            ensureCategoryAndUnitTablesAndSeed($pdo);
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }
    return $pdo;
}

function ensureTablesAndSeed($pdo) {
    // Check if tables exist
    $check = $pdo->query("SHOW TABLES LIKE 'branches'")->rowCount();
    if ($check > 0) return; // already initialized

    // Create Tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS branches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE,
            name VARCHAR(100)
        );

        CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE,
            name VARCHAR(150),
            phone VARCHAR(50),
            balance DECIMAL(10,2) DEFAULT 0.00,
            branch VARCHAR(100) DEFAULT 'Boorama Branch'
        );

        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE,
            name VARCHAR(255),
            category VARCHAR(100),
            type VARCHAR(50) DEFAULT 'Brand New',
            ram VARCHAR(50) DEFAULT '',
            storage VARCHAR(50) DEFAULT '',
            retail_price DECIMAL(10,2) DEFAULT 0.00,
            wholesale_price DECIMAL(10,2) DEFAULT 0.00,
            stock_qty INT DEFAULT 15,
            min_threshold INT DEFAULT 5,
            image_icon VARCHAR(50) DEFAULT '📱'
        );

        CREATE TABLE IF NOT EXISTS sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_number VARCHAR(50) UNIQUE,
            customer_name VARCHAR(150),
            phone VARCHAR(50),
            branch VARCHAR(100),
            sale_date DATE,
            return_date DATE,
            year VARCHAR(10),
            month VARCHAR(20),
            subtotal DECIMAL(10,2) DEFAULT 0.00,
            discount DECIMAL(10,2) DEFAULT 0.00,
            total_sale DECIMAL(10,2) DEFAULT 0.00,
            paid_amount DECIMAL(10,2) DEFAULT 0.00,
            payment_method VARCHAR(50) DEFAULT 'Cash On Hand',
            notes TEXT,
            items_summary TEXT,
            status VARCHAR(50) DEFAULT 'Due',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS sale_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sale_id INT,
            product_id INT NULL,
            product_name VARCHAR(255),
            qty INT DEFAULT 1,
            unit_price DECIMAL(10,2) DEFAULT 0.00,
            total_price DECIMAL(10,2) DEFAULT 0.00,
            FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sale_id INT,
            invoice_number VARCHAR(50),
            amount DECIMAL(10,2),
            payment_method VARCHAR(50),
            reference_code VARCHAR(100),
            notes TEXT,
            paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS quotations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quotation_number VARCHAR(50) UNIQUE,
            customer_name VARCHAR(150),
            branch VARCHAR(100),
            quote_date DATE,
            valid_until DATE,
            total_amount DECIMAL(10,2) DEFAULT 0.00,
            items_summary TEXT,
            status VARCHAR(50) DEFAULT 'Pending'
        );

        CREATE TABLE IF NOT EXISTS product_exchanges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exchange_number VARCHAR(50) UNIQUE,
            customer_name VARCHAR(150),
            returned_item VARCHAR(255),
            new_item VARCHAR(255),
            price_diff DECIMAL(10,2) DEFAULT 0.00,
            exchange_date DATE,
            notes TEXT
        );

        CREATE TABLE IF NOT EXISTS deliveries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE,
            customer VARCHAR(150),
            address VARCHAR(255),
            status VARCHAR(50),
            driver VARCHAR(100),
            eta VARCHAR(50),
            items TEXT,
            priority VARCHAR(20) DEFAULT 'Normal'
        );
    ");

    // Seed Branches
    $pdo->exec("
        INSERT INTO branches (code, name) VALUES
        ('boorama', 'Boorama Branch'),
        ('hargeisa', 'Hargeisa Main Branch'),
        ('berbera', 'Berbera Port Branch'),
        ('burco', 'Burco Central Branch');
    ");

    // Seed Customers
    $pdo->exec("
        INSERT INTO customers (code, name, phone, balance, branch) VALUES
        ('walk-in', 'Walk-In Customer', '', 0.00, 'Boorama Branch'),
        ('cust-01', 'COSOB ELECTRONIC', '+252 63 4452109', 68.70, 'Boorama Branch'),
        ('cust-02', 'WARSAME ELECTRONIC', '+252 63 4001923', 345.50, 'Boorama Branch'),
        ('cust-03', 'ALKHAYR ELECTRONIC', '+252 63 4887712', 1250.00, 'Boorama Branch'),
        ('cust-04', 'SAMATAR ELECTRONICS', '+252 63 4771234', 0.00, 'Boorama Branch'),
        ('cust-05', 'GOLIS TELECOM', '+252 63 4118899', 0.00, 'Boorama Branch');
    ");

    // Seed Products
    $pdo->exec("
        INSERT INTO products (code, name, category, type, ram, storage, retail_price, wholesale_price, stock_qty, min_threshold, image_icon) VALUES
        ('p-01', 'Iphone 11 64GB', 'Mobile', 'Used', '2GB', '64GB', 200.00, 185.00, 8, 3, '📱'),
        ('p-02', 'iPhone 13 128GB', 'Mobile', 'Brand New', '4GB', '128GB', 530.00, 495.00, 12, 4, '📱'),
        ('p-03', 'Samsung Galaxy A15', 'Mobile', 'Brand New', '4GB', '128GB', 150.00, 135.00, 20, 5, '📱'),
        ('p-04', 'Samsung Galaxy A16', 'Mobile', 'Brand New', '4GB', '128GB', 180.00, 160.00, 15, 5, '📱'),
        ('p-05', 'iPhone 15 Pro 128GB', 'Mobile', 'Brand New', '8GB', '128GB', 1150.00, 1080.00, 6, 2, '📱'),
        ('p-06', 'Samsung Galaxy S24 Ultra', 'Mobile', 'Brand New', '12GB', '512GB', 1280.00, 1200.00, 5, 2, '📱'),
        ('p-07', 'iPhone 16 Pro Max 256GB', 'Mobile', 'Brand New', '8GB', '256GB', 1450.00, 1380.00, 4, 2, '📱'),
        ('p-08', 'Samsung Crystal UHD 55\" 4K TV', 'TV & Display', 'Brand New', '', '', 480.00, 440.00, 2, 8, '📺'),
        ('p-09', 'HP EliteBook 830 G8', 'Laptop', 'Brand New', '16GB', '512GB', 620.00, 580.00, 7, 3, '💻'),
        ('p-10', 'Dell Latitude 5420 i7', 'Laptop', 'Brand New', '16GB', '512GB', 650.00, 610.00, 5, 3, '💻'),
        ('p-11', 'Apple MacBook Air M2', 'Laptop', 'Brand New', '8GB', '256GB', 980.00, 920.00, 4, 2, '💻'),
        ('p-12', 'Starlink Standard Kit V4', 'Networking', 'Brand New', '', '', 580.00, 540.00, 1, 6, '📡'),
        ('p-13', 'TP-Link Deco M4 Mesh WiFi (Pack of 2)', 'Networking', 'Brand New', '', '', 95.00, 85.00, 18, 5, '📶'),
        ('p-14', 'Hikvision 5MP IP Dome Camera', 'Security', 'Brand New', '', '', 65.00, 55.00, 25, 8, '📷'),
        ('p-15', 'Anker 737 PowerBank 24,000mAh 140W', 'Accessories', 'Brand New', '', '', 85.00, 75.00, 3, 10, '🔋'),
        ('p-16', 'Type-C Fast Charging Cable 65W', 'Accessories', 'Brand New', '', '', 8.00, 5.00, 50, 15, '🔌'),
        ('p-17', 'Apple 20W USB-C Power Adapter', 'Accessories', 'Brand New', '', '', 22.00, 18.00, 40, 10, '🔌'),
        ('p-18', 'Felicity Solar Inverter 3.5kVA', 'Power', 'Brand New', '', '', 450.00, 410.00, 4, 2, '⚡');
    ");

    // Seed Sales & Invoices
    $sales = [
        ['#83', 'COSOB ELECTRONIC', '+252 63 4452109', 'Boorama Branch', '2026-10-15', '2026-10-25', '2026', 'October', 16.00, 0.00, 16.00, 0.00, 'Cash On Hand', 'Type-C Fast Charger Cable (3x)', 'Due'],
        ['#84', 'WARSAME ELECTRONIC', '+252 63 4001923', 'Boorama Branch', '2026-10-18', '2026-10-27', '2026', 'October', 185.00, 0.00, 185.00, 40.00, 'ZAAD (Telesom)', 'Starlink Mini PoE Injector, Cat6 Ethernet Reel 50m', 'Due'],
        ['#87', 'WARSAME ELECTRONIC', '+252 63 4001923', 'Boorama Branch', '2026-10-22', '2026-10-31', '2026', 'October', 420.00, 0.00, 420.00, 100.00, 'e-Dahab (Somtel)', 'Hikvision 4MP IP Dome Cameras (2x), 1TB WD Purple HDD', 'Due'],
        ['#88', 'ALKHAYR ELECTRONIC', '+252 63 4887712', 'Boorama Branch', '2026-10-24', '2026-11-01', '2026', 'October', 780.00, 0.00, 780.00, 200.00, 'Bank Transfer', 'Samsung Crystal UHD 55\" 4K TV, Wall Mount Bracket', 'Due'],
        ['#92', 'WARSAME ELECTRONIC', '+252 63 4001923', 'Boorama Branch', '2026-10-28', '2026-11-04', '2026', 'October', 95.00, 0.00, 95.00, 20.00, 'Cash On Hand', 'TP-Link Deco M4 Mesh System', 'Due'],
        ['#95', 'WARSAME ELECTRONIC', '+252 63 4001923', 'Boorama Branch', '2026-11-01', '2026-11-08', '2026', 'November', 310.00, 0.00, 310.00, 60.00, 'ZAAD (Telesom)', 'Anker Prime Power Bank 20,000mAh, MagSafe Stand', 'Due'],
        ['#98', 'DAHIR TELECOM & DIGITAL', '+252 63 4902188', 'Boorama Branch', '2026-11-03', '2026-11-10', '2026', 'November', 650.00, 0.00, 650.00, 150.00, 'ZAAD (Telesom)', 'Apple iPad 10th Gen 64GB WiFi Blue, Smart Folio', 'Due'],
        ['#101', 'GOLIS SMART HUB', '+252 63 4110943', 'Boorama Branch', '2026-11-05', '2026-11-12', '2026', 'November', 320.00, 0.00, 320.00, 50.00, 'Cash On Hand', 'JBL PartyBox Stage 320 Bluetooth Speaker', 'Due'],
        ['#104', 'ILAYS COMPUTER SOLUTIONS', '+252 63 4567890', 'Boorama Branch', '2026-11-06', '2026-11-13', '2026', 'November', 1450.00, 0.00, 1450.00, 300.00, 'Bank Transfer', 'Dell OptiPlex Core i7 Desktop Set (2x), 24\" IPS Monitors', 'Due'],
        ['#108', 'SAHAL SOLAR & GADGETS', '+252 63 4771234', 'Boorama Branch', '2026-11-07', '2026-11-14', '2026', 'November', 480.00, 0.00, 480.00, 100.00, 'ZAAD (Telesom)', 'Felicity Solar Inverter 3.5kVA, MPPT Controller', 'Due'],
        ['#112', 'SOMTRONICS HARGEISA', '+252 63 4223344', 'Hargeisa Main Branch', '2026-10-04', '2026-10-18', '2026', 'October', 1200.00, 0.00, 1200.00, 300.00, 'Bank Transfer', 'Sony PlayStation 5 Slim 1TB + Extra DualSense', 'Due'],
        ['#115', 'BERBERA SHIP SUPPLY ELEC', '+252 63 4668899', 'Berbera Port Branch', '2026-10-12', '2026-10-26', '2026', 'October', 850.00, 0.00, 850.00, 200.00, 'Cash On Hand', 'Garmin Marine GPS Navigator & VHF Two-way Radio Set', 'Due'],
        ['#119', 'TOGDHEER TECH REPAIRS', '+252 63 4335566', 'Burco Central Branch', '2025-11-15', '2025-11-30', '2025', 'November', 520.00, 0.00, 520.00, 120.00, 'e-Dahab (Somtel)', 'Soldering Station Quick 861DW, Microscope 4K Camera', 'Due']
    ];

    $stmt = $pdo->prepare("INSERT INTO sales (invoice_number, customer_name, phone, branch, sale_date, return_date, year, month, subtotal, discount, total_sale, paid_amount, payment_method, items_summary, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($sales as $s) {
        $stmt->execute($s);
    }

    // Seed Deliveries
    $pdo->exec("
        INSERT INTO deliveries (code, customer, address, status, driver, eta, items, priority) VALUES
        ('DEL-401', 'Al-Baraka Hospital IT Dept', 'Borama West Medical District, Near Amoud', 'In Transit', 'Mukhtar Jama', '35 mins', '3x UPS APC Smart 1500VA + Batteries', 'High'),
        ('DEL-402', 'Eng. Mustafe Ahmed', 'Haji Dahir St, Boorama Central', 'Dispatched', 'Farhan Ali', '1 hour', 'Solar Inverter 5kW + Cable Pack', 'Normal'),
        ('DEL-403', 'City Plaza Electronics', 'Commercial Market Road, Boorama', 'Delivered', 'Guled Hassan', 'Completed', '50x Fast Charging Cables, 20x Adapters', 'Normal');
    ");

    // Seed Quotations
    $pdo->exec("
        INSERT INTO quotations (quotation_number, customer_name, branch, quote_date, valid_until, total_amount, items_summary, status) VALUES
        ('QT-201', 'Amoud University Computer Lab', 'Boorama Branch', '2026-11-10', '2026-11-24', 4500.00, '10x HP ProDesk Desktops, 10x Monitors, 1x Switch 24-Port', 'Pending'),
        ('QT-202', 'Telesom Boorama Regional Office', 'Boorama Branch', '2026-11-12', '2026-11-26', 1250.00, '2x Starlink V4 Kits + Mounting Poles & PoE kits', 'Pending'),
        ('QT-203', 'Borama General Hospital Pharmacy', 'Boorama Branch', '2026-11-14', '2026-11-28', 850.00, '3x Barcode Scanners, 2x Thermal Receipt Printers, POS Drawer', 'Pending');
    ");

    // Seed Exchanges
    $pdo->exec("
        INSERT INTO product_exchanges (exchange_number, customer_name, returned_item, new_item, price_diff, exchange_date, notes) VALUES
        ('EXC-101', 'WARSAME ELECTRONIC', 'iPhone 13 (Screen issue)', 'iPhone 13 128GB (Replacement)', 0.00, '2026-11-12', 'Warranty replacement approved by technician'),
        ('EXC-102', 'COSOB ELECTRONIC', 'Samsung A15 64GB', 'Samsung A16 128GB', 30.00, '2026-11-13', 'Customer upgraded, paid $30 difference');
    ");
}

function ensureExtraProductColumns($pdo) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('unit', $cols)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN unit VARCHAR(50) DEFAULT 'Pcs'");
        }
        if (!in_array('last_price', $cols)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN last_price DECIMAL(10,2) DEFAULT 0.00");
        }
        if (!in_array('prev_price', $cols)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN prev_price DECIMAL(10,2) DEFAULT 0.00");
        }
        if (!in_array('prev_wholesale', $cols)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN prev_wholesale DECIMAL(10,2) DEFAULT 0.00");
        }
        if (!in_array('supplier', $cols)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN supplier VARCHAR(150) DEFAULT ''");
        }
        if (!in_array('created_user', $cols)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN created_user VARCHAR(100) DEFAULT 'Admin'");
        }
        if (!in_array('created_at', $cols)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN created_at DATE DEFAULT (CURRENT_DATE)");
        }
        if (!in_array('barcode', $cols)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN barcode VARCHAR(100) DEFAULT ''");
        }
        if (!in_array('notes', $cols)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN notes TEXT DEFAULT NULL");
        }
        
        // Ensure default data has realistic values matching the user's screenshots
        $pdo->exec("UPDATE products SET unit = 'Pcs' WHERE unit IS NULL OR unit = ''");
        $pdo->exec("UPDATE products SET created_user = 'Admin' WHERE created_user IS NULL OR created_user = ''");
        $pdo->exec("UPDATE products SET created_at = '2026-09-19' WHERE created_at IS NULL");
    } catch (Exception $e) {
        // Continue silently if already upgraded
    }
}

function ensurePurchaseTablesAndSeed($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS purchases (
                id INT AUTO_INCREMENT PRIMARY KEY,
                purchase_no VARCHAR(50),
                supplier_name VARCHAR(150),
                phone VARCHAR(50),
                warehouse VARCHAR(100) DEFAULT 'Boorama Branch',
                purchase_date DATE,
                due_date DATE,
                type VARCHAR(50) DEFAULT 'Cash & Credit',
                subtotal DECIMAL(10,2) DEFAULT 0.00,
                discount DECIMAL(10,2) DEFAULT 0.00,
                net_price DECIMAL(10,2) DEFAULT 0.00,
                additionals DECIMAL(10,2) DEFAULT 0.00,
                paid_amount DECIMAL(10,2) DEFAULT 0.00,
                balance DECIMAL(10,2) DEFAULT 0.00,
                payment_method VARCHAR(50) DEFAULT 'ZAAD',
                created_user VARCHAR(100) DEFAULT 'Ridwaan',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS purchase_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                purchase_id INT DEFAULT NULL,
                product_name VARCHAR(255),
                category VARCHAR(100),
                qty INT DEFAULT 1,
                unit VARCHAR(50) DEFAULT 'Pcs',
                last_price DECIMAL(10,2) DEFAULT 0.00,
                add_cost DECIMAL(10,2) DEFAULT 0.00,
                wholesale_price DECIMAL(10,2) DEFAULT 0.00,
                retail_price DECIMAL(10,2) DEFAULT 0.00,
                total_cost DECIMAL(10,2) DEFAULT 0.00,
                remaining_qty INT DEFAULT 0,
                supplier VARCHAR(150) DEFAULT 'INVENTORY STORE',
                created_user VARCHAR(100) DEFAULT 'Ridwaan',
                purchased_date DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS purchase_returns (
                id INT AUTO_INCREMENT PRIMARY KEY,
                return_no VARCHAR(50) UNIQUE,
                purchase_id INT DEFAULT NULL,
                return_date DATE,
                supplier VARCHAR(150),
                product_name VARCHAR(255),
                return_qty INT DEFAULT 1,
                unit_cost DECIMAL(10,2) DEFAULT 0.00,
                total_amount DECIMAL(10,2) DEFAULT 0.00,
                refund_method VARCHAR(100) DEFAULT 'Deduct Balance',
                reason TEXT,
                recorded_by VARCHAR(100) DEFAULT 'A/rahman Ismail Ali',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed initial Purchases if empty
        $pCount = $pdo->query("SELECT COUNT(*) FROM purchases")->fetchColumn();
        if ($pCount == 0) {
            $pdo->exec("
                INSERT INTO purchases (id, purchase_no, supplier_name, phone, warehouse, purchase_date, due_date, type, subtotal, discount, net_price, additionals, paid_amount, balance, payment_method, created_user) VALUES
                (4, '#4', 'INVENTORY STORE', '0634531454', 'Boorama Branch', '2026-11-17', '2026-11-19', 'Cash & Credit', 12000.00, 0.00, 12000.00, 0.00, 100.00, 11900.00, 'ZAAD', 'Ridwaan');
            ");
        }

        // Seed initial Purchase Items if empty
        $piCount = $pdo->query("SELECT COUNT(*) FROM purchase_items")->fetchColumn();
        if ($piCount == 0) {
            $pdo->exec("
                INSERT INTO purchase_items (id, purchase_id, product_name, category, qty, unit, last_price, add_cost, wholesale_price, retail_price, total_cost, remaining_qty, supplier, created_user, purchased_date) VALUES
                (1177, 4, 'Iphone 16 pro max', 'Mobile', 12, 'Pcs', 1000.00, 0.00, 1000.00, 1200.00, 12000.00, 12, 'INVENTORY STORE', 'Ridwaan', '2026-11-17 22:37:48'),
                (1170, 4, 'Iphone 17', 'Mobile', 2, 'Pcs', 50.00, 5.00, 50.00, 75.00, 110.00, 2, 'STORE INVENTORY', 'A/rahman Ismail Ali', '2026-10-05 17:10:28'),
                (1169, 4, 'Iphone 15', 'Mobile', 20, 'Pcs', 1200.00, 0.00, 1200.00, 1350.00, 12000.00, 20, 'Apple Direct', 'A/rahman Ismail Ali', '2026-09-23 16:43:21'),
                (1168, 4, 'Price testing', 'Accessories', 1, 'Pcs', 12.00, 0.00, 12.00, 15.00, 12.00, 1, 'Test Supplier', 'A/rahman Ismail Ali', '2026-09-23 08:40:11'),
                (1167, 4, 'Price testing', 'Accessories', 10, 'Pcs', 11.00, 0.00, 11.00, 15.00, 110.00, 10, 'Test Supplier', 'A/rahman Ismail Ali', '2026-09-23 08:17:25'),
                (1166, 4, 'Laptop Flex', 'Laptop', 1, 'Pcs', 1.00, 0.00, 1.00, 2.00, 1.00, 1, 'Test Supplier', 'A/rahman Ismail Ali', '2026-09-21 14:23:12'),
                (1165, 4, 'Iphone 16 pro max', 'Mobile', 6, 'Pcs', 450.00, 0.00, 450.00, 600.00, 2700.00, 6, 'Dubai Mobile Hub', 'N/A', '2026-08-19 14:32:19'),
                (1164, 4, 'Hp laptop 830', 'Laptop', 24, 'Pcs', 200.00, 0.00, 200.00, 300.00, 4800.00, 24, 'HP Authorized', 'N/A', '2026-08-19 14:32:19');
            ");
        }

        // Seed initial Purchase Returns if empty
        $prCount = $pdo->query("SELECT COUNT(*) FROM purchase_returns")->fetchColumn();
        if ($prCount == 0) {
            $pdo->exec("
                INSERT INTO purchase_returns (id, return_no, return_date, supplier, product_name, return_qty, unit_cost, total_amount, refund_method, reason, recorded_by) VALUES
                (2, 'PR-20261005-001', '2026-10-05', 'STORE INVENTORY', 'Iphone 17', 1, 50.00, 50.00, 'Deduct Balance', 'Defective return test', 'A/rahman Ismail Ali'),
                (1, 'PR-20260924-001', '2026-09-24', 'Test', 'Iphone 15', 1, 1.00, 1.00, 'Deduct Balance', 'Damaged screen upon arrival', 'A/rahman Ismail Ali');
            ");
        }
    } catch (Exception $e) {
        // Continue silently if tables already exist
    }
}

function ensureSupplierTableAndSeed($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS suppliers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150),
                phone VARCHAR(50),
                address VARCHAR(255) DEFAULT 'BOORAMA',
                balance DECIMAL(10,2) DEFAULT 0.00,
                created_user VARCHAR(100) DEFAULT 'N/A',
                joined_at DATE DEFAULT (CURRENT_DATE),
                notes TEXT
            );
        ");

        $sCount = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
        if ($sCount == 0) {
            $pdo->exec("
                INSERT INTO suppliers (id, name, phone, address, balance, created_user, joined_at) VALUES
                (1, 'STORE INVENTORY', '0634531454', 'BOORAMA', 10.00, 'N/A', '2026-02-21'),
                (2, 'INVERNTURI', '0634531454', 'BOORAMA', 0.00, 'N/A', '2026-02-21'),
                (3, 'INVERNTURI', '0634531454', 'BOORAMA', 0.00, 'N/A', '2026-02-21'),
                (4, 'INVERNTURI', '0634531454', 'BOORAMA', 0.00, 'N/A', '2026-02-21'),
                (5, 'INVENTORY STORE', '0634531454', 'BOORAMA', 11900.00, 'N/A', '2026-02-22'),
                (6, 'AXMED MAAL', '4603255', 'BOORAMA', 69.00, 'N/A', '2026-02-25'),
                (7, 'AXMED MAAL', '4603255', 'BOORAMA', 38.00, 'N/A', '2026-02-25'),
                (8, 'AXMED MAAL', '4603255', 'BOORAMA', 0.00, 'N/A', '2026-02-25'),
                (9, 'MUGANI', '-', 'BOORAMA', 0.00, 'N/A', '2026-02-25'),
                (10, 'EDIT BURCHASE', '0634531454', 'BOORAMA', 52.90, 'N/A', '2026-02-28');
            ");
        }
    } catch (Exception $e) {
        // Continue silently
    }
}

function ensureCategoryAndUnitTablesAndSeed($pdo) {
    try {
        // 1. Categories Table (Image 1)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL UNIQUE,
                status VARCHAR(50) DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed 13 initial categories matching the user's screenshot
        $catCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        if ($catCount == 0) {
            $pdo->exec("
                INSERT INTO categories (id, name, status) VALUES
                (1, 'PHONE PARTS', 'Active'),
                (2, 'EARPHONE', 'Active'),
                (3, 'POWER BANK', 'Active'),
                (4, 'LIGHT', 'Active'),
                (5, 'FIISH', 'Active'),
                (6, 'CHARGER', 'Active'),
                (7, 'PHONE COVER', 'Active'),
                (8, 'GAME PARTS', 'Active'),
                (9, 'GLASS', 'Active'),
                (10, 'COOL BACK', 'Active'),
                (11, 'BATTERY', 'Active'),
                (12, 'CABLE', 'Active'),
                (13, 'DISPLAY', 'Active');
            ");
        }

        // 2. Sub Categories Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sub_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category_name VARCHAR(150) NOT NULL,
                name VARCHAR(150) NOT NULL,
                status VARCHAR(50) DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $subCount = $pdo->query("SELECT COUNT(*) FROM sub_categories")->fetchColumn();
        if ($subCount == 0) {
            $pdo->exec("
                INSERT INTO sub_categories (category_name, name, status) VALUES
                ('PHONE PARTS', 'LCD Screen Replacement', 'Active'),
                ('PHONE PARTS', 'Charging Flex Cable', 'Active'),
                ('PHONE PARTS', 'Camera Module', 'Active'),
                ('PHONE PARTS', 'Original Battery Pack', 'Active'),
                ('EARPHONE', 'Bluetooth Wireless Earbuds', 'Active'),
                ('EARPHONE', 'Wired 3.5mm Earphone', 'Active'),
                ('EARPHONE', 'Airpods Pro / Max', 'Active'),
                ('EARPHONE', 'Gaming Headset', 'Active'),
                ('POWER BANK', '10,000mAh Slim Pocket', 'Active'),
                ('POWER BANK', '20,000mAh Fast Charge', 'Active'),
                ('POWER BANK', '30,000mAh Heavy Duty', 'Active'),
                ('POWER BANK', 'MagSafe Wireless PowerBank', 'Active'),
                ('LIGHT', 'Ring Light 12 inch', 'Active'),
                ('LIGHT', 'RGB Desk Atmosphere Light', 'Active'),
                ('LIGHT', 'Studio Softbox Lamp', 'Active'),
                ('FIISH', 'Multi-Socket Extension 3M', 'Active'),
                ('FIISH', 'Universal Travel Plug', 'Active'),
                ('FIISH', 'Surge Protector 5-Way', 'Active'),
                ('CHARGER', '20W PD Fast Charger', 'Active'),
                ('CHARGER', '65W GaN Laptop Charger', 'Active'),
                ('CHARGER', 'Car Fast Charger Adapter', 'Active'),
                ('PHONE COVER', 'Silicone Soft Case', 'Active'),
                ('PHONE COVER', 'Shockproof Armor Case', 'Active'),
                ('PHONE COVER', 'Clear MagSafe Case', 'Active'),
                ('GAME PARTS', 'PS5 Wireless Controller', 'Active'),
                ('GAME PARTS', 'Console Cooling Fan Stand', 'Active'),
                ('GAME PARTS', 'Thumbstick Grips Pack', 'Active'),
                ('GLASS', '9D Privacy Glass Screen', 'Active'),
                ('GLASS', 'Ultra Clear Tempered Glass', 'Active'),
                ('GLASS', 'Camera Lens Protector', 'Active'),
                ('COOL BACK', 'Magnetic Phone Cooler Fan', 'Active'),
                ('COOL BACK', 'Dual Fan Radiator', 'Active'),
                ('BATTERY', 'iPhone Replacement Battery', 'Active'),
                ('BATTERY', 'Samsung Replacement Battery', 'Active'),
                ('BATTERY', 'Laptop Li-ion Battery', 'Active'),
                ('CABLE', 'Type-C to Type-C 100W Cable', 'Active'),
                ('CABLE', 'Lightning to Type-C Fast Cable', 'Active'),
                ('CABLE', 'HDMI 4K Ultra High Speed Cable', 'Active'),
                ('DISPLAY', 'Super AMOLED Touch Display', 'Active'),
                ('DISPLAY', 'Retina LCD Display Screen', 'Active'),
                ('Mobile', 'Brand New Smartphones', 'Active'),
                ('Mobile', 'Used / Refurbished Phones', 'Active'),
                ('Laptop', 'Business Laptops', 'Active'),
                ('Laptop', 'Gaming & Workstation', 'Active'),
                ('Accessories', 'Smart Watches & Bands', 'Active'),
                ('Accessories', 'Bluetooth Speakers', 'Active');
            ");
        }

        // Ensure subcategories for other categories are present
        $extraSubs = [
            ['BATTERY', 'iPhone Replacement Battery'],
            ['BATTERY', 'Samsung Replacement Battery'],
            ['BATTERY', 'Laptop Li-ion Battery'],
            ['CABLE', 'Type-C to Type-C 100W Cable'],
            ['CABLE', 'Lightning to Type-C Fast Cable'],
            ['CABLE', 'HDMI 4K Ultra High Speed Cable'],
            ['DISPLAY', 'Super AMOLED Touch Display'],
            ['DISPLAY', 'Retina LCD Display Screen'],
            ['Mobile', 'Brand New Smartphones'],
            ['Mobile', 'Used / Refurbished Phones'],
            ['Laptop', 'Business Laptops'],
            ['Laptop', 'Gaming & Workstation'],
            ['Accessories', 'Smart Watches & Bands'],
            ['Accessories', 'Bluetooth Speakers']
        ];
        $chkSub = $pdo->prepare("SELECT COUNT(*) FROM sub_categories WHERE category_name = ? AND name = ?");
        $insSub = $pdo->prepare("INSERT INTO sub_categories (category_name, name, status) VALUES (?, ?, 'Active')");
        foreach ($extraSubs as $es) {
            $chkSub->execute([$es[0], $es[1]]);
            if ($chkSub->fetchColumn() == 0) {
                $insSub->execute([$es[0], $es[1]]);
            }
        }

        // 3. Measurements / Units Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS measurements (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                symbol VARCHAR(50) NOT NULL,
                status VARCHAR(50) DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $mCount = $pdo->query("SELECT COUNT(*) FROM measurements")->fetchColumn();
        if ($mCount == 0) {
            $pdo->exec("
                INSERT INTO measurements (name, symbol, status) VALUES
                ('Pieces', 'Pcs', 'Active'),
                ('Box', 'Box', 'Active'),
                ('Set', 'Set', 'Active'),
                ('Meter', 'M', 'Active'),
                ('Kilogram', 'Kg', 'Active'),
                ('Pack', 'Pk', 'Active'),
                ('Roll', 'Roll', 'Active');
            ");
        }

        // 4. Ensure products table has subcategory column
        $cols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('subcategory', $cols)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN subcategory VARCHAR(150) DEFAULT '' AFTER category");
        }
    } catch (Exception $e) {
        // Continue silently
    }
}
