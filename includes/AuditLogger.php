<?php
namespace KFood\Monitoring;

class AuditLogger {
    private $db;
    private $currentUser;
    
    public function __construct($db) {
        $this->db = $db;
        $this->currentUser = $_SESSION['user_id'] ?? null;
    }
    
    public function log($action, $entityType, $entityId, $oldValues = null, $newValues = null, $metadata = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $this->currentUser,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'metadata' => json_encode(array_merge($metadata, [
                'session_id' => session_id(),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'referer' => $_SERVER['HTTP_REFERER'] ?? null
            ]))
        ];
        
        $this->storeAuditLog($logEntry);
        
        // Check for suspicious activity
        $this->detectSuspiciousActivity($logEntry);
        
        return $logEntry;
    }
    
    private function storeAuditLog($logEntry) {
        $stmt = $this->db->prepare("
            INSERT INTO audit_trail (
                timestamp, user_id, action, entity_type,
                entity_id, old_values, new_values,
                ip_address, user_agent, metadata
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $logEntry['timestamp'],
            $logEntry['user_id'],
            $logEntry['action'],
            $logEntry['entity_type'],
            $logEntry['entity_id'],
            $logEntry['old_values'],
            $logEntry['new_values'],
            $logEntry['ip_address'],
            $logEntry['user_agent'],
            $logEntry['metadata']
        ]);
    }
    
    private function detectSuspiciousActivity($logEntry) {
        // Check for rapid succession of actions
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as action_count
            FROM audit_trail
            WHERE user_id = ?
            AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        
        $stmt->execute([$logEntry['user_id']]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // If more than 30 actions in 1 minute, flag as suspicious
        if ($result['action_count'] > 30) {
            $this->flagSuspiciousActivity(
                'high_frequency_actions',
                $logEntry['user_id'],
                [
                    'action_count' => $result['action_count'],
                    'timeframe' => '1 minute',
                    'last_action' => $logEntry
                ]
            );
        }
        
        // Check for unusual access patterns
        if ($this->isUnusualAccessPattern($logEntry)) {
            $this->flagSuspiciousActivity(
                'unusual_access_pattern',
                $logEntry['user_id'],
                $logEntry
            );
        }
    }
    
    private function isUnusualAccessPattern($logEntry) {
        // Check if user is accessing from a new IP
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as ip_count
            FROM audit_trail
            WHERE user_id = ?
            AND ip_address = ?
            AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        $stmt->execute([
            $logEntry['user_id'],
            $logEntry['ip_address']
        ]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // If this is the first time using this IP in 30 days
        if ($result['ip_count'] === 0) {
            return true;
        }
        
        // Add more pattern detection logic here
        
        return false;
    }
    
    private function flagSuspiciousActivity($type, $userId, $details) {
        $stmt = $this->db->prepare("
            INSERT INTO security_alerts (
                alert_type, user_id, details,
                created_at, status
            ) VALUES (?, ?, ?, NOW(), 'unread')
        ");
        
        $stmt->execute([
            $type,
            $userId,
            json_encode($details)
        ]);
        
        // Notify security team
        $this->notifySecurityTeam($type, $userId, $details);
    }
    
    private function notifySecurityTeam($type, $userId, $details) {
        // Implementation of security team notification
        // This would integrate with your notification system
    }
    
    public function getAuditHistory($filters = []) {
        $sql = "SELECT * FROM audit_trail WHERE 1=1";
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['entity_type'])) {
            $sql .= " AND entity_type = ?";
            $params[] = $filters['entity_type'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND timestamp >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND timestamp <= ?";
            $params[] = $filters['end_date'];
        }
        
        $sql .= " ORDER BY timestamp DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function getActivitySummary($timeframe = '24h') {
        $timeframeSql = match($timeframe) {
            '1h' => 'INTERVAL 1 HOUR',
            '24h' => 'INTERVAL 24 HOUR',
            '7d' => 'INTERVAL 7 DAY',
            '30d' => 'INTERVAL 30 DAY',
            default => 'INTERVAL 24 HOUR'
        };
        
        $sql = "
            SELECT 
                action,
                entity_type,
                COUNT(*) as action_count,
                MIN(timestamp) as first_action,
                MAX(timestamp) as last_action
            FROM audit_trail
            WHERE timestamp >= DATE_SUB(NOW(), {$timeframeSql})
            GROUP BY action, entity_type
            ORDER BY action_count DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}