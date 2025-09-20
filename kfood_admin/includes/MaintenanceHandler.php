<?php
require_once 'config.php';

class MaintenanceHandler {
    private $db;
    private $backup_dir;
    private $log_dir;
    
    public function __construct($mysqli) {
        $this->db = $mysqli;
        $this->backup_dir = dirname(__DIR__) . '/backups';
        $this->log_dir = dirname(__DIR__) . '/logs';
        
        // Ensure backup and log directories exist
        $this->ensureDirectories();
    }
    
    private function ensureDirectories() {
        foreach ([$this->backup_dir, $this->log_dir] as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
                
                // Create .htaccess to prevent direct access
                file_put_contents($dir . '/.htaccess', "Deny from all");
            }
        }
    }
    
    public function writeSystemLog($type, $level, $message, $context = null) {
        $stmt = $this->db->prepare("
            INSERT INTO system_logs 
            (log_type, log_level, message, context, source, ip_address, user_agent, admin_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $contextJson = $context ? json_encode($context) : null;
        $source = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['file'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $adminId = $_SESSION['admin_id'] ?? null;
        
        $stmt->bind_param("sssssssi", 
            $type, 
            $level, 
            $message, 
            $contextJson,
            $source,
            $ip,
            $userAgent,
            $adminId
        );
        
        return $stmt->execute();
    }
    
    public function getSystemLogs($filters = []) {
        $where = [];
        $params = [];
        $types = '';
        
        if (!empty($filters['type'])) {
            $where[] = "log_type = ?";
            $params[] = $filters['type'];
            $types .= 's';
        }
        
        if (!empty($filters['level'])) {
            $where[] = "log_level = ?";
            $params[] = $filters['level'];
            $types .= 's';
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $query = "
            SELECT l.*, a.username as admin_username
            FROM system_logs l
            LEFT JOIN admins a ON l.admin_id = a.admin_id
            {$whereClause}
            ORDER BY l.created_at DESC
            LIMIT 1000
        ";
        
        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function createBackup($type = 'FULL', $notes = '') {
        try {
            // Start backup record
            $stmt = $this->db->prepare("
                INSERT INTO system_backups 
                (backup_name, backup_type, file_path, status, created_by, notes)
                VALUES (?, ?, ?, 'PENDING', ?, ?)
            ");
            
            $backupName = 'backup_' . date('Y-m-d_His');
            $filePath = $this->backup_dir . '/' . $backupName . '.sql';
            $adminId = $_SESSION['admin_id'] ?? null;
            
            $stmt->bind_param("sssss", 
                $backupName,
                $type,
                $filePath,
                $adminId,
                $notes
            );
            
            $stmt->execute();
            $backupId = $stmt->insert_id;
            
            // Update status to IN_PROGRESS
            $this->db->query("
                UPDATE system_backups 
                SET status = 'IN_PROGRESS' 
                WHERE backup_id = {$backupId}
            ");
            
            // Perform the backup
            $this->performDatabaseBackup($filePath);
            
            // Update backup record with completion info
            $fileSize = filesize($filePath);
            $checksum = hash_file('sha256', $filePath);
            
            $stmt = $this->db->prepare("
                UPDATE system_backups 
                SET status = 'COMPLETED',
                    file_size = ?,
                    checksum = ?,
                    completed_at = CURRENT_TIMESTAMP
                WHERE backup_id = ?
            ");
            
            $stmt->bind_param("isi", $fileSize, $checksum, $backupId);
            $stmt->execute();
            
            return [
                'success' => true,
                'backup_id' => $backupId,
                'message' => 'Backup completed successfully'
            ];
            
        } catch (Exception $e) {
            // Log error and update backup status
            $this->writeSystemLog(
                'backup',
                'ERROR',
                'Backup failed: ' . $e->getMessage(),
                ['backup_id' => $backupId ?? null]
            );
            
            if (isset($backupId)) {
                $this->db->query("
                    UPDATE system_backups 
                    SET status = 'FAILED',
                        completed_at = CURRENT_TIMESTAMP
                    WHERE backup_id = {$backupId}
                ");
            }
            
            return [
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function performDatabaseBackup($filePath) {
        // Get database credentials from config
        $dbHost = DB_HOST;
        $dbUser = DB_USER;
        $dbPass = DB_PASSWORD;
        $dbName = DB_NAME;
        
        // Construct mysqldump command
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s %s > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($filePath)
        );
        
        // Execute backup
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new Exception('Database backup failed');
        }
    }
    
    public function getBackups() {
        return $this->db->query("
            SELECT 
                b.*,
                a.username as created_by_username,
                CASE 
                    WHEN status IN ('PENDING', 'IN_PROGRESS') THEN 0
                    WHEN status = 'FAILED' THEN 2
                    ELSE 1
                END as status_order
            FROM system_backups b
            LEFT JOIN admins a ON b.created_by = a.admin_id
            ORDER BY status_order, started_at DESC
        ")->fetch_all(MYSQLI_ASSOC);
    }
    
    public function restoreBackup($backupId) {
        try {
            // Get backup details
            $stmt = $this->db->prepare("
                SELECT * FROM system_backups 
                WHERE backup_id = ? AND status = 'COMPLETED'
            ");
            $stmt->bind_param("i", $backupId);
            $stmt->execute();
            $backup = $stmt->get_result()->fetch_assoc();
            
            if (!$backup) {
                throw new Exception('Invalid backup selected');
            }
            
            // Verify backup file exists and is readable
            if (!file_exists($backup['file_path']) || !is_readable($backup['file_path'])) {
                throw new Exception('Backup file not accessible');
            }
            
            // Verify checksum
            $currentChecksum = hash_file('sha256', $backup['file_path']);
            if ($currentChecksum !== $backup['checksum']) {
                throw new Exception('Backup file integrity check failed');
            }
            
            // Perform restore
            $this->restoreDatabaseFromBackup($backup['file_path']);
            
            // Log successful restore
            $this->writeSystemLog(
                'backup',
                'INFO',
                'Database restored successfully from backup #' . $backupId,
                ['backup_id' => $backupId]
            );
            
            return [
                'success' => true,
                'message' => 'Database restored successfully'
            ];
            
        } catch (Exception $e) {
            $this->writeSystemLog(
                'backup',
                'ERROR',
                'Database restore failed: ' . $e->getMessage(),
                ['backup_id' => $backupId]
            );
            
            return [
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function restoreDatabaseFromBackup($filePath) {
        // Get database credentials from config
        $dbHost = DB_HOST;
        $dbUser = DB_USER;
        $dbPass = DB_PASSWORD;
        $dbName = DB_NAME;
        
        // Construct mysql command for restore
        $command = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($filePath)
        );
        
        // Execute restore
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new Exception('Database restore failed');
        }
    }
    
    public function checkForUpdates() {
        // In a real application, this would check against a remote update server
        // For demonstration, we'll return a mock update
        return [
            'has_update' => true,
            'current_version' => '1.0.0',
            'latest_version' => '1.1.0',
            'update_type' => 'FEATURE',
            'description' => 'New features and improvements',
            'changelog' => [
                'Added new dashboard widgets',
                'Improved order processing performance',
                'Fixed various bugs'
            ]
        ];
    }
    
    public function getSystemStatus() {
        return [
            'php_version' => phpversion(),
            'mysql_version' => $this->db->get_server_info(),
            'disk_space' => [
                'total' => disk_total_space('/'),
                'free' => disk_free_space('/')
            ],
            'memory_usage' => [
                'limit' => ini_get('memory_limit'),
                'used' => memory_get_usage(true)
            ],
            'last_backup' => $this->getLastBackupInfo(),
            'pending_updates' => $this->getPendingUpdatesCount(),
            'active_sessions' => $this->getActiveSessionsCount()
        ];
    }
    
    private function getLastBackupInfo() {
        return $this->db->query("
            SELECT * FROM system_backups
            WHERE status = 'COMPLETED'
            ORDER BY completed_at DESC
            LIMIT 1
        ")->fetch_assoc();
    }
    
    private function getPendingUpdatesCount() {
        return $this->db->query("
            SELECT COUNT(*) as count
            FROM system_updates
            WHERE status = 'PENDING'
        ")->fetch_assoc()['count'];
    }
    
    private function getActiveSessionsCount() {
        return $this->db->query("
            SELECT COUNT(DISTINCT session_id) as count
            FROM user_sessions
            WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ")->fetch_assoc()['count'];
    }
}
