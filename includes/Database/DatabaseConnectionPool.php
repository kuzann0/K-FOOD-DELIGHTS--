<?php
namespace KFoodDelights\Database;

class DatabaseConnectionPool {
    private static $instance = null;
    private $connections = [];
    
    private function __construct() {
        // Private constructor to enforce singleton pattern
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new DatabaseConnectionPool();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        // For now, we'll create a new connection each time
        // In a production environment, this should be a proper connection pool
        $conn = new \mysqli(
            'localhost',
            'root',
            '',
            'k_food_delights_db'
        );
        
        if ($conn->connect_error) {
            throw new \Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    }
    
    public function releaseConnection($conn) {
        // In a real connection pool, we would return the connection to the pool
        // For now, we'll just close it
        if ($conn instanceof \mysqli) {
            $conn->close();
        }
    }
}