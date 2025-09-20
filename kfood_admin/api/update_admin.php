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
    $adminId = $_POST['adminId'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $roleId = $_POST['roleId'];
    $newPassword = $_POST['newPassword'];

    // Check if trying to edit super admin
    $stmt = $conn->prepare("SELECT role_id FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Administrator not found');
    }
    
    $admin = $result->fetch_assoc();
    if ($admin['role_id'] === 1) {
        throw new Exception('Cannot edit super administrator');
    }

    // Check if username exists (except for current admin)
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $adminId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Username already exists');
    }

    // Check if email exists (except for current admin)
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $adminId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Email already exists');
    }

    // Update admin
    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            UPDATE admin_users 
            SET username = ?, email = ?, password = ?, role_id = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("sssii", $username, $email, $hashedPassword, $roleId, $adminId);
    } else {
        $stmt = $conn->prepare("
            UPDATE admin_users 
            SET username = ?, email = ?, role_id = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("ssii", $username, $email, $roleId, $adminId);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Administrator updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update administrator');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
