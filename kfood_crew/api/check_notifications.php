<?php
session_start();
require_once '../../k_food_customer/config.php';
require_once '../includes/crew_auth.php';
require_once '../../k_food_customer/includes/NotificationHandler.php';

// Validate crew session
validateCrewSession();

header('Content-Type: application/json');

try {
    $notificationHandler = new NotificationHandler($conn);
    
    // Get notifications for crew role
    $notifications = $notificationHandler->getNotifications(null, 3, 10);

    echo json_encode([
        'success' => true,
        'notifications' => $notifications
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching notifications: ' . $e->getMessage()
    ]);
}

$conn->close();