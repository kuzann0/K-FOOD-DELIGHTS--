<?php
/**
 * Custom exception class for order processing errors
 */
class OrderProcessingError extends Exception {
    protected $errorType;
    protected $errorData;
    protected $userMessage;

    public function __construct(string $message, string $errorType, array $errorData = [], ?string $userMessage = null) {
        parent::__construct($message);
        $this->errorType = $errorType;
        $this->errorData = $errorData;
        $this->userMessage = $userMessage ?? $message;
    }

    public function getErrorType(): string {
        return $this->errorType;
    }

    public function getErrorData(): array {
        return $this->errorData;
    }

    public function getUserMessage(): string {
        return $this->userMessage;
    }
}

/**
 * Main error handling class for the application
 */
require_once __DIR__ . '/AdminNotificationHandler.php';

class ErrorHandler {
    use AdminNotificationHandler;

    protected $conn;
    protected $errors = [];
    protected $debugMode;
    protected $logDirectory;
    protected $criticalErrorTypes = ['DATABASE_ERROR', 'SYSTEM_ERROR', 'PAYMENT_ERROR'];

    /**
     * Constructor
     * 
     * @param mysqli $conn Database connection
     * @param string $logDir Optional custom log directory
     */
    public function __construct($conn, string $logDir = null) {
        $this->conn = $conn;
        $this->debugMode = defined('DEBUG_MODE') && DEBUG_MODE === true;
        $this->logDirectory = $logDir ?? dirname(__DIR__) . '/logs';
        
        // Ensure log directory exists
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0755, true);
        }
    }

    /**
     * Log an error to both database and file system
     * 
     * @param Throwable $error The error to log
     * @param array $context Additional context data
     * @return array The logged error data
     */
    public function logError(Throwable $error, array $context = []): array {
        $errorData = [
            'message' => $error->getMessage(),
            'type' => $error instanceof OrderProcessingError ? $error->getErrorType() : 'GENERAL_ERROR',
            'userMessage' => $error instanceof OrderProcessingError ? $error->getUserMessage() : $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString(),
            'context' => json_encode($context, JSON_PRETTY_PRINT),
            'timestamp' => date('Y-m-d H:i:s'),
            'errorId' => uniqid('err_')
        ];

        try {
            // First attempt database logging
            $this->logToDatabase($errorData);
        } catch (Throwable $e) {
            // Fallback to file logging if database fails
            $this->logToFile($errorData, $e);
        }

        // Always log critical errors to separate file
        if (in_array($errorData['type'], $this->criticalErrorTypes)) {
            $this->logCriticalError($errorData);
        }

        return $errorData;
    }

    /**
     * Log error to database
     * 
     * @param array $errorData The error data to log
     * @throws Exception If database logging fails
     */
    private function logToDatabase(array $errorData): void {
        $sql = "INSERT INTO error_logs (
            error_id,
            error_type,
            error_message,
            user_message,
            error_file,
            error_line,
            error_trace,
            context_data,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare error log statement: " . $this->conn->error);
        }

        $stmt->bind_param("sssssssss",
            $errorData['errorId'],
            $errorData['type'],
            $errorData['message'],
            $errorData['userMessage'],
            $errorData['file'],
            $errorData['line'],
            $errorData['trace'],
            $errorData['context'],
            $errorData['timestamp']
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to execute error log statement: " . $stmt->error);
        }

        $stmt->close();
    }

    /**
     * Handle and format an error for response
     * 
     * @param Throwable $error The error to handle
     * @param array $data Additional context data
     * @return array Formatted error response
     */
    public function handleError(Throwable $error, array $data = []): array {
        $errorData = $this->logError($error, $data);
        
        // Base error response structure
        $response = [
            'success' => false,
            'errorCode' => $errorData['type'],
            'message' => $error instanceof OrderProcessingError ? $error->getUserMessage() : $error->getMessage(),
            'errorId' => $errorData['errorId'],
            'timestamp' => date('c')
        ];

        // Add debug information in debug mode
        if ($this->debugMode) {
            $response['debug'] = [
                'file' => $errorData['file'],
                'line' => $errorData['line'],
                'trace' => $errorData['trace'],
                'context' => $data
            ];
        }
        
        // Set HTTP status and customize response based on error type
        $this->setResponseHeaders($errorData['type']);
        $this->customizeErrorResponse($response, $error, $errorData);
        
        // Handle critical errors
        if (in_array($errorData['type'], $this->criticalErrorTypes)) {
            $this->handleCriticalError($errorData);
        }

        return $response;
    }

    /**
     * Set appropriate HTTP response headers based on error type
     */
    private function setResponseHeaders(string $errorType): void {
        $statusCodes = [
            'VALIDATION_ERROR' => 400,
            'AUTHENTICATION_ERROR' => 401,
            'AUTHORIZATION_ERROR' => 403,
            'NOT_FOUND_ERROR' => 404,
            'PAYMENT_ERROR' => 402,
            'CART_ERROR' => 400,
            'STOCK_ERROR' => 409,
            'DATABASE_ERROR' => 500,
            'SYSTEM_ERROR' => 500,
        ];

        $statusCode = $statusCodes[$errorType] ?? 500;
        http_response_code($statusCode);
        header('Content-Type: application/json');
    }

    /**
     * Customize error response based on error type
     */
    private function customizeErrorResponse(array &$response, Throwable $error, array $errorData): void {
        $customizations = [
            'VALIDATION_ERROR' => function(&$r, $e) {
                $r['validationErrors'] = $e instanceof OrderProcessingError ? $e->getErrorData() : [];
            },
            'PAYMENT_ERROR' => function(&$r, $e) {
                $r['paymentDetails'] = $e instanceof OrderProcessingError ? $e->getErrorData() : [];
                if (!$this->debugMode) {
                    $r['message'] = 'Payment processing failed. Please try again or use a different payment method.';
                }
            },
            'CART_ERROR' => function(&$r, $e) {
                $r['cartDetails'] = $e instanceof OrderProcessingError ? $e->getErrorData() : [];
            },
            'STOCK_ERROR' => function(&$r, $e) {
                $r['stockDetails'] = $e instanceof OrderProcessingError ? $e->getErrorData() : [];
            },
            'DATABASE_ERROR' => function(&$r) {
                if (!$this->debugMode) {
                    $r['message'] = 'An error occurred while processing your order. Please try again later.';
                }
            },
            'SYSTEM_ERROR' => function(&$r) {
                if (!$this->debugMode) {
                    $r['message'] = 'A system error occurred. Our team has been notified.';
                }
            }
        ];

        if (isset($customizations[$errorData['type']])) {
            $customizations[$errorData['type']]($response, $error);
        }
    }

    /**
     * Handle a critical error
     */
    private function handleCriticalError(array $errorData): void {
        try {
            $this->notifyAdmin($errorData);
            $this->logCriticalError($errorData);
            
            if ($errorData['type'] === 'DATABASE_ERROR') {
                $this->attemptDatabaseRecovery();
            }
        } catch (Throwable $e) {
            // Log but don't re-throw to prevent loops
            error_log("Failed to handle critical error: " . $e->getMessage());
        }
    }

    // Removed duplicate method - see implementation at the bottom of the class

    public function getLastError() {
        return end($this->errors) ?: null;
    }

    public function clearErrors() {
        $this->errors = [];
    }

    public function validateOrder($data) {
        $errors = [];

        // Validate customer info
        if (!isset($data['customerInfo']) || empty($data['customerInfo'])) {
            $errors[] = 'Customer information is required';
        } else {
            foreach (['name', 'email', 'phone', 'address'] as $field) {
                if (!isset($data['customerInfo'][$field]) || empty($data['customerInfo'][$field])) {
                    $errors[] = ucfirst($field) . ' is required';
                }
            }
        }

        // Validate items
        if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
            $errors[] = 'Order items are required';
        } else {
            foreach ($data['items'] as $index => $item) {
                if (!isset($item['name']) || !isset($item['price']) || !isset($item['quantity'])) {
                    $errors[] = "Invalid item data at position " . ($index + 1);
                }
            }
        }

        // Validate payment
        if (!isset($data['payment']) || empty($data['payment'])) {
            $errors[] = 'Payment information is required';
        } else {
            if (!isset($data['payment']['method'])) {
                $errors[] = 'Payment method is required';
            }
            if ($data['payment']['method'] === 'gcash' && empty($data['payment']['reference'])) {
                $errors[] = 'GCash reference number is required';
            }
        }

        if (!empty($errors)) {
            throw new OrderProcessingError(
                'Order validation failed',
                'VALIDATION_ERROR',
                $errors
            );
        }
    }

    public function attemptRecovery($error, $orderData) {
        if ($error instanceof OrderProcessingError) {
            switch ($error->getErrorType()) {
                case 'DATABASE_ERROR':
                    // Try to recover database connection
                    if ($this->conn->ping() === false) {
                        $this->conn->close();
                        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                    }
                    break;

                case 'NOTIFICATION_ERROR':
                    // Queue notification for retry
                    $this->queueNotificationRetry($orderData);
                    break;
            }
        }
    }

    /**
     * Log an error to file
     */
    private function logToFile(array $errorData, ?Throwable $dbError = null): void {
        $logEntry = date('Y-m-d H:i:s') . " | " . json_encode($errorData, JSON_PRETTY_PRINT) . "\n";
        if ($dbError) {
            $logEntry .= "Database logging failed: " . $dbError->getMessage() . "\n";
        }
        
        $logFile = $this->logDirectory . '/error.log';
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Log critical errors separately
     */
    private function logCriticalError(array $errorData): void {
        $logEntry = date('Y-m-d H:i:s') . " | CRITICAL | " . json_encode($errorData, JSON_PRETTY_PRINT) . "\n";
        $logFile = $this->logDirectory . '/critical.log';
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Notify administrator about critical errors
     */
    /**
     * Send notifications to admin through multiple channels (email and admin interface)
     * 
     * @param array $errorData The error data to send
     * @return void
     */
    private function notifyAdmin(array $errorData): void {
        try {
            // Send email notification
            $this->sendAdminEmail($errorData);
            
            // Store notification in database for admin interface
            $this->storeAdminNotification($errorData);
            
            // Send real-time notification if WebSocket server is available
            $this->sendRealTimeNotification($errorData);
            
        } catch (Throwable $e) {
            error_log("Failed to send admin notification: " . $e->getMessage());
            // Attempt to log to file as last resort
            $this->logToFile([
                'type' => 'ADMIN_NOTIFICATION_FAILED',
                'originalError' => $errorData,
                'notificationError' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email notification to admin
     * 
     * @param array $errorData The error data to send
     * @return void
     */
    private function sendAdminEmail(array $errorData): void {
        $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@kfood-delight.com';
        $subject = "Critical Error Alert - K-Food Delight";
        
        $message = "A critical error occurred:\n\n";
        $message .= "Error ID: {$errorData['errorId']}\n";
        $message .= "Type: {$errorData['type']}\n";
        $message .= "Message: {$errorData['message']}\n";
        $message .= "User Message: {$errorData['userMessage']}\n";
        $message .= "File: {$errorData['file']}\n";
        $message .= "Line: {$errorData['line']}\n";
        $message .= "Time: {$errorData['timestamp']}\n\n";
        $message .= "Stack Trace:\n{$errorData['trace']}";
        
        if (!mail($adminEmail, $subject, $message)) {
            throw new Exception("Failed to send admin email notification");
        }
    }

    /**
     * Store notification in database for admin interface
     * 
     * @param array $errorData The error data to store
     * @return void
     */
    private function storeAdminNotification(array $errorData): void {
        $sql = "INSERT INTO admin_notifications (
            notification_id,
            error_id,
            type,
            message,
            user_message,
            file_path,
            line_number,
            stack_trace,
            context_data,
            is_urgent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $notificationId = uniqid('notif_', true);
        $isUrgent = in_array($errorData['type'], $this->criticalErrorTypes) ? 1 : 0;
        
        try {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare notification statement: " . $this->conn->error);
            }

            $stmt->bind_param("sssssssssi",
                $notificationId,
                $errorData['errorId'],
                $errorData['type'],
                $errorData['message'],
                $errorData['userMessage'],
                $errorData['file'],
                $errorData['line'],
                $errorData['trace'],
                $errorData['context'],
                $isUrgent
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to store admin notification: " . $stmt->error);
            }

            $stmt->close();
        } catch (Throwable $e) {
            // Log but continue - email notification might still work
            error_log("Failed to store admin notification: " . $e->getMessage());
            throw $e; // Re-throw to be handled by caller
        }
    }

    /**
     * Send real-time notification via WebSocket if available
     * 
     * @param array $errorData The error data to send
     * @return void
     */
    private function sendRealTimeNotification(array $errorData): void {
        // Only attempt WebSocket notification if the server is configured
        if (!defined('WEBSOCKET_ENABLED') || !WEBSOCKET_ENABLED) {
            return;
        }

        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            $wsClient = new WebSocket\Client("ws://localhost:8080");
            
            $notification = [
                'type' => 'admin_error_notification',
                'data' => [
                    'errorId' => $errorData['errorId'],
                    'type' => $errorData['type'],
                    'message' => $errorData['message'],
                    'timestamp' => $errorData['timestamp']
                ]
            ];

            $wsClient->send(json_encode($notification));
            $wsClient->close();
        } catch (Throwable $e) {
            // Log but don't throw - WebSocket notification is optional
            error_log("Failed to send WebSocket notification: " . $e->getMessage());
        }
    }

    /**
     * Attempt database recovery after a connection error
     */
    private function attemptDatabaseRecovery(): void {
        try {
            if ($this->conn && !$this->conn->ping()) {
                $this->conn->close();
                if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
                    $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                }
            }
        } catch (Throwable $e) {
            error_log("Database recovery failed: " . $e->getMessage());
        }
    }

    /**
     * Queue failed notification for retry
     */
    private function queueNotificationRetry(array $notificationData): void {
        $retryFile = $this->logDirectory . '/notification_retries.json';
        $retries = file_exists($retryFile) ? json_decode(file_get_contents($retryFile), true) : [];
        $retries[] = [
            'data' => $notificationData,
            'timestamp' => time(),
            'attempts' => 0
        ];
        file_put_contents($retryFile, json_encode($retries, JSON_PRETTY_PRINT));
    }

    /**
     * Send a JSON response with proper headers
     */
    public static function jsonResponse(array $data): void {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}