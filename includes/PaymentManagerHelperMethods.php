<?php
trait PaymentManagerHelperMethods {
    private function getOrderDetails($orderId) {
        $stmt = $this->conn->prepare("
            SELECT 
                o.id,
                o.order_number,
                o.total_amount,
                o.status,
                o.customer_id,
                o.created_at
            FROM orders o
            WHERE o.id = ?
        ");
        
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        
        return $order;
    }
    
    private function hasExistingPayment($orderId) {
        $stmt = $this->conn->prepare("
            SELECT 1 
            FROM payments 
            WHERE order_id = ? 
            AND status = 'completed'
            LIMIT 1
        ");
        
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    private function updateOrderStatus($orderId, $status) {
        $stmt = $this->conn->prepare("
            UPDATE orders 
            SET 
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->bind_param("si", $status, $orderId);
        $success = $stmt->execute();
        $stmt->close();
        
        if (!$success) {
            throw new Exception("Failed to update order status");
        }
        
        // Send notification about status update
        $this->sendOrderStatusNotification($orderId, $status);
    }
    
    private function getPaymentDetails($paymentId) {
        $stmt = $this->conn->prepare("
            SELECT 
                p.id,
                p.order_id,
                p.amount,
                p.payment_method,
                p.transaction_id,
                p.status,
                p.created_at
            FROM payments p
            WHERE p.id = ?
        ");
        
        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $payment = $result->fetch_assoc();
        $stmt->close();
        
        return $payment;
    }
    
    private function sendOrderStatusNotification($orderId, $status) {
        $order = $this->getOrderDetails($orderId);
        if (!$order) {
            return;
        }
        
        $message = [
            'type' => 'order_status',
            'order_id' => $orderId,
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Insert into notifications table
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (
                type,
                message,
                user_id,
                created_at,
                is_read
            ) VALUES (
                'order_status',
                ?,
                ?,
                NOW(),
                0
            )
        ");
        
        $messageText = "Order #{$order['order_number']} status updated to: $status";
        $stmt->bind_param("si", $messageText, $order['customer_id']);
        $stmt->execute();
        $stmt->close();
        
        // Send real-time notification if WebSocket server is available
        try {
            $this->sendWebSocketNotification($message);
        } catch (Exception $e) {
            error_log("Failed to send WebSocket notification: " . $e->getMessage());
        }
    }
}