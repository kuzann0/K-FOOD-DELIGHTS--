<?php
namespace KFood\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocketServer implements MessageComponentInterface {
    protected $clients;
    protected $subscriptions;
    protected $authTokens;
    protected $heartbeats;
    protected $messageQueue;
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
        $this->authTokens = [];
        $this->heartbeats = [];
        $this->messageQueue = [];
    }
    
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $this->heartbeats[$conn->resourceId] = time();
        echo "New connection! ({$conn->resourceId})\n";
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data) {
            $this->sendError($from, "Invalid message format");
            return;
        }
        
        // Update heartbeat
        $this->heartbeats[$from->resourceId] = time();
        
        switch ($data['type']) {
            case 'auth':
                $this->handleAuth($from, $data);
                break;
            case 'subscribe':
                $this->handleSubscribe($from, $data);
                break;
            case 'heartbeat':
                $this->handleHeartbeat($from);
                break;
            case 'order_update':
                $this->handleOrderUpdate($from, $data);
                break;
            default:
                $this->handleGenericMessage($from, $data);
        }
    }
    
    protected function handleAuth($conn, $data) {
        if (!isset($data['token'])) {
            $this->sendError($conn, "Authentication token required");
            return;
        }
        
        try {
            // Verify token with your authentication system
            if ($this->verifyAuthToken($data['token'])) {
                $this->authTokens[$conn->resourceId] = $data['token'];
                $this->sendSuccess($conn, "Authentication successful");
            } else {
                $this->sendError($conn, "Invalid authentication token");
                $conn->close();
            }
        } catch (\Exception $e) {
            $this->sendError($conn, "Authentication failed");
            $conn->close();
        }
    }
    
    protected function handleSubscribe($conn, $data) {
        if (!isset($this->authTokens[$conn->resourceId])) {
            $this->sendError($conn, "Authentication required");
            return;
        }
        
        if (!isset($data['channel'])) {
            $this->sendError($conn, "Channel required for subscription");
            return;
        }
        
        $channel = $data['channel'];
        if (!isset($this->subscriptions[$channel])) {
            $this->subscriptions[$channel] = new \SplObjectStorage;
        }
        
        $this->subscriptions[$channel]->attach($conn);
        $this->sendSuccess($conn, "Subscribed to $channel");
    }
    
    protected function handleOrderUpdate($from, $data) {
        if (!isset($this->authTokens[$from->resourceId])) {
            $this->sendError($from, "Authentication required");
            return;
        }
        
        if (!isset($data['orderId']) || !isset($data['status'])) {
            $this->sendError($from, "Invalid order update data");
            return;
        }
        
        // Broadcast to relevant subscribers
        $this->broadcast('orders', $data, $from);
    }
    
    protected function broadcast($channel, $data, $except = null) {
        if (!isset($this->subscriptions[$channel])) {
            return;
        }
        
        foreach ($this->subscriptions[$channel] as $client) {
            if ($except !== $client) {
                $client->send(json_encode($data));
            }
        }
    }
    
    protected function verifyAuthToken($token) {
        // Implement your token verification logic
        return true; // Temporary placeholder
    }
    
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        unset($this->authTokens[$conn->resourceId]);
        unset($this->heartbeats[$conn->resourceId]);
        
        // Remove from all subscriptions
        foreach ($this->subscriptions as $channel => $subscribers) {
            $subscribers->detach($conn);
        }
        
        echo "Connection {$conn->resourceId} has disconnected\n";
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
    
    protected function sendError($conn, $message) {
        $conn->send(json_encode([
            'type' => 'error',
            'message' => $message
        ]));
    }
    
    protected function sendSuccess($conn, $message) {
        $conn->send(json_encode([
            'type' => 'success',
            'message' => $message
        ]));
    }

    protected function handleGenericMessage($conn, $data) {
        if (!isset($this->authTokens[$conn->resourceId])) {
            $this->sendError($conn, "Authentication required");
            return;
        }
        $this->sendSuccess($conn, "Message received");
    }

    protected function handleHeartbeat($conn) {
        $this->heartbeats[$conn->resourceId] = time();
        $this->sendSuccess($conn, "Heartbeat acknowledged");
    }
    
    public function cleanup() {
        $now = time();
        foreach ($this->heartbeats as $id => $lastBeat) {
            if ($now - $lastBeat > 30) { // 30 seconds timeout
                foreach ($this->clients as $client) {
                    if ($client->resourceId == $id) {
                        $client->close();
                        break;
                    }
                }
            }
        }
    }
}