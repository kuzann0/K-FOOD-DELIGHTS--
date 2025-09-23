<?php
namespace KFoodDelights\Payment;

use Exception;
use KFoodDelights\Database\DatabaseConnectionPool;
use KFoodDelights\Database\TransactionManager;

class PaymentManager {
    private static $instance = null;
    private $conn;
    private $logger;
    private $validator;
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
        $this->logger = new PaymentLogger();
        $this->validator = new PaymentValidator();
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
            
            // Validate payment data
            $this->validator->validatePaymentData($paymentData);
            
            // Validate amount
            $this->validator->validateAmount($paymentData['amount']);
            
            // Validate payment source
            $this->validator->validatePaymentSource(
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $transactionManager->beginTransaction("payment_$orderId");
            
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
    
    private function processPaymentGateway($gateway, $amount, $data) {
        switch ($gateway) {
            case 'cash':
                return $this->processCashPayment($amount, $data);
            case 'gcash':
                return $this->processGcashPayment($amount, $data);
            default:
                throw new Exception("Unsupported payment gateway");
        }
    }
    
    private function processCashPayment($amount, $data) {
        // For cash payments, we'll generate a reference number
        return [
            'transaction_id' => 'CASH-' . uniqid(),
            'method' => 'cash',
            'status' => self::STATUS_COMPLETED
        ];
    }
    
    private function processGcashPayment($amount, $data) {
        // Validate GCash number
        $this->validator->validateGcashNumber($data['phone_number']);
        
        $gcash = new GcashPaymentGateway(PaymentConfig::GCASH_API_KEY);
        return $gcash->processPayment([
            'amount' => $amount,
            'phone_number' => $data['phone_number'],
            'description' => 'K-Food Delights Order Payment'
        ]);
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
            WHERE order_id = ? AND status = ?
        ");
        $completed = self::STATUS_COMPLETED;
        $stmt->bind_param("is", $orderId, $completed);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['count'] > 0;
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
    
    public function updateOrderStatus($orderId, $status) {
        $stmt = $this->conn->prepare("
            UPDATE orders
            SET status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("si", $status, $orderId);
        return $stmt->execute();
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

    /**
     * Cancel a pending payment for an order
     * 
     * @param int $orderId The order ID to cancel payment for
     * @return array Status of the cancellation operation
     * @throws Exception If cancellation fails
     */
    public function cancelPayment($orderId) {
        $transactionManager = new TransactionManager($this->conn);
        
        try {
            $transactionManager->beginTransaction("cancel_payment_$orderId");
            
            // First, check if there's an active payment to cancel
            $stmt = $this->conn->prepare("
                SELECT id, status, transaction_id 
                FROM payments 
                WHERE order_id = ? 
                AND status IN (?, ?)
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            
            $initiated = self::STATUS_PENDING;
            $processing = self::STATUS_PROCESSING;
            $stmt->bind_param("iss", $orderId, $initiated, $processing);
            $stmt->execute();
            $payment = $stmt->get_result()->fetch_assoc();
            
            if (!$payment) {
                throw new Exception("No active payment found for order #$orderId");
            }
            
            // Update payment status to cancelled
            $updateStmt = $this->conn->prepare("
                UPDATE payments 
                SET 
                    status = ?,
                    updated_at = NOW(),
                    cancellation_reason = ?
                WHERE id = ?
            ");
            
            $status = self::STATUS_CANCELLED;
            $reason = 'User cancelled payment';
            $updateStmt->bind_param("ssi", $status, $reason, $payment['id']);
            $updateStmt->execute();
            
            // Log the cancellation in payment_failures for tracking
            $logMessage = "Payment cancelled by user";
            $this->recordFailedPayment($orderId, $logMessage);
            
            // Update order status
            $this->updateOrderStatus($orderId, 'payment_cancelled');
            
            $transactionManager->commit();
            
            // Log the cancellation
            $this->logger->logPaymentStatusChange($payment['transaction_id'], self::STATUS_CANCELLED);
            
            return [
                'status' => 'success',
                'message' => 'Payment cancelled successfully',
                'order_id' => $orderId,
                'payment_id' => $payment['id'],
                'transaction_id' => $payment['transaction_id']
            ];
            
        } catch (Exception $e) {
            $transactionManager->rollback();
            $this->logger->logPaymentError($orderId, $e);
            throw new Exception("Failed to cancel payment: " . $e->getMessage());
        }
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
        $this->logger->logPaymentError($orderId, new Exception($errorMessage));
    }
}