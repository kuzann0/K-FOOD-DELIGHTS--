<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Ensure only authenticated admins can access this endpoint
requireAdminLogin();

// Get notifications
function getNotifications() {
    global $conn;
    
    // Get unread notification count
    $unreadCount = $conn->query("
        SELECT COUNT(*) as count
        FROM admin_notifications
        WHERE admin_id = {$_SESSION['admin_id']}
        AND is_read = 0
    ")->fetch_assoc()['count'];
    
    // Get recent notifications
    $notifications = [];
    $result = $conn->query("
        SELECT notification_id, type, message, is_read, created_at
        FROM admin_notifications
        WHERE admin_id = {$_SESSION['admin_id']}
        ORDER BY created_at DESC
        LIMIT 10
    ");
    
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    return [
        'unread' => $unreadCount,
        'notifications' => $notifications
    ];
}

// Mark notification as read
function markNotificationRead($notificationId) {
    global $conn;
    
    $stmt = $conn->prepare("
        UPDATE admin_notifications
        SET is_read = 1
        WHERE notification_id = ?
        AND admin_id = ?
    ");
    
    $stmt->bind_param("ii", $notificationId, $_SESSION['admin_id']);
    return $stmt->execute();
}

// Mark all notifications as read
function markAllNotificationsRead() {
    global $conn;
    
    return $conn->query("
        UPDATE admin_notifications
        SET is_read = 1
        WHERE admin_id = {$_SESSION['admin_id']}
    ");
}

// Handle request
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $response = getNotifications();
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['action'])) {
                throw new Exception('Missing action parameter');
            }
            
            switch ($data['action']) {
                case 'mark_read':
                    if (!isset($data['notification_id'])) {
                        throw new Exception('Missing notification_id parameter');
                    }
                    $success = markNotificationRead($data['notification_id']);
                    $response = ['success' => $success];
                    break;
                    
                case 'mark_all_read':
                    $success = markAllNotificationsRead();
                    $response = ['success' => $success];
                    break;
                    
                default:
                    throw new Exception('Invalid action');
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
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
