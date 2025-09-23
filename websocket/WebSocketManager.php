<?php
namespace KFoodDelights\WebSocket;

require_once __DIR__ . '/../k_food_customer/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;

class WebSocketManager {
    private $logger;
    private $servers = [];
    private $ports = [
        'orders' => 8080,
        'payments' => 8081
    ];
    
    public function __construct() {
        $this->initializeLogger();
    }
    
    private function initializeLogger() {
        $this->logger = new Logger('websocket_manager');
        
        // Add rotating file handler
        $this->logger->pushHandler(
            new RotatingFileHandler(
                __DIR__ . '/../logs/websocket.log',
                0,
                Logger::DEBUG
            )
        );
        
        // Add stream handler for console output
        $this->logger->pushHandler(
            new StreamHandler('php://stdout', Logger::INFO)
        );
    }
    
    public function startServers() {
        try {
            $this->logger->info("Starting WebSocket servers...");
            
            // Start Order WebSocket Server
            $orderServer = IoServer::factory(
                new HttpServer(
                    new WsServer(
                        new OrderWebSocketServer($this->logger)
                    )
                ),
                $this->ports['orders']
            );
            
            $this->logger->info("Order WebSocket server started on port {$this->ports['orders']}");
            
            // Start Payment WebSocket Server
            $paymentServer = IoServer::factory(
                new HttpServer(
                    new WsServer(
                        new PaymentWebSocketServer($this->logger)
                    )
                ),
                $this->ports['payments']
            );
            
            $this->logger->info("Payment WebSocket server started on port {$this->ports['payments']}");
            
            // Store server instances
            $this->servers['orders'] = $orderServer;
            $this->servers['payments'] = $paymentServer;
            
            // Create event loop for handling multiple servers
            $loop = Loop::get();
            
            // Add periodic health check
            $loop->addPeriodicTimer(30, function () {
                $this->performHealthCheck();
            });
            
            $this->logger->info("All WebSocket servers started successfully");
            $this->logger->info("Press Ctrl+C to stop the servers");
            
            // Run the event loop
            $loop->run();
            
        } catch (\Exception $e) {
            $this->logger->critical("Failed to start WebSocket servers: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function performHealthCheck() {
        $this->logger->debug("Performing health check...");
        
        foreach ($this->servers as $name => $server) {
            try {
                // Basic server health check
                $this->logger->info("Server '$name' is running on port {$this->ports[$name]}");
            } catch (\Exception $e) {
                $this->logger->error("Health check failed for '$name' server: " . $e->getMessage());
            }
        }
    }
    
    public function stopServers() {
        $this->logger->info("Stopping WebSocket servers...");
        
        foreach ($this->servers as $name => $server) {
            try {
                // Implement proper shutdown logic
                $this->logger->info("Stopping '$name' server...");
            } catch (\Exception $e) {
                $this->logger->error("Error stopping '$name' server: " . $e->getMessage());
            }
        }
    }
}