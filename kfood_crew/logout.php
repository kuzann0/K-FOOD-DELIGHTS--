<?php
require_once 'includes/crew_auth.php';

// Clear all session data
session_unset();
session_destroy();

// Redirect to login page
header('Location: ../k_food_customer/login.php');
exit();