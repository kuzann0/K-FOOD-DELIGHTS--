<?php
require 'vendor/autoload.php';
require_once 'config.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\Server;
use React\EventLoop\LoopInterface;

class OrderWebSocket implements MessageComponentInterface {
    protected $clients;
    protected $subscriptions;
    protected $clientTypes;
    protected $conn;
    protected $loop;
    protected $lastCheckTime;

    public function __construct(LoopInterface $loop) {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
        $this->clientTypes = [];
        $this->loop = $loop;
        $this->lastCheckTime = date('Y-m-d H:i:s');
        
        // Connect to database
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }
        
        // Set up periodic order checks
        $this->loop->addPeriodicTimer(10, function () {
            $this->checkNewOrders();
        });
    }
    
    protected function checkNewOrders() {
        $query = "
            SELECT o.*, u.first_name, u.last_name, u.phone,
                   GROUP_CONCAT(
                       CONCAT(oi.quantity, 'x ', p.name) 
                       SEPARATOR ', '
                   ) as items
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.product_id
            WHERE o.created_at > ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $this->lastCheckTime);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($order = $result->fetch_assoc()) {
            $this->broadcastNewOrder($order);
        }
        
        $this->lastCheckTime = date('Y-m-d H:i:s');
    }
    
    protected function broadcastNewOrder($order) {
        $orderData = [
            'type' => 'new_order',
            'order' => [
                'order_id' => $order['id'],
                'order_number' => $order['order_number'],
                'customer_name' => $order['first_name'] . ' ' . $order['last_name'],
                'customer_phone' => $order['phone'],
                'items' => $order['items'],
                'total_amount' => $order['total_amount'],
                'delivery_address' => $order['delivery_address'],
                'special_instructions' => $order['special_instructions'],
                'status' => $order['status'],
                'created_at' => $order['created_at']
            ]
        ];

        $message = json_encode($orderData);
        
        foreach ($this->clients as $client) {
            if (isset($this->subscriptions[$client->resourceId])) {
                $role = $this->subscriptions[$client->resourceId];
                if ($role === 'admin' || $role === 'crew') {
                    $client->send($message);
                }
            }
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg);
        
        if ($data && isset($data->action)) {
            switch ($data->action) {
                case 'subscribe':
                    if (isset($data->role)) {
                        $this->subscriptions[$from->resourceId] = $data->role;
                        echo "Client {$from->resourceId} subscribed as {$data->role}\n";
                    }
                    break;
                    
                case 'order_update':
                    if (isset($data->orderId, $data->status)) {
                        $this->broadcastOrderUpdate($data->orderId, $data->status);
                    }
                    break;
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        unset($this->subscriptions[$conn->resourceId]);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    public function broadcastOrderUpdate($orderId, $status) {
        // Get order details from database
        $stmt = $this->conn->prepare("
            SELECT o.*, u.first_name, u.last_name, u.phone
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            WHERE o.id = ?
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        
        if (!$order) return;
        
        $orderData = [
            'orderId' => $order['id'],
            'orderNumber' => $order['order_number'],
            'status' => $status,
            'customerName' => $order['first_name'] . ' ' . $order['last_name'],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $message = json_encode([
            'type' => 'order_update',
            'data' => $orderData
        ]);
        
        foreach ($this->clients as $client) {
            if (isset($this->subscriptions[$client->resourceId])) {
                $role = $this->subscriptions[$client->resourceId];
                if ($role === 'admin' || $role === 'crew') {
                    $client->send($message);
                }
            }
        }
    }
}

// Create event loop and socket server
$loop = Factory::create();
$webSocket = new OrderWebSocket();

// Create socket server with SSL/TLS support
$secureServer = new Server('0.0.0.0:3000', $loop);
$secureServer = new SecureServer($secureServer, $loop, [
    'local_cert' => '/path/to/your/certificate.pem',
    'local_pk' => '/path/to/your/private.key',
    'verify_peer' => false
]);

// Create WebSocket server
$webServer = new IoServer(
    new HttpServer(
        new WsServer($webSocket)
    ),
    $secureServer,
    $loop
);

echo "WebSocket server started on port 3000\n";
$loop->run();