<?php
namespace KFood\Monitoring;

class PerformanceProfiler {
    private $db;
    private $profiles = [];
    private $activeProfiles = [];
    private $thresholds;
    
    public function __construct($db) {
        $this->db = $db;
        $this->thresholds = [
            'query_time' => 1.0, // seconds
            'memory_usage' => 50 * 1024 * 1024, // 50MB
            'cpu_usage' => 70, // percentage
        ];
    }
    
    public function startProfile($name, $context = []) {
        $profile = [
            'name' => $name,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'context' => $context,
            'queries' => [],
            'events' => []
        ];
        
        $this->activeProfiles[$name] = $profile;
        return $name;
    }
    
    public function endProfile($name) {
        if (!isset($this->activeProfiles[$name])) {
            return false;
        }
        
        $profile = $this->activeProfiles[$name];
        $profile['end_time'] = microtime(true);
        $profile['end_memory'] = memory_get_usage(true);
        $profile['duration'] = $profile['end_time'] - $profile['start_time'];
        $profile['memory_peak'] = memory_get_peak_usage(true);
        $profile['memory_usage'] = $profile['end_memory'] - $profile['start_memory'];
        
        // Store the profile
        $this->storeProfile($profile);
        
        // Check for performance issues
        $this->analyzePerformance($profile);
        
        unset($this->activeProfiles[$name]);
        $this->profiles[] = $profile;
        
        return $profile;
    }
    
    public function addEvent($profileName, $eventName, $data = []) {
        if (!isset($this->activeProfiles[$profileName])) {
            return false;
        }
        
        $event = [
            'name' => $eventName,
            'timestamp' => microtime(true),
            'memory' => memory_get_usage(true),
            'data' => $data
        ];
        
        $this->activeProfiles[$profileName]['events'][] = $event;
        return true;
    }
    
    public function logQuery($profileName, $sql, $params = [], $duration = null) {
        if (!isset($this->activeProfiles[$profileName])) {
            return false;
        }
        
        $query = [
            'sql' => $sql,
            'params' => $params,
            'timestamp' => microtime(true),
            'duration' => $duration
        ];
        
        $this->activeProfiles[$profileName]['queries'][] = $query;
        
        // Check if query is slow
        if ($duration !== null && $duration > $this->thresholds['query_time']) {
            $this->flagSlowQuery($query);
        }
        
        return true;
    }
    
    private function storeProfile($profile) {
        $stmt = $this->db->prepare("
            INSERT INTO performance_profiles (
                name, start_time, end_time, duration,
                memory_start, memory_end, memory_peak,
                context_data, events_data, queries_data
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $profile['name'],
            date('Y-m-d H:i:s', (int)$profile['start_time']),
            date('Y-m-d H:i:s', (int)$profile['end_time']),
            $profile['duration'],
            $profile['start_memory'],
            $profile['end_memory'],
            $profile['memory_peak'],
            json_encode($profile['context']),
            json_encode($profile['events']),
            json_encode($profile['queries'])
        ]);
    }
    
    private function analyzePerformance($profile) {
        // Check memory usage
        if ($profile['memory_usage'] > $this->thresholds['memory_usage']) {
            $this->createAlert(
                'high_memory_usage',
                "High memory usage detected in {$profile['name']}",
                'warning',
                [
                    'profile' => $profile['name'],
                    'memory_usage' => $profile['memory_usage'],
                    'threshold' => $this->thresholds['memory_usage']
                ]
            );
        }
        
        // Check execution time
        if ($profile['duration'] > $this->thresholds['query_time']) {
            $this->createAlert(
                'slow_execution',
                "Slow execution detected in {$profile['name']}",
                'warning',
                [
                    'profile' => $profile['name'],
                    'duration' => $profile['duration'],
                    'threshold' => $this->thresholds['query_time']
                ]
            );
        }
        
        // Analyze query patterns
        $this->analyzeQueryPatterns($profile);
    }
    
    private function analyzeQueryPatterns($profile) {
        $queryCount = count($profile['queries']);
        
        // Check for N+1 query pattern
        $tables = [];
        foreach ($profile['queries'] as $query) {
            if (preg_match('/FROM\s+`?(\w+)`?/i', $query['sql'], $matches)) {
                $table = $matches[1];
                $tables[$table] = ($tables[$table] ?? 0) + 1;
            }
        }
        
        foreach ($tables as $table => $count) {
            if ($count > 10) {
                $this->createAlert(
                    'potential_n_plus_one',
                    "Potential N+1 query pattern detected",
                    'warning',
                    [
                        'profile' => $profile['name'],
                        'table' => $table,
                        'query_count' => $count
                    ]
                );
            }
        }
    }
    
    private function flagSlowQuery($query) {
        $stmt = $this->db->prepare("
            INSERT INTO slow_queries (
                sql_text, parameters, duration,
                timestamp
            ) VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $query['sql'],
            json_encode($query['params']),
            $query['duration']
        ]);
        
        $this->createAlert(
            'slow_query',
            "Slow query detected",
            'warning',
            [
                'sql' => $query['sql'],
                'duration' => $query['duration']
            ]
        );
    }
    
    private function createAlert($type, $message, $severity, $context) {
        $stmt = $this->db->prepare("
            INSERT INTO system_alerts (
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
    }
    
    public function getPerformanceReport($timeframe = '24h') {
        $timeframeSql = match($timeframe) {
            '1h' => 'INTERVAL 1 HOUR',
            '24h' => 'INTERVAL 24 HOUR',
            '7d' => 'INTERVAL 7 DAY',
            '30d' => 'INTERVAL 30 DAY',
            default => 'INTERVAL 24 HOUR'
        };
        
        $sql = "
            SELECT 
                name,
                AVG(duration) as avg_duration,
                MAX(duration) as max_duration,
                AVG(memory_peak) as avg_memory,
                MAX(memory_peak) as max_memory,
                COUNT(*) as execution_count
            FROM performance_profiles
            WHERE start_time >= DATE_SUB(NOW(), {$timeframeSql})
            GROUP BY name
            ORDER BY avg_duration DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function getSlowQueries($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT *
            FROM slow_queries
            ORDER BY duration DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}