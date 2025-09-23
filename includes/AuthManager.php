<?php
namespace KFood\Auth;

use PDO;
use Exception;

class AuthManager {
    private $db;
    private $config;
    
    // Rate limiting settings
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_TIMEOUT = 900; // 15 minutes
    
    public function __construct(PDO $db, array $config) {
        $this->db = $db;
        $this->config = $config;
    }
    
    public function login($username, $password, $role = 'customer') {
        try {
            // Check rate limiting
            $this->checkRateLimit($username);
            
            // Get user by username and role
            $user = $this->getUserByUsername($username, $role);
            
            if (!$user) {
                $this->recordFailedAttempt($username);
                throw new Exception('Invalid username or password');
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->recordFailedAttempt($username);
                throw new Exception('Invalid username or password');
            }
            
            // Check account status
            if ($user['status'] !== 'active') {
                throw new Exception('Account is not active');
            }
            
            // Clear failed attempts
            $this->clearFailedAttempts($username);
            
            // Generate new session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['auth_token'] = $this->generateAuthToken($user['id']);
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'name' => $user['name']
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function getUserByUsername($username, $role) {
        $stmt = $this->db->prepare("
            SELECT id, username, password_hash, role, status, name
            FROM users 
            WHERE username = ? AND role = ?
        ");
        
        $stmt->execute([$username, $role]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function checkRateLimit($username) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt
            FROM login_attempts
            WHERE username = ? AND attempt_time > NOW() - INTERVAL ? SECOND
        ");
        
        $stmt->execute([$username, self::LOGIN_TIMEOUT]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['attempts'] >= self::MAX_LOGIN_ATTEMPTS) {
            $timeLeft = self::LOGIN_TIMEOUT - (time() - strtotime($result['last_attempt']));
            throw new Exception("Too many login attempts. Please try again in " . ceil($timeLeft / 60) . " minutes.");
        }
    }
    
    private function recordFailedAttempt($username) {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (username, attempt_time, ip_address)
            VALUES (?, NOW(), ?)
        ");
        
        $stmt->execute([
            $username,
            $_SERVER['REMOTE_ADDR']
        ]);
    }
    
    private function clearFailedAttempts($username) {
        $stmt = $this->db->prepare("
            DELETE FROM login_attempts
            WHERE username = ?
        ");
        
        $stmt->execute([$username]);
    }
    
    private function generateAuthToken($userId) {
        return bin2hex(random_bytes(32));
    }
    
    private function updateLastLogin($userId) {
        $stmt = $this->db->prepare("
            UPDATE users
            SET last_login = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$userId]);
    }
    
    public function logout() {
        // Clear all session data
        $_SESSION = array();
        
        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
    }
    
    public function verifySession() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['auth_token'])) {
            return false;
        }
        
        // Verify the auth token is still valid
        $stmt = $this->db->prepare("
            SELECT id, status 
            FROM users 
            WHERE id = ? AND status = 'active'
        ");
        
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    public function refreshSession() {
        if ($this->verifySession()) {
            // Regenerate session ID periodically
            if (!isset($_SESSION['last_regeneration']) || 
                time() - $_SESSION['last_regeneration'] > 300) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
            return true;
        }
        return false;
    }
}