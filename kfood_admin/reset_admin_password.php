<?php
require_once 'includes/config.php';

// This is a one-time use script to reset the admin password
// Delete this file after using it

$newPassword = 'Admin@123'; // You can change this password
$username = 'admin';

$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
$stmt->bind_param("ss", $hashedPassword, $username);

if ($stmt->execute()) {
    echo "Password successfully reset!<br>";
    echo "Username: admin<br>";
    echo "New password: " . $newPassword . "<br>";
    echo "Please delete this file after use for security reasons.";
} else {
    echo "Error resetting password: " . $conn->error;
}
?>
