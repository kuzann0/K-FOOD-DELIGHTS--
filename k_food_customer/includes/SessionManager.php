<?php
class SessionManager {
    const SESSION_TIMEOUT = 1800; // 30 minutes
    const ROLE_CUSTOMER = 4;

    public function __construct() {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Lax');
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function startSecureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration']) || 
            (time() - $_SESSION['last_regeneration']) > 300) { // Every 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }

    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
            return false;
        }

        if (time() - $_SESSION['last_activity'] > self::SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    public function isCustomer() {
        return $this->isLoggedIn() && isset($_SESSION['role_id']) && 
               $_SESSION['role_id'] === self::ROLE_CUSTOMER;
    }

    public function requireCustomerAccess() {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: login.php');
            exit();
        }

        if (!$this->isCustomer()) {
            header('Location: unauthorized.php');
            exit();
        }
    }

    public function createSession($userId, $roleId) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['role_id'] = $roleId;
        $_SESSION['last_activity'] = time();
        $_SESSION['last_regeneration'] = time();
    }

    public function logout() {
        $_SESSION = array();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }

    public function setFlashMessage($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    public function getFlashMessage() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}
?>