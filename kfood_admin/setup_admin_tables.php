<?php
require_once 'config.php';

// Create admin_roles table
$conn->query("
    CREATE TABLE IF NOT EXISTS admin_roles (
        role_id INT PRIMARY KEY AUTO_INCREMENT,
        role_name VARCHAR(50) NOT NULL,
        role_description TEXT,
        is_super_admin TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Create admin_users table
$conn->query("
    CREATE TABLE IF NOT EXISTS admin_users (
        admin_id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        full_name VARCHAR(100) NOT NULL,
        role_id INT NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        FOREIGN KEY (role_id) REFERENCES admin_roles(role_id),
        FOREIGN KEY (created_by) REFERENCES admin_users(admin_id)
    )
");

// Create audit_trail table
$conn->query("
    CREATE TABLE IF NOT EXISTS audit_trail (
        audit_id INT PRIMARY KEY AUTO_INCREMENT,
        admin_id INT NOT NULL,
        action_type VARCHAR(50) NOT NULL,
        table_name VARCHAR(50) NOT NULL,
        record_id VARCHAR(50) NOT NULL,
        action_details TEXT,
        action_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45),
        FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id)
    )
");

// Insert default admin role if it doesn't exist
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_roles WHERE is_super_admin = 1");
$stmt->execute();
$result = $stmt->get_result();
if ($result->fetch_assoc()['count'] == 0) {
    $conn->query("
        INSERT INTO admin_roles (role_name, role_description, is_super_admin)
        VALUES ('Super Admin', 'Full system access with ability to manage other admins', 1)
    ");
}

echo "Database tables created successfully!\n";
?>
