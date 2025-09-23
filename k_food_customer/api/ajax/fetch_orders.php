<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../../config.php';
require_once '../check_session.php';

// Verify AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Get the timestamp from query string
    $since = isset($_GET['since']) ? $_GET['since'] : date('Y-m-d H:i:s', strtotime('-1 hour'));

    // Query for orders and their items
    $stmt = $conn->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.status,
            o.created_at,
            o.total_amount,
            o.payment_method,
            o.delivery_instructions,
            c.name as customer_name,
            c.phone as customer_phone,
            c.address as customer_address,
            GROUP_CONCAT(
                CONCAT_WS(':', 
                    oi.item_name,
                    oi.quantity,
                    oi.price
                )
            ) as items
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.created_at > ? OR o.updated_at > ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    
    $stmt->bind_param('ss', $since, $since);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        // Process items string into array
        $items = [];
        $itemsStr = explode(',', $row['items']);
        foreach ($itemsStr as $item) {
            list($name, $quantity, $price) = explode(':', $item);
            $items[] = [
                'name' => $name,
                'quantity' => (int)$quantity,
                'price' => (float)$price
            ];
        }

        $orders[] = [
            'id' => $row['id'],
            'orderNumber' => $row['order_number'],
            'status' => $row['status'],
            'createdAt' => $row['created_at'],
            'totalAmount' => (float)$row['total_amount'],
            'paymentMethod' => $row['payment_method'],
            'customer' => [
                'name' => $row['customer_name'],
                'phone' => $row['customer_phone'],
                'address' => $row['customer_address']
            ],
            'items' => $items,
            'deliveryInstructions' => $row['delivery_instructions']
        ];
    }

    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch orders'
    ]);
}