<?php
require_once 'config.php';

// Array of SQL statements to update the users table
$sql_updates = [
    // Add role_id column if it doesn't exist
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS role_id INT DEFAULT 1 COMMENT 'Default 1=customer, 2=admin, 3=crew'",
    
    // Add account_status column if it doesn't exist
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS account_status VARCHAR(20) DEFAULT 'active' 
     COMMENT 'Values: active, inactive, suspended'",
    
    // Add login_attempts column if it doesn't exist
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS login_attempts INT DEFAULT 0",
    
    // Add last_login column if it doesn't exist (only if it wasn't already added)
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME NULL"
];

$success = true;
$messages = [];

// Execute each SQL statement
foreach ($sql_updates as $sql) {
    try {
        if ($conn->query($sql)) {
            $messages[] = "Success: " . $sql;
        } else {
            $success = false;
            $messages[] = "Error: " . $conn->error;
        }
    } catch (Exception $e) {
        $success = false;
        $messages[] = "Error: " . $e->getMessage();
    }
}

// Set all existing users to 'active' status if they don't have a status
$conn->query("UPDATE users SET account_status = 'active' WHERE account_status IS NULL");

// Output results in a clean HTML format
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Update | K-Food Delight</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
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
    </style>
</head>
<body>
    <h1>Database Update Results</h1>
    
    <?php if ($success): ?>
        <div class="message success">
            <h3>✅ Database update completed successfully!</h3>
        </div>
    <?php else: ?>
        <div class="message error">
            <h3>❌ Some updates failed. Please check the messages below.</h3>
        </div>
    <?php endif; ?>

    <h2>Update Messages:</h2>
    <?php foreach ($messages as $message): ?>
        <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endforeach; ?>

    <div class="message note">
        <p><strong>Next Steps:</strong></p>
        <ol>
            <li>Return to the login page and try logging in again</li>
            <li>If you encounter any issues, please contact the system administrator</li>
        </ol>
    </div>

    <p><a href="login.php">Return to Login Page</a></p>
</body>
</html>