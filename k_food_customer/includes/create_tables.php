<?php
require_once __DIR__ . '/../config.php';

// Create users table with all required fields
function createUsersTable($conn) {
    $createTableSQL = "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        delivery_address TEXT,
        delivery_instructions TEXT,
        preferred_payment_method VARCHAR(50),
        last_used_gcash_number VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    try {
        $conn->query($createTableSQL);
        return true;
    } catch (Exception $e) {
        error_log("Failed to create users table: " . $e->getMessage());
        return false;
    }
}

// Create user_activity_log table
function createUserActivityLogTable($conn) {
    $createTableSQL = "CREATE TABLE IF NOT EXISTS user_activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        activity_type VARCHAR(50) NOT NULL,
        activity_details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    try {
        $conn->query($createTableSQL);
        return true;
    } catch (Exception $e) {
        error_log("Failed to create user_activity_log table: " . $e->getMessage());
        return false;
    }
}

// Update users table columns
function updateUsersTable($conn) {
    $columns = [
        'delivery_address' => 'TEXT',
        'delivery_instructions' => 'TEXT',
        'preferred_payment_method' => 'VARCHAR(50)',
        'last_used_gcash_number' => 'VARCHAR(20)'
    ];

    foreach ($columns as $column => $type) {
        try {
            $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS $column $type");
        } catch (Exception $e) {
            error_log("Failed to add column $column: " . $e->getMessage());
        }
    }

    // Copy address to delivery_address for existing users
    try {
        $conn->query("UPDATE users SET delivery_address = address WHERE delivery_address IS NULL");
    } catch (Exception $e) {
        error_log("Failed to update delivery_address: " . $e->getMessage());
    }
}

// Create and update all tables
createUsersTable($conn);
createUserActivityLogTable($conn);
updateUsersTable($conn);
?>
