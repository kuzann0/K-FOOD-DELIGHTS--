<?php
require_once '../includes/ajax-handler.php';
require_once '../includes/database.php';

// Get order status
function getOrderStatus($orderId) {
    global $conn;
    
    if (!RequestValidator::validateOrderId($orderId)) {
        return AjaxResponse::error('Invalid order ID');
    }

    $sql = "SELECT o.*, os.name as status_name, os.description as status_description 
            FROM orders o 
            JOIN order_status os ON o.status_id = os.id 
            WHERE o.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $orderId);
    
    if (!$stmt->execute()) {
        return AjaxResponse::error('Failed to fetch order status');
    }
    
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        return AjaxResponse::error('Order not found', 404);
    }
    
    return AjaxResponse::success($order);
}

// Get all active orders for crew
function getActiveOrders() {
    global $conn;
    
    $sql = "SELECT o.*, c.name as customer_name, os.name as status_name 
            FROM orders o 
            JOIN customers c ON o.customer_id = c.id 
            JOIN order_status os ON o.status_id = os.id 
            WHERE o.status_id NOT IN (SELECT id FROM order_status WHERE name IN ('completed', 'cancelled'))
            ORDER BY o.created_at DESC";
            
    $result = $conn->query($sql);
    
    if (!$result) {
        return AjaxResponse::error('Failed to fetch active orders');
    }
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    return AjaxResponse::success($orders);
}

// Handle request
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_order_status':
        $orderId = $_GET['order_id'] ?? null;
        getOrderStatus($orderId);
        break;
        
    case 'get_active_orders':
        // Verify crew role
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'crew') {
            AjaxResponse::error('Unauthorized', 401);
        }
        getActiveOrders();
        break;
        
    default:
        AjaxResponse::error('Invalid action', 400);
}