<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/WebSocketClient.php';

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

// Get and validate JSON input
$rawInput = file_get_contents('php://input');
if (!$rawInput) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No input data provided'
    ]);
    exit;
}

$data = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data: ' . json_last_error_msg()
    ]);
    exit;
}

// Validate required parameters
if (!isset($data['order_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

// Validate status value
$validStatuses = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
if (!in_array($data['status'], $validStatuses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status value'
    ]);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Verify order exists and get current status
    $stmt = $conn->prepare("
        SELECT status, user_id 
        FROM orders 
        WHERE order_id = ? 
        LIMIT 1
    ");
    
    $stmt->bind_param("i", $data['order_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Order not found');
    }
    
    $orderInfo = $result->fetch_assoc();
    $stmt->close();

    // Update order status
    $stmt = $conn->prepare("
        UPDATE orders 
        SET 
            status = ?,
            updated_at = NOW(),
            updated_by = ?
        WHERE order_id = ?
    ");
    
    $crewId = $_SESSION['user_id'];
    $stmt->bind_param("sii", $data['status'], $crewId, $data['order_id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update order status: ' . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('No changes made to order');
    }
    
    $stmt->close();

    // Log the status change
    $stmt = $conn->prepare("
        INSERT INTO order_status_history (
            order_id,
            previous_status,
            new_status,
            notes,
            created_by,
            created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $notes = $data['notes'] ?? null;
    $stmt->bind_param("isssi", 
        $data['order_id'], 
        $orderInfo['status'],
        $data['status'],
        $notes,
        $crewId
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to log status change: ' . $stmt->error);
    }
    
    $stmt->close();

    // If status is 'completed', update completion details
    if ($data['status'] === 'completed') {
        $stmt = $conn->prepare("
            UPDATE orders 
            SET 
                completed_at = NOW(),
                completed_by = ?
            WHERE order_id = ?
        ");
        
        $stmt->bind_param("ii", $crewId, $data['order_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update completion details: ' . $stmt->error);
        }
        
        $stmt->close();
    }

    // Notify customer through WebSocket
    try {
        $wsClient = new WebSocketClient();
        $wsClient->send([
            'type' => 'order_update',
            'order' => [
                'order_id' => $data['order_id'],
                'status' => $data['status'],
                'updated_at' => date('Y-m-d H:i:s'),
                'user_id' => $orderInfo['user_id']
            ]
        ]);
    } catch (Exception $e) {
        // Log WebSocket error but don't fail the transaction
        error_log("WebSocket notification failed: " . $e->getMessage());
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully',
        'data' => [
            'order_id' => $data['order_id'],
            'status' => $data['status'],
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    // Rollback on error
    try {
        $conn->rollback();
    } catch (Exception $e) {
        // Transaction was not active
    }

    // Log the error
    error_log("Error in update_order_status.php: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating order status: ' . $e->getMessage()
    ]);
} finally {
    // Ensure all statements are closed
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
}