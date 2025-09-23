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

// Handle POST request for updating order status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get update data from request body
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['orderId']) || !isset($data['status'])) {
            throw new Exception('Invalid request data');
        }
        
        // Validate status
        $allowedStatuses = ['pending', 'processing', 'ready', 'completed', 'cancelled'];
        if (!in_array($data['status'], $allowedStatuses)) {
            throw new Exception('Invalid status');
        }
        
        // Update order status
        $stmt = $conn->prepare("
            UPDATE orders 
            SET status = ?, 
                updated_at = NOW(),
                updated_by = ?
            WHERE order_id = ?
        ");
        
        $crewId = $_SESSION['user_id'];
        $stmt->bind_param('sii', 
            $data['status'],
            $crewId,
            $data['orderId']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update order status');
        }
        
        if ($stmt->affected_rows === 0) {
            throw new Exception('Order not found');
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'order' => [
                'id' => $data['orderId'],
                'status' => $data['status'],
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to update order status',
            'message' => $e->getMessage()
        ]);
    }
}

// Close database connection
$conn->close();