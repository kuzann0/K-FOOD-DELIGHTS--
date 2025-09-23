<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/DatabaseManager.php';

try {
    // Test database connection
    $db = \KFood\Database\DatabaseManager::getInstance();
    $conn = $db->getConnection();
    
    // Test basic query
    $stmt = $conn->prepare("SELECT DATABASE()");
    $stmt->execute();
    $result = $stmt->get_result();
    $dbname = $result->fetch_row()[0];
    
    echo "✅ Database connection successful\n";
    echo "Connected to database: $dbname\n";
    
    // Test table access
    $tables = ['users', 'orders', 'order_items', 'products'];
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM $table");
        $stmt->execute();
        $count = $stmt->get_result()->fetch_row()[0];
        echo "✅ Table '$table' accessible ($count records)\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    // Additional diagnostics
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "\nPossible solutions:\n";
        echo "1. Verify DB_USER and DB_PASS in config.php\n";
        echo "2. Check that the user has proper privileges\n";
        echo "3. Verify the user can connect from localhost\n";
    }
    
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "\nPossible solutions:\n";
        echo "1. Verify DB_NAME in config.php\n";
        echo "2. Import k_food_delights_db.sql to create the database\n";
        echo "3. Check database name spelling\n";
    }
    
    exit(1);
}