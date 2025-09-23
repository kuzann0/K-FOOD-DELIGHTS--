<?php
namespace KFoodDelights\WebSocket;

use Ratchet\ConnectionInterface;
use KFoodDelights\Payment\PaymentManager;

/**
 * Handles real-time payment processing and notifications
 */
class PaymentWebSocketServer extends BaseWebSocketServer {
    private $paymentManager;
    private $userConnections;
    private $paymentSessions;
    
    public function __construct($logger) {
        parent::__construct($logger);
        $this->paymentManager = PaymentManager::getInstance();
        $this->userConnections = [];
        $this->paymentSessions = [];
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['type'])) {
                throw new \Exception("Invalid message format");
            }
            
            switch ($data['type']) {
                case 'init_payment':
                    $this->handlePaymentInitiation($from, $data);
                    break;
                    
                case 'payment_update':
                    $this->handlePaymentUpdate($from, $data);
                    break;
                    
                case 'verify_payment':
                    $this->handlePaymentVerification($from, $data);
                    break;
                    
                case 'ping':
                    $this->handlePing($from);
                    break;
                    
                default:
                    $this->handleMessage($from, $data);
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in PaymentWebSocketServer: " . $e->getMessage());
            $this->sendMessage($from, [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    protected function handleMessage(ConnectionInterface $from, $payload) {
        $this->logger->debug("Received message in payment server", ['payload' => $payload]);
    }
    
    private function handlePaymentInitiation($from, $data) {
        if (!isset($data['orderId']) || !isset($data['paymentMethod'])) {
            throw new \Exception("Invalid payment initiation data");
        }
        
        // Create payment session
        $sessionId = uniqid('pay_', true);
        $this->paymentSessions[$from->resourceId] = [
            'sessionId' => $sessionId,
            'orderId' => $data['orderId'],
            'method' => $data['paymentMethod'],
            'status' => 'initiated',
            'timestamp' => time()
        ];
        
        // Send payment initialization response
        $this->sendMessage($from, [
            'type' => 'payment_initiated',
            'sessionId' => $sessionId,
            'orderId' => $data['orderId']
        ]);
    }
    
    private function handlePaymentUpdate($from, $data) {
        if (!isset($this->paymentSessions[$from->resourceId])) {
            throw new \Exception("No active payment session");
        }
        
        $session = $this->paymentSessions[$from->resourceId];
        
        // Process payment update
        $result = $this->paymentManager->processPayment(
            $session['orderId'],
            array_merge($data, ['gateway' => $session['method']])
        );
        
        // Update session status
        $this->paymentSessions[$from->resourceId]['status'] = $result['status'];
        
        // Send response
        $this->sendMessage($from, [
            'type' => 'payment_processed',
            'status' => $result['status'],
            'transactionId' => $result['transaction_id'] ?? null,
            'message' => $result['message'] ?? null
        ]);
    }
    
    private function handlePaymentVerification($from, $data) {
        if (!isset($data['transactionId'])) {
            throw new \Exception("Transaction ID required for verification");
        }
        
        // Verify payment status
        $status = $this->paymentManager->getPaymentStatus($data['transactionId']);
        
        $this->sendMessage($from, [
            'type' => 'payment_verified',
            'transactionId' => $data['transactionId'],
            'status' => $status
        ]);
    }
    
    public function onClose(ConnectionInterface $conn) {
        parent::onClose($conn);
        
        // Clean up payment session
        if (isset($this->paymentSessions[$conn->resourceId])) {
            $session = $this->paymentSessions[$conn->resourceId];
            if ($session['status'] === 'initiated') {
                // Cancel any pending payments
                $this->paymentManager->cancelPayment($session['orderId']);
            }
            unset($this->paymentSessions[$conn->resourceId]);
        }
    }
}