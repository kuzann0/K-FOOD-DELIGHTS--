<?php
require_once __DIR__ . '/../k_food_customer/config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Create users table with all necessary fields
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        phone_number VARCHAR(20),
        address TEXT,
        security_question VARCHAR(255),
        security_answer VARCHAR(255),
        role ENUM('customer', 'crew', 'admin') DEFAULT 'customer',
        account_status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        email_verified BOOLEAN DEFAULT FALSE,
        phone_verified BOOLEAN DEFAULT FALSE,
        last_login DATETIME,
        password_reset_token VARCHAR(255),
        password_reset_expires DATETIME,
        verification_token VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_role (role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!$conn->query($sql)) {
        throw new Exception("Error creating users table: " . $conn->error);
    }

    // Add sample admin user for testing if table was just created
    if ($conn->affected_rows > 0) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password, full_name, role, email_verified, phone_verified) 
                VALUES ('admin', 'admin@kfooddelights.com', ?, 'System Administrator', 'admin', TRUE, TRUE)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $adminPassword);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating admin user: " . $stmt->error);
        }
    }

    echo "Users table created successfully\n";

} catch (Exception $e) {
    die("Setup failed: " . $e->getMessage() . "\n");
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}