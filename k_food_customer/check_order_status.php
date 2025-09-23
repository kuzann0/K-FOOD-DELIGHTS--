<?php
// Endpoint: check_order_status.php
// Purpose: Check the status of a specific order

require_once 'config.php';
header('Content-Type: application/json');

// Validate request
if (!isset($_GET['order_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Order ID is required'
    ]);
    exit;
}

$orderId = $_GET['order_id'];

try {
    // Get order status from database
    $sql = "SELECT o.*, u.name as customer_name, 
            GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as items
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.id = ?
            GROUP BY o.id";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement");
    }

    $stmt->bind_param("i", $orderId);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query");
    }

    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Order not found'
        ]);
        exit;
    }

    // Return order status and details
    echo json_encode([
        'success' => true,
        'status' => $order['status'],
        'order' => [
            'id' => $order['id'],
            'status' => $order['status'],
            'orderNumber' => $order['order_number'],
            'customerName' => $order['customer_name'],
            'items' => $order['items'],
            'total' => $order['total_amount'],
            'createdAt' => $order['created_at'],
            'updatedAt' => $order['updated_at']
        ]
    ]);

} catch (Exception $e) {
    error_log("Error checking order status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error checking order status'
    ]);
}