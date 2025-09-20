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
    if (!isset($_GET['id'])) {
        throw new Exception('Admin ID is required');
    }

    $stmt = $conn->prepare("
        SELECT a.*, r.name as role_name 
        FROM admin_users a 
        JOIN roles r ON a.role_id = r.id 
        WHERE a.id = ?
    ");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Administrator not found');
    }
    
    $admin = $result->fetch_assoc();
    // Don't expose password hash
    unset($admin['password']);
    
    echo json_encode([
        'success' => true,
        'admin' => $admin
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
