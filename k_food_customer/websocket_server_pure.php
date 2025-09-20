<?php
require_once 'config.php';

class OrderWebSocketServer {
    private $socket;
    private $clients = [];
    private $clientTypes = [];
    private $conn;
    private $lastCheckTime;

    public function __construct() {
        $this->lastCheckTime = date('Y-m-d H:i:s');
        
        // Connect to database
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }
        
        // Create WebSocket server
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->socket, '0.0.0.0', 8080);
        socket_listen($this->socket);
        
        $this->run();
    }

    private function handshake($client) {
        $request = socket_read($client, 5000);
        preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches);
        
        if (isset($matches[1])) {
            $key = base64_encode(pack(
                'H*',
                sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
            ));
            
            $headers = "HTTP/1.1 101 Switching Protocols\r\n";
            $headers .= "Upgrade: websocket\r\n";
            $headers .= "Connection: Upgrade\r\n";
            $headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";
            socket_write($client, $headers, strlen($headers));
            
            return true;
        }
        
        return false;
    }

    private function decodeMessage($data) {
        $length = ord($data[1]) & 127;
        
        if ($length == 126) {
            $masks = substr($data, 4, 4);
            $data = substr($data, 8);
        } elseif ($length == 127) {
            $masks = substr($data, 10, 4);
            $data = substr($data, 14);
        } else {
            $masks = substr($data, 2, 4);
            $data = substr($data, 6);
        }
        
        $text = '';
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }
        return $text;
    }

    private function encodeMessage($text) {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);
        
        if ($length <= 125)
            $header = pack('CC', $b1, $length);
        elseif ($length > 125 && $length < 65536)
            $header = pack('CCn', $b1, 126, $length);
        else
            $header = pack('CCNN', $b1, 127, $length);
        
        return $header . $text;
    }

    private function processMessage($client, $message) {
        try {
            $data = json_decode($message, true);
            if (!$data) {
                throw new Exception("Invalid message format");
            }

            $clientId = array_search($client, $this->clients);
            
            switch ($data['action'] ?? '') {
                case 'authenticate':
                    if (isset($data['type']) && in_array($data['type'], ['crew', 'admin', 'customer'])) {
                        $this->clientTypes[$clientId] = $data['type'];
                        $this->sendToClient($client, json_encode([
                            'type' => 'authentication',
                            'status' => 'success',
                            'message' => 'Successfully authenticated'
                        ]));
                    }
                    break;

                case 'update_order':
                    if (!isset($this->clientTypes[$clientId]) || 
                        !in_array($this->clientTypes[$clientId], ['crew', 'admin'])) {
                        throw new Exception("Unauthorized action");
                    }
                    
                    if (isset($data['order_id'], $data['status'])) {
                        $this->updateOrderStatus($data['order_id'], $data['status']);
                    }
                    break;

                case 'check_orders':
                    if (!isset($this->clientTypes[$clientId]) || 
                        !in_array($this->clientTypes[$clientId], ['crew', 'admin'])) {
                        throw new Exception("Unauthorized action");
                    }
                    $this->checkNewOrders();
                    break;
            }
        } catch (Exception $e) {
            $this->sendToClient($client, json_encode([
                'type' => 'error',
                'message' => $e->getMessage()
            ]));
        }
    }

    private function sendToClient($client, $message) {
        $encoded = $this->encodeMessage($message);
        socket_write($client, $encoded, strlen($encoded));
    }

    private function broadcast($message, $roles = null) {
        $encoded = $this->encodeMessage($message);
        foreach ($this->clients as $id => $client) {
            if (!$roles || 
                (isset($this->clientTypes[$id]) && in_array($this->clientTypes[$id], $roles))) {
                socket_write($client, $encoded, strlen($encoded));
            }
        }
    }

    private function updateOrderStatus($orderId, $status) {
        $validStatuses = ['pending', 'preparing', 'ready', 'delivering', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Invalid status");
        }

        $stmt = $this->conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $orderId);
        
        if ($stmt->execute()) {
            $this->broadcastOrderUpdate($orderId, $status);
            return true;
        }
        throw new Exception("Failed to update order status");
    }

    private function broadcastOrderUpdate($orderId, $status) {
        $query = "
            SELECT o.*, u.first_name, u.last_name
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            WHERE o.id = ?
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $orderId);
        
        if ($stmt->execute()) {
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

                $this->broadcast(json_encode($updateData));
            }
        }
    }

    private function checkNewOrders() {
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
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                
                while ($order = $result->fetch_assoc()) {
                    $this->broadcastNewOrder($order);
                }
                
                $this->lastCheckTime = date('Y-m-d H:i:s');
            }
        } catch (Exception $e) {
            echo "Error checking new orders: " . $e->getMessage() . "\n";
        }
    }

    private function broadcastNewOrder($order) {
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

        $this->broadcast(json_encode($orderData), ['crew', 'admin']);
    }

    public function run() {
        echo "WebSocket server running on port 8080...\n";
        
        while (true) {
            $changed = $this->clients;
            $changed[] = $this->socket;
            
            socket_select($changed, $null, $null, 0, 10);
            
            // Check for new connections
            if (in_array($this->socket, $changed)) {
                $newClient = socket_accept($this->socket);
                if ($this->handshake($newClient)) {
                    echo "New client connected\n";
                    $this->clients[] = $newClient;
                }
                
                $key = array_search($this->socket, $changed);
                unset($changed[$key]);
            }
            
            // Check for client messages
            foreach ($changed as $key => $client) {
                $data = @socket_read($client, 1024, PHP_NORMAL_READ);
                
                if ($data === false) {
                    echo "Client disconnected\n";
                    unset($this->clients[array_search($client, $this->clients)]);
                    unset($this->clientTypes[array_search($client, $this->clients)]);
                    continue;
                }
                
                if (strlen($data) > 0) {
                    $message = $this->decodeMessage($data);
                    $this->processMessage($client, $message);
                }
            }
            
            // Check for new orders periodically
            if (time() % 10 == 0) {
                $this->checkNewOrders();
            }
        }
    }
}

// Create and run server
$server = new OrderWebSocketServer();