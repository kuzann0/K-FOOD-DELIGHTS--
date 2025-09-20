<?php
include_once '../config.php';

// Create notifications table
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    notification_type ENUM('order', 'system', 'alert') NOT NULL,
    reference_id INT,
    user_id INT,
    role_id INT,
    message TEXT NOT NULL,
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_role_status (role_id, status),
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB;";

try {
    if ($conn->query($sql)) {
        echo "Notifications table created successfully";
    } else {
        throw new Exception("Error creating notifications table: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
}
?>