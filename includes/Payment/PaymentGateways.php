<?php
namespace KFoodDelights\Payment\Gateways;

interface PaymentGatewayInterface {
    public function processPayment(array $data);
    public function processRefund($transactionId, $amount);
    public function verifyPayment($transactionId);
}

abstract class BasePaymentGateway implements PaymentGatewayInterface {
    protected $config;
    protected $environment;
    
    public function __construct($config) {
        $this->config = $config;
        $this->environment = $config['environment'] ?? 'sandbox';
    }
    
    protected function getEndpoint() {
        return $this->config[$this->environment]['endpoint'];
    }
    
    protected function handleApiError($response) {
        $error = json_decode($response, true);
        throw new \Exception($error['message'] ?? 'Payment gateway error');
    }
}

class GCashGateway extends BasePaymentGateway {
    public function processPayment(array $data) {
        // Implementation for GCash payment processing
        $apiKey = $this->config[$this->environment]['api_key'];
        // API call implementation
        return [
            'transaction_id' => uniqid('gcash_'),
            'method' => 'gcash',
            'status' => 'success'
        ];
    }
    
    public function processRefund($transactionId, $amount) {
        // Implementation for GCash refund
        return [
            'transaction_id' => uniqid('gcash_refund_'),
            'status' => 'success'
        ];
    }
    
    public function verifyPayment($transactionId) {
        // Implementation for payment verification
        return true;
    }
}

class PayMayaGateway extends BasePaymentGateway {
    public function processPayment(array $data) {
        // Implementation for PayMaya payment processing
        $publicKey = $this->config[$this->environment]['public_key'];
        // API call implementation
        return [
            'transaction_id' => uniqid('paymaya_'),
            'method' => 'paymaya',
            'status' => 'success'
        ];
    }
    
    public function processRefund($transactionId, $amount) {
        // Implementation for PayMaya refund
        return [
            'transaction_id' => uniqid('paymaya_refund_'),
            'status' => 'success'
        ];
    }
    
    public function verifyPayment($transactionId) {
        // Implementation for payment verification
        return true;
    }
}

class CreditCardGateway extends BasePaymentGateway {
    public function processPayment(array $data) {
        // Implementation for credit card processing
        $merchantId = $this->config[$this->environment]['merchant_id'];
        // API call implementation
        return [
            'transaction_id' => uniqid('cc_'),
            'method' => 'credit_card',
            'status' => 'success'
        ];
    }
    
    public function processRefund($transactionId, $amount) {
        // Implementation for credit card refund
        return [
            'transaction_id' => uniqid('cc_refund_'),
            'status' => 'success'
        ];
    }
    
    public function verifyPayment($transactionId) {
        // Implementation for payment verification
        return true;
    }
}