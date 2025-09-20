<?php
// Session configuration must be done before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
session_name('kfood_admin');
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => 'C:/xampp/htdocs/testing/kfood_admin',
    'domain' => '',
    'secure' => true,
    'httponly' => true
]);

// Now we can start the session
session_start();

// Include error handler
require_once __DIR__ . '/error_handler.php';

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'k_food_delights');

// Application configuration
define('SITE_NAME', 'K-Food Delights Admin');
define('BASE_URL', '/kfood/kfood_admin');
define('UPLOAD_PATH', __DIR__ . '/uploads');

// Initialize database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("System error. Please try again later.");
}

// Session is already initialized at the top of this file
