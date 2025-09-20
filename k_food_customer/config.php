<?php
// Debug Mode Configuration
define('DEBUG_MODE', true); // Set to false in production

// Database Configuration
$host = "localhost";
$user = "root"; // change if your phpMyAdmin user is different
$password = ""; // default is empty for XAMPP
$dbname = "k_food_delights"; // replace with your actual DB name

// SMS Gateway Configuration (Semaphore)
define('SEMAPHORE_API_KEY', 'your_api_key_here'); // Replace with your actual Semaphore API key
define('SMS_SENDER_NAME', 'KFOOD'); // Your registered sender name in Semaphore
define('SMS_OTP_EXPIRY', 300); // OTP expiry in seconds (5 minutes)
define('SMS_MAX_ATTEMPTS', 3); // Maximum verification attempts

$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
