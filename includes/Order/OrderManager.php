<?php
namespace KFoodDelights\Order;

class OrderManager {
    private $conn;
    private $subscribers;
    
    public function __construct() {
        $this->conn = \KFoodDelights\Database\DatabaseConnectionPool::getInstance()->getConnection();
        $this->subscribers = [];
    }
    
    public function getOrderDetails($orderId) {
        $stmt = $this->conn->prepare("
            SELECT o.*, u.user_id, COALESCE(s.user_id, 0) as staff_id
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id
            LEFT JOIN staff s ON o.staff_id = s.user_id
            WHERE o.id = ?
        ");
        
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    public function updateOrder($orderId, $status, $userId) {
        try {
            $this->conn->begin_transaction();

            // Update order status
            $stmt = $this->conn->prepare("
                UPDATE orders 
                SET status = ?, 
                    updated_at = NOW(), 
                    updated_by = ?
                WHERE id = ?
            ");
            
            $stmt->bind_param("sii", $status, $userId, $orderId);
            
            if (!$stmt->execute()) {
                throw new \Exception("Failed to update order status");
            }

            // Log the status change
            $logStmt = $this->conn->prepare("
                INSERT INTO order_status_logs 
                (order_id, status, updated_by, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            $logStmt->bind_param("isi", $orderId, $status, $userId);
            
            if (!$logStmt->execute()) {
                throw new \Exception("Failed to log status change");
            }

            $this->conn->commit();

            // Notify subscribers
            $this->notifySubscribers($orderId);

            return [
                'success' => true,
                'message' => 'Order updated successfully'
            ];

        } catch (\Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function subscribeToUpdates($userId, $callback) {
        if (!isset($this->subscribers[$userId])) {
            $this->subscribers[$userId] = [];
        }
        $this->subscribers[$userId][] = $callback;
    }

    /**
     * Gets the current status of an order
     * @param int $orderId The order ID
     * @return string The current order status
     * @throws \Exception If order not found
     */
    public function getOrderStatus($orderId) {
        $stmt = $this->conn->prepare("SELECT status FROM orders WHERE id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!$row) {
            throw new \Exception("Order not found");
        }
        
        return $row['status'];
    }

    /**
     * Notifies all subscribers about an order update
     * @param int $orderId The order ID that was updated
     */
    private function notifySubscribers($orderId) {
        $orderDetails = $this->getOrderDetails($orderId);
        if (!$orderDetails) return;
        
        $status = $orderDetails['status'];
        $timestamp = time();
        
        foreach ($this->subscribers as $userId => $callbacks) {
            // Check if user should receive the notification (customer or staff)
            if ($userId == $orderDetails['user_id'] || $userId == $orderDetails['staff_id']) {
                foreach ($callbacks as $callback) {
                    $callback([
                        'type' => 'order_update',
                        'orderId' => $orderId,
                        'status' => $status,
                        'orderDetails' => $orderDetails,
                        'timestamp' => $timestamp,
                        'message' => $this->getStatusMessage($status)
                    ]);
                }
            }
        }
    }

    /**
     * Gets a user-friendly message for status updates
     * @param string $status The status
     * @return string The message
     */
    private function getStatusMessage($status) {
        $messages = [
            'pending' => 'Your order has been received and is pending confirmation.',
            'accepted' => 'Your order has been accepted! We\'ll start preparing it soon.',
            'preparing' => 'Your delicious food is being prepared!',
            'ready' => 'Your order is ready for delivery!',
            'delivered' => 'Your order has been delivered. Enjoy your meal!',
            'cancelled' => 'Your order has been cancelled.'
        ];
        
        return $messages[$status] ?? 'Your order status has been updated.';
    }
}