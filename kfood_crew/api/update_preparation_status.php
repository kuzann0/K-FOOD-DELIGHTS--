<?php
require_once '../includes/crew_auth.php';
require_once '../includes/WebSocketClient.php';
require_once '../k_food_customer/config.php';

header('Content-Type: application/json');

// Validate crew session
validateCrewSession();

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate required parameters
if (!isset($data['order_id']) || !isset($data['preparation_status']) || !isset($data['estimated_time'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Update preparation status
    $stmt = $conn->prepare("
        UPDATE orders 
        SET 
            preparation_status = ?,
            estimated_completion_time = ?,
            updated_at = NOW(),
            updated_by = ?
        WHERE order_id = ?
    ");
    
    $stmt->bind_param("ssii", 
        $data['preparation_status'],
        $data['estimated_time'],
        $_SESSION['user_id'],
        $data['order_id']
    );
    
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Order not found or no changes made');
    }

    // Log the preparation update
    $stmt = $conn->prepare("
        INSERT INTO order_preparation_history (
            order_id,
            status,
            estimated_time,
            notes,
            created_by
        ) VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("isssi",
        $data['order_id'],
        $data['preparation_status'],
        $data['estimated_time'],
        $data['notes'] ?? '',
        $_SESSION['user_id']
    );
    
    $stmt->execute();

    // Get order details for notification
    $stmt = $conn->prepare("
        SELECT o.*, u.first_name, u.last_name
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id = ?
    ");
    
    $stmt->bind_param("i", $data['order_id']);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    // Commit transaction
    $conn->commit();

    // Send WebSocket notification
    try {
        $wsClient = new WebSocketClient();
        $wsClient->send([
            'type' => 'preparation_update',
            'order' => [
                'order_id' => $order['order_id'],
                'order_number' => $order['order_number'],
                'preparation_status' => $data['preparation_status'],
                'estimated_completion_time' => $data['estimated_time'],
                'customer_name' => $order['first_name'] . ' ' . $order['last_name'],
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } catch (Exception $e) {
        // Log WebSocket error but don't fail the request
        error_log("WebSocket notification failed: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Order preparation status updated successfully',
        'order' => [
            'order_id' => $order['order_id'],
            'preparation_status' => $data['preparation_status'],
            'estimated_completion_time' => $data['estimated_time'],
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update preparation status: ' . $e->getMessage()
    ]);
}