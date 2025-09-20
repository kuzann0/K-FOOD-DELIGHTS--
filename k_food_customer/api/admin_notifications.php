<?php
require_once '../config/database.php';
require_once '../config/admin_config.php';
require_once '../includes/AdminNotifications.php';

header('Content-Type: application/json');

// Verify admin authentication
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$notifications = new AdminNotifications($conn);

// Get notifications
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $result = $notifications->getUnreadNotifications();
        echo json_encode(['success' => true, 'notifications' => $result]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to load notifications']);
    }
}

// Mark notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['notificationId'])) {
        try {
            $success = $notifications->markAsRead($data['notificationId']);
            echo json_encode(['success' => $success]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to update notification']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing notification ID']);
    }
}