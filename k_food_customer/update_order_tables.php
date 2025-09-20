<?php
require_once 'config.php';

try {
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/sql/create_order_tables.sql');
    
    if ($conn->multi_query($sql)) {
        do {
            // Store first result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        
        echo "Orders tables updated successfully";
    } else {
        throw new Exception("Error updating orders tables: " . $conn->error);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    $conn->close();
}
?>