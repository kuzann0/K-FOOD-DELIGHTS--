<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Ensure only authorized admins can access this endpoint
requireAdminLogin();

if (!hasPermission('manage_system')) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized access']));
}

// Get maintenance settings
function getMaintenanceSettings() {
    global $conn;
    
    $settings = [];
    $result = $conn->query("
        SELECT setting_key, setting_value, is_active
        FROM system_settings
        WHERE setting_key IN ('system_status', 'maintenance_message', 'maintenance_schedule')
    ");
    
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = [
            'value' => $row['setting_value'],
            'is_active' => $row['is_active']
        ];
    }
    
    return $settings;
}

// Update maintenance settings
function updateMaintenanceSettings($data) {
    global $conn;
    
    $systemStatus = $data['system_status'] ?? null;
    $maintenanceMessage = $data['maintenance_message'] ?? null;
    $maintenanceSchedule = $data['maintenance_schedule'] ?? null;
    
    try {
        $conn->begin_transaction();
        
        if ($systemStatus !== null) {
            $stmt = $conn->prepare("
                INSERT INTO system_settings (setting_key, setting_value, is_active, updated_by)
                VALUES ('system_status', ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                is_active = VALUES(is_active),
                updated_by = VALUES(updated_by)
            ");
            
            $isActive = $systemStatus === 'maintenance' ? 1 : 0;
            $stmt->bind_param("sii", $systemStatus, $isActive, $_SESSION['admin_id']);
            $stmt->execute();
        }
        
        if ($maintenanceMessage !== null) {
            $stmt = $conn->prepare("
                INSERT INTO system_settings (setting_key, setting_value, updated_by)
                VALUES ('maintenance_message', ?, ?)
                ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                updated_by = VALUES(updated_by)
            ");
            
            $stmt->bind_param("si", $maintenanceMessage, $_SESSION['admin_id']);
            $stmt->execute();
        }
        
        if ($maintenanceSchedule !== null) {
            $stmt = $conn->prepare("
                INSERT INTO system_settings (setting_key, setting_value, updated_by)
                VALUES ('maintenance_schedule', ?, ?)
                ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                updated_by = VALUES(updated_by)
            ");
            
            $scheduleJson = json_encode($maintenanceSchedule);
            $stmt->bind_param("si", $scheduleJson, $_SESSION['admin_id']);
            $stmt->execute();
        }
        
        // Log the maintenance update
        logAuditTrail(
            'update',
            'system_settings',
            0,
            json_encode([
                'type' => 'maintenance_update',
                'changes' => $data
            ])
        );
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// Handle API request
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $response = getMaintenanceSettings();
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception('Invalid request data');
            }
            
            $response = [
                'success' => updateMaintenanceSettings($data),
                'message' => 'Maintenance settings updated successfully'
            ];
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
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
