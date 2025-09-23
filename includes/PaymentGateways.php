<?php
/**
 * Payment Gateway Interface
 * Defines the contract for payment gateway implementations
 */
interface PaymentGatewayInterface {
    public function processPayment(array $data);
    public function processRefund($transactionId, $amount);
    public function verifyPayment($transactionId);
}

/**
 * GCash Payment Gateway
 */
class GcashPaymentGateway implements PaymentGatewayInterface {
    private $apiKey;
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    public function processPayment(array $data) {
        // Implement GCash payment processing
        return [
            'transaction_id' => uniqid('gcash_'),
            'method' => 'gcash',
            'status' => 'success'
        ];
    }
    
    public function processRefund($transactionId, $amount) {
        // Implement GCash refund processing
        return [
            'transaction_id' => uniqid('gcash_refund_'),
            'status' => 'success'
        ];
    }
    
    public function verifyPayment($transactionId) {
        // Implement payment verification
        return true;
    }
}

/**
 * PayMaya Payment Gateway
 */
class PaymayaPaymentGateway implements PaymentGatewayInterface {
    private $apiKey;
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    public function processPayment(array $data) {
        // Implement PayMaya payment processing
        return [
            'transaction_id' => uniqid('paymaya_'),
            'method' => 'paymaya',
            'status' => 'success'
        ];
    }
    
    public function processRefund($transactionId, $amount) {
        // Implement PayMaya refund processing
        return [
            'transaction_id' => uniqid('paymaya_refund_'),
            'status' => 'success'
        ];
    }
    
    public function verifyPayment($transactionId) {
        // Implement payment verification
        return true;
    }
}

/**
 * Credit Card Payment Gateway
 */
class CreditCardGateway implements PaymentGatewayInterface {
    private $apiKey;
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    public function processPayment(array $data) {
        // Implement credit card payment processing
        return [
            'transaction_id' => uniqid('cc_'),
            'method' => 'credit_card',
            'status' => 'success'
        ];
    }
    
    public function processRefund($transactionId, $amount) {
        // Implement credit card refund processing
        return [
            'transaction_id' => uniqid('cc_refund_'),
            'status' => 'success'
        ];
    }
    
    public function verifyPayment($transactionId) {
        // Implement payment verification
        return true;
    }
}