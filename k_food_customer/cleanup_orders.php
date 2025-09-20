<?php
require_once 'config.php';

try {
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/sql/cleanup_orders.sql');
    
    // Execute multi query
    if ($conn->multi_query($sql)) {
        do {
            // Consume results to allow next query to execute
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    } else {
        throw new Exception("Error executing cleanup: " . $conn->error);
    }
    
    echo "Successfully cleaned up orders and order_items tables";

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo "Error: " . $e->getMessage();
    
    // Make sure foreign key checks are re-enabled even if there's an error
    $conn->query('SET FOREIGN_KEY_CHECKS = 1');
} finally {
    $conn->close();
}
?>