<?php
require_once __DIR__ . '/../k_food_customer/config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Drop and recreate the database
    $sql = "DROP DATABASE IF EXISTS " . DB_NAME;
    if (!$conn->query($sql)) {
        throw new Exception("Error dropping database: " . $conn->error);
    }

    $sql = "CREATE DATABASE " . DB_NAME;
    if (!$conn->query($sql)) {
        throw new Exception("Error creating database: " . $conn->error);
    }

    // Select the database
    $conn->select_db(DB_NAME);

    // Create orders table
    $sql = "CREATE TABLE orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        items JSON NOT NULL,
        total DECIMAL(10, 2) NOT NULL,
        customer_info JSON NOT NULL,
        status ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        payment_status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    if (!$conn->query($sql)) {
        throw new Exception("Error creating orders table: " . $conn->error);
    }

    echo "Database tables created successfully\n";

} catch (Exception $e) {
    die("Setup failed: " . $e->getMessage() . "\n");
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}