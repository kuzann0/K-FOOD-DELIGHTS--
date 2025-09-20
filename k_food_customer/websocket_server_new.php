<?php
namespace App\WebSocket;

require 'vendor/autoload.php';
require_once 'config.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Socket\Server as SocketServer;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use \mysqli;
use \Exception;

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
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $this->lastCheckTime);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($order = $result->fetch_assoc()) {
                $this->broadcastNewOrder($order);
            }
            
            $this->lastCheckTime = date('Y-m-d H:i:s');
        } catch (\Exception $e) {
            echo "Error checking new orders: " . $e->getMessage() . "\n";
        }
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
            if (isset($this->clientTypes[$client->resourceId]) && 
                ($this->clientTypes[$client->resourceId] === 'crew' || 
                 $this->clientTypes[$client->resourceId] === 'admin')) {
                try {
                    $client->send($message);
                } catch (\Exception $e) {
                    echo "Error sending to client {$client->resourceId}: " . $e->getMessage() . "\n";
                }
            }
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $this->clientTypes[$conn->resourceId] = 'guest';
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            if (!$data) {
                throw new \Exception("Invalid message format");
            }

            switch ($data['action'] ?? '') {
                case 'authenticate':
                    if (isset($data['type']) && in_array($data['type'], ['crew', 'admin', 'customer'])) {
                        $this->clientTypes[$from->resourceId] = $data['type'];
                        $from->send(json_encode([
                            'type' => 'authentication',
                            'status' => 'success',
                            'message' => 'Successfully authenticated'
                        ]));
                    }
                    break;

                case 'update_order':
                    if (!isset($this->clientTypes[$from->resourceId]) || 
                        !in_array($this->clientTypes[$from->resourceId], ['crew', 'admin'])) {
                        throw new \Exception("Unauthorized action");
                    }
                    
                    if (isset($data['order_id'], $data['status'])) {
                        $this->updateOrderStatus($data['order_id'], $data['status']);
                    }
                    break;

                default:
                    echo "Unknown action received\n";
            }
        } catch (\Exception $e) {
            echo "Error processing message: " . $e->getMessage() . "\n";
            $from->send(json_encode([
                'type' => 'error',
                'message' => $e->getMessage()
            ]));
        }
    }

    protected function updateOrderStatus($orderId, $status) {
        $validStatuses = ['pending', 'preparing', 'ready', 'delivering', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception("Invalid status");
        }

        $stmt = $this->conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $orderId);
        
        if ($stmt->execute()) {
            $this->broadcastOrderUpdate($orderId, $status);
        } else {
            throw new \Exception("Failed to update order status");
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        unset($this->clientTypes[$conn->resourceId]);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function broadcastOrderUpdate($orderId, $status) {
        $query = "
            SELECT o.*, u.first_name, u.last_name
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            WHERE o.id = ?
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();

            if ($order) {
                $updateData = [
                    'type' => 'order_update',
                    'order' => [
                        'order_id' => $order['id'],
                        'order_number' => $order['order_number'],
                        'status' => $status,
                        'customer_name' => $order['first_name'] . ' ' . $order['last_name'],
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                ];

                $message = json_encode($updateData);
                foreach ($this->clients as $client) {
                    try {
                        $client->send($message);
                    } catch (\Exception $e) {
                        echo "Error sending update to client {$client->resourceId}: " . $e->getMessage() . "\n";
                    }
                }
            }
        } catch (\Exception $e) {
            echo "Error broadcasting order update: " . $e->getMessage() . "\n";
        }
    }
}

// Create event loop and WebSocket handler
$loop = Factory::create();
$webSocket = new OrderWebSocket($loop);

// Create server socket
$socket = new SocketServer('0.0.0.0:8080', $loop);

// Create WebSocket server
$server = new IoServer(
    new HttpServer(
        new WsServer($webSocket)
    ),
    $socket
);

echo "WebSocket server started on port 8080\n";
$loop->run();