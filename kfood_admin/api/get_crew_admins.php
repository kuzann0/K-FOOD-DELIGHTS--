<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!hasPermission('manage_roles')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Permission denied'
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT a.*, r.name as role_name 
        FROM admin_users a 
        JOIN roles r ON a.role_id = r.id 
        WHERE a.role_id = 2
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $admins = [];
    while ($row = $result->fetch_assoc()) {
        // Don't expose password hash
        unset($row['password']);
        $admins[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'admins' => $admins
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch crew administrators'
    ]);
}
