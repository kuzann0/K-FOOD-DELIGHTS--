<?php
require_once 'includes/config.php';

// Create tables with foreign key relationships
$tables = [
    // System Settings Table
    "CREATE TABLE IF NOT EXISTS system_settings (
        setting_id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(50) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_description TEXT,
        updated_by INT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (updated_by) REFERENCES admin_users(admin_id)
    )",

    // Landing Page Content
    "CREATE TABLE IF NOT EXISTS landing_content (
        content_id INT PRIMARY KEY AUTO_INCREMENT,
        section_name VARCHAR(50) NOT NULL,
        content_type ENUM('text', 'image', 'banner') NOT NULL,
        content TEXT,
        is_active TINYINT(1) DEFAULT 1,
        created_by INT,
        updated_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES admin_users(admin_id),
        FOREIGN KEY (updated_by) REFERENCES admin_users(admin_id)
    )",

    // Categories Table
    "CREATE TABLE IF NOT EXISTS categories (
        category_id INT PRIMARY KEY AUTO_INCREMENT,
        category_name VARCHAR(100) NOT NULL,
        description TEXT,
        image_path VARCHAR(255),
        is_active TINYINT(1) DEFAULT 1,
        created_by INT,
        updated_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES admin_users(admin_id),
        FOREIGN KEY (updated_by) REFERENCES admin_users(admin_id)
    )",

    // Products Table
    "CREATE TABLE IF NOT EXISTS products (
        product_id INT PRIMARY KEY AUTO_INCREMENT,
        category_id INT,
        product_name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        image_path VARCHAR(255),
        is_available TINYINT(1) DEFAULT 1,
        created_by INT,
        updated_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(category_id),
        FOREIGN KEY (created_by) REFERENCES admin_users(admin_id),
        FOREIGN KEY (updated_by) REFERENCES admin_users(admin_id)
    )",

    // Inventory Items Table
    "CREATE TABLE IF NOT EXISTS inventory_items (
        item_id INT PRIMARY KEY AUTO_INCREMENT,
        item_name VARCHAR(100) NOT NULL,
        description TEXT,
        quantity INT DEFAULT 0,
        unit VARCHAR(20),
        minimum_stock INT DEFAULT 0,
        supplier_name VARCHAR(100),
        supplier_contact VARCHAR(100),
        expiration_date DATE,
        created_by INT,
        updated_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES admin_users(admin_id),
        FOREIGN KEY (updated_by) REFERENCES admin_users(admin_id)
    )",

    // Inventory Transactions Table
    "CREATE TABLE IF NOT EXISTS inventory_transactions (
        transaction_id INT PRIMARY KEY AUTO_INCREMENT,
        item_id INT,
        transaction_type ENUM('in', 'out') NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2),
        transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        created_by INT,
        FOREIGN KEY (item_id) REFERENCES inventory_items(item_id),
        FOREIGN KEY (created_by) REFERENCES admin_users(admin_id)
    )",

    // Orders Table
    "CREATE TABLE IF NOT EXISTS orders (
        order_id INT PRIMARY KEY AUTO_INCREMENT,
        customer_id INT,
        order_status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
        total_amount DECIMAL(10,2) NOT NULL,
        payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
        payment_method VARCHAR(50),
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        notes TEXT
    )",

    // Order Items Table
    "CREATE TABLE IF NOT EXISTS order_items (
        order_item_id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT,
        product_id INT,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(order_id),
        FOREIGN KEY (product_id) REFERENCES products(product_id)
    )",

    // Module Permissions Table
    "CREATE TABLE IF NOT EXISTS module_permissions (
        permission_id INT PRIMARY KEY AUTO_INCREMENT,
        role_id INT,
        module_name VARCHAR(50) NOT NULL,
        can_view TINYINT(1) DEFAULT 0,
        can_add TINYINT(1) DEFAULT 0,
        can_edit TINYINT(1) DEFAULT 0,
        can_delete TINYINT(1) DEFAULT 0,
        updated_by INT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES admin_roles(role_id),
        FOREIGN KEY (updated_by) REFERENCES admin_users(admin_id)
    )",

    // System Alerts Table
    "CREATE TABLE IF NOT EXISTS system_alerts (
        alert_id INT PRIMARY KEY AUTO_INCREMENT,
        alert_type ENUM('info', 'warning', 'danger') NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL
    )"
];

// Execute each table creation query
foreach ($tables as $query) {
    if (!$conn->query($query)) {
        die("Error creating table: " . $conn->error);
    }
}

// Insert default system settings
$default_settings = [
    ['maintenance_mode', '0', 'Enable/disable system maintenance mode'],
    ['site_name', 'K-Food Delights', 'Website name'],
    ['contact_email', 'admin@kfood.com', 'Contact email address'],
    ['items_per_page', '10', 'Number of items to display per page'],
    ['low_stock_threshold', '10', 'Low stock alert threshold'],
    ['enable_notifications', '1', 'Enable system notifications'],
    ['currency_symbol', '₱', 'Currency symbol'],
    ['tax_rate', '12', 'Tax rate percentage']
];

$stmt = $conn->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_description) VALUES (?, ?, ?)");

foreach ($default_settings as $setting) {
    $stmt->bind_param("sss", $setting[0], $setting[1], $setting[2]);
    $stmt->execute();
}

echo "Database tables and default settings created successfully!\n";
?>
