<?php
require_once 'config.php';

try {
    // Create order_status_history table
    $sql = "CREATE TABLE IF NOT EXISTS order_status_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        status VARCHAR(50) NOT NULL,
        status_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
    )";

    if ($conn->query($sql)) {
        echo "Successfully created order_status_history table.\n";
    } else {
        throw new Exception("Error creating order_status_history table: " . $conn->error);
    }

    // Create trigger for tracking order status changes
    $triggerSql = "
    CREATE TRIGGER IF NOT EXISTS order_status_history_trigger
    AFTER UPDATE ON orders
    FOR EACH ROW
    BEGIN
        IF OLD.status != NEW.status THEN
            INSERT INTO order_status_history (order_id, status, notes)
            VALUES (NEW.order_id, NEW.status, CONCAT('Status changed from ', OLD.status, ' to ', NEW.status));
        END IF;
    END;
    ";

    // Drop existing trigger if it exists
    $conn->query("DROP TRIGGER IF EXISTS order_status_history_trigger");

    // Create new trigger
    if ($conn->multi_query($triggerSql)) {
        echo "Successfully created order status history trigger.\n";
    } else {
        throw new Exception("Error creating trigger: " . $conn->error);
    }

    echo "Setup completed successfully.\n";

} catch (Exception $e) {
    echo "Error during setup: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
