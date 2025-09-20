<?php
require_once __DIR__ . '/config.php';

// Authentication functions
function isAdmin() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] === 1; // Assuming role_id 1 is admin
}

function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit();
}

function loginAdmin($username, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT admin_id, username, password, role_id FROM admin_users WHERE username = ? AND is_active = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // Update last login
            $updateStmt = $conn->prepare("UPDATE admin_users SET last_login = CURRENT_TIMESTAMP WHERE admin_id = ?");
            $updateStmt->bind_param("i", $user['admin_id']);
            $updateStmt->execute();
            
            // Set session variables
            $_SESSION['admin_id'] = $user['admin_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['last_activity'] = time();
            
            return true;
        }
    }
    
    return false;
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        logout();
        header('Location: ' . BASE_URL . '/login.php?timeout=1');
        exit();
    }
    
    $_SESSION['last_activity'] = time();
}

function logout() {
    session_unset();
    session_destroy();
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
}

function hasPermission($permission) {
    global $conn;
    
    if (!isset($_SESSION['role_id'])) {
        return false;
    }
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as has_permission
        FROM role_permissions rp
        JOIN permissions p ON rp.permission_id = p.permission_id
        WHERE rp.role_id = ? AND p.permission_name = ?
    ");
    
    $stmt->bind_param("is", $_SESSION['role_id'], $permission);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['has_permission'] > 0;
}

function getCurrentAdmin() {
    global $conn;
    
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    $stmt = $conn->prepare("
        SELECT au.*, ar.role_name
        FROM admin_users au
        JOIN admin_roles ar ON au.role_id = ar.role_id
        WHERE au.admin_id = ?
    ");
    
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function logAuditTrail($actionType, $tableName, $recordId, $changes) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO audit_log (admin_id, action_type, table_name, record_id, changes, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $adminId = $_SESSION['admin_id'] ?? null;
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    
    $stmt->bind_param("ississ", $adminId, $actionType, $tableName, $recordId, $changes, $ipAddress);
    $stmt->execute();
}
