<?php
require_once '../includes/ajax-handler.php';
require_once '../includes/database.php';

// Get kitchen status
function getKitchenStatus() {
    global $conn;
    
    // Get pending orders count
    $sql = "SELECT COUNT(*) as pending_count 
            FROM orders 
            WHERE status_id = (SELECT id FROM order_status WHERE name = 'pending')";
    
    $pendingResult = $conn->query($sql);
    if (!$pendingResult) {
        return AjaxResponse::error('Failed to fetch pending orders');
    }
    
    $pendingCount = $pendingResult->fetch_assoc()['pending_count'];
    
    // Get in-progress orders
    $sql = "SELECT o.*, c.name as customer_name, os.name as status_name 
            FROM orders o 
            JOIN customers c ON o.customer_id = c.id 
            JOIN order_status os ON o.status_id = os.id 
            WHERE os.name = 'preparing'
            ORDER BY o.created_at ASC";
            
    $inProgressResult = $conn->query($sql);
    if (!$inProgressResult) {
        return AjaxResponse::error('Failed to fetch in-progress orders');
    }
    
    $inProgressOrders = [];
    while ($row = $inProgressResult->fetch_assoc()) {
        $inProgressOrders[] = $row;
    }
    
    // Get kitchen staff status
    $sql = "SELECT u.id, u.name, u.status 
            FROM users u 
            JOIN user_roles ur ON u.id = ur.user_id 
            WHERE ur.role = 'kitchen'
            AND u.status = 'active'";
            
    $staffResult = $conn->query($sql);
    if (!$staffResult) {
        return AjaxResponse::error('Failed to fetch kitchen staff status');
    }
    
    $staff = [];
    while ($row = $staffResult->fetch_assoc()) {
        $staff[] = $row;
    }
    
    return AjaxResponse::success([
        'pending_orders' => $pendingCount,
        'in_progress_orders' => $inProgressOrders,
        'staff' => $staff
    ]);
}

// Handle request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    AjaxResponse::error('Method not allowed', 405);
}

// Verify crew role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'crew') {
    AjaxResponse::error('Unauthorized', 401);
}

getKitchenStatus();