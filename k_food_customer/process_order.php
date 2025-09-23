<?php
session_start();
require_once 'config.php';
require_once 'includes/ErrorHandler.php';
require_once 'includes/OrderValidator.php';
require_once 'includes/NotificationHandler.php';

// Helper functions
function generateUniqueOrderNumber($conn) {
    $maxAttempts = 10;
    $attempt = 0;
    
    do {
        $attempt++;
        $microtime = microtime(true);
        $micro = sprintf("%06d", ($microtime - floor($microtime)) * 1000000);
        $orderNumber = 'KFD-' . date('Ymd-His-') . $micro . sprintf('%02d', rand(0, 99));
        
        // Check if order number exists
        $checkStmt = $conn->prepare("SELECT 1 FROM orders WHERE order_number = ? LIMIT 1");
        $checkStmt->bind_param("s", $orderNumber);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->num_rows > 0;
        $checkStmt->close();
        
        if ($attempt >= $maxAttempts && $exists) {
            throw new OrderProcessingError(
                "Failed to generate unique order number",
                "SYSTEM_ERROR"
            );
        }
    } while ($exists);
    
    return $orderNumber;
}

function processOrder($conn, $data, $orderNumber) {
    try {
        $conn->begin_transaction();
        
        // Check inventory levels first
        foreach ($data['cartItems'] as $item) {
            $checkStmt = $conn->prepare("SELECT id, stock_quantity FROM menu_items WHERE item_name = ? AND status = 'active' FOR UPDATE");
            $checkStmt->bind_param("s", $item['name']);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new OrderProcessingError(
                    "Item '{$item['name']}' is no longer available",
                    "INVENTORY_ERROR"
                );
            }
            
            $menuItem = $result->fetch_assoc();
            if ($menuItem['stock_quantity'] < $item['quantity']) {
                throw new OrderProcessingError(
                    "Not enough stock for item '{$item['name']}'. Available: {$menuItem['stock_quantity']}, Requested: {$item['quantity']}",
                    "INVENTORY_ERROR"
                );
            }
            $checkStmt->close();
        }
        
        // Insert main order
        $sql = "INSERT INTO orders (
            user_id, customer_name, order_number, total_amount,
            status, payment_status, delivery_address, contact_number,
            special_instructions, promo_id, discount_amount,
            senior_pwd_discount, senior_pwd_id, created_at
        ) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new OrderProcessingError(
                "Database error while preparing order: " . $conn->error,
                "DATABASE_ERROR",
                ['sql_error' => $conn->error]
            );
        }

        // Get promo ID if code exists
        $promoId = null;
        if (!empty($data['discounts']['promoCode'])) {
            $promoStmt = $conn->prepare("SELECT id FROM promo_codes WHERE code = ? AND status = 'active'");
            $promoStmt->bind_param("s", $data['discounts']['promoCode']);
            $promoStmt->execute();
            $promoResult = $promoStmt->get_result();
            if ($promoResult->num_rows > 0) {
                $promoId = $promoResult->fetch_assoc()['id'];
            }
            $promoStmt->close();
        }

        // Set payment status based on method
        $paymentStatus = $data['payment']['method'] === 'cod' ? 'pending' : 'paid';
        
        // Prepare discount ID (senior or PWD)
        $discountId = null;
        if (!empty($data['discounts']['seniorId'])) {
            $discountId = $data['discounts']['seniorId'];
        } elseif (!empty($data['discounts']['pwdId'])) {
            $discountId = $data['discounts']['pwdId'];
        }

        $stmt->bind_param(
            "issdsssssddds",
            $_SESSION['user_id'],
            $data['customerInfo']['name'],
            $orderNumber,
            $data['amounts']['total'],
            $paymentStatus,
            $data['customerInfo']['address'],
            $data['customerInfo']['phone'],
            $data['customerInfo']['deliveryInstructions'],
            $promoId,
            $data['amounts']['totalDiscount'],
            0, // No senior/PWD discounts for now
            $discountId
        );
        
        if (!$stmt->execute()) {
            throw new OrderProcessingError(
                "Failed to insert order: " . $stmt->error,
                "DATABASE_ERROR"
            );
        }
        
        $orderId = $stmt->insert_id;
        $stmt->close();

        // Insert order items
        $itemSql = "INSERT INTO order_items (
            order_id, item_name, quantity, price, subtotal
        ) VALUES (?, ?, ?, ?, ?)";
        
        $itemStmt = $conn->prepare($itemSql);
        if (!$itemStmt) {
            throw new OrderProcessingError(
                "Failed to prepare order items statement",
                "SYSTEM_ERROR"
            );
        }

        foreach ($data['cartItems'] as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $itemStmt->bind_param(
                "isids",
                $orderId,
                $item['name'],
                $item['quantity'],
                $item['price'],
                $subtotal
            );
            
            if (!$itemStmt->execute()) {
                throw new OrderProcessingError(
                    "Failed to insert order item",
                    "DATABASE_ERROR"
                );
            }

            // Update inventory
            $updateStmt = $conn->prepare("
                UPDATE menu_items 
                SET stock_quantity = stock_quantity - ?,
                    last_updated = NOW()
                WHERE item_name = ? AND status = 'active'
            ");
            $updateStmt->bind_param("is", $item['quantity'], $item['name']);
            
            if (!$updateStmt->execute()) {
                throw new OrderProcessingError(
                    "Failed to update inventory for item '{$item['name']}'",
                    "DATABASE_ERROR"
                );
            }
            $updateStmt->close();
        }
        
        $itemStmt->close();

        // If GCash payment, validate and store reference
        if ($data['payment']['method'] === 'gcash') {
            if (empty($data['payment']['gcashReference'])) {
                throw new OrderProcessingError(
                    "GCash reference number is required",
                    "PAYMENT_ERROR"
                );
            }

            // Validate GCash reference number format
            $referenceNumber = $data['payment']['gcashReference'];
            if (!preg_match('/^[0-9]{10,13}$/', $referenceNumber)) {
                throw new OrderProcessingError(
                    "Invalid GCash reference number format",
                    "PAYMENT_ERROR",
                    ['reference' => $referenceNumber]
                );
            }

            // Check for duplicate reference number
            $checkRefStmt = $conn->prepare("
                SELECT 1 FROM payment_details 
                WHERE payment_method = 'gcash' 
                AND reference_number = ?
                LIMIT 1
            ");
            $checkRefStmt->bind_param("s", $referenceNumber);
            $checkRefStmt->execute();
            if ($checkRefStmt->get_result()->num_rows > 0) {
                throw new OrderProcessingError(
                    "This GCash reference number has already been used",
                    "PAYMENT_ERROR",
                    ['reference' => $referenceNumber]
                );
            }
            $checkRefStmt->close();

            // Validate payment amount
            $expectedAmount = $data['amounts']['total'];
            $paidAmount = $data['payment']['amount'] ?? null;
            if ($paidAmount === null || abs($paidAmount - $expectedAmount) > 0.01) {
                throw new OrderProcessingError(
                    "Payment amount does not match order total",
                    "PAYMENT_ERROR",
                    [
                        'expected' => $expectedAmount,
                        'received' => $paidAmount
                    ]
                );
            }

            // Store payment details
            $paymentSql = "INSERT INTO payment_details (
                order_id, payment_method, reference_number, amount, 
                status, payment_date, verified_at
            ) VALUES (?, 'gcash', ?, ?, 'completed', NOW(), NOW())";
            
            $paymentStmt = $conn->prepare($paymentSql);
            if (!$paymentStmt) {
                throw new OrderProcessingError(
                    "Failed to prepare payment statement",
                    "SYSTEM_ERROR"
                );
            }

            $paymentStmt->bind_param(
                "isd",
                $orderId,
                $referenceNumber,
                $expectedAmount
            );
            
            if (!$paymentStmt->execute()) {
                throw new OrderProcessingError(
                    "Failed to store payment details",
                    "DATABASE_ERROR"
                );
            }
            
            $paymentStmt->close();
        }

        // Create notifications
        $notificationHandler = new NotificationHandler($conn);
        $notificationHandler->sendOrderNotifications($orderId, [
            'orderNumber' => $orderNumber,
            'customerInfo' => $data['customerInfo'],
            'amounts' => $data['amounts'],
            'cartItems' => $data['cartItems']
        ]);

        // Notify WebSocket server about new order
        $webSocketHandler = new WebSocketHandler();
        $webSocketHandler->broadcastNewOrder([
            'orderId' => $orderId,
            'orderNumber' => $orderNumber,
            'customerName' => $data['customerInfo']['fullName'],
            'total' => $data['amounts']['total'],
            'items' => $data['cartItems'],
            'status' => 'pending'
        ]);

        $conn->commit();
        return $orderId;
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function createOrderNotifications($conn, $orderId, $orderNumber, $totalAmount) {
    $notificationHandler = new NotificationHandler($conn);
    
    // Format amount for display
    $formattedAmount = number_format($totalAmount, 2);
    
    // Notify crew (role_id = 3)
    $crewMsg = "New order #$orderNumber received! Total: ₱$formattedAmount";
    $notificationHandler->createNotification('order', $orderId, $crewMsg, 3);
    
    // Notify admin (role_id = 2)
    $adminMsg = "New order #$orderNumber placed. Amount: ₱$formattedAmount";
    $notificationHandler->createNotification('order', $orderId, $adminMsg, 2);
}

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

    // Initialize error handler
    $errorHandler = new ErrorHandler($conn);

    try {
        // Check rate limits - max 5 orders per 30 minutes per user
        $rateLimitStmt = $conn->prepare("
            SELECT COUNT(*) as order_count
            FROM orders
            WHERE user_id = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ");
        $rateLimitStmt->bind_param("i", $_SESSION['user_id']);
        $rateLimitStmt->execute();
        $result = $rateLimitStmt->get_result();
        $orderCount = $result->fetch_assoc()['order_count'];
        $rateLimitStmt->close();

        if ($orderCount >= 5) {
            throw new OrderProcessingError(
                "Order limit reached. Please try again later.",
                "RATE_LIMIT_ERROR",
                [
                    'limit' => 5,
                    'windowMinutes' => 30,
                    'currentCount' => $orderCount
                ]
            );
        }
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new OrderProcessingError(
            'Please login to place an order',
            'AUTHENTICATION_ERROR'
        );
    }

    // Check for idempotency key
    $idempotencyKey = $_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? null;
    if (!$idempotencyKey) {
        throw new OrderProcessingError(
            'Idempotency key is required',
            'VALIDATION_ERROR'
        );
    }

    // Check if this request was already processed
    $idempotencyStmt = $conn->prepare("
        SELECT order_id, status 
        FROM order_requests 
        WHERE idempotency_key = ? AND user_id = ?
        LIMIT 1
    ");
    $idempotencyStmt->bind_param("si", $idempotencyKey, $_SESSION['user_id']);
    $idempotencyStmt->execute();
    $result = $idempotencyStmt->get_result();
    
    if ($result->num_rows > 0) {
        $previousRequest = $result->fetch_assoc();
        if ($previousRequest['status'] === 'completed') {
            throw new OrderProcessingError(
                'This order has already been processed',
                'DUPLICATE_ORDER',
                ['orderId' => $previousRequest['order_id']]
            );
        }
        // If status is 'in_progress', the previous request might have failed
        // We'll allow this request to proceed but use the same idempotency record
    } else {
        // Create new idempotency record
        $createIdempotencyStmt = $conn->prepare("
            INSERT INTO order_requests (
                idempotency_key, user_id, request_data, status, created_at
            ) VALUES (?, ?, ?, 'in_progress', NOW())
        ");
        $requestData = json_encode($data);
        $createIdempotencyStmt->bind_param("sis", $idempotencyKey, $_SESSION['user_id'], $requestData);
        $createIdempotencyStmt->execute();
        $createIdempotencyStmt->close();
    }
    $idempotencyStmt->close();

    // Get and validate raw POST data
    $jsonData = file_get_contents('php://input');
    if (!$jsonData) {
        throw new OrderProcessingError(
            'No data received',
            'VALIDATION_ERROR'
        );
    }

    // Parse and validate JSON data
    $data = json_decode($jsonData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new OrderProcessingError(
            'Invalid JSON format: ' . json_last_error_msg(),
            'VALIDATION_ERROR',
            ['jsonError' => json_last_error_msg()]
        );
    }

    // Validate request format
    if (!is_array($data)) {
        throw new OrderProcessingError(
            'Invalid request format',
            'VALIDATION_ERROR',
            ['received' => gettype($data)]
        );
    }

    // Initialize validator with database connection and error handler
    $orderValidator = new OrderValidator($conn, $errorHandler);
    
    // Validate complete order structure
    $validationResult = $orderValidator->validateOrder($data);
    if (!$validationResult['success']) {
        throw new OrderProcessingError(
            $validationResult['message'],
            'VALIDATION_ERROR',
            $validationResult['errors']
        );
    }
        
        // Begin database transaction
    $conn->begin_transaction();

    try {
        // Generate unique order number
        $orderNumber = generateUniqueOrderNumber($conn);
        
        // Process order in database
        $orderId = processOrder($conn, $data, $orderNumber);
        
        // Create notifications
        createOrderNotifications($conn, $orderId, $orderNumber, $data['amounts']['total']);
        
        // Prepare order data for WebSocket notification
        $orderData = [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'customer_name' => $data['customerInfo']['name'],
            'total_amount' => $data['amounts']['total'],
            'status' => 'Pending',
            'items' => array_map(function($item) {
                return $item['quantity'] . 'x ' . $item['name'];
            }, $data['items']),
            'delivery_address' => $data['customerInfo']['address'],
            'contact_number' => $data['customerInfo']['phone'],
            'special_instructions' => $data['customerInfo']['deliveryInstructions'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Notify WebSocket server about new order
        $wsHandler = new WebSocketHandler();
        $wsHandler->broadcastNewOrder($orderData);
        
        // Update idempotency record
        $updateIdempotencyStmt = $conn->prepare("
            UPDATE order_requests 
            SET status = 'completed', 
                order_id = ?,
                completed_at = NOW() 
            WHERE idempotency_key = ? 
            AND user_id = ?
        ");
        $updateIdempotencyStmt->bind_param(
            "isi", 
            $orderId, 
            $idempotencyKey, 
            $_SESSION['user_id']
        );
        $updateIdempotencyStmt->execute();
        $updateIdempotencyStmt->close();

        // Commit transaction
        $conn->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully',
            'orderId' => $orderId,
            'orderNumber' => $orderNumber,
            'routing' => [
                'nextStep' => 'confirmation',
                'redirectUrl' => "order_confirmation.php?order_id=$orderId",
                'trackingEnabled' => true
            ],
            'status' => [
                'current' => 'Pending',
                'timestamp' => date('Y-m-d H:i:s'),
                'estimatedDelivery' => date('Y-m-d H:i:s', strtotime('+45 minutes'))
            ]
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (OrderProcessingError $e) {
    // Log the error with context
    $errorContext = [
        'errorType' => $e->getErrorType(),
        'userId' => $_SESSION['user_id'] ?? null,
        'requestData' => $data ?? null,
        'timestamp' => date('Y-m-d H:i:s'),
        'additionalData' => $e->getErrorData()
    ];
    error_log(json_encode([
        'level' => 'ERROR',
        'message' => $e->getMessage(),
        'context' => $errorContext
    ]));

    // Set appropriate HTTP status code
    switch ($e->getErrorType()) {
        case 'AUTHENTICATION_ERROR':
            http_response_code(401);
            break;
        case 'VALIDATION_ERROR':
            http_response_code(400);
            break;
        case 'INVENTORY_ERROR':
            http_response_code(409);
            break;
        case 'PAYMENT_ERROR':
            http_response_code(402);
            break;
        default:
            http_response_code(400);
    }

    // Return detailed error response
    $response = $errorHandler->handleError($e);
    echo json_encode($response);

} catch (Exception $e) {
    // Log unexpected errors with stack trace
    $errorContext = [
        'errorType' => 'SYSTEM_ERROR',
        'userId' => $_SESSION['user_id'] ?? null,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    error_log(json_encode([
        'level' => 'CRITICAL',
        'message' => $e->getMessage(),
        'context' => $errorContext
    ]));

    // Return sanitized error for production
    $response = $errorHandler->handleError(new OrderProcessingError(
        'An unexpected error occurred. Our team has been notified.',
        'SYSTEM_ERROR',
        ['errorId' => uniqid('err_')] // Tracking ID for support
    ));
    http_response_code(500);
    echo json_encode($response);

} finally {
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
}
?>
