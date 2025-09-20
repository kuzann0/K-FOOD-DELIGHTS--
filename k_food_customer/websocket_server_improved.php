<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Socket\Server as SocketServer;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class OrderWebSocket implements MessageComponentInterface {
    protected $clients;
    protected $clientTypes;
    protected $conn;
    protected $loop;
    protected $lastCheckTime;
    protected $reconnectAttempts;
    protected $logger;
    protected $maxReconnectAttempts = 5;
    protected $reconnectDelay = 5; // seconds

    public function __construct(LoopInterface $loop) {
        $this->clients = new \SplObjectStorage;
        $this->clientTypes = [];
        $this->loop = $loop;
        $this->lastCheckTime = date('Y-m-d H:i:s');
        $this->reconnectAttempts = 0;
        
        // Set up logging
        $this->setupLogger();
        
        // Initialize database connection
        $this->initDatabaseConnection();
        
        // Set up periodic tasks
        $this->setupPeriodicTasks();
    }

    protected function setupLogger() {
        $logFile = __DIR__ . '/logs/websocket_' . date('Y-m-d') . '.log';
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0777, true);
        }
        $this->logger = fopen($logFile, 'a');
    }

    protected function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        fwrite($this->logger, $logMessage);
        echo $logMessage; // Also output to console
    }

    protected function initDatabaseConnection() {
        try {
            global $host, $user, $password, $dbname;
            $this->conn = new mysqli($host, $user, $password, $dbname);
            if ($this->conn->connect_error) {
                throw new Exception("Database connection failed: " . $this->conn->connect_error);
            }
            $this->conn->set_charset('utf8mb4');
            $this->log("Database connection established successfully");
        } catch (Exception $e) {
            $this->log("Database connection error: " . $e->getMessage(), 'ERROR');
            $this->scheduleReconnect();
        }
    }

    protected function setupPeriodicTasks() {
        // Check for new orders every 5 seconds
        $this->loop->addPeriodicTimer(5, function () {
            $this->checkNewOrders();
        });

        // Ping clients every 30 seconds to keep connections alive
        $this->loop->addPeriodicTimer(30, function () {
            $this->pingClients();
        });

        // Clean up stale connections every 5 minutes
        $this->loop->addPeriodicTimer(300, function () {
            $this->cleanupStaleConnections();
        });
    }

    protected function scheduleReconnect() {
        if ($this->reconnectAttempts < $this->maxReconnectAttempts) {
            $this->reconnectAttempts++;
            $this->log("Scheduling database reconnection attempt {$this->reconnectAttempts} in {$this->reconnectDelay} seconds", 'WARN');
            
            $this->loop->addTimer($this->reconnectDelay, function () {
                $this->initDatabaseConnection();
            });
        } else {
            $this->log("Max reconnection attempts reached. Server needs manual intervention.", 'CRITICAL');
            // Notify admin clients about the critical database issue
            $this->broadcastToAdmins([
                'type' => 'system_error',
                'message' => 'Database connection lost. Manual intervention required.'
            ]);
        }
    }

    protected function pingClients() {
        $ping = json_encode(['type' => 'ping', 'timestamp' => time()]);
        foreach ($this->clients as $client) {
            try {
                $client->send($ping);
            } catch (Exception $e) {
                $this->log("Error pinging client {$client->resourceId}: " . $e->getMessage(), 'ERROR');
                $this->clients->detach($client);
            }
        }
    }

    protected function cleanupStaleConnections() {
        foreach ($this->clients as $client) {
            if (!$client->isConnected()) {
                $this->log("Removing stale connection: {$client->resourceId}");
                $this->clients->detach($client);
                unset($this->clientTypes[$client->resourceId]);
            }
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $this->clientTypes[$conn->resourceId] = 'guest';
        $this->log("New connection established: {$conn->resourceId}");
        
        // Send welcome message with connection info
        $conn->send(json_encode([
            'type' => 'welcome',
            'message' => 'Connected to KFoodDelights WebSocket Server',
            'clientId' => $conn->resourceId,
            'timestamp' => date('Y-m-d H:i:s')
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            if (!$data) {
                throw new Exception("Invalid JSON message format");
            }

            $this->log("Received message from client {$from->resourceId}: " . json_encode($data));

            switch ($data['action'] ?? '') {
                case 'authenticate':
                    $this->handleAuthentication($from, $data);
                    break;

                case 'update_order':
                    $this->handleOrderUpdate($from, $data);
                    break;

                case 'subscribe':
                    $this->handleSubscription($from, $data);
                    break;

                case 'pong':
                    // Handle ping response
                    break;

                default:
                    throw new Exception("Unknown action: " . ($data['action'] ?? 'not specified'));
            }
        } catch (Exception $e) {
            $this->log("Error processing message: " . $e->getMessage(), 'ERROR');
            $this->sendError($from, $e->getMessage());
        }
    }

    protected function handleAuthentication(ConnectionInterface $client, array $data) {
        if (!isset($data['type']) || !in_array($data['type'], ['crew', 'admin', 'customer'])) {
            throw new Exception("Invalid authentication type");
        }

        $this->clientTypes[$client->resourceId] = $data['type'];
        $this->log("Client {$client->resourceId} authenticated as {$data['type']}");
        
        $client->send(json_encode([
            'type' => 'authentication',
            'status' => 'success',
            'message' => "Successfully authenticated as {$data['type']}",
            'timestamp' => date('Y-m-d H:i:s')
        ]));
    }

    protected function handleOrderUpdate(ConnectionInterface $from, array $data) {
        if (!isset($this->clientTypes[$from->resourceId]) || 
            !in_array($this->clientTypes[$from->resourceId], ['crew', 'admin'])) {
            throw new Exception("Unauthorized: Only crew and admin can update orders");
        }

        if (!isset($data['order_id'], $data['status'])) {
            throw new Exception("Missing required fields: order_id and status");
        }

        $this->updateOrderStatus($data['order_id'], $data['status']);
    }

    protected function handleSubscription(ConnectionInterface $from, array $data) {
        if (!isset($data['topics']) || !is_array($data['topics'])) {
            throw new Exception("Invalid subscription request");
        }

        $allowedTopics = ['orders', 'updates', 'system'];
        $validTopics = array_intersect($data['topics'], $allowedTopics);

        if (empty($validTopics)) {
            throw new Exception("No valid topics specified");
        }

        // Store subscription preferences
        foreach ($validTopics as $topic) {
            $this->subscriptions[$from->resourceId][] = $topic;
        }

        $from->send(json_encode([
            'type' => 'subscription',
            'status' => 'success',
            'topics' => $validTopics,
            'timestamp' => date('Y-m-d H:i:s')
        ]));
    }

    protected function updateOrderStatus($orderId, $status) {
        $validStatuses = ['pending', 'preparing', 'ready', 'delivering', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Invalid status: $status");
        }

        try {
            $this->conn->begin_transaction();

            $stmt = $this->conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $status, $orderId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update order status: " . $stmt->error);
            }

            // Log the status change
            $stmt = $this->conn->prepare("INSERT INTO order_status_logs (order_id, status, changed_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("is", $orderId, $status);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to log status change: " . $stmt->error);
            }

            $this->conn->commit();
            $this->broadcastOrderUpdate($orderId, $status);
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    protected function broadcastOrderUpdate($orderId, $status) {
        $query = "
            SELECT o.*, u.first_name, u.last_name, u.phone,
                   GROUP_CONCAT(DISTINCT CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as items
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.product_id
            WHERE o.id = ?
            GROUP BY o.id
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();

            if (!$order) {
                throw new Exception("Order not found: $orderId");
            }

            $updateData = [
                'type' => 'order_update',
                'order' => [
                    'order_id' => $order['id'],
                    'order_number' => $order['order_number'],
                    'status' => $status,
                    'customer_name' => $order['first_name'] . ' ' . $order['last_name'],
                    'customer_phone' => $order['phone'],
                    'items' => $order['items'],
                    'total_amount' => $order['total_amount'],
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ];

            $this->broadcastMessage($updateData);

        } catch (Exception $e) {
            $this->log("Error broadcasting order update: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    protected function broadcastMessage($data, $targetTypes = ['crew', 'admin']) {
        $message = json_encode($data);
        $sent = 0;
        $failed = 0;

        foreach ($this->clients as $client) {
            if (isset($this->clientTypes[$client->resourceId]) && 
                in_array($this->clientTypes[$client->resourceId], $targetTypes)) {
                try {
                    $client->send($message);
                    $sent++;
                } catch (Exception $e) {
                    $this->log("Failed to send to client {$client->resourceId}: " . $e->getMessage(), 'ERROR');
                    $failed++;
                }
            }
        }

        $this->log("Broadcast complete - Sent: $sent, Failed: $failed");
    }

    protected function broadcastToAdmins($data) {
        $this->broadcastMessage($data, ['admin']);
    }

    protected function sendError(ConnectionInterface $client, $message) {
        try {
            $client->send(json_encode([
                'type' => 'error',
                'message' => $message,
                'timestamp' => date('Y-m-d H:i:s')
            ]));
        } catch (Exception $e) {
            $this->log("Failed to send error message to client {$client->resourceId}: " . $e->getMessage(), 'ERROR');
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $type = $this->clientTypes[$conn->resourceId] ?? 'unknown';
        unset($this->clientTypes[$conn->resourceId]);
        $this->log("Client disconnected: {$conn->resourceId} (Type: $type)");
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->log("Error occurred for client {$conn->resourceId}: " . $e->getMessage(), 'ERROR');
        $conn->close();
    }

    protected function checkNewOrders() {
        if (!$this->conn || $this->conn->connect_error) {
            $this->log("Database connection not available for checking new orders", 'ERROR');
            return;
        }

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
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to check new orders: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $newOrders = [];
            
            while ($order = $result->fetch_assoc()) {
                $this->broadcastNewOrder($order);
                $newOrders[] = $order['id'];
            }
            
            if (!empty($newOrders)) {
                $this->log("Broadcasted " . count($newOrders) . " new orders: " . implode(', ', $newOrders));
            }
            
            $this->lastCheckTime = date('Y-m-d H:i:s');
            
        } catch (Exception $e) {
            $this->log("Error checking new orders: " . $e->getMessage(), 'ERROR');
            if (stripos($e->getMessage(), 'connection') !== false) {
                $this->scheduleReconnect();
            }
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

        $this->broadcastMessage($orderData);
    }

    public function __destruct() {
        if ($this->logger) {
            fclose($this->logger);
        }
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Create log directory if it doesn't exist
$logDir = __DIR__ . '/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}

// Create event loop
$loop = Factory::create();

// Create WebSocket handler
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