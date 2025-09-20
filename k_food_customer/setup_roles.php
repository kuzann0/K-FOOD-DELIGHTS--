<?php
require_once 'config.php';

// Array of SQL statements to create and populate the admin_roles table
$sql_updates = [
    // Create admin_roles table if it doesn't exist
    "CREATE TABLE IF NOT EXISTS admin_roles (
        role_id INT PRIMARY KEY AUTO_INCREMENT,
        role_name VARCHAR(50) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    // Insert default roles if they don't exist
    "INSERT IGNORE INTO admin_roles (role_id, role_name, description) VALUES
    (4, 'Customer', 'Regular customer account with ordering privileges'),
    (2, 'Administrator', 'Full system administrative access'),
    (3, 'Crew', 'Restaurant staff member with order processing capabilities')"
];

// Execute each SQL statement
$success = true;
$messages = [];

foreach ($sql_updates as $sql) {
    try {
        if ($conn->query($sql)) {
            $messages[] = "Success: Role setup completed";
        } else {
            $success = false;
            $messages[] = "Error: " . $conn->error;
        }
    } catch (Exception $e) {
        // If the error is about duplicate entries, we can ignore it
        if (strpos($e->getMessage(), 'Duplicate entry') === false) {
            $success = false;
            $messages[] = "Error: " . $e->getMessage();
        }
    }
}

// Check if users table has role_id column and foreign key
$checkRoleColumn = $conn->query("
    SELECT COUNT(*) as exists_count 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'role_id'
");

if ($checkRoleColumn->fetch_assoc()['exists_count'] == 0) {
    // Add role_id column if it doesn't exist
    try {
        $conn->query("ALTER TABLE users ADD COLUMN role_id INT DEFAULT 1");
        $conn->query("ALTER TABLE users ADD CONSTRAINT fk_users_role 
                     FOREIGN KEY (role_id) REFERENCES admin_roles(role_id) 
                     ON DELETE CASCADE ON UPDATE CASCADE");
        $messages[] = "Success: Added role_id column to users table";
    } catch (Exception $e) {
        $messages[] = "Note: Role column setup - " . $e->getMessage();
    }
}

// Set default role for existing users if needed
try {
    $conn->query("UPDATE users SET role_id = 4 WHERE role_id IS NULL");
    $messages[] = "Success: Updated existing users with default role";
} catch (Exception $e) {
    $messages[] = "Note: Default role update - " . $e->getMessage();
}

// Output results in a clean HTML format
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Setup | K-Food Delight</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .note {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Role Setup Results</h1>
        
        <?php if ($success): ?>
            <div class="message success">
                <h3>✅ Role setup completed successfully!</h3>
            </div>
        <?php else: ?>
            <div class="message error">
                <h3>❌ Some updates failed. Please check the messages below.</h3>
            </div>
        <?php endif; ?>

        <h2>Update Messages:</h2>
        <?php foreach ($messages as $message): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : (strpos($message, 'Note') !== false ? 'note' : 'success'); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endforeach; ?>

        <div class="message note">
            <p><strong>Next Steps:</strong></p>
            <ol>
                <li>Return to the registration page</li>
                <li>Try creating a new account</li>
                <li>If you encounter any issues, please contact the system administrator</li>
            </ol>
        </div>

        <a href="register.php" class="back-link">← Return to Registration Page</a>
    </div>
</body>
</html>