<?php
namespace KFood\Monitoring;

use PDO;
use Exception;
use KFood\Monitoring\AlertRuleEngine;

class SystemMonitor {
    private $db;
    private $logPath;
    private $alertThresholds;
    private $alertEngine;
    
    public function __construct(PDO $db, $config = []) {
        $this->db = $db;
        $this->logPath = $config['logPath'] ?? __DIR__ . '/../logs';
        $this->alertThresholds = $config['thresholds'] ?? [
            'lowStock' => 10,
            'orderProcessingTime' => 30, // seconds
            'systemLoad' => 80, // percentage
            'errorRate' => 5 // percentage per minute
        ];
        
        // Initialize Alert Rule Engine
        $this->alertEngine = new AlertRuleEngine($db);
        
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory() {
        if (!file_exists($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
        
        // Create separate log files for different concerns
        $logFiles = ['system.log', 'orders.log', 'security.log', 'performance.log'];
        foreach ($logFiles as $file) {
            $filePath = $this->logPath . '/' . $file;
            if (!file_exists($filePath)) {
                touch($filePath);
                chmod($filePath, 0644);
            }
        }
    }
    
    public function logSystemEvent($type, $message, $data = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'type' => $type,
            'message' => $message,
            'data' => $data
        ];
        
        // Determine log file based on type
        $logFile = $this->getLogFileForType($type);
        
        // Format log entry
        $formattedLog = $this->formatLogEntry($logEntry);
        
        // Write to file
        file_put_contents(
            $this->logPath . '/' . $logFile,
            $formattedLog . PHP_EOL,
            FILE_APPEND
        );
        
        // Store in database for querying
        $this->storeLogInDatabase($logEntry);
        
        // Evaluate the event as a metric
        if (in_array($type, ['error', 'warning', 'critical'])) {
            $this->evaluateMetric('system_event', 1, [
                'type' => $type,
                'message' => $message,
                'timestamp' => strtotime($timestamp)
            ]);
        }
    }
    
    private function getLogFileForType($type) {
        switch ($type) {
            case 'order':
            case 'payment':
                return 'orders.log';
            case 'security':
            case 'auth':
                return 'security.log';
            case 'performance':
                return 'performance.log';
            default:
                return 'system.log';
        }
    }
    
    private function formatLogEntry($entry) {
        return sprintf(
            "[%s] %s: %s - %s",
            $entry['timestamp'],
            strtoupper($entry['type']),
            $entry['message'],
            json_encode($entry['data'])
        );
    }
    
    private function storeLogInDatabase($entry) {
        $stmt = $this->db->prepare("
            INSERT INTO system_logs 
            (timestamp, type, message, data) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $entry['timestamp'],
            $entry['type'],
            $entry['message'],
            json_encode($entry['data'])
        ]);
    }
    
    public function monitorInventory() {
        $stmt = $this->db->prepare("
            SELECT id, name, stock_quantity 
            FROM inventory
        ");
        
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as $item) {
            // Evaluate each item's stock level
            $this->evaluateMetric('inventory_stock', $item['stock_quantity'], [
                'item_id' => $item['id'],
                'item_name' => $item['name'],
                'timestamp' => time()
            ]);
        }
        
        // Also track total inventory level
        $totalStock = array_sum(array_column($items, 'stock_quantity'));
        $this->evaluateMetric('total_inventory', $totalStock, [
            'timestamp' => time(),
            'item_count' => count($items)
        ]);
    }
    
    public function monitorOrderProcessing() {
        // Get all pending orders
        $stmt = $this->db->prepare("
            SELECT id, created_at, status
            FROM orders
            WHERE status IN ('pending', 'processing')
        ");
        
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($orders as $order) {
            // Calculate processing time
            $processingTime = time() - strtotime($order['created_at']);
            
            // Evaluate order processing time
            $this->evaluateMetric('order_processing_time', $processingTime, [
                'order_id' => $order['id'],
                'status' => $order['status'],
                'timestamp' => time(),
                'created_at' => $order['created_at']
            ]);
        }
        
        // Track total pending orders
        $pendingCount = count($orders);
        $this->evaluateMetric('pending_orders', $pendingCount, [
            'timestamp' => time()
        ]);
    }
    
    public function monitorSystemLoad() {
        // Get system metrics
        $load = sys_getloadavg();
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        
        // Evaluate system load
        $this->evaluateMetric('system_load', $load[0], [
            'timestamp' => time(),
            'load_5min' => $load[1],
            'load_15min' => $load[2]
        ]);
        
        // Evaluate memory usage
        $this->evaluateMetric('memory_usage', $memoryUsage, [
            'timestamp' => time(),
            'peak_usage' => $memoryPeak,
            'percentage' => ($memoryUsage / $memoryPeak) * 100
        ]);
        
        // Evaluate disk space
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskUsedPercentage = (($diskTotal - $diskFree) / $diskTotal) * 100;
        
        $this->evaluateMetric('disk_usage', $diskUsedPercentage, [
            'timestamp' => time(),
            'free_space' => $diskFree,
            'total_space' => $diskTotal
        ]);
    }
    
    public function monitorErrorRates() {
        // Get error counts for different time periods
        $intervals = [
            '1 MINUTE' => 'per_minute',
            '5 MINUTE' => 'per_5_minutes',
            '1 HOUR' => 'per_hour'
        ];
        
        foreach ($intervals as $interval => $metricName) {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as error_count,
                    COUNT(DISTINCT SUBSTRING_INDEX(message, ':', 1)) as unique_errors
                FROM system_logs
                WHERE type = 'error'
                AND timestamp >= DATE_SUB(NOW(), INTERVAL $interval)
            ");
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Evaluate error rate
            $this->evaluateMetric('error_rate_' . $metricName, $result['error_count'], [
                'timestamp' => time(),
                'unique_errors' => $result['unique_errors'],
                'interval' => $interval
            ]);
        }
        
        // Get error types distribution
        $stmt = $this->db->prepare("
            SELECT 
                SUBSTRING_INDEX(message, ':', 1) as error_type,
                COUNT(*) as count
            FROM system_logs
            WHERE type = 'error'
            AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY error_type
            ORDER BY count DESC
            LIMIT 5
        ");
        
        $stmt->execute();
        $errorTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Evaluate most common error types
        foreach ($errorTypes as $error) {
            $this->evaluateMetric('error_type_frequency', $error['count'], [
                'timestamp' => time(),
                'error_type' => $error['error_type']
            ]);
        }
    }
    
    private function evaluateMetric($type, $value, $context = []) {
        // Store metric value
        $stmt = $this->db->prepare("
            INSERT INTO metric_values 
            (metric_name, value, timestamp) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$type, $value]);
        
        // Evaluate metric against alert rules
        $this->alertEngine->evaluateMetric($type, $value, $context);
    }

    private function sendAlert($title, $message, $severity = 'medium') {
        // Store alert in database
        $stmt = $this->db->prepare("
            INSERT INTO alerts 
            (rule_id, type, severity, value, context_data, created_at, status) 
            VALUES (0, 'system', ?, ?, ?, NOW(), 'new')
        ");
        
        $contextData = json_encode([
            'title' => $title,
            'message' => $message
        ]);
        
        $stmt->execute([$severity, $message, $contextData]);
        
        // Store alert for WebSocket server to pick up
        $alertData = [
            'type' => 'alert',
            'title' => $title,
            'message' => $message,
            'severity' => $severity,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO websocket_messages 
            (type, data, created_at) 
            VALUES ('alert', ?, NOW())
        ");
        
        $stmt->execute([json_encode($alertData)]);
    }
    
    private function sendAlertEmail($title, $message) {
        // Implementation of alert email sending
        // This would use your email service configuration
    }
    
    public function getSystemStatus() {
        $metrics = [
            'load' => sys_getloadavg(),
            'memory' => [
                'used' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true)
            ],
            'disk' => [
                'free' => disk_free_space('/'),
                'total' => disk_total_space('/')
            ]
        ];
        
        // Get current metrics
        foreach ($metrics as $type => $value) {
            if (is_array($value)) {
                foreach ($value as $subtype => $subvalue) {
                    $this->evaluateMetric("{$type}_{$subtype}", $subvalue, [
                        'timestamp' => time(),
                        'type' => $type,
                        'subtype' => $subtype
                    ]);
                }
            } else {
                $this->evaluateMetric($type, $value, [
                    'timestamp' => time()
                ]);
            }
        }
        
        return $metrics;
    }
    
    private function getRecentErrors() {
        $stmt = $this->db->prepare("
            SELECT * FROM system_logs
            WHERE type = 'error'
            AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY timestamp DESC
            LIMIT 10
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getActiveAlerts() {
        $stmt = $this->db->prepare("
            SELECT * FROM system_alerts
            WHERE status = 'unread'
            ORDER BY created_at DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}