<?php
class DatabaseConnection {
    private static $instance = null;
    private $connections = [];
    private $maxConnections = 10;
    private $config;

    private function __construct() {
        $this->config = [
            'host' => 'localhost',
            'user' => 'root',
            'password' => '',
            'dbname' => 'kfood_delights',
            'charset' => 'utf8mb4'
        ];
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        // Remove closed connections
        $this->connections = array_filter($this->connections, function($conn) {
            return $conn->ping();
        });

        // Reuse existing connection if available
        foreach ($this->connections as $conn) {
            if ($conn->thread_id !== null) {
                return $conn;
            }
        }

        // Create new connection if under max limit
        if (count($this->connections) < $this->maxConnections) {
            $mysqli = new mysqli(
                $this->config['host'],
                $this->config['user'],
                $this->config['password'],
                $this->config['dbname']
            );

            if ($mysqli->connect_error) {
                error_log("Database connection failed: " . $mysqli->connect_error);
                throw new Exception("Database connection error. Please try again later.");
            }

            // Set charset
            if (!$mysqli->set_charset($this->config['charset'])) {
                error_log("Error setting charset: " . $mysqli->error);
                throw new Exception("Database configuration error.");
            }

            // Set connection timeout
            $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
            
            // Set strict mode
            $mysqli->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");

            $mysqli->set_charset($this->config['charset']);
            
            // Enable prepared statement cache
            $mysqli->query("SET SESSION query_cache_type = ON");
            
            $this->connections[] = $mysqli;
            return $mysqli;
        }

        // Wait for available connection
        return $this->waitForConnection();
    }

    private function waitForConnection() {
        $timeout = 30; // 30 seconds timeout
        $start = time();
        
        while (time() - $start < $timeout) {
            foreach ($this->connections as $conn) {
                if ($conn->thread_id !== null) {
                    return $conn;
                }
            }
            usleep(100000); // Sleep for 100ms
        }
        
        throw new Exception("Connection timeout after {$timeout} seconds");
    }

    public function executeQuery($sql, $params = [], $types = "") {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt;
    }

    public function beginTransaction() {
        $conn = $this->getConnection();
        $conn->begin_transaction();
    }

    public function commit() {
        $conn = $this->getConnection();
        $conn->commit();
    }

    public function rollback() {
        $conn = $this->getConnection();
        $conn->rollback();
    }
}
