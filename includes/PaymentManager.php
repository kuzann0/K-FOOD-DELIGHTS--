<?php
/**
 * Payment Manager
 * Handles payment processing, refunds, and payment status tracking
 */
class PaymentManager {
    private static $instance = null;
    private $conn;
    private $logger;
    private $supportedGateways = ['cash', 'gcash'];
    
    // Payment status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    
    // Maximum retry attempts
    const MAX_RETRY_ATTEMPTS = 3;
    const RETRY_DELAY_SECONDS = 60;
    
    private function __construct() {
        $this->conn = DatabaseConnectionPool::getInstance()->getConnection();
        $this->logger = new \KFoodDelights\Payment\PaymentLogger();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new PaymentManager();
        }
        return self::$instance;
    }
    
    public function processPayment($orderId, $paymentData) {
        $transactionManager = new TransactionManager($this->conn);
        
        try {
            $this->logger->logPaymentAttempt($orderId, $paymentData);
            
            $transactionManager->beginTransaction("payment_$orderId");
            
            // Validate payment data
            $this->validatePaymentData($paymentData);
            
            // Get order details
            $order = $this->getOrderDetails($orderId);
            if (!$order) {
                throw new Exception("Order not found");
            }
            
            // Check if payment already exists
            if ($this->hasExistingPayment($orderId)) {
                throw new Exception("Payment already exists for this order");
            }
            
            // Process payment through gateway
            $paymentResult = $this->processPaymentGateway(
                $paymentData['gateway'],
                $order['total_amount'],
                $paymentData
            );
            
            // Record payment
            $paymentId = $this->recordPayment($order, $paymentResult);
            
            // Log successful payment
            $this->logger->logPaymentSuccess($orderId, $paymentResult['transaction_id']);
            
            // Update order status
            $this->updateOrderStatus($orderId, 'paid');
            
            $transactionManager->commit();
            
            return [
                'payment_id' => $paymentId,
                'status' => 'success',
                'transaction_id' => $paymentResult['transaction_id']
            ];
        } catch (Exception $e) {
            $transactionManager->rollback();
            
            // Record failed payment attempt
            $this->recordFailedPayment($orderId, $e->getMessage());
            
            throw $e;
        }
    }
    
    private function validatePaymentData($data) {
        if (!isset($data['gateway']) || !in_array($data['gateway'], $this->supportedGateways)) {
            throw new Exception("Invalid payment gateway");
        }
        
        $required_fields = [
            'gcash' => ['phone_number'],
            'paymaya' => ['token'],
            'credit_card' => ['card_token', 'card_type']
        ];
        
        foreach ($required_fields[$data['gateway']] as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
    }
    
    private function processPaymentGateway($gateway, $amount, $data) {
        switch ($gateway) {
            case 'gcash':
                return $this->processGcashPayment($amount, $data);
            case 'paymaya':
                return $this->processPaymayaPayment($amount, $data);
            case 'credit_card':
                return $this->processCreditCardPayment($amount, $data);
            default:
                throw new Exception("Unsupported payment gateway");
        }
    }
    
    private function getOrderDetails($orderId) {
        $stmt = $this->conn->prepare("
            SELECT id, total_amount, status
            FROM orders
            WHERE id = ?
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    private function hasExistingPayment($orderId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count
            FROM payments
            WHERE order_id = ? AND status = 'completed'
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['count'] > 0;
    }

    private function updateOrderStatus($orderId, $status) {
        $stmt = $this->conn->prepare("
            UPDATE orders
            SET status = ?
            WHERE id = ?
        ");
        $stmt->bind_param("si", $status, $orderId);
        return $stmt->execute();
    }

    private function processGcashPayment($amount, $data) {
        // Implement GCash API integration
        $gcash = new GcashPaymentGateway(GCASH_API_KEY);
        return $gcash->processPayment([
            'amount' => $amount,
            'phone_number' => $data['phone_number'],
            'description' => 'K-Food Delights Order Payment'
        ]);
    }
    
    private function processPaymayaPayment($amount, $data) {
        // Implement PayMaya API integration
        $paymaya = new PaymayaPaymentGateway(PAYMAYA_API_KEY);
        return $paymaya->processPayment([
            'amount' => $amount,
            'token' => $data['token']
        ]);
    }
    
    private function processCreditCardPayment($amount, $data) {
        // Implement Credit Card processing
        $gateway = new CreditCardGateway(CC_GATEWAY_KEY);
        return $gateway->processPayment([
            'amount' => $amount,
            'token' => $data['card_token'],
            'card_type' => $data['card_type']
        ]);
    }
    
    private function recordPayment($order, $paymentResult) {
        $stmt = $this->conn->prepare("
            INSERT INTO payments (
                order_id,
                amount,
                payment_method,
                transaction_id,
                status,
                retry_count,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, 0, NOW(), NOW())
        ");
        
        $stmt->bind_param(
            "idss",
            $order['id'],
            $order['total_amount'],
            $paymentResult['method'],
            $paymentResult['transaction_id']
        );
        
        $stmt->execute();
        $paymentId = $stmt->insert_id;
        $stmt->close();
        
        return $paymentId;
    }
    
    private function recordFailedPayment($orderId, $errorMessage) {
        $stmt = $this->conn->prepare("
            INSERT INTO payment_failures (
                order_id,
                error_message,
                created_at
            ) VALUES (?, ?, NOW())
        ");
        
        $stmt->bind_param("is", $orderId, $errorMessage);
        $stmt->execute();
        $stmt->close();
        
        // Log the failed payment
        $this->logger->logPaymentError($orderId, new \Exception($errorMessage));
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
            AND p.status = 'completed'
        ");
        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    public function processRefund($paymentId, $amount = null, $reason = '') {
        $transactionManager = new TransactionManager($this->conn);
        
        try {
            $transactionManager->beginTransaction("refund_$paymentId");
            
            // Get payment details
            $payment = $this->getPaymentDetails($paymentId);
            if (!$payment) {
                throw new Exception("Payment not found");
            }
            
            // Validate refund amount
            $refundAmount = $amount ?? $payment['amount'];
            if ($refundAmount > $payment['amount']) {
                throw new Exception("Refund amount cannot exceed payment amount");
            }
            
            // Process refund through gateway
            $refundResult = $this->processRefundGateway(
                $payment['payment_method'],
                $payment['transaction_id'],
                $refundAmount
            );
            
            // Record refund
            $refundId = $this->recordRefund($payment, $refundAmount, $reason);
            
            $transactionManager->commit();
            
            return [
                'refund_id' => $refundId,
                'status' => 'success',
                'transaction_id' => $refundResult['transaction_id']
            ];
        } catch (Exception $e) {
            $transactionManager->rollback();
            throw $e;
        }
    }
    
    private function processRefundGateway($gateway, $transactionId, $amount) {
        switch ($gateway) {
            case 'gcash':
                $gcash = new GcashPaymentGateway(GCASH_API_KEY);
                return $gcash->processRefund($transactionId, $amount);
            case 'paymaya':
                $paymaya = new PaymayaPaymentGateway(PAYMAYA_API_KEY);
                return $paymaya->processRefund($transactionId, $amount);
            case 'credit_card':
                $gateway = new CreditCardGateway(CC_GATEWAY_KEY);
                return $gateway->processRefund($transactionId, $amount);
            default:
                throw new Exception("Unsupported payment gateway for refund");
        }
    }
    
    private function recordRefund($payment, $amount, $reason) {
        $stmt = $this->conn->prepare("
            INSERT INTO refunds (
                payment_id,
                amount,
                reason,
                status,
                created_at
            ) VALUES (?, ?, ?, 'completed', NOW())
        ");
        
        $stmt->bind_param("ids", $payment['id'], $amount, $reason);
        $stmt->execute();
        $refundId = $stmt->insert_id;
        $stmt->close();
        
        return $refundId;
    }
    
    public function updatePaymentStatus($transactionId, $status) {
        $stmt = $this->conn->prepare("
            UPDATE payments
            SET 
                status = ?,
                updated_at = NOW(),
                retry_count = CASE 
                    WHEN ? = ? THEN retry_count + 1
                    ELSE retry_count
                END
            WHERE transaction_id = ?
        ");
        
        $isRetry = $status === self::STATUS_PROCESSING ? 1 : 0;
        $stmt->bind_param(
            "siis",
            $status,
            $isRetry,
            $isRetry,
            $transactionId
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        // Log status change
        $this->logger->logPaymentStatusChange($transactionId, $status);
        
        return $result;
    }
    
    public function getPaymentStatus($transactionId) {
        $stmt = $this->conn->prepare("
            SELECT 
                status,
                retry_count,
                created_at,
                updated_at
            FROM payments
            WHERE transaction_id = ?
        ");
        
        $stmt->bind_param("s", $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}