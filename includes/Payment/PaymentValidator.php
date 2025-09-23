<?php
namespace KFoodDelights\Payment;

class PaymentValidator {
    private $logger;
    private $supportedGateways = ['cash', 'gcash'];
    
    public function __construct() {
        $this->logger = new PaymentLogger();
    }
    
    public function validatePaymentData($data) {
        if (!isset($data['gateway']) || !in_array($data['gateway'], $this->supportedGateways)) {
            throw new \Exception("Invalid payment gateway");
        }
        
        $required_fields = [
            'cash' => ['amount'],
            'gcash' => ['amount', 'phone_number']
        ];
        
        if (!isset($required_fields[$data['gateway']])) {
            throw new \Exception("Unsupported payment gateway");
        }
        
        foreach ($required_fields[$data['gateway']] as $field) {
            if (!isset($data[$field])) {
                throw new \Exception("Missing required field: $field");
            }
        }
    }
    
    public function validateAmount($amount) {
        if (!is_numeric($amount) || $amount <= 0) {
            throw new \Exception("Invalid payment amount");
        }
        
        // Maximum amount limit for security
        if ($amount > 50000) { // 50,000 PHP limit
            throw new \Exception("Amount exceeds maximum limit");
        }
    }
    
    public function validateGcashNumber($phoneNumber) {
        // Philippine mobile number format
        if (!preg_match('/^(09|\+639)\d{9}$/', $phoneNumber)) {
            throw new \Exception("Invalid GCash phone number format");
        }
    }
    
    public function validatePaymentSource($ip, $userAgent) {
        // Basic fraud detection
        if ($this->isHighRiskIP($ip)) {
            $this->logger->logPaymentError('VALIDATION', new \Exception("High risk IP detected: $ip"));
            throw new \Exception("Payment source not allowed");
        }
        
        // Rate limiting check
        if ($this->isRateLimitExceeded($ip)) {
            throw new \Exception("Too many payment attempts. Please try again later");
        }
    }
    
    private function isHighRiskIP($ip) {
        // Check against known VPN/Proxy IPs
        // This should be implemented with a proper IP reputation service
        return false;
    }
    
    private function isRateLimitExceeded($ip) {
        // Implement rate limiting logic
        // For example: max 5 attempts per minute per IP
        return false;
    }
    
    public function validateTransactionSignature($payload, $signature, $secretKey) {
        $expectedSignature = hash_hmac('sha256', json_encode($payload), $secretKey);
        if ($signature !== $expectedSignature) {
            throw new \Exception("Invalid transaction signature");
        }
    }
}