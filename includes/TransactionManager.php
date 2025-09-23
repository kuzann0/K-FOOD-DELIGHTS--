<?php
/**
 * Transaction Manager
 * Handles distributed locking and transaction safety
 */
class TransactionManager {
    private $conn;
    private $locks = [];
    private $transactionTimeout = 30; // seconds
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function beginTransaction($name = null) {
        if ($name) {
            // Acquire distributed lock
            if (!$this->acquireLock($name)) {
                throw new Exception("Could not acquire lock for transaction: $name");
            }
        }
        
        $this->conn->begin_transaction();
        
        // Set transaction timeout
        $this->conn->query("SET SESSION innodb_lock_wait_timeout = " . $this->transactionTimeout);
    }
    
    public function commit() {
        try {
            $this->conn->commit();
        } finally {
            $this->releaseLocks();
        }
    }
    
    public function rollback() {
        try {
            $this->conn->rollback();
        } finally {
            $this->releaseLocks();
        }
    }
    
    private function acquireLock($name) {
        // Try to get a named lock
        $stmt = $this->conn->prepare("SELECT GET_LOCK(?, ?)");
        $lockName = "kfood_lock_" . $name;
        $timeout = $this->transactionTimeout;
        $stmt->bind_param("si", $lockName, $timeout);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_row();
        $stmt->close();
        
        if ($row[0] == 1) {
            $this->locks[] = $lockName;
            return true;
        }
        
        return false;
    }
    
    private function releaseLocks() {
        foreach ($this->locks as $lockName) {
            $stmt = $this->conn->prepare("SELECT RELEASE_LOCK(?)");
            $stmt->bind_param("s", $lockName);
            $stmt->execute();
            $stmt->close();
        }
        $this->locks = [];
    }
    
    public function __destruct() {
        $this->releaseLocks();
    }
}