<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Read the SQL file
    $sql = file_get_contents('sql/create_user_activity_tables.sql');
    
    // Execute multiple SQL statements
    if ($conn->multi_query($sql)) {
        do {
            // Store first result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
            // Prepare next result set
        } while ($conn->more_results() && $conn->next_result());
        
        if ($conn->errno) {
            throw new Exception("Error executing SQL: " . $conn->error);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'User activity tables created successfully'
        ]);
    } else {
        throw new Exception("Error creating tables: " . $conn->error);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>