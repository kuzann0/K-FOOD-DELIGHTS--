<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

echo "<h2>Database Connection Test</h2>";

// Test database connection
try {
    $testConn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($testConn->connect_error) {
        throw new Exception("Connection failed: " . $testConn->connect_error);
    }
    echo "✅ Database connection successful<br>";

    // Check if admin_users table exists
    $result = $testConn->query("SHOW TABLES LIKE 'admin_users'");
    if ($result->num_rows > 0) {
        echo "✅ admin_users table exists<br>";
    } else {
        echo "❌ admin_users table does not exist<br>";
    }

    // Check if admin user exists
    $stmt = $testConn->prepare("SELECT admin_id, username, password, is_active, role_id FROM admin_users WHERE username = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $testConn->error);
    }

    $username = 'admin';
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo "✅ Admin user found:<br>";
        echo "- Username: " . htmlspecialchars($row['username']) . "<br>";
        echo "- Active: " . ($row['is_active'] ? 'Yes' : 'No') . "<br>";
        echo "- Role ID: " . $row['role_id'] . "<br>";
        
        // Test password verification
        $testPassword = 'admin123';
        if (password_verify($testPassword, $row['password'])) {
            echo "✅ Password verification successful<br>";
        } else {
            echo "❌ Password verification failed<br>";
            echo "Note: This means the stored password hash doesn't match 'admin123'<br>";
        }
    } else {
        echo "❌ Admin user not found<br>";
    }

    // Check admin_roles table and role assignment
    $result = $testConn->query("SHOW TABLES LIKE 'admin_roles'");
    if ($result->num_rows > 0) {
        echo "✅ admin_roles table exists<br>";
        
        // Check if Super Admin role exists
        $result = $testConn->query("SELECT role_id, role_name FROM admin_roles WHERE role_name = 'Super Admin'");
        if ($result->num_rows > 0) {
            $role = $result->fetch_assoc();
            echo "✅ Super Admin role found (ID: {$role['role_id']})<br>";
        } else {
            echo "❌ Super Admin role not found<br>";
        }
    } else {
        echo "❌ admin_roles table does not exist<br>";
    }

} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// Add a link to reset everything
echo "<br><hr><br>";
echo "<a href='setup.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Run Setup Again</a>";
?>
