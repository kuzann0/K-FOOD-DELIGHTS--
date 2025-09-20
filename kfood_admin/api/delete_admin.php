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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        throw new Exception('Admin ID is required');
    }

    // Check if trying to delete super admin
    $stmt = $conn->prepare("SELECT role_id FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Administrator not found');
    }
    
    $admin = $result->fetch_assoc();
    if ($admin['role_id'] === 1) {
        throw new Exception('Cannot delete super administrator');
    }

    // Delete admin
    $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Administrator deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete administrator');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
