<?php
header('Content-Type: application/json');
require_once '../config.php';

try {
    // Get the latest order
    $query = "
        SELECT 
            o.id,
            o.order_number,
            o.status,
            o.total_amount,
            o.created_at,
            c.name as customer_name,
            c.email,
            c.phone,
            c.address,
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
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 1
    ";
    
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        // Process items string into array
        $items = [];
        if ($row['items']) {
            $itemsStr = explode(',', $row['items']);
            foreach ($itemsStr as $item) {
                list($name, $quantity, $price) = explode(':', $item);
                $items[] = [
                    'name' => $name,
                    'quantity' => (int)$quantity,
                    'price' => (float)$price
                ];
            }
        }
        
        $order = [
            'id' => $row['id'],
            'orderNumber' => $row['order_number'],
            'status' => $row['status'],
            'createdAt' => $row['created_at'],
            'totalAmount' => (float)$row['total_amount'],
            'customer' => [
                'name' => $row['customer_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'address' => $row['address']
            ],
            'items' => $items
        ];
        
        echo json_encode([
            'success' => true,
            'order' => $order
        ]);
    } else {
        throw new Exception("No orders found");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}