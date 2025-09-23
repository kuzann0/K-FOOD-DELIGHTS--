<?php
namespace KFoodDelights\Payment;

require_once __DIR__ . '/../../k_food_customer/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;

class PaymentLogger {
    private $logger;
    
    public function __construct() {
        $this->logger = new Logger('payment');
        
        // Add handlers
        $this->logger->pushHandler(
            new RotatingFileHandler(
                __DIR__ . '/../../logs/payment.log',
                0,
                Logger::DEBUG,
                true,
                0644
            )
        );
        
        // Add error handler
        $this->logger->pushHandler(
            new StreamHandler(
                __DIR__ . '/../../logs/payment_error.log',
                Logger::ERROR
            )
        );
    }
    
    public function logPaymentAttempt($orderId, $paymentData) {
        $this->logger->info('Payment attempt', [
            'order_id' => $orderId,
            'gateway' => $paymentData['gateway'],
            'amount' => $paymentData['amount'] ?? null
        ]);
    }
    
    public function logPaymentSuccess($orderId, $transactionId) {
        $this->logger->info('Payment successful', [
            'order_id' => $orderId,
            'transaction_id' => $transactionId
        ]);
    }
    
    public function logPaymentError($orderId, \Exception $error) {
        $this->logger->error('Payment failed', [
            'order_id' => $orderId,
            'error' => $error->getMessage(),
            'trace' => $error->getTraceAsString()
        ]);
    }
    
    public function logRefundAttempt($paymentId, $amount) {
        $this->logger->info('Refund attempt', [
            'payment_id' => $paymentId,
            'amount' => $amount
        ]);
    }
    
    public function logRefundSuccess($paymentId, $transactionId) {
        $this->logger->info('Refund successful', [
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId
        ]);
    }
    
    public function logRefundError($paymentId, \Exception $error) {
        $this->logger->error('Refund failed', [
            'payment_id' => $paymentId,
            'error' => $error->getMessage(),
            'trace' => $error->getTraceAsString()
        ]);
    }
    
    public function logPaymentStatusChange($transactionId, $status) {
        $this->logger->info('Payment status changed', [
            'transaction_id' => $transactionId,
            'new_status' => $status,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}