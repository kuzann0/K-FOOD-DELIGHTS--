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
            'dbname' => 'k_food_delights',
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
        try {
            // Create new connection using PDO
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
            $pdo = new PDO($dsn, $this->config['user'], $this->config['password']);
            
            // Set PDO attributes
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }

    public function executeQuery($sql, $params = []) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Query preparation failed");
        }

        $stmt->execute($params);
        return $stmt;
    }

    public function beginTransaction() {
        $conn = $this->getConnection();
        $conn->beginTransaction();
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