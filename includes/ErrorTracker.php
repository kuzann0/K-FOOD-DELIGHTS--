<?php
namespace KFood\Monitoring;

class ErrorTracker {
    private $db;
    private $logger;
    private $context;
    
    public function __construct($db, $logger) {
        $this->db = $db;
        $this->logger = $logger;
        $this->context = [];
    }
    
    public function captureError($error, $context = []) {
        $errorData = [
            'type' => $error instanceof \Exception ? get_class($error) : 'Error',
            'message' => $error->getMessage(),
            'code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString(),
            'context' => array_merge($this->context, $context),
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $_SESSION['user_id'] ?? null,
            'url' => $_SERVER['REQUEST_URI'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'session_id' => session_id() ?? null,
            'request_data' => [
                'get' => $_GET,
                'post' => $this->sanitizePostData($_POST),
                'headers' => getallheaders()
            ],
            'severity' => $this->calculateSeverity($error)
        ];
        
        // Store in database
        $this->storeError($errorData);
        
        // Log error
        $this->logger->error($errorData['message'], $errorData);
        
        // Check if alert should be created
        $this->checkAlertThreshold($errorData);
        
        return $errorData;
    }
    
    private function sanitizePostData($data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            // Remove sensitive data
            if (in_array(strtolower($key), ['password', 'token', 'credit_card', 'secret'])) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
    
    private function calculateSeverity($error) {
        if ($error instanceof \ErrorException) {
            switch ($error->getSeverity()) {
                case E_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                    return 'critical';
                case E_WARNING:
                case E_CORE_WARNING:
                case E_COMPILE_WARNING:
                case E_USER_WARNING:
                    return 'high';
                case E_NOTICE:
                case E_USER_NOTICE:
                    return 'medium';
                default:
                    return 'low';
            }
        }
        
        // For custom exceptions, check if they define a severity
        if (method_exists($error, 'getSeverity')) {
            return $error->getSeverity();
        }
        
        return 'medium';
    }
    
    private function storeError($errorData) {
        $stmt = $this->db->prepare("
            INSERT INTO error_tracking (
                error_type, message, code, file_path, line_number,
                stack_trace, context_data, timestamp, user_id,
                request_url, request_method, ip_address, user_agent,
                session_id, request_data, severity, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open')
        ");
        
        $stmt->execute([
            $errorData['type'],
            $errorData['message'],
            $errorData['code'],
            $errorData['file'],
            $errorData['line'],
            $errorData['trace'],
            json_encode($errorData['context']),
            $errorData['timestamp'],
            $errorData['user_id'],
            $errorData['url'],
            $errorData['method'],
            $errorData['ip_address'],
            $errorData['user_agent'],
            $errorData['session_id'],
            json_encode($errorData['request_data']),
            $errorData['severity']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    private function checkAlertThreshold($errorData) {
        // Check error frequency for this type in the last hour
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as error_count
            FROM error_tracking
            WHERE error_type = ?
            AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        
        $stmt->execute([$errorData['type']]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // If error occurs more than 10 times in an hour, create an alert
        if ($result['error_count'] >= 10) {
            $alertData = [
                'title' => "Frequent {$errorData['type']} Errors Detected",
                'message' => "Encountered {$result['error_count']} instances of {$errorData['type']} in the last hour",
                'severity' => $errorData['severity'],
                'context' => [
                    'error_type' => $errorData['type'],
                    'frequency' => $result['error_count'],
                    'latest_occurrence' => $errorData['timestamp']
                ]
            ];
            
            $this->createAlert($alertData);
        }
    }
    
    private function createAlert($alertData) {
        $stmt = $this->db->prepare("
            INSERT INTO system_alerts (
                title, message, severity, context_data,
                created_at, status
            ) VALUES (?, ?, ?, ?, NOW(), 'unread')
        ");
        
        $stmt->execute([
            $alertData['title'],
            $alertData['message'],
            $alertData['severity'],
            json_encode($alertData['context'])
        ]);
    }
    
    public function getErrorStats($timeframe = '24h') {
        $timeframeSql = match($timeframe) {
            '1h' => 'INTERVAL 1 HOUR',
            '24h' => 'INTERVAL 24 HOUR',
            '7d' => 'INTERVAL 7 DAY',
            '30d' => 'INTERVAL 30 DAY',
            default => 'INTERVAL 24 HOUR'
        };
        
        $stmt = $this->db->prepare("
            SELECT 
                error_type,
                COUNT(*) as error_count,
                severity,
                MIN(timestamp) as first_occurrence,
                MAX(timestamp) as last_occurrence
            FROM error_tracking
            WHERE timestamp >= DATE_SUB(NOW(), {$timeframeSql})
            GROUP BY error_type, severity
            ORDER BY error_count DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function setContext($key, $value) {
        $this->context[$key] = $value;
    }
    
    public function clearContext($key = null) {
        if ($key === null) {
            $this->context = [];
        } else {
            unset($this->context[$key]);
        }
    }
}