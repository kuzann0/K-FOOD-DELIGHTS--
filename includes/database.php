<?php
/**
 * Shared database configuration for K-Food Delights
 * This file should be included by all modules
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'k_food_delights');
define('DB_CHARSET', 'utf8mb4');
define('DB_CONNECT_TIMEOUT', 5);
define('DB_RETRY_ATTEMPTS', 3);
define('DB_MAX_CONNECTIONS', 100);
define('DB_IDLE_TIMEOUT', 300); // 5 minutes
define('DB_ERROR_LOG_PATH', __DIR__ . '/../logs/database_errors.log');

// Connection initialization function
function initDatabaseConnection() {
    $attempts = 0;
    while ($attempts < DB_RETRY_ATTEMPTS) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if (!$conn->connect_error) {
                // Set character set and timezone
                $conn->set_charset(DB_CHARSET);
                $conn->query("SET time_zone = '+08:00'");
                return $conn;
            }
            
            $attempts++;
            if ($attempts < DB_RETRY_ATTEMPTS) {
                sleep(1);
            }
        } catch (Exception $e) {
            error_log("Database connection attempt {$attempts} failed: " . $e->getMessage());
            $attempts++;
            if ($attempts >= DB_RETRY_ATTEMPTS) {
                throw $e;
            }
            sleep(1);
        }
    }
    
    throw new Exception("Failed to connect to database after {$attempts} attempts");
}

// Sanitization helper
function sanitizeInput($data, $conn = null) {
    if (!$conn) {
        $conn = initDatabaseConnection();
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Validation helpers
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidPassword($password) {
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[0-9]/', $password);
}

// Error logging helper
function logError($message, $context = [], $severity = 'ERROR') {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[$timestamp] [$severity] $message $contextStr\n";
    error_log($logMessage, 3, __DIR__ . '/../logs/error.log');
}