<?php
/**
 * Security Manager
 * Handles session security, CSRF protection, and input validation
 */
class SecurityManager {
    private static $instance = null;
    private $sessionTimeout = 1800; // 30 minutes
    private $csrfToken;
    
    private function __construct() {
        // Configure secure session handling
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.gc_maxlifetime', $this->sessionTimeout);
        
        // Set session handler
        session_set_save_handler(
            array($this, 'openSession'),
            array($this, 'closeSession'),
            array($this, 'readSession'),
            array($this, 'writeSession'),
            array($this, 'destroySession'),
            array($this, 'gcSession')
        );
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new SecurityManager();
        }
        return self::$instance;
    }
    
    public function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check session validity
        if ($this->isSessionExpired()) {
            $this->regenerateSession();
        }
        
        // Initialize CSRF token if needed
        if (!isset($_SESSION['csrf_token'])) {
            $this->generateCsrfToken();
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
    }
    
    public function validateCsrfToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            throw new Exception("Invalid CSRF token");
        }
    }
    
    public function getCsrfToken() {
        return $_SESSION['csrf_token'];
    }
    
    private function generateCsrfToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    private function isSessionExpired() {
        if (!isset($_SESSION['last_activity'])) {
            return true;
        }
        
        if (time() - $_SESSION['last_activity'] > $this->sessionTimeout) {
            return true;
        }
        
        return false;
    }
    
    private function regenerateSession() {
        // Clear old session
        session_unset();
        session_destroy();
        
        // Start new session
        session_start();
        session_regenerate_id(true);
        
        // Initialize new session data
        $_SESSION['created'] = time();
        $this->generateCsrfToken();
    }
    
    public function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            if (!isset($data[$field])) {
                if (isset($rule['required']) && $rule['required']) {
                    $errors[$field] = "Field is required";
                }
                continue;
            }
            
            $value = $data[$field];
            
            // Type validation
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "Invalid email format";
                        }
                        break;
                    case 'number':
                        if (!is_numeric($value)) {
                            $errors[$field] = "Must be a number";
                        }
                        break;
                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field] = "Must be a string";
                        }
                        break;
                }
            }
            
            // Length validation
            if (isset($rule['minLength']) && strlen($value) < $rule['minLength']) {
                $errors[$field] = "Minimum length is " . $rule['minLength'];
            }
            if (isset($rule['maxLength']) && strlen($value) > $rule['maxLength']) {
                $errors[$field] = "Maximum length is " . $rule['maxLength'];
            }
            
            // Pattern validation
            if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                $errors[$field] = "Invalid format";
            }
            
            // Custom validation
            if (isset($rule['custom']) && is_callable($rule['custom'])) {
                $result = call_user_func($rule['custom'], $value);
                if ($result !== true) {
                    $errors[$field] = $result;
                }
            }
        }
        
        return $errors;
    }
    
    // Session handler methods
    public function openSession($savePath, $sessionName) {
        return true;
    }
    
    public function closeSession() {
        return true;
    }
    
    public function readSession($id) {
        $stmt = DatabaseConnectionPool::getInstance()
            ->getConnection()
            ->prepare("SELECT data FROM sessions WHERE id = ? AND expire > ?");
        $stmt->bind_param("si", $id, time());
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? $row['data'] : '';
    }
    
    public function writeSession($id, $data) {
        $expire = time() + $this->sessionTimeout;
        $stmt = DatabaseConnectionPool::getInstance()
            ->getConnection()
            ->prepare("REPLACE INTO sessions (id, data, expire) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $id, $data, $expire);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    public function destroySession($id) {
        $stmt = DatabaseConnectionPool::getInstance()
            ->getConnection()
            ->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->bind_param("s", $id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    public function gcSession($maxlifetime) {
        $stmt = DatabaseConnectionPool::getInstance()
            ->getConnection()
            ->prepare("DELETE FROM sessions WHERE expire < ?");
        $old = time() - $maxlifetime;
        $stmt->bind_param("i", $old);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
}