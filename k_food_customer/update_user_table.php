<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // First check if the column already exists
    $checkColumn = $conn->query("
        SELECT COUNT(*) as exists_count 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'users' 
        AND COLUMN_NAME = 'last_login'
    ");
    
    $columnExists = $checkColumn->fetch_assoc()['exists_count'] > 0;
    
    if (!$columnExists) {
        // Add the column
        $alterTable = $conn->query("
            ALTER TABLE users 
            ADD COLUMN last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ");
        
        if (!$alterTable) {
            throw new Exception("Failed to add last_login column: " . $conn->error);
        }
        
        // Update existing rows with current timestamp
        $updateExisting = $conn->query("
            UPDATE users 
            SET last_login = CURRENT_TIMESTAMP 
            WHERE last_login IS NULL
        ");
        
        if (!$updateExisting) {
            throw new Exception("Failed to update existing rows: " . $conn->error);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Last login column added and initialized successfully'
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'message' => 'Last login column already exists'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>