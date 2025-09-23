<?php
namespace KFoodDelights\Database;

class TransactionManager {
    private $conn;
    private $transactionName;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function beginTransaction($name = null) {
        $this->transactionName = $name;
        $this->conn->begin_transaction();
        
        if ($name) {
            $this->conn->query("SAVEPOINT `$name`");
        }
    }
    
    public function commit() {
        if ($this->transactionName) {
            $this->conn->query("RELEASE SAVEPOINT `{$this->transactionName}`");
        }
        $this->conn->commit();
    }
    
    public function rollback() {
        if ($this->transactionName) {
            $this->conn->query("ROLLBACK TO SAVEPOINT `{$this->transactionName}`");
        } else {
            $this->conn->rollback();
        }
    }
}