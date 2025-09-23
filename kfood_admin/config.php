<?php
/**
 * Main configuration file for K-Food Admin Module
 */

// Session security configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
session_name('kfood_admin');
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true
]);

session_start();

// Include shared database configuration
require_once __DIR__ . '/../../includes/database.php';

// Module-specific configuration
define('MODULE_NAME', 'K-Food Admin');
define('BASE_URL', '/kfood/kfood_admin');
define('UPLOAD_PATH', __DIR__ . '/uploads');

// Initialize database connection
try {
    $conn = initDatabaseConnection();
} catch (Exception $e) {
    logError("Database initialization failed", ['error' => $e->getMessage()]);
    die("System error occurred. Please try again later.");
}

// Error handling
function handleError($errno, $errstr, $errfile, $errline) {
    $severity = match($errno) {
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_NOTICE => 'NOTICE',
        default => 'UNKNOWN'
    };
    
    logError($errstr, [
        'file' => $errfile,
        'line' => $errline,
        'severity' => $severity
    ]);
    
    if ($errno == E_ERROR) {
        die("A system error occurred. Please try again later.");
    }
    return true;
}

set_error_handler('handleError');