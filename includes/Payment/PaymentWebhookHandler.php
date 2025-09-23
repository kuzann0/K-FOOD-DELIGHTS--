<?php
namespace KFoodDelights\Payment;

use KFoodDelights\Database\DatabaseConnectionPool;

class PaymentWebhookHandler {
    private $logger;
    private $validator;
    private $paymentManager;
    
    public function __construct() {
        $this->logger = new PaymentLogger();
        $this->validator = new PaymentValidator();
        $this->paymentManager = PaymentManager::getInstance();
    }
    
    public function handleGcashWebhook($payload, $signature) {
        try {
            // Validate webhook signature
            $this->validator->validateTransactionSignature(
                $payload,
                $signature,
                PaymentConfig::GCASH_WEBHOOK_SECRET
            );
            
            $event = $payload['event'];
            $transactionId = $payload['data']['transaction_id'];
            
            switch ($event) {
                case 'payment.success':
                    $this->handlePaymentSuccess($payload['data']);
                    break;
                    
                case 'payment.failed':
                    $this->handlePaymentFailure($payload['data']);
                    break;
                    
                case 'payment.expired':
                    $this->handlePaymentExpired($payload['data']);
                    break;
                    
                default:
                    $this->logger->logPaymentError(
                        $transactionId,
                        new \Exception("Unknown webhook event: $event")
                    );
            }
            
            http_response_code(200);
            echo json_encode(['status' => 'success']);
            
        } catch (\Exception $e) {
            $this->logger->logPaymentError('WEBHOOK', $e);
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    
    private function handlePaymentSuccess($data) {
        $orderId = $data['order_id'];
        $transactionId = $data['transaction_id'];
        
        // Update payment status
        $this->paymentManager->updatePaymentStatus(
            $transactionId,
            PaymentManager::STATUS_COMPLETED
        );
        
        // Update order status
        $this->paymentManager->updateOrderStatus($orderId, 'paid');
        
        // Log success
        $this->logger->logPaymentSuccess($orderId, $transactionId);
    }
    
    private function handlePaymentFailure($data) {
        $orderId = $data['order_id'];
        $transactionId = $data['transaction_id'];
        $errorMessage = $data['error_message'] ?? 'Payment failed';
        
        // Check if we should retry
        if ($this->shouldRetryPayment($transactionId)) {
            $this->schedulePaymentRetry($orderId, $data);
        } else {
            $this->paymentManager->updatePaymentStatus(
                $transactionId,
                PaymentManager::STATUS_FAILED
            );
        }
        
        $this->logger->logPaymentError(
            $orderId,
            new \Exception($errorMessage)
        );
    }
    
    private function handlePaymentExpired($data) {
        $orderId = $data['order_id'];
        $transactionId = $data['transaction_id'];
        
        $this->paymentManager->updatePaymentStatus(
            $transactionId,
            PaymentManager::STATUS_CANCELLED
        );
        
        $this->logger->logPaymentError(
            $orderId,
            new \Exception("Payment expired")
        );
    }
    
    private function shouldRetryPayment($transactionId) {
        $attempts = $this->getRetryAttempts($transactionId);
        return $attempts < PaymentManager::MAX_RETRY_ATTEMPTS;
    }
    
    private function getRetryAttempts($transactionId) {
        // Get retry attempts from database
        return 0; // Implement actual retry count tracking
    }
    
    private function schedulePaymentRetry($orderId, $paymentData) {
        // Schedule a retry using a job queue or cron
        // This is a simplified implementation
        $retryTime = time() + PaymentManager::RETRY_DELAY_SECONDS;
        
        $stmt = DatabaseConnectionPool::getInstance()
            ->getConnection()
            ->prepare("
                INSERT INTO payment_retries (
                    order_id,
                    payment_data,
                    retry_time,
                    created_at
                ) VALUES (?, ?, ?, NOW())
            ");
        
        $paymentDataJson = json_encode($paymentData);
        $stmt->bind_param("isi", $orderId, $paymentDataJson, $retryTime);
        $stmt->execute();
    }
}