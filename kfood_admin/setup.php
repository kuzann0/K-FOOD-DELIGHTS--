<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'kfood_delights';

try {
    // Create connection
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS k_food_delights CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!$conn->query($sql)) {
        throw new Exception("Error creating database: " . $conn->error);
    }
    echo "Database created successfully\n";

    // Select the database
    $conn->select_db('k_food_delights');

    // Import core tables schema
    $coreTables = file_get_contents(__DIR__ . '/sql/core_tables.sql');
    if (!$conn->multi_query($coreTables)) {
        throw new Exception("Error importing core tables: " . $conn->error);
    }
    while ($conn->more_results() && $conn->next_result()); // Clear multi_query results

    echo "Core tables created successfully\n";

    // Import admin schema
    $adminSchema = file_get_contents(__DIR__ . '/sql/admin_schema.sql');
    if (!$conn->multi_query($adminSchema)) {
        throw new Exception("Error importing admin schema: " . $conn->error);
    }
    while ($conn->more_results() && $conn->next_result()); // Clear multi_query results

    echo "Admin schema imported successfully\n";

    // Import inventory schema
    $inventorySchema = file_get_contents(__DIR__ . '/sql/inventory_schema.sql');
    if (!$conn->multi_query($inventorySchema)) {
        throw new Exception("Error importing inventory schema: " . $conn->error);
    }
    while ($conn->more_results() && $conn->next_result()); // Clear multi_query results

    echo "Inventory schema imported successfully\n";

    // Create default admin user if not exists
    $username = 'admin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $email = 'admin@kfood.com';

    $stmt = $conn->prepare("
        INSERT IGNORE INTO admin_users (username, password, email, role_id) 
        VALUES (?, ?, ?, 1)
    ");
    $stmt->bind_param("sss", $username, $password, $email);
    if (!$stmt->execute()) {
        throw new Exception("Error creating default admin user: " . $stmt->error);
    }
    echo "Default admin user created successfully\n";
    echo "\nSetup completed successfully! You can now log in with:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";

} catch (Exception $e) {
    die("Setup failed: " . $e->getMessage());
}
?>
