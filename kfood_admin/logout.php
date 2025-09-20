<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth functions
require_once 'includes/auth.php';

// Clear all session data
$_SESSION = array();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page with a logged out message
header('Location:../k_food_customer/index.php');
exit();
?>