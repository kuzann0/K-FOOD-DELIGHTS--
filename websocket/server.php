<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Composer autoloader
require __DIR__ . '/../k_food_customer/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Initialize logger
$logger = new Logger('websocket');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/websocket.log', Logger::DEBUG));

class OrderWebSocketServer implements \Ratchet\MessageComponentInterface {
    protected $clients;
    protected $users;
    protected $logger;
    protected $sessions;
    
    public function __construct($logger) {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
        $this->logger = $logger;
        $this->sessions = [];
        
        $this->logger->info('WebSocket server initialized');
    }
    
    public function onOpen(\Ratchet\ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New client connected! ({$conn->resourceId})\n";
    }
    
    public function onMessage(\Ratchet\ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['type'])) {
            return;
        }
        
        switch ($data['type']) {
            case 'order_update':
                $this->broadcastOrderUpdate($data['order']);
                break;
            case 'payment_update':
                $this->broadcastPaymentUpdate($data['payment']);
                break;
        }
    }
    
    public function onClose(\Ratchet\ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Client disconnected! ({$conn->resourceId})\n";
    }
    
    public function onError(\Ratchet\ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
    
    protected function broadcastOrderUpdate($orderData) {
        foreach ($this->clients as $client) {
            $client->send(json_encode([
                'type' => 'order_update',
                'data' => $orderData
            ]));
        }
    }
    
    protected function broadcastPaymentUpdate($paymentData) {
        foreach ($this->clients as $client) {
            $client->send(json_encode([
                'type' => 'payment_update',
                'data' => $paymentData
            ]));
        }
    }
}

// Create WebSocket server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new OrderWebSocketServer($logger)
        )
    ),
    8080
);

echo "WebSocket server started on port 8080\n";
$server->run();