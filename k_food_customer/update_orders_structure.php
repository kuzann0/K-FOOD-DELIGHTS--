<?php
require_once 'config.php';

try {
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/sql/update_orders_structure.sql');
    
    if ($conn->multi_query($sql)) {
        do {
            // Store first result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        
        echo "Orders table structure updated successfully";
    } else {
        throw new Exception("Error updating orders table structure: " . $conn->error);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    $conn->close();
}
?>