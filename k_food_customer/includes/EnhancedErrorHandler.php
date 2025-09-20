<?php
require_once __DIR__ . '/ErrorHandler.php';

/**
 * Enhanced error handler with additional features:
 * - Error rate limiting
 * - Error grouping and analytics
 * - Automatic log cleanup
 * - Error correlation
 */
class EnhancedErrorHandler extends ErrorHandler {
    private $rateLimits = [];
    private $cleanupInterval = '7 days';
    private $maxErrorsPerMinute = 60;
    
    /**
     * Constructor
     * 
     * @param mysqli $conn Database connection
     * @param string|null $logDir Optional custom log directory
     * @param int $maxErrorsPerMinute Maximum number of similar errors per minute
     * @param string $cleanupInterval Interval for cleaning up old logs
     */
    public function __construct(
        $conn, 
        string $logDir = null, 
        int $maxErrorsPerMinute = 60,
        string $cleanupInterval = '7 days'
    ) {
        parent::__construct($conn, $logDir);
        $this->maxErrorsPerMinute = $maxErrorsPerMinute;
        $this->cleanupInterval = $cleanupInterval;
        $this->initializeAnalyticsTables();
    }
    
    /**
     * Initialize analytics tables
     */
    private function initializeAnalyticsTables(): void {
        $sql = "CREATE TABLE IF NOT EXISTS error_analytics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            error_type VARCHAR(50) NOT NULL,
            error_hash VARCHAR(32) NOT NULL,
            first_seen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_seen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            occurrence_count INT NOT NULL DEFAULT 1,
            affected_files TEXT,
            error_pattern TEXT,
            resolved BOOLEAN DEFAULT FALSE,
            resolution_notes TEXT,
            INDEX (error_type),
            INDEX (error_hash),
            INDEX (last_seen)
        )";
        
        try {
            $this->conn->query($sql);
        } catch (Throwable $e) {
            error_log("Failed to initialize analytics tables: " . $e->getMessage());
        }
    }
    
    /**
     * Override parent's logError to add rate limiting and analytics
     */
    public function logError(Throwable $error, array $context = []): array {
        $errorHash = md5($error->getMessage() . $error->getFile() . $error->getLine());
        
        if ($this->isRateLimited($errorHash)) {
            return [
                'success' => false,
                'rate_limited' => true,
                'message' => 'Error logging rate limit exceeded'
            ];
        }
        
        $errorData = parent::logError($error, $context);
        $errorData['success'] = true;
        
        try {
            $this->updateErrorAnalytics($errorHash, $errorData);
            if (rand(1, 100) === 1) {
                $this->cleanupOldLogs();
            }
        } catch (Throwable $e) {
            error_log("Failed to update analytics: " . $e->getMessage());
        }
        
        return $errorData;
    }
    
    /**
     * Check if error is rate limited
     */
    private function isRateLimited(string $errorHash): bool {
        $now = time();
        
        // Clean up old rate limits
        foreach ($this->rateLimits as $hash => $limit) {
            if ($limit['timestamp'] < $now - 60) {
                unset($this->rateLimits[$hash]);
            }
        }
        
        if (isset($this->rateLimits[$errorHash])) {
            $this->rateLimits[$errorHash]['count']++;
            return $this->rateLimits[$errorHash]['count'] > $this->maxErrorsPerMinute;
        }
        
        $this->rateLimits[$errorHash] = [
            'timestamp' => $now,
            'count' => 1
        ];
        
        return false;
    }
    
    /**
     * Update analytics data
     */
    private function updateErrorAnalytics(string $errorHash, array $errorData): void {
        $sql = "SELECT id, occurrence_count, affected_files FROM error_analytics WHERE error_hash = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $errorHash);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $files = array_unique(array_merge(
                json_decode($row['affected_files'], true) ?? [],
                [$errorData['file']]
            ));
            
            $sql = "UPDATE error_analytics SET 
                occurrence_count = occurrence_count + 1,
                affected_files = ?
            WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $filesJson = json_encode($files);
            $stmt->bind_param("si", $filesJson, $row['id']);
            $stmt->execute();
        } else {
            $sql = "INSERT INTO error_analytics (
                error_type, error_hash, first_seen, last_seen,
                affected_files, error_pattern
            ) VALUES (?, ?, NOW(), NOW(), ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $files = json_encode([$errorData['file']]);
            $pattern = $errorData['message'];
            $stmt->bind_param("ssss", 
                $errorData['type'],
                $errorHash,
                $files,
                $pattern
            );
            $stmt->execute();
        }
        
        $stmt->close();
    }
    
    /**
     * Get error statistics
     */
    public function getErrorStats(string $period = '24h'): array {
        $hours = intval($period);
        $sql = "SELECT 
            error_type,
            COUNT(*) as total_errors,
            COUNT(DISTINCT error_hash) as unique_errors,
            MIN(first_seen) as earliest_occurrence,
            MAX(last_seen) as latest_occurrence,
            SUM(occurrence_count) as total_occurrences,
            COUNT(CASE WHEN resolved = 1 THEN 1 END) as resolved_count
        FROM error_analytics
        WHERE last_seen >= DATE_SUB(NOW(), INTERVAL ? HOUR)
        GROUP BY error_type
        ORDER BY total_occurrences DESC";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $hours);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $stats = [
                'summary' => [],
                'total_errors' => 0,
                'unique_errors' => 0,
                'resolution_rate' => 0
            ];
            
            while ($row = $result->fetch_assoc()) {
                $stats['summary'][] = $row;
                $stats['total_errors'] += $row['total_errors'];
                $stats['unique_errors'] += $row['unique_errors'];
            }
            
            if ($stats['total_errors'] > 0) {
                $stats['resolution_rate'] = array_sum(array_column($stats['summary'], 'resolved_count')) 
                    / $stats['total_errors'] * 100;
            }
            
            return $stats;
        } catch (Throwable $e) {
            error_log("Failed to get error stats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean up old logs
     */
    public function cleanupOldLogs(): array {
        $stats = [
            'deleted_logs' => 0,
            'deleted_analytics' => 0,
            'deleted_files' => 0,
            'errors' => []
        ];
        
        try {
            $days = intval($this->cleanupInterval);
            
            $sql = "DELETE FROM error_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $days);
            $stmt->execute();
            $stats['deleted_logs'] = $stmt->affected_rows;
            
            $sql = "DELETE FROM error_analytics WHERE resolved = 1 AND last_seen < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $days);
            $stmt->execute();
            $stats['deleted_analytics'] = $stmt->affected_rows;
            
            $cutoff = strtotime("-{$this->cleanupInterval}");
            foreach (glob($this->logDirectory . '/*.log') as $file) {
                if (filemtime($file) < $cutoff) {
                    if (unlink($file)) {
                        $stats['deleted_files']++;
                    } else {
                        $stats['errors'][] = "Failed to delete file: $file";
                    }
                }
            }
        } catch (Throwable $e) {
            $stats['errors'][] = $e->getMessage();
            error_log("Log cleanup failed: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Set cleanup interval
     */
    public function setCleanupInterval(string $interval): void {
        $this->cleanupInterval = $interval;
    }
    
    /**
     * Set maximum errors per minute
     */
    public function setMaxErrorsPerMinute(int $max): void {
        $this->maxErrorsPerMinute = $max;
    }
}