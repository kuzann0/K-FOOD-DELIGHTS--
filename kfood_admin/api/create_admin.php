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
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $roleId = $_POST['roleId'];
    $crewAdminId = isset($_POST['crewAdminId']) ? $_POST['crewAdminId'] : null;

    // Validate role ID
    if (!in_array($roleId, [2, 3])) {
        throw new Exception('Invalid role ID');
    }

    // Validate crew admin assignment
    if ($roleId == 3 && empty($crewAdminId)) {
        throw new Exception('Crew members must be assigned to a crew admin');
    }

    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Username already exists');
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Email already exists');
    }

    // Create admin account
    $stmt = $conn->prepare("
        INSERT INTO admin_users (username, email, password, role_id, crew_admin_id, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt->bind_param("sssii", $username, $email, $hashedPassword, $roleId, $crewAdminId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Administrator account created successfully'
        ]);
    } else {
        throw new Exception('Failed to create administrator account');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
