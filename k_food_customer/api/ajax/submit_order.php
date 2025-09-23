<?php
require_once '../../config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Verify user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Initialize database connection
$conn = initDatabaseConnection();

// Handle POST request for new order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get order data from request body
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            throw new Exception('Invalid request data');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        // Insert order
        $stmt = $conn->prepare("
            INSERT INTO orders (user_id, total_amount, status, created_at)
            VALUES (?, ?, 'pending', NOW())
        ");
        
        $userId = $_SESSION['user_id'];
        $stmt->bind_param('id', $userId, $data['total']);
        $stmt->execute();
        
        $orderId = $conn->insert_id;
        
        // Insert order items
        $stmt = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($data['items'] as $item) {
            $stmt->bind_param('iidd', 
                $orderId,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            );
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Return success response with order details
        echo json_encode([
            'success' => true,
            'order' => [
                'id' => $orderId,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'total' => $data['total'],
                'items' => $data['items']
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to create order',
            'message' => $e->getMessage()
        ]);
    }
}

// Close database connection
$conn->close();