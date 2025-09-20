<?php
// Set secure session parameters
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
session_start();

/**
 * Check if the current user is authenticated as a crew member
 * @return bool True if user is authenticated as crew, false otherwise
 */
function isCrewMember() {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
        return false;
    }

    // Check if user has crew role (role_id = 3)
    return $_SESSION['role_id'] === 3;
}

/**
 * Validate crew session and redirect if not authorized
 * @return void
 */
function validateCrewSession() {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
        header('Location: ../k_food_customer/login.php');
        exit();
    }

    // Check if user has crew role (role_id = 3)
    if ($_SESSION['role_id'] != 3) {
        header('Location: ../k_food_customer/unauthorized.php');
        exit();
    }

    // Check session timeout (30 minutes)
    $timeout = 1800; // 30 minutes in seconds
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        // Session has expired
        session_unset();
        session_destroy();
        header('Location: ../k_food_customer/login.php?error=session_expired');
        exit();
    }

    // Update last activity time stamp
    $_SESSION['last_activity'] = time();
}

/**
 * Log crew member activity
 * @param mysqli $conn Database connection
 * @param string $action Action performed
 * @param string $details Additional details about the action
 * @return bool True if logged successfully, false otherwise
 */
function logCrewActivity($conn, $action, $details = null) {
    if (!isCrewMember()) {
        return false;
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO crew_activity_log (
                user_id,
                action,
                details,
                ip_address,
                created_at
            ) VALUES (?, ?, ?, ?, NOW())
        ");

        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $stmt->bind_param("isss", 
            $_SESSION['user_id'],
            $action,
            $details,
            $ipAddress
        );

        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error logging crew activity: " . $e->getMessage());
        return false;
    }
}