<?php
namespace KFoodDelights\Payment;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GcashPaymentGateway {
    private $apiKey;
    private $client;
    private $logger;
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
        $this->client = new Client([
            'base_uri' => PaymentConfig::GCASH_API_BASE_URL,
            'timeout' => PaymentConfig::API_TIMEOUT,
            'connect_timeout' => PaymentConfig::API_CONNECT_TIMEOUT,
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json'
            ]
        ]);
        $this->logger = new PaymentLogger();
    }
    
    public function processPayment($paymentData) {
        try {
            $response = $this->client->post('/v1/payments', [
                'json' => [
                    'amount' => $paymentData['amount'],
                    'phone_number' => $paymentData['phone_number'],
                    'description' => $paymentData['description'],
                    'currency' => 'PHP'
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            return [
                'transaction_id' => $result['id'],
                'method' => 'gcash',
                'status' => $result['status']
            ];
        } catch (GuzzleException $e) {
            $error = new \Exception("GCash payment failed: " . $e->getMessage(), 0, $e);
            $this->logger->logPaymentError($paymentData['order_id'] ?? 'unknown', $error);
            throw $error;
        }
    }
    
    public function processRefund($transactionId, $amount) {
        try {
            $response = $this->client->post("/v1/payments/{$transactionId}/refund", [
                'json' => [
                    'amount' => $amount,
                    'currency' => 'PHP'
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            return [
                'transaction_id' => $result['id'],
                'status' => $result['status']
            ];
        } catch (GuzzleException $e) {
            $error = new \Exception("GCash refund failed: " . $e->getMessage(), 0, $e);
            $this->logger->logRefundError($transactionId, $error);
            throw $error;
        }
    }
    
    public function checkPaymentStatus($transactionId) {
        try {
            $response = $this->client->get("/v1/payments/{$transactionId}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \Exception("Failed to check payment status: " . $e->getMessage());
        }
    }
}