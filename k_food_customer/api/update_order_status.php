<?php
require_once '../config.php';
require_once '../includes/auth.php';

// Ensure only crew members can update order status
if (!isAuthenticated() || !hasRole('crew')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['orderId'] ?? null;
$status = $data['status'] ?? null;

// Validate input
if (!$orderId || !$status) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Order ID and status are required'
    ]);
    exit;
}

// Validate status value
$validStatuses = ['Pending', 'Processing', 'Out for Delivery', 'Delivered', 'Cancelled'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status value'
    ]);
    exit;
}

try {
    // Initialize database connection
    $conn = initDatabaseConnection();
    
    // Begin transaction
    $conn->begin_transaction();
    
    // Update order status
    $query = "
        UPDATE orders 
        SET 
            order_status = ?,
            delivery_date = CASE 
                WHEN ? = 'Delivered' THEN NOW()
                ELSE delivery_date
            END,
            updated_at = NOW()
        WHERE order_id = ?
    ";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . $conn->error);
    }
    
    $stmt->bind_param("ssi", $status, $status, $orderId);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update order: " . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Order not found or no changes made");
    }
    
    // Log status change
    $logQuery = "
        INSERT INTO order_status_history (
            order_id, status, changed_by, changed_at
        ) VALUES (?, ?, ?, NOW())
    ";
    
    $logStmt = $conn->prepare($logQuery);
    if (!$logStmt) {
        throw new Exception("Failed to prepare log query: " . $conn->error);
    }
    
    $userId = $_SESSION['user_id'];
    $logStmt->bind_param("isi", $orderId, $status, $userId);
    if (!$logStmt->execute()) {
        throw new Exception("Failed to log status change: " . $logStmt->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    // Clean up
    $logStmt->close();
    $stmt->close();
    $conn->close();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully',
        'orderId' => $orderId,
        'status' => $status,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    // Rollback on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    error_log("Error updating order status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update order status',
        'error' => $e->getMessage()
    ]);
}