<?php
/**
 * Process order and send updates through WebSocket
 */
class OrderProcessor {
    private $conn;
    private $userId;
    private $orderData;
    private $wsHandler;

    public function __construct($conn, $userId, $orderData) {
        $this->conn = $conn;
        $this->userId = $userId;
        $this->orderData = $orderData;
        $this->wsHandler = new WebSocketHandler();
    }

    /**
     * Process the order
     */
    public function processOrder() {
        try {
            // Start transaction
            $this->conn->begin_transaction();

            // Create order record
            $orderId = $this->createOrderRecord();

            // Store order items
            $this->storeOrderItems($orderId);

            // Create payment record
            $paymentId = $this->createPaymentRecord($orderId);

            // Send WebSocket notification
            $this->notifyOrderCreated($orderId);

            // Commit transaction
            $this->conn->commit();

            return [
                'success' => true,
                'order_id' => $orderId,
                'payment_id' => $paymentId
            ];

        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("Order processing error: " . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to process order'
            ];
        }
    }

    /**
     * Create order record in database
     */
    private function createOrderRecord() {
        $query = "INSERT INTO orders (user_id, total_amount, status, special_instructions, created_at) 
                 VALUES (?, ?, 'pending', ?, NOW())";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new \Exception("Failed to prepare order query");
        }

        $stmt->bind_param("ids", 
            $this->userId,
            $this->orderData['total_amount'],
            $this->orderData['special_instructions']
        );

        if (!$stmt->execute()) {
            throw new \Exception("Failed to create order record");
        }

        return $stmt->insert_id;
    }

    /**
     * Store order items in database
     */
    private function storeOrderItems($orderId) {
        $query = "INSERT INTO order_items (order_id, product_id, quantity, price, notes) 
                 VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new \Exception("Failed to prepare order items query");
        }

        foreach ($this->orderData['items'] as $item) {
            $stmt->bind_param("iidds",
                $orderId,
                $item['id'],
                $item['quantity'],
                $item['price'],
                $item['notes']
            );

            if (!$stmt->execute()) {
                throw new \Exception("Failed to store order item");
            }
        }
    }

    /**
     * Create payment record in database
     */
    private function createPaymentRecord($orderId) {
        $query = "INSERT INTO payments (order_id, amount, method, status, created_at) 
                 VALUES (?, ?, ?, 'pending', NOW())";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new \Exception("Failed to prepare payment query");
        }

        $stmt->bind_param("ids",
            $orderId,
            $this->orderData['total_amount'],
            $this->orderData['payment_method']
        );

        if (!$stmt->execute()) {
            throw new \Exception("Failed to create payment record");
        }

        return $stmt->insert_id;
    }

    /**
     * Send WebSocket notification for new order
     */
    private function notifyOrderCreated($orderId) {
        // Fetch complete order details
        $orderDetails = $this->getOrderDetails($orderId);

        // Prepare WebSocket message
        $message = [
            'type' => 'new_order',
            'data' => [
                'orderId' => $orderId,
                'items' => $orderDetails['items'],
                'customerInfo' => [
                    'name' => $orderDetails['customer_name'],
                    'address' => $orderDetails['address'],
                    'phone' => $orderDetails['phone']
                ],
                'total' => $this->orderData['total_amount'],
                'status' => 'pending',
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];

        // Send notification through WebSocket
        $this->wsHandler->sendMessage('new_order', $message['data']);
    }

    /**
     * Get complete order details
     */
    private function getOrderDetails($orderId) {
        $query = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone, u.address,
                        oi.product_id, oi.quantity, oi.price, oi.notes,
                        p.name as product_name
                 FROM orders o
                 JOIN users u ON o.user_id = u.user_id
                 JOIN order_items oi ON o.id = oi.order_id
                 JOIN products p ON oi.product_id = p.id
                 WHERE o.id = ?";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new \Exception("Failed to prepare order details query");
        }

        $stmt->bind_param("i", $orderId);
        
        if (!$stmt->execute()) {
            throw new \Exception("Failed to fetch order details");
        }

        $result = $stmt->get_result();
        $orderData = [
            'items' => [],
            'customer_name' => '',
            'address' => '',
            'phone' => ''
        ];

        while ($row = $result->fetch_assoc()) {
            if (empty($orderData['customer_name'])) {
                $orderData['customer_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $orderData['address'] = $row['address'];
                $orderData['phone'] = $row['phone'];
            }

            $orderData['items'][] = [
                'id' => $row['product_id'],
                'name' => $row['product_name'],
                'quantity' => $row['quantity'],
                'price' => $row['price'],
                'notes' => $row['notes']
            ];
        }

        return $orderData;
    }
}