<?php
// Set secure session parameters
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.gc_maxlifetime', 1800); // 30 minutes
session_start();

/**
 * Check if the current user is authenticated as an admin
 * @return bool True if user is authenticated as admin, false otherwise
 */
function isAdminUser() {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
        return false;
    }

    // Check if user has admin role (role_id = 1)
    return $_SESSION['role_id'] === 1;
}

/**
 * Validate admin session and redirect if not authorized
 * @return void
 */
function validateAdminSession() {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
        // Store the requested URL for redirect after login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: ../k_food_customer/login.php');
        exit();
    }

    // Check if user has admin role (role_id = 1)
    if ($_SESSION['role_id'] !== 1) {
        header('Location: ../k_food_customer/unauthorized.php');
        exit();
    }

    // Check session timeout (30 minutes)
    $timeout = 1800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        // Session has expired
        logoutAdmin();
        header('Location: ../k_food_customer/login.php?error=session_expired');
        exit();
    }

    // Update last activity time stamp
    $_SESSION['last_activity'] = time();

    // Regenerate session ID periodically to prevent fixation
    if (!isset($_SESSION['id_generated_at']) || time() - $_SESSION['id_generated_at'] > 300) {
        session_regenerate_id(true);
        $_SESSION['id_generated_at'] = time();
    }
}

/**
 * Get admin details from database
 * @param mysqli $conn Database connection
 * @return array|null Admin details array or null if not found
 */
function getAdminDetails($conn) {
    if (!isAdminUser()) {
        return null;
    }
    
    $stmt = $conn->prepare("
        SELECT 
            user_id,
            username,
            first_name,
            last_name,
            email,
            phone,
            last_login,
            is_active
        FROM users 
        WHERE user_id = ? AND role_id = 1
        LIMIT 1
    ");
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    
    if (!$stmt->execute()) {
        error_log("Error fetching admin details: " . $stmt->error);
        return null;
    }
    
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();
    
    return $admin;
}

/**
 * Log out the admin user
 * @return void
 */
function logoutAdmin() {
    // Clear all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy the session
    session_destroy();
}

/**
 * Log admin activity
 * @param mysqli $conn Database connection
 * @param string $action Action performed
 * @param string|null $details Additional details about the action
 * @return bool True if logged successfully, false otherwise
 */
function logAdminActivity($conn, $action, $details = null) {
    if (!isAdminUser()) {
        return false;
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO activity_log (
                user_id,
                action,
                details,
                ip_address,
                user_agent,
                created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        
        $stmt->bind_param("issss", 
            $_SESSION['user_id'],
            $action,
            $details,
            $ipAddress,
            $userAgent
        );

        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Error logging admin activity: " . $e->getMessage());
        return false;
    }
}
?>