<?php
require_once '../../config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Verify admin is logged in
if (!isAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Initialize database connection
$conn = initDatabaseConnection();

try {
    // Get parameters
    $lastCheck = isset($_GET['since']) ? $_GET['since'] : date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // Build query based on parameters
    $query = "
        SELECT o.*, 
               u.first_name, u.last_name, u.phone,
               c.first_name as crew_first_name,
               c.last_name as crew_last_name,
               GROUP_CONCAT(
                   CONCAT(oi.quantity, 'x ', p.name)
                   SEPARATOR ', '
               ) as items
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        LEFT JOIN users c ON o.updated_by = c.user_id
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        WHERE (o.created_at > ? OR o.updated_at > ?)
    ";
    
    $params = [$lastCheck, $lastCheck];
    $types = 'ss';
    
    if ($status) {
        $query .= " AND o.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    $query .= "
        GROUP BY o.order_id
        ORDER BY o.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    $statistics = [
        'total' => 0,
        'pending' => 0,
        'processing' => 0,
        'ready' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'revenue' => 0
    ];
    
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
            'crew' => $row['crew_first_name'] ? [
                'name' => $row['crew_first_name'] . ' ' . $row['crew_last_name']
            ] : null,
            'total' => $row['total_amount'],
            'items' => $row['items']
        ];
        
        $statistics['total']++;
        $statistics[$row['status']]++;
        if ($row['status'] === 'completed') {
            $statistics['revenue'] += $row['total_amount'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'statistics' => $statistics,
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