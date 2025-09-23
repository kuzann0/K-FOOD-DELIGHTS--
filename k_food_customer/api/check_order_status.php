<?php
require_once '../config.php';
require_once '../includes/auth.php';

// Ensure user is authenticated
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Get order ID from request
$orderId = $_GET['orderId'] ?? null;
if (!$orderId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Order ID is required'
    ]);
    exit;
}

try {
    // Initialize database connection
    $conn = initDatabaseConnection();
    
    // Fetch order status
    $query = "
        SELECT 
            o.order_id,
            o.order_status,
            o.delivery_date,
            o.updated_at
        FROM orders o
        WHERE o.order_id = ?
    ";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $orderId);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
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
    
    $stmt->close();
    $conn->close();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'status' => $order['order_status'],
        'deliveryDate' => $order['delivery_date'],
        'lastUpdate' => $order['updated_at'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log("Error checking order status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to check order status',
        'error' => $e->getMessage()
    ]);
}