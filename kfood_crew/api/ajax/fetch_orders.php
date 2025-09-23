<?php
require_once '../../config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Verify crew member is logged in
if (!isCrewMember()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Initialize database connection
$conn = initDatabaseConnection();

try {
    // Get timestamp from last check
    $lastCheck = isset($_GET['since']) ? $_GET['since'] : date('Y-m-d H:i:s', strtotime('-5 minutes'));
    
    // Get new and updated orders
    $stmt = $conn->prepare("
        SELECT o.*, u.first_name, u.last_name, u.phone,
               GROUP_CONCAT(
                   CONCAT(oi.quantity, 'x ', p.name)
                   SEPARATOR ', '
               ) as items
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        WHERE (o.created_at > ? OR o.updated_at > ?)
        AND o.status IN ('pending', 'processing', 'ready')
        GROUP BY o.order_id
        ORDER BY 
            CASE o.status
                WHEN 'pending' THEN 1
                WHEN 'processing' THEN 2
                WHEN 'ready' THEN 3
                ELSE 4
            END,
            o.created_at ASC
    ");
    
    $stmt->bind_param('ss', $lastCheck, $lastCheck);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = [
            'id' => $row['order_id'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'customer' => [
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'phone' => $row['phone']
            ],
            'total' => $row['total_amount'],
            'items' => $row['items']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'timestamp' => date('Y-m-d H:i:s')
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