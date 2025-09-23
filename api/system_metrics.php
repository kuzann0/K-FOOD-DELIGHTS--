<?php
require_once '../includes/ajax-handler.php';
require_once '../includes/database.php';

// Get system metrics
function getSystemMetrics() {
    global $conn;
    
    // Get orders metrics
    $sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status_id = (SELECT id FROM order_status WHERE name = 'completed') THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status_id = (SELECT id FROM order_status WHERE name = 'cancelled') THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(total_amount) as total_revenue,
                AVG(preparation_time) as avg_preparation_time
            FROM orders 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            
    $ordersResult = $conn->query($sql);
    if (!$ordersResult) {
        return AjaxResponse::error('Failed to fetch orders metrics');
    }
    
    $ordersMetrics = $ordersResult->fetch_assoc();
    
    // Get user metrics
    $sql = "SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as active_users
            FROM users";
            
    $usersResult = $conn->query($sql);
    if (!$usersResult) {
        return AjaxResponse::error('Failed to fetch user metrics');
    }
    
    $userMetrics = $usersResult->fetch_assoc();
    
    // Get system alerts
    $sql = "SELECT * FROM system_alerts 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND status = 'active'
            ORDER BY priority DESC, created_at DESC";
            
    $alertsResult = $conn->query($sql);
    if (!$alertsResult) {
        return AjaxResponse::error('Failed to fetch system alerts');
    }
    
    $alerts = [];
    while ($row = $alertsResult->fetch_assoc()) {
        $alerts[] = $row;
    }
    
    return AjaxResponse::success([
        'orders' => $ordersMetrics,
        'users' => $userMetrics,
        'alerts' => $alerts,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

// Handle request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    AjaxResponse::error('Method not allowed', 405);
}

// Verify admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    AjaxResponse::error('Unauthorized', 401);
}

getSystemMetrics();