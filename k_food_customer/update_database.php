<?php
require_once 'config.php';

// Read the SQL file
$sql = file_get_contents('sql/update_users_table.sql');

// Execute the SQL
if ($conn->multi_query($sql)) {
    echo "Users table updated successfully";
} else {
    echo "Error updating users table: " . $conn->error;
}

$conn->close();
?>
