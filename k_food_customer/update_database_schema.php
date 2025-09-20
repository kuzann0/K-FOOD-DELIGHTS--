<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Ensure only admin can run this script
if (!isAdminUser()) {
    die("Access denied");
}

try {
    // Start transaction
    $conn->begin_transaction();

    // System Settings Table - Added indexes and validation
    $conn->query("CREATE TABLE IF NOT EXISTS system_settings (
        setting_id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(50) NOT NULL,
        setting_value TEXT,
        setting_description TEXT,
        is_public TINYINT(1) DEFAULT 0,
        requires_restart TINYINT(1) DEFAULT 0,
        updated_by INT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_setting_key (setting_key),
        FOREIGN KEY (updated_by) REFERENCES users(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Categories Table - Added validation and soft delete
    $conn->query("CREATE TABLE IF NOT EXISTS categories (
        category_id INT PRIMARY KEY AUTO_INCREMENT,
        category_name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        description TEXT,
        image_path VARCHAR(255),
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        is_deleted TINYINT(1) DEFAULT 0,
        created_by INT,
        updated_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL,
        UNIQUE KEY unique_category_slug (slug),
        FOREIGN KEY (created_by) REFERENCES users(user_id),
        FOREIGN KEY (updated_by) REFERENCES users(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Products Table - Added validation and soft delete
    $conn->query("CREATE TABLE IF NOT EXISTS products (
        product_id INT PRIMARY KEY AUTO_INCREMENT,
        category_id INT,
        product_name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        sale_price DECIMAL(10,2) NULL,
        image_path VARCHAR(255),
        stock_quantity INT DEFAULT 0,
        minimum_stock INT DEFAULT 5,
        preparation_time INT DEFAULT 15,
        is_available TINYINT(1) DEFAULT 1,
        is_featured TINYINT(1) DEFAULT 0,
        is_deleted TINYINT(1) DEFAULT 0,
        created_by INT,
        updated_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL,
        UNIQUE KEY unique_product_slug (slug),
        FOREIGN KEY (category_id) REFERENCES categories(category_id),
        FOREIGN KEY (created_by) REFERENCES users(user_id),
        FOREIGN KEY (updated_by) REFERENCES users(user_id),
        CHECK (sale_price IS NULL OR sale_price <= price)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Orders Table - Enhanced status tracking and validation
    $conn->query("CREATE TABLE IF NOT EXISTS orders (
        order_id INT PRIMARY KEY AUTO_INCREMENT,
        order_number VARCHAR(50) NOT NULL,
        user_id INT NOT NULL,
        customer_name VARCHAR(100) NOT NULL,
        contact_number VARCHAR(20) NOT NULL,
        delivery_address TEXT NOT NULL,
        special_instructions TEXT,
        status ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending',
        payment_status ENUM('pending', 'processing', 'paid', 'failed', 'refunded') DEFAULT 'pending',
        payment_method VARCHAR(50),
        subtotal DECIMAL(10,2) NOT NULL,
        delivery_fee DECIMAL(10,2) DEFAULT 0.00,
        discount_amount DECIMAL(10,2) DEFAULT 0.00,
        tax_amount DECIMAL(10,2) DEFAULT 0.00,
        total_amount DECIMAL(10,2) NOT NULL,
        promo_code VARCHAR(50),
        preparation_status VARCHAR(50) DEFAULT 'not_started',
        estimated_delivery_time DATETIME,
        actual_delivery_time DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by INT,
        completed_at DATETIME,
        completed_by INT,
        cancelled_at DATETIME,
        cancellation_reason TEXT,
        is_deleted TINYINT(1) DEFAULT 0,
        UNIQUE KEY unique_order_number (order_number),
        FOREIGN KEY (user_id) REFERENCES users(user_id),
        FOREIGN KEY (updated_by) REFERENCES users(user_id),
        FOREIGN KEY (completed_by) REFERENCES users(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Order Items Table - Added validation and price tracking
    $conn->query("CREATE TABLE IF NOT EXISTS order_items (
        order_item_id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        product_name VARCHAR(100) NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        special_instructions TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(order_id),
        FOREIGN KEY (product_id) REFERENCES products(product_id),
        CHECK (quantity > 0),
        CHECK (unit_price >= 0),
        CHECK (subtotal = quantity * unit_price)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Order Status History Table - For tracking all status changes
    $conn->query("CREATE TABLE IF NOT EXISTS order_status_history (
        history_id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL,
        previous_status VARCHAR(50) NOT NULL,
        new_status VARCHAR(50) NOT NULL,
        notes TEXT,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(order_id),
        FOREIGN KEY (created_by) REFERENCES users(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Order Preparation History Table - For tracking preparation progress
    $conn->query("CREATE TABLE IF NOT EXISTS order_preparation_history (
        history_id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL,
        status VARCHAR(50) NOT NULL,
        estimated_time DATETIME,
        notes TEXT,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(order_id),
        FOREIGN KEY (created_by) REFERENCES users(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Notifications Table - For system notifications
    $conn->query("CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        type ENUM('order', 'system', 'payment', 'inventory') NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(255),
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL,
        expires_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Activity Log Table - For tracking all system activity
    $conn->query("CREATE TABLE IF NOT EXISTS activity_log (
        log_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        action VARCHAR(50) NOT NULL,
        entity_type VARCHAR(50) NOT NULL,
        entity_id INT,
        details TEXT,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create indexes for better performance
    $indexes = [
        "ALTER TABLE orders ADD INDEX idx_user_id (user_id)",
        "ALTER TABLE orders ADD INDEX idx_status (status)",
        "ALTER TABLE orders ADD INDEX idx_created_at (created_at)",
        "ALTER TABLE order_items ADD INDEX idx_order_product (order_id, product_id)",
        "ALTER TABLE notifications ADD INDEX idx_user_unread (user_id, is_read)",
        "ALTER TABLE activity_log ADD INDEX idx_user_action (user_id, action)",
        "ALTER TABLE activity_log ADD INDEX idx_entity (entity_type, entity_id)"
    ];

    foreach ($indexes as $index) {
        try {
            $conn->query($index);
        } catch (Exception $e) {
            // Index might already exist
            continue;
        }
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Database schema updated successfully'
    ]);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => 'Error updating database schema: ' . $e->getMessage()
    ]);
}