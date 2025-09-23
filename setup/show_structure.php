<?php
require_once __DIR__ . '/../k_food_customer/config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Show table structure
    $result = $conn->query("DESCRIBE orders");
    
    echo "Table structure for 'orders':\n";
    echo str_repeat("=", 80) . "\n";
    echo sprintf("%-20s %-30s %-10s %s\n", "Field", "Type", "Null", "Default");
    echo str_repeat("-", 80) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-20s %-30s %-10s %s\n",
            $row['Field'],
            $row['Type'],
            $row['Null'],
            $row['Default'] ?? 'NULL'
        );
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}