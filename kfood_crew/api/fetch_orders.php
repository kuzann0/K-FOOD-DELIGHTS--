<?php
require_once '../k_food_customer/config.php';
require_once 'includes/crew_auth.php';

header('Content-Type: application/json');

// Validate crew session
validateCrewSession();

try {
    // Get filters from request
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : 'today';
    $lastTimestamp = isset($_GET['since']) ? $_GET['since'] : null;
    
    // Base query
    $sql = "SELECT o.*, 
            u.first_name, u.last_name, u.email,
            GROUP_CONCAT(oi.product_name, ' (', oi.quantity, ')' SEPARATOR ', ') as items,
            COUNT(oi.order_item_id) as item_count,
            SUM(oi.subtotal) as items_total
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id
            LEFT JOIN order_items oi ON o.order_id = oi.order_id
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    // Add status filter
    if ($status) {
        $sql .= " AND o.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    // Add timeframe filter
    switch ($timeframe) {
        case 'today':
            $sql .= " AND DATE(o.created_at) = CURDATE()";
            break;
        case 'week':
            $sql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $sql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
    }

    // Add real-time filter if since parameter is provided
    if ($lastTimestamp) {
        $sql .= " AND o.created_at > ?";
        $params[] = $lastTimestamp;
        $types .= "s";
    }
    
    // Group and order
    $sql .= " GROUP BY o.order_id ORDER BY o.created_at DESC";
    
    // Prepare and execute
    $stmt = $conn->prepare($sql);
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        throw new Exception("Database query failed: " . $conn->error);
    }
    
    $result = $stmt->get_result();
    $orders = [];
    $currentTimestamp = date('Y-m-d H:i:s');
    
    while ($row = $result->fetch_assoc()) {
        // Format order data
        $order = [
            'order_id' => $row['order_id'],
            'order_number' => $row['order_number'],
            'customer_name' => $row['first_name'] . ' ' . $row['last_name'],
            'customer_email' => $row['email'],
            'contact_number' => $row['contact_number'],
            'delivery_address' => $row['delivery_address'],
            'special_instructions' => $row['special_instructions'],
            'total_amount' => number_format($row['total_amount'], 2),
            'status' => $row['status'],
            'payment_status' => $row['payment_status'],
            'created_at' => date('M d, Y h:i A', strtotime($row['created_at'])),
            'raw_timestamp' => $row['created_at'], // For client-side comparisons
            'items' => $row['items'],
            'item_count' => $row['item_count']
        ];
        
        $orders[] = $order;
    }

    // Get order statistics
    $statsQuery = "SELECT 
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_count,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count
    FROM orders 
    WHERE DATE(created_at) = CURDATE()";

    $statsResult = $conn->query($statsQuery);
    $stats = $statsResult ? $statsResult->fetch_assoc() : null;
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'metadata' => [
            'total' => count($orders),
            'status' => $status,
            'timeframe' => $timeframe,
            'lastTimestamp' => $currentTimestamp,
            'stats' => $stats
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in fetch_orders.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch orders: ' . $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}