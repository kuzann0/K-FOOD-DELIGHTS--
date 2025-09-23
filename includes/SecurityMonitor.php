<?php
namespace KFood\Monitoring;

class SecurityMonitor {
    private $db;
    private $logger;
    private $thresholds;
    
    public function __construct($db, $logger) {
        $this->db = $db;
        $this->logger = $logger;
        $this->thresholds = [
            'login_attempts' => 5,
            'api_requests' => 100,
            'concurrent_sessions' => 3,
            'password_age' => 90, // days
            'suspicious_ips' => []
        ];
    }
    
    public function monitorLoginAttempt($username, $success, $ipAddress) {
        // Record login attempt
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (
                username, success, ip_address,
                attempt_time
            ) VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->execute([$username, $success ? 1 : 0, $ipAddress]);
        
        // Check for brute force attempts
        $this->checkBruteForce($username, $ipAddress);
        
        // Monitor successful logins from new IPs
        if ($success) {
            $this->checkNewIPLogin($username, $ipAddress);
        }
    }
    
    private function checkBruteForce($username, $ipAddress) {
        // Check attempts in last 15 minutes
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempt_count
            FROM login_attempts
            WHERE (username = ? OR ip_address = ?)
            AND success = 0
            AND attempt_time >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        
        $stmt->execute([$username, $ipAddress]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result['attempt_count'] >= $this->thresholds['login_attempts']) {
            $this->createSecurityAlert(
                'brute_force_attempt',
                'Multiple failed login attempts detected',
                'high',
                [
                    'username' => $username,
                    'ip_address' => $ipAddress,
                    'attempt_count' => $result['attempt_count']
                ]
            );
            
            // Add IP to suspicious list
            $this->flagSuspiciousIP($ipAddress);
        }
    }
    
    private function checkNewIPLogin($username, $ipAddress) {
        // Check if this IP has been used before for this user
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as login_count
            FROM login_attempts
            WHERE username = ?
            AND ip_address = ?
            AND success = 1
            AND attempt_time < DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        
        $stmt->execute([$username, $ipAddress]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result['login_count'] === 0) {
            $this->createSecurityAlert(
                'new_ip_login',
                'Login from new IP address detected',
                'medium',
                [
                    'username' => $username,
                    'ip_address' => $ipAddress
                ]
            );
        }
    }
    
    public function monitorApiUsage($endpoint, $userId, $ipAddress) {
        // Record API request
        $stmt = $this->db->prepare("
            INSERT INTO api_requests (
                endpoint, user_id, ip_address,
                request_time
            ) VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->execute([$endpoint, $userId, $ipAddress]);
        
        // Check for API abuse
        $this->checkApiThrottling($endpoint, $userId, $ipAddress);
    }
    
    private function checkApiThrottling($endpoint, $userId, $ipAddress) {
        // Check requests in last minute
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as request_count
            FROM api_requests
            WHERE (user_id = ? OR ip_address = ?)
            AND request_time >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        
        $stmt->execute([$userId, $ipAddress]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result['request_count'] > $this->thresholds['api_requests']) {
            $this->createSecurityAlert(
                'api_abuse',
                'API rate limit exceeded',
                'high',
                [
                    'user_id' => $userId,
                    'ip_address' => $ipAddress,
                    'endpoint' => $endpoint,
                    'request_count' => $result['request_count']
                ]
            );
        }
    }
    
    public function monitorUserSessions($userId) {
        // Check concurrent sessions
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT session_id) as session_count
            FROM user_sessions
            WHERE user_id = ?
            AND last_activity >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result['session_count'] > $this->thresholds['concurrent_sessions']) {
            $this->createSecurityAlert(
                'multiple_sessions',
                'Multiple concurrent sessions detected',
                'medium',
                [
                    'user_id' => $userId,
                    'session_count' => $result['session_count']
                ]
            );
        }
    }
    
    public function monitorPasswordAge() {
        // Check for expired passwords
        $stmt = $this->db->prepare("
            SELECT id, username, last_password_change
            FROM users
            WHERE last_password_change < DATE_SUB(NOW(), INTERVAL ? DAY)
            AND status = 'active'
        ");
        
        $stmt->execute([$this->thresholds['password_age']]);
        $expiredPasswords = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($expiredPasswords as $user) {
            $this->createSecurityAlert(
                'password_expired',
                'User password has expired',
                'medium',
                [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'last_change' => $user['last_password_change']
                ]
            );
        }
    }
    
    public function monitorFileAccess($filePath, $userId, $action) {
        // Record file access
        $stmt = $this->db->prepare("
            INSERT INTO file_access_log (
                file_path, user_id, action,
                access_time
            ) VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->execute([$filePath, $userId, $action]);
        
        // Check for suspicious file access patterns
        $this->checkSuspiciousFileAccess($userId);
    }
    
    private function checkSuspiciousFileAccess($userId) {
        // Check for rapid file access
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as access_count
            FROM file_access_log
            WHERE user_id = ?
            AND access_time >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result['access_count'] > 50) { // Threshold for suspicious activity
            $this->createSecurityAlert(
                'suspicious_file_access',
                'Unusual file access pattern detected',
                'high',
                [
                    'user_id' => $userId,
                    'access_count' => $result['access_count']
                ]
            );
        }
    }
    
    private function createSecurityAlert($type, $message, $severity, $context) {
        $stmt = $this->db->prepare("
            INSERT INTO security_alerts (
                alert_type, message, severity,
                context_data, created_at, status
            ) VALUES (?, ?, ?, ?, NOW(), 'unread')
        ");
        
        $stmt->execute([
            $type,
            $message,
            $severity,
            json_encode($context)
        ]);
        
        // Log the security event
        $this->logger->warning($message, [
            'type' => 'security_alert',
            'alert_type' => $type,
            'severity' => $severity,
            'context' => $context
        ]);
    }
    
    private function flagSuspiciousIP($ipAddress) {
        $stmt = $this->db->prepare("
            INSERT INTO suspicious_ips (
                ip_address, reason, first_detected,
                last_detected, status
            ) VALUES (?, 'brute_force', NOW(), NOW(), 'active')
            ON DUPLICATE KEY UPDATE
                last_detected = NOW(),
                detection_count = detection_count + 1
        ");
        
        $stmt->execute([$ipAddress]);
    }
    
    public function getSecurityReport($timeframe = '24h') {
        $timeframeSql = match($timeframe) {
            '1h' => 'INTERVAL 1 HOUR',
            '24h' => 'INTERVAL 24 HOUR',
            '7d' => 'INTERVAL 7 DAY',
            '30d' => 'INTERVAL 30 DAY',
            default => 'INTERVAL 24 HOUR'
        };
        
        return [
            'failed_logins' => $this->getFailedLoginStats($timeframeSql),
            'api_abuse' => $this->getApiAbuseStats($timeframeSql),
            'suspicious_ips' => $this->getSuspiciousIPs($timeframeSql),
            'security_alerts' => $this->getSecurityAlerts($timeframeSql)
        ];
    }
    
    private function getFailedLoginStats($timeframe) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as attempt_count,
                COUNT(DISTINCT ip_address) as unique_ips,
                COUNT(DISTINCT username) as unique_users
            FROM login_attempts
            WHERE success = 0
            AND attempt_time >= DATE_SUB(NOW(), {$timeframe})
        ");
        
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    private function getApiAbuseStats($timeframe) {
        $stmt = $this->db->prepare("
            SELECT 
                endpoint,
                COUNT(*) as request_count,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT ip_address) as unique_ips
            FROM api_requests
            WHERE request_time >= DATE_SUB(NOW(), {$timeframe})
            GROUP BY endpoint
            HAVING request_count > ?
        ");
        
        $stmt->execute([$this->thresholds['api_requests']]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    private function getSuspiciousIPs($timeframe) {
        $stmt = $this->db->prepare("
            SELECT *
            FROM suspicious_ips
            WHERE last_detected >= DATE_SUB(NOW(), {$timeframe})
            AND status = 'active'
            ORDER BY detection_count DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    private function getSecurityAlerts($timeframe) {
        $stmt = $this->db->prepare("
            SELECT *
            FROM security_alerts
            WHERE created_at >= DATE_SUB(NOW(), {$timeframe})
            ORDER BY severity DESC, created_at DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}