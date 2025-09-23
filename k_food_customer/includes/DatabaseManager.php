<?php
namespace KFood\Database;

class DatabaseManager {
    private static $instance = null;
    private $connection = null;
    private $config = [];
    private $logger = null;
    private $reconnectAttempts = 0;
    private $maxReconnectAttempts = 5;
    private $reconnectDelay = 5; // seconds
    
    private function __construct() {
        $this->loadConfig();
        $this->setupLogger();
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfig() {
        if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_NAME')) {
            throw new \Exception('Database configuration constants not defined. Please include config.php');
        }
        
        $this->config = [
            'host' => DB_HOST,
            'user' => DB_USER,
            'pass' => DB_PASS,
            'name' => DB_NAME,
            'charset' => defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4'
        ];
    }
    
    private function setupLogger() {
        $logDir = __DIR__ . '/../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $this->logger = fopen($logDir . '/database_' . date('Y-m-d') . '.log', 'a');
    }
    
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        fwrite($this->logger, $logMessage);
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo $logMessage;
        }
    }
    
    private function connect() {
        try {
            // Validate configuration
            if (empty($this->config['user'])) {
                throw new \Exception('Database username not configured');
            }
            
            $this->connection = new \mysqli(
                $this->config['host'],
                $this->config['user'],
                $this->config['pass'],
                $this->config['name']
            );
            
            if ($this->connection->connect_error) {
                throw new \Exception($this->connection->connect_error);
            }
            
            $this->connection->set_charset($this->config['charset']);
            $this->connection->query("SET time_zone = '+08:00'");
            
            $this->log('Database connection established successfully');
            $this->reconnectAttempts = 0;
            
            return true;
        } catch (\Exception $e) {
            $this->log('Database connection error: ' . $e->getMessage(), 'ERROR');
            
            if ($this->reconnectAttempts < $this->maxReconnectAttempts) {
                $this->reconnectAttempts++;
                $this->log("Attempting reconnection {$this->reconnectAttempts} of {$this->maxReconnectAttempts}", 'WARN');
                sleep($this->reconnectDelay);
                return $this->connect();
            } else {
                $this->log('Maximum reconnection attempts reached', 'CRITICAL');
                throw $e;
            }
        }
    }
    
    public function getConnection() {
        if (!$this->connection || !$this->ping()) {
            $this->connect();
        }
        return $this->connection;
    }
    
    private function ping() {
        try {
            return $this->connection && $this->connection->ping();
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function prepare($query) {
        $stmt = $this->getConnection()->prepare($query);
        if (!$stmt) {
            throw new \Exception('Failed to prepare statement: ' . $this->getConnection()->error);
        }
        return $stmt;
    }
    
    public function beginTransaction() {
        return $this->getConnection()->begin_transaction();
    }
    
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    public function rollback() {
        return $this->getConnection()->rollback();
    }
    
    public function __destruct() {
        if ($this->logger) {
            fclose($this->logger);
        }
        if ($this->connection) {
            $this->connection->close();
        }
    }
}