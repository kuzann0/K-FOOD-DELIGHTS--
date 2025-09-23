<?php
/**
 * Database Connection Pool Manager
 * Handles connection pooling and error recovery
 */
class DatabaseConnectionPool {
    private static $instance = null;
    private $connections = [];
    private $inUse = [];
    private $lastCleanup = 0;
    
    private function __construct() {
        // Initialize empty pool
        $this->lastCleanup = time();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new DatabaseConnectionPool();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        // Clean up idle connections
        $this->cleanupIdleConnections();
        
        // Try to reuse an existing connection
        foreach ($this->connections as $key => $conn) {
            if (!isset($this->inUse[$key]) || !$this->inUse[$key]) {
                if ($this->isConnectionValid($conn)) {
                    $this->inUse[$key] = true;
                    return $conn;
                } else {
                    // Remove invalid connection
                    unset($this->connections[$key]);
                    unset($this->inUse[$key]);
                }
            }
        }
        
        // Create new connection if pool is not full
        if (count($this->connections) < DB_MAX_CONNECTIONS) {
            try {
                $conn = $this->createNewConnection();
                $key = spl_object_hash($conn);
                $this->connections[$key] = $conn;
                $this->inUse[$key] = true;
                return $conn;
            } catch (Exception $e) {
                $this->logError($e);
                throw new Exception("Failed to create database connection: " . $e->getMessage());
            }
        }
        
        // Wait for available connection
        $timeout = time() + DB_CONNECT_TIMEOUT;
        while (time() < $timeout) {
            foreach ($this->connections as $key => $conn) {
                if (!$this->inUse[$key] && $this->isConnectionValid($conn)) {
                    $this->inUse[$key] = true;
                    return $conn;
                }
            }
            usleep(100000); // Wait 100ms
        }
        
        throw new Exception("Connection pool exhausted");
    }
    
    public function releaseConnection($conn) {
        $key = spl_object_hash($conn);
        if (isset($this->inUse[$key])) {
            $this->inUse[$key] = false;
        }
    }
    
    private function createNewConnection() {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset(DB_CHARSET);
        $conn->query("SET time_zone = '+08:00'");
        
        return $conn;
    }
    
    private function isConnectionValid($conn) {
        try {
            return $conn->ping();
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function cleanupIdleConnections() {
        $now = time();
        
        // Run cleanup every 5 minutes
        if ($now - $this->lastCleanup < 300) {
            return;
        }
        
        $this->lastCleanup = $now;
        
        foreach ($this->connections as $key => $conn) {
            if (!$this->inUse[$key]) {
                try {
                    if (!$this->isConnectionValid($conn)) {
                        $conn->close();
                        unset($this->connections[$key]);
                        unset($this->inUse[$key]);
                    }
                } catch (Exception $e) {
                    // Connection already died, remove it
                    unset($this->connections[$key]);
                    unset($this->inUse[$key]);
                }
            }
        }
    }
    
    private function logError($error) {
        $timestamp = date('Y-m-d H:i:s');
        $message = "[{$timestamp}] Database Error: " . $error->getMessage() . "\n";
        $message .= "Stack trace:\n" . $error->getTraceAsString() . "\n\n";
        
        error_log($message, 3, DB_ERROR_LOG_PATH);
    }
    
    public function __destruct() {
        // Close all connections
        foreach ($this->connections as $conn) {
            try {
                $conn->close();
            } catch (Exception $e) {
                // Ignore closing errors
            }
        }
    }
}