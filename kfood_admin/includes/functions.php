<?php
// Core functions for K-Food Delights Admin System

function getSetting($key) {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return null;
}

function updateSetting($key, $value, $adminId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ?, updated_by = ? WHERE setting_key = ?");
    $stmt->bind_param("sis", $value, $adminId, $key);
    return $stmt->execute();
}

function hasPermission($moduleName, $permission = 'view') {
    global $conn;
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role_id'])) {
        return false;
    }

    $permissionColumn = 'can_' . strtolower($permission);
    $stmt = $conn->prepare("
        SELECT $permissionColumn 
        FROM module_permissions 
        WHERE role_id = ? AND module_name = ?
    ");
    $stmt->bind_param("is", $_SESSION['role_id'], $moduleName);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return (bool)$row[$permissionColumn];
    }
    return false;
}

function getCurrentAdmin() {
    global $conn;
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }

    $stmt = $conn->prepare("
        SELECT au.*, ar.role_name, ar.is_super_admin
        FROM admin_users au
        JOIN admin_roles ar ON au.role_id = ar.role_id
        WHERE au.admin_id = ?
    ");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function formatCurrency($amount) {
    $symbol = getSetting('currency_symbol') ?? '₱';
    return $symbol . ' ' . number_format($amount, 2);
}

function createAlert($type, $message, $expiresAt = null) {
    global $conn;
    $stmt = $conn->prepare("
        INSERT INTO system_alerts (alert_type, message, expires_at)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("sss", $type, $message, $expiresAt);
    return $stmt->execute();
}

function getRecentAlerts($limit = 5) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT * FROM system_alerts
        WHERE (expires_at IS NULL OR expires_at > NOW())
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function checkLowStock() {
    global $conn;
    $threshold = getSetting('low_stock_threshold') ?? 10;
    $stmt = $conn->prepare("
        SELECT *
        FROM inventory_items
        WHERE quantity <= minimum_stock
        OR quantity <= ?
    ");
    $stmt->bind_param("i", $threshold);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getTodayStats() {
    global $conn;
    $today = date('Y-m-d');
    
    // Get orders count and revenue
    $stmt = $conn->prepare("
        SELECT COUNT(*) as order_count,
               SUM(total_amount) as revenue
        FROM orders
        WHERE DATE(order_date) = ?
        AND order_status != 'cancelled'
    ");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $orderStats = $stmt->get_result()->fetch_assoc();
    
    // Get active users count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as user_count
        FROM admin_users
        WHERE is_active = 1
    ");
    $stmt->execute();
    $userStats = $stmt->get_result()->fetch_assoc();
    
    return [
        'orders' => $orderStats['order_count'] ?? 0,
        'revenue' => $orderStats['revenue'] ?? 0,
        'active_users' => $userStats['user_count'] ?? 0,
        'low_stock' => count(checkLowStock())
    ];
}

function getPopularItems($limit = 5) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT p.*, 
               COUNT(oi.order_item_id) as order_count,
               SUM(oi.quantity) as total_quantity
        FROM products p
        JOIN order_items oi ON p.product_id = oi.product_id
        JOIN orders o ON oi.order_id = o.order_id
        WHERE o.order_status = 'completed'
        GROUP BY p.product_id
        ORDER BY total_quantity DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getSalesTrend($days = 7) {
    global $conn;
    $data = [];
    $stmt = $conn->prepare("
        SELECT DATE(order_date) as date,
               COUNT(*) as order_count,
               SUM(total_amount) as daily_revenue
        FROM orders
        WHERE order_date >= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY)
        AND order_status = 'completed'
        GROUP BY DATE(order_date)
        ORDER BY date
    ");
    $stmt->bind_param("i", $days);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function sanitizeOutput($data) {
    if (is_array($data)) {
        return array_map('sanitizeOutput', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function isSystemInMaintenance() {
    return getSetting('maintenance_mode') == '1';
}

function generateReport($type, $startDate, $endDate) {
    global $conn;
    
    switch ($type) {
        case 'sales':
            $query = "
                SELECT o.order_id, o.order_date, o.total_amount,
                       GROUP_CONCAT(p.product_name) as products
                FROM orders o
                JOIN order_items oi ON o.order_id = oi.order_id
                JOIN products p ON oi.product_id = p.product_id
                WHERE DATE(o.order_date) BETWEEN ? AND ?
                AND o.order_status = 'completed'
                GROUP BY o.order_id
                ORDER BY o.order_date DESC
            ";
            break;
            
        case 'inventory':
            $query = "
                SELECT i.*, 
                       SUM(CASE WHEN it.transaction_type = 'in' THEN it.quantity ELSE -it.quantity END) as stock_movement
                FROM inventory_items i
                LEFT JOIN inventory_transactions it ON i.item_id = it.item_id
                WHERE DATE(it.transaction_date) BETWEEN ? AND ?
                GROUP BY i.item_id
            ";
            break;
            
        case 'expiration':
            $query = "
                SELECT *
                FROM inventory_items
                WHERE expiration_date BETWEEN ? AND ?
                ORDER BY expiration_date
            ";
            break;
            
        default:
            return null;
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to log important actions
function logAction($action, $details) {
    global $conn;
    $adminId = $_SESSION['admin_id'] ?? null;
    
    $stmt = $conn->prepare("
        INSERT INTO audit_trail (admin_id, action_type, details, ip_address)
        VALUES (?, ?, ?, ?)
    ");
    
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("isss", $adminId, $action, $details, $ipAddress);
    return $stmt->execute();
}
?>
