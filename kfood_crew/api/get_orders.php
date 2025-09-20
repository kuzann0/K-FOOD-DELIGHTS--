<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Ensure only crew members can access this endpoint
if (!isCrewMember()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied'
    ]);
    exit;
}

try {
    $since = isset($_GET['since']) ? $_GET['since'] : null;
    $query = "
        SELECT 
            o.order_id,
            o.customer_id,
            o.status,
            o.total_amount,
            o.special_instructions,
            o.created_at,
            u.first_name,
            u.last_name,
            u.phone,
            u.address
        FROM orders o
        JOIN users u ON o.customer_id = u.user_id
        WHERE o.status != 'completed'
    ";

    if ($since) {
        $query .= " AND o.created_at > ?";
    }

    $query .= " ORDER BY o.created_at DESC";
    
    $stmt = $conn->prepare($query);
    
    if ($since) {
        $stmt->bind_param("s", $since);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($order = $result->fetch_assoc()) {
        // Get order items
        $itemsStmt = $conn->prepare("
            SELECT 
                oi.quantity,
                oi.unit_price,
                p.name,
                p.product_id
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ");
        
        $itemsStmt->bind_param("i", $order['order_id']);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        
        $items = [];
        while ($item = $itemsResult->fetch_assoc()) {
            $items[] = [
                'product_id' => $item['product_id'],
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'price' => $item['unit_price']
            ];
        }
        
        $orders[] = [
            'order_id' => $order['order_id'],
            'status' => $order['status'],
            'total' => $order['total_amount'],
            'created_at' => $order['created_at'],
            'customer_name' => $order['first_name'] . ' ' . $order['last_name'],
            'customer_phone' => $order['phone'],
            'delivery_address' => $order['address'],
            'special_instructions' => $order['special_instructions'],
            'items' => $items
        ];
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching orders: ' . $e->getMessage()
    ]);
}