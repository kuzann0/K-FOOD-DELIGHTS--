<?php
require_once __DIR__ . '/../k_food_customer/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class WebSocketHealthChecker {
    private $logger;
    private $ports = [
        'order' => 8080,
        'payment' => 8081
    ];
    
    public function __construct() {
        $this->logger = new Logger('websocket_health');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
    }
    
    public function checkServers() {
        $allHealthy = true;
        
        foreach ($this->ports as $server => $port) {
            echo "\nChecking $server server on port $port...\n";
            
            if ($this->isPortInUse($port)) {
                if ($this->testWebSocketConnection($port)) {
                    echo "✓ $server server is running and accepting connections\n";
                } else {
                    echo "✗ $server server is running but not accepting WebSocket connections\n";
                    $allHealthy = false;
                }
            } else {
                echo "✗ $server server is not running\n";
                $allHealthy = false;
            }
        }
        
        return $allHealthy;
    }
    
    private function isPortInUse($port) {
        $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }
        return false;
    }
    
    private function testWebSocketConnection($port) {
        try {
            $client = new \WebSocket\Client("ws://127.0.0.1:$port");
            $client->send(json_encode(['type' => 'ping']));
            $response = $client->receive();
            $client->close();
            
            $data = json_decode($response, true);
            return isset($data['type']) && $data['type'] === 'pong';
        } catch (\Exception $e) {
            $this->logger->error("WebSocket connection test failed: " . $e->getMessage());
            return false;
        }
    }
}

// Run health check
$checker = new WebSocketHealthChecker();
$isHealthy = $checker->checkServers();

exit($isHealthy ? 0 : 1);