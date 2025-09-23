<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../../config.php';
require_once '../check_session.php';

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }

    // Validate required data
    if (!isset($input['customer']) || !isset($input['order'])) {
        throw new Exception('Missing required order data');
    }

    // Start transaction
    $conn->begin_transaction();

    // Insert customer data
    $customerStmt = $conn->prepare("
        INSERT INTO customers (name, email, phone, address)
        VALUES (?, ?, ?, ?)
    ");
    $customerStmt->bind_param('ssss',
        $input['customer']['name'],
        $input['customer']['email'],
        $input['customer']['phone'],
        $input['customer']['address']
    );
    $customerStmt->execute();
    $customerId = $conn->insert_id;

    // Generate order number
    $orderNumber = 'KFD' . date('Ymd') . str_pad($customerId, 4, '0', STR_PAD_LEFT);

    // Insert order
    $orderStmt = $conn->prepare("
        INSERT INTO orders (order_number, customer_id, total_amount, payment_method, delivery_instructions, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $orderStmt->bind_param('sidss',
        $orderNumber,
        $customerId,
        $input['order']['amounts']['total'],
        $input['order']['payment']['method'],
        $input['order']['instructions']
    );
    $orderStmt->execute();
    $orderId = $conn->insert_id;

    // Insert order items
    $itemStmt = $conn->prepare("
        INSERT INTO order_items (order_id, item_name, quantity, price)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($input['order']['items'] as $item) {
        $itemStmt->bind_param('isid',
            $orderId,
            $item['name'],
            $item['quantity'],
            $item['price']
        );
        $itemStmt->execute();
    }

    // Insert payment details if GCash
    if ($input['order']['payment']['method'] === 'gcash' && isset($input['order']['payment']['details'])) {
        $paymentStmt = $conn->prepare("
            INSERT INTO payment_details (order_id, gcash_number, reference_number)
            VALUES (?, ?, ?)
        ");
        $paymentStmt->bind_param('iss',
            $orderId,
            $input['order']['payment']['details']['gcashNumber'],
            $input['order']['payment']['details']['referenceNumber']
        );
        $paymentStmt->execute();
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully',
        'orderId' => $orderId,
        'orderNumber' => $orderNumber
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }

    // Log error
    error_log("Order processing error: " . $e->getMessage());

    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process order: ' . $e->getMessage()
    ]);
}