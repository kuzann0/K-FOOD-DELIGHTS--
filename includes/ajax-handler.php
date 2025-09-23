<?php
/**
 * Common response handler for AJAX endpoints
 */
class AjaxResponse {
    public static function success($data = null, $message = 'Success') {
        return self::send([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    public static function error($message = 'Error', $code = 400) {
        http_response_code($code);
        return self::send([
            'success' => false,
            'message' => $message
        ]);
    }

    private static function send($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

/**
 * Rate limiting for AJAX requests
 */
class RateLimiter {
    private $redis;
    private $maxRequests;
    private $window;

    public function __construct($maxRequests = 60, $window = 60) {
        $this->maxRequests = $maxRequests;
        $this->window = $window;
        
        // Initialize Redis connection
        try {
            if (!extension_loaded('redis')) {
                throw new Exception('Redis extension is not loaded');
            }
            $this->redis = new Redis();
            $this->redis->connect('127.0.0.1', 6379);
        } catch (Exception $e) {
            // Fallback to no rate limiting if Redis is unavailable
            error_log("Rate limiter disabled: " . $e->getMessage());
            $this->redis = null;
        }
    }

    public function checkLimit($key) {
        if (!$this->redis) return true;

        $current = $this->redis->get($key);
        
        if (!$current) {
            $this->redis->setex($key, $this->window, 1);
            return true;
        }

        if ($current >= $this->maxRequests) {
            return false;
        }

        $this->redis->incr($key);
        return true;
    }
}

/**
 * Request validation
 */
class RequestValidator {
    public static function validateOrderId($orderId) {
        return is_numeric($orderId) && $orderId > 0;
    }

    public static function validateTimestamp($timestamp) {
        return strtotime($timestamp) !== false;
    }

    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(strip_tags($data));
    }
}

// Initialize rate limiter
$rateLimiter = new RateLimiter();

// Check rate limit
$clientIp = $_SERVER['REMOTE_ADDR'];
if (!$rateLimiter->checkLimit("ajax_$clientIp")) {
    AjaxResponse::error('Too many requests', 429);
}

// Validate session
session_start();
if (!isset($_SESSION['user_id'])) {
    AjaxResponse::error('Unauthorized', 401);
}

// Parse JSON body for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonBody = file_get_contents('php://input');
    if ($jsonBody) {
        $_POST = json_decode($jsonBody, true) ?? [];
    }
}

// Sanitize input
$_GET = RequestValidator::sanitizeInput($_GET);
$_POST = RequestValidator::sanitizeInput($_POST);