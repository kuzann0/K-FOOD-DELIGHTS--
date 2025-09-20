<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store any messages before clearing session
$flash_message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : null;

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Clear any other specific cookies
setcookie('remember_me', '', time() - 3600, '/');
setcookie('user_preferences', '', time() - 3600, '/');

// Destroy the current session
session_destroy();

// Start a new session for the message
session_start();

// Set success message
$_SESSION['flash_message'] = 'You have been successfully logged out.';

// Redirect to login page
header('Location: index.php');
exit();
?>