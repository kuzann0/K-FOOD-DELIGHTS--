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

    // Query for new notifications
    $stmt = $conn->prepare("
        SELECT n.id, n.type, n.title, n.message, n.created_at, n.read_at,
               CASE WHEN n.type = 'order' THEN o.order_number ELSE NULL END as order_number
        FROM notifications n
        LEFT JOIN orders o ON n.reference_id = o.id AND n.type = 'order'
        WHERE n.created_at > ?
        AND n.user_role = 'admin'
        ORDER BY n.created_at DESC
        LIMIT 50
    ");
    
    $stmt->bind_param('s', $since);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['id'],
            'type' => $row['type'],
            'title' => $row['title'],
            'message' => $row['message'],
            'orderNumber' => $row['order_number'],
            'createdAt' => $row['created_at'],
            'isRead' => !is_null($row['read_at'])
        ];
    }

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log("Error fetching admin notifications: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch notifications'
    ]);
}