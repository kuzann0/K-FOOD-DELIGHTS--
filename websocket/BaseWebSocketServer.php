<?php
namespace KFoodDelights\WebSocket;

require_once __DIR__ . '/../k_food_customer/vendor/autoload.php';

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Monolog\Logger;
use KFoodDelights\Payment\PaymentManager;
use KFoodDelights\Order\OrderManager;

/**
 * Base WebSocket Server class with common functionality
 */
abstract class BaseWebSocketServer implements MessageComponentInterface {
    protected $clients;
    protected $logger;
    protected $authTokens;
    
    public function __construct(Logger $logger) {
        $this->clients = new \SplObjectStorage;
        $this->logger = $logger;
        $this->authTokens = [];
    }
    
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $this->logger->info("New connection: {$conn->resourceId}");
        
        // Send welcome message
        $this->sendMessage($conn, [
            'type' => 'connection',
            'status' => 'connected',
            'serverId' => static::class
        ]);
    }
    
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $this->logger->info("Connection closed: {$conn->resourceId}");
        
        // Clean up any auth tokens
        unset($this->authTokens[$conn->resourceId]);
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->logger->error("Error occurred: " . $e->getMessage(), [
            'connection' => $conn->resourceId,
            'trace' => $e->getTraceAsString()
        ]);
        $conn->close();
    }
    
    protected function sendMessage(ConnectionInterface $conn, array $data) {
        try {
            $conn->send(json_encode($data));
        } catch (\Exception $e) {
            $this->logger->error("Failed to send message: " . $e->getMessage());
        }
    }
    
    protected function broadcast(array $data, ConnectionInterface $except = null) {
        foreach ($this->clients as $client) {
            if ($except === null || $client !== $except) {
                $this->sendMessage($client, $data);
            }
        }
    }
    
    protected function validateAuth($token) {
        // Implement token validation logic
        return isset($this->authTokens[$token]);
    }
    
    protected function handlePing(ConnectionInterface $from) {
        $this->sendMessage($from, ['type' => 'pong', 'timestamp' => time()]);
    }
    
    abstract protected function handleMessage(ConnectionInterface $from, $payload);
}