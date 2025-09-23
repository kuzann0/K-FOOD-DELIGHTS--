<?php
namespace KFood\Monitoring;

class AlertRuleEngine {
    private $db;
    private $rules = [];
    private $activeRules = [];
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadRules();
    }
    
    private function loadRules() {
        $stmt = $this->db->prepare("
            SELECT * FROM alert_rules
            WHERE status = 'active'
        ");
        
        $stmt->execute();
        $this->rules = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($this->rules as $rule) {
            $this->activeRules[$rule['type']][] = $rule;
        }
    }
    
    public function createRule($ruleData) {
        $stmt = $this->db->prepare("
            INSERT INTO alert_rules (
                name, description, type, conditions,
                actions, severity, status, created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
        ");
        
        $stmt->execute([
            $ruleData['name'],
            $ruleData['description'],
            $ruleData['type'],
            json_encode($ruleData['conditions']),
            json_encode($ruleData['actions']),
            $ruleData['severity']
        ]);
        
        // Reload rules
        $this->loadRules();
        
        return $this->db->lastInsertId();
    }
    
    public function evaluateMetric($type, $value, $context = []) {
        if (!isset($this->activeRules[$type])) {
            return;
        }
        
        foreach ($this->activeRules[$type] as $rule) {
            if ($this->checkConditions($rule['conditions'], $value, $context)) {
                $this->triggerAlert($rule, $value, $context);
            }
        }
    }
    
    private function checkConditions($conditions, $value, $context) {
        $conditions = json_decode($conditions, true);
        
        foreach ($conditions as $condition) {
            switch ($condition['operator']) {
                case '>':
                    if (!($value > $condition['value'])) return false;
                    break;
                case '<':
                    if (!($value < $condition['value'])) return false;
                    break;
                case '>=':
                    if (!($value >= $condition['value'])) return false;
                    break;
                case '<=':
                    if (!($value <= $condition['value'])) return false;
                    break;
                case '==':
                    if (!($value == $condition['value'])) return false;
                    break;
                case '!=':
                    if (!($value != $condition['value'])) return false;
                    break;
                case 'contains':
                    if (!str_contains($value, $condition['value'])) return false;
                    break;
                case 'regex':
                    if (!preg_match($condition['value'], $value)) return false;
                    break;
                case 'timeRange':
                    $time = $context['timestamp'] ?? time();
                    if (!$this->isInTimeRange($time, $condition['value'])) return false;
                    break;
                case 'threshold':
                    if (!$this->checkThreshold(
                        $condition['metric'],
                        $condition['value'],
                        $condition['period']
                    )) return false;
                    break;
            }
        }
        
        return true;
    }
    
    private function isInTimeRange($timestamp, $range) {
        $time = date('H:i', $timestamp);
        return ($time >= $range['start'] && $time <= $range['end']);
    }
    
    private function checkThreshold($metric, $threshold, $period) {
        $stmt = $this->db->prepare("
            SELECT AVG(value) as avg_value
            FROM metric_values
            WHERE metric_name = ?
            AND timestamp >= DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        
        $stmt->execute([$metric, $period]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result['avg_value'] > $threshold;
    }
    
    private function triggerAlert($rule, $value, $context) {
        $alertData = [
            'rule_id' => $rule['id'],
            'rule_name' => $rule['name'],
            'type' => $rule['type'],
            'severity' => $rule['severity'],
            'value' => $value,
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Create alert
        $stmt = $this->db->prepare("
            INSERT INTO alerts (
                rule_id, type, severity, value,
                context_data, created_at, status
            ) VALUES (?, ?, ?, ?, ?, NOW(), 'new')
        ");
        
        $stmt->execute([
            $rule['id'],
            $rule['type'],
            $rule['severity'],
            $value,
            json_encode($context)
        ]);
        
        $alertId = $this->db->lastInsertId();
        
        // Execute alert actions
        $this->executeActions(json_decode($rule['actions'], true), $alertData);
        
        return $alertId;
    }
    
    private function executeActions($actions, $alertData) {
        foreach ($actions as $action) {
            switch ($action['type']) {
                case 'email':
                    $this->sendEmailNotification($action['recipients'], $alertData);
                    break;
                case 'webhook':
                    $this->sendWebhookNotification($action['url'], $alertData);
                    break;
                case 'slack':
                    $this->sendSlackNotification($action['webhook'], $alertData);
                    break;
                case 'sms':
                    $this->sendSMSNotification($action['numbers'], $alertData);
                    break;
            }
        }
    }
    
    private function sendEmailNotification($recipients, $alertData) {
        // Implementation of email notification
    }
    
    private function sendWebhookNotification($url, $alertData) {
        // Implementation of webhook notification
    }
    
    private function sendSlackNotification($webhook, $alertData) {
        // Implementation of Slack notification
    }
    
    private function sendSMSNotification($numbers, $alertData) {
        // Implementation of SMS notification
    }
    
    public function getRules($filters = []) {
        $sql = "SELECT * FROM alert_rules WHERE 1=1";
        $params = [];
        
        if (isset($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }
        
        if (isset($filters['severity'])) {
            $sql .= " AND severity = ?";
            $params[] = $filters['severity'];
        }
        
        if (isset($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function updateRule($ruleId, $data) {
        $updateFields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, ['name', 'description', 'type', 'severity', 'status'])) {
                $updateFields[] = "$field = ?";
                $params[] = $value;
            } elseif (in_array($field, ['conditions', 'actions'])) {
                $updateFields[] = "$field = ?";
                $params[] = json_encode($value);
            }
        }
        
        if (empty($updateFields)) {
            return false;
        }
        
        $updateFields[] = "updated_at = NOW()";
        $params[] = $ruleId;
        
        $sql = "UPDATE alert_rules SET " . implode(", ", $updateFields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        // Reload rules
        $this->loadRules();
        
        return true;
    }
    
    public function deleteRule($ruleId) {
        $stmt = $this->db->prepare("
            UPDATE alert_rules
            SET status = 'deleted', updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$ruleId]);
        
        // Reload rules
        $this->loadRules();
        
        return true;
    }
}