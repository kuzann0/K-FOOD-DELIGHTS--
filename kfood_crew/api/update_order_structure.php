<?php
require_once '../k_food_customer/config.php';
require_once '../includes/crew_auth.php';

// Validate crew session
validateCrewSession();

try {
    // Create order_status_history table if not exists
    $conn->query("
        CREATE TABLE IF NOT EXISTS order_status_history (
            history_id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            notes TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(order_id),
            FOREIGN KEY (created_by) REFERENCES users(user_id)
        )
    ");

    // Create order_preparation_history table if not exists
    $conn->query("
        CREATE TABLE IF NOT EXISTS order_preparation_history (
            history_id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            estimated_time DATETIME,
            notes TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(order_id),
            FOREIGN KEY (created_by) REFERENCES users(user_id)
        )
    ");

    // Add preparation_status and estimated_completion_time to orders table if not exists
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'preparation_status'");
    if ($result->num_rows === 0) {
        $conn->query("
            ALTER TABLE orders 
            ADD COLUMN preparation_status VARCHAR(50) DEFAULT 'not_started' AFTER status,
            ADD COLUMN estimated_completion_time DATETIME NULL AFTER preparation_status
        ");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Database structure updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update database structure: ' . $e->getMessage()
    ]);
}