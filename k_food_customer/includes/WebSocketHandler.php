<?php
require_once __DIR__ . '/../vendor/autoload.php';

use WebSocket\Client;
use WebSocket\ConnectionException;

class WebSocketHandler {
    private $wsHost;
    private $wsPort;
    private $wsClient;

    public function __construct($host = 'localhost', $port = 8080) {
        $this->wsHost = $host;
        $this->wsPort = $port;
    }

    public function broadcastNewOrder($order) {
        try {
            // Prepare order data for broadcast
            $message = json_encode([
                'type' => 'new_order',
                'order' => [
                    'order_id' => $order['order_id'],
                    'order_number' => $order['order_number'],
                    'customer_name' => $order['customer_name'],
                    'total_amount' => $order['total_amount'],
                    'status' => $order['status'],
                    'items' => $order['items'] ?? [],
                    'delivery_address' => $order['delivery_address'],
                    'contact_number' => $order['contact_number'],
                    'special_instructions' => $order['special_instructions'],
                    'created_at' => $order['created_at'] ?? date('Y-m-d H:i:s')
                ]
            ]);

            // Send to WebSocket server
            $this->sendToWebSocketServer($message);
            
            return true;
        } catch (Exception $e) {
            error_log("WebSocket broadcast error: " . $e->getMessage());
            // Don't throw - this is a non-critical operation
            return false;
        }
    }

    public function broadcastOrderUpdate($order) {
        try {
            // Prepare order update data
            $message = json_encode([
                'type' => 'order_update',
                'order' => [
                    'order_id' => $order['order_id'],
                    'order_number' => $order['order_number'],
                    'status' => $order['status'],
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ]);

            // Send to WebSocket server
            $this->sendToWebSocketServer($message);
            
            return true;
        } catch (Exception $e) {
            error_log("WebSocket update broadcast error: " . $e->getMessage());
            return false;
        }
    }

    private function sendToWebSocketServer($message) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            
            $context = stream_context_create();
            $this->wsClient = new \WebSocket\Client(
                "ws://{$this->wsHost}:{$this->wsPort}",
                [
                    'timeout' => 10,
                    'context' => $context
                ]
            );

            $this->wsClient->send($message);
            $this->wsClient->close();
            return true;
        } catch (\WebSocket\ConnectionException $e) {
            error_log("WebSocket connection error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("WebSocket general error: " . $e->getMessage());
            return false;
        }
    }

    private function storeMessageForPolling($message) {
        global $conn;
        if (!$conn) return;

        try {
            $decoded = json_decode($message, true);
            if (!$decoded) return;

            $stmt = $conn->prepare("
                INSERT INTO order_notifications 
                (type, order_id, message, created_at) 
                VALUES (?, ?, ?, NOW())
            ");

            $type = $decoded['type'];
            $orderId = $decoded['order']['order_id'];
            $stmt->bind_param("sis", $type, $orderId, $message);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to store notification: " . $e->getMessage());
        }
    }
}