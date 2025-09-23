<?php
require_once '../../config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Verify user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Initialize database connection
$conn = initDatabaseConnection();

try {
    // Get orders for the current user
    $userId = $_SESSION['user_id'];
    
    // Handle specific order query
    if (isset($_GET['order_id'])) {
        $stmt = $conn->prepare("
            SELECT o.*, 
                   GROUP_CONCAT(
                       CONCAT(oi.quantity, 'x ', p.name)
                       SEPARATOR ', '
                   ) as items
            FROM orders o
            JOIN order_items oi ON o.order_id = oi.order_id
            JOIN products p ON oi.product_id = p.product_id
            WHERE o.order_id = ? AND o.user_id = ?
            GROUP BY o.order_id
        ");
        
        $orderId = intval($_GET['order_id']);
        $stmt->bind_param('ii', $orderId, $userId);
        
    // Handle recent orders query
    } else {
        $stmt = $conn->prepare("
            SELECT o.*, 
                   GROUP_CONCAT(
                       CONCAT(oi.quantity, 'x ', p.name)
                       SEPARATOR ', '
                   ) as items
            FROM orders o
            JOIN order_items oi ON o.order_id = oi.order_id
            JOIN products p ON oi.product_id = p.product_id
            WHERE o.user_id = ?
            AND o.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY o.order_id
            ORDER BY o.created_at DESC
        ");
        
        $stmt->bind_param('i', $userId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = [
            'id' => $row['order_id'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'total' => $row['total_amount'],
            'items' => $row['items']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch orders',
        'message' => $e->getMessage()
    ]);
}

// Close database connection
$conn->close();