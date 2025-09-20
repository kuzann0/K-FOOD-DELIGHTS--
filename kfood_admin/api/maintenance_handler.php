<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/MaintenanceHandler.php';

// Ensure only authorized admins can access this endpoint
requireAdminLogin();

if (!hasPermission('manage_system')) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized access']));
}

$maintenance = new MaintenanceHandler($mysqli);

// Handle API request
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? 'settings';
            
            switch ($action) {
                case 'settings':
                    $response = getMaintenanceSettings();
                    break;
                    
                case 'logs':
                    $filters = [
                        'type' => $_GET['type'] ?? null,
                        'level' => $_GET['level'] ?? null,
                        'date_from' => $_GET['date_from'] ?? null,
                        'date_to' => $_GET['date_to'] ?? null
                    ];
                    $response = $maintenance->getSystemLogs($filters);
                    break;
                    
                case 'backups':
                    $response = $maintenance->getBackups();
                    break;
                    
                case 'updates':
                    $response = $maintenance->checkForUpdates();
                    break;
                    
                case 'status':
                    $response = $maintenance->getSystemStatus();
                    break;
                    
                default:
                    throw new Exception('Invalid action specified');
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception('Invalid request data');
            }
            
            $action = $data['action'] ?? 'update_settings';
            
            switch ($action) {
                case 'update_settings':
                    $response = [
                        'success' => updateMaintenanceSettings($data),
                        'message' => 'Maintenance settings updated successfully'
                    ];
                    break;
                    
                case 'create_backup':
                    $response = $maintenance->createBackup(
                        $data['type'] ?? 'FULL',
                        $data['notes'] ?? ''
                    );
                    break;
                    
                case 'restore_backup':
                    if (empty($data['backup_id'])) {
                        throw new Exception('Backup ID is required');
                    }
                    $response = $maintenance->restoreBackup($data['backup_id']);
                    break;
                    
                case 'log_event':
                    if (empty($data['type']) || empty($data['level']) || empty($data['message'])) {
                        throw new Exception('Type, level, and message are required for logging');
                    }
                    $success = $maintenance->writeSystemLog(
                        $data['type'],
                        $data['level'],
                        $data['message'],
                        $data['context'] ?? null
                    );
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Event logged successfully' : 'Failed to log event'
                    ];
                    break;
                    
                default:
                    throw new Exception('Invalid action specified');
            }
            break;
            
        default:
            http_response_code(405);
            $response = ['error' => 'Method not allowed'];
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'error' => 'Server error',
        'message' => $e->getMessage()
    ];
    
    // Log the error
    if (isset($maintenance)) {
        $maintenance->writeSystemLog(
            'api_error',
            'ERROR',
            $e->getMessage(),
            [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]
        );
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
