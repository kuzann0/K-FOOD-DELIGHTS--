<?php
function createNotificationLogsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS notification_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL,
        role_id INT NOT NULL,
        notification_type VARCHAR(50) NOT NULL,
        notification_data JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_delivered TINYINT(1) DEFAULT 0,
        delivered_at TIMESTAMP NULL,
        INDEX idx_order_role (order_id, role_id),
        INDEX idx_type (notification_type),
        INDEX idx_created (created_at),
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    if ($conn->query($sql) !== TRUE) {
        error_log("Error creating notification_logs table: " . $conn->error);
        return false;
    }

    // Create a cleanup procedure
    $cleanupProcedure = "
    CREATE PROCEDURE IF NOT EXISTS cleanup_old_notifications()
    BEGIN
        DELETE FROM notification_logs 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL " . NOTIFICATION_EXPIRY_DAYS . " DAY);
    END;";

    if ($conn->query($cleanupProcedure) !== TRUE) {
        error_log("Error creating cleanup procedure: " . $conn->error);
        return false;
    }

    // Create an event to run the cleanup daily
    $conn->query("SET GLOBAL event_scheduler = ON;");
    
    $createEvent = "
    CREATE EVENT IF NOT EXISTS cleanup_notifications_daily
    ON SCHEDULE EVERY 1 DAY
    DO CALL cleanup_old_notifications();";

    if ($conn->query($createEvent) !== TRUE) {
        error_log("Error creating cleanup event: " . $conn->error);
        return false;
    }

    return true;
}

// Call the function if this file is included in setup
if (isset($conn)) {
    createNotificationLogsTable($conn);
}
?>