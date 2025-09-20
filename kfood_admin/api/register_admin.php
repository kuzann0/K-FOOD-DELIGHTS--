<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Only super admins can register new admins
requireAdminLogin();
if (!isAdmin()) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized access']));
}

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $requiredFields = ['fullName', 'email', 'username', 'password', 'confirmPassword', 'role'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("All fields are required");
        }
    }

    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Validate password strength
    if (strlen($data['password']) < 8) {
        throw new Exception("Password must be at least 8 characters long");
    }
    if (!preg_match("/[A-Z]/", $data['password'])) {
        throw new Exception("Password must contain at least one uppercase letter");
    }
    if (!preg_match("/[a-z]/", $data['password'])) {
        throw new Exception("Password must contain at least one lowercase letter");
    }
    if (!preg_match("/[0-9]/", $data['password'])) {
        throw new Exception("Password must contain at least one number");
    }
    if (!preg_match("/[^A-Za-z0-9]/", $data['password'])) {
        throw new Exception("Password must contain at least one special character");
    }

    // Check if passwords match
    if ($data['password'] !== $data['confirmPassword']) {
        throw new Exception("Passwords do not match");
    }

    // Check for duplicate username
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $data['username']);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()['count'] > 0) {
        throw new Exception("Username already exists");
    }

    // Check for duplicate email
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_users WHERE email = ?");
    $stmt->bind_param("s", $data['email']);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()['count'] > 0) {
        throw new Exception("Email already exists");
    }

    // Verify role exists
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_roles WHERE role_id = ?");
    $stmt->bind_param("i", $data['role']);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()['count'] === 0) {
        throw new Exception("Invalid role selected");
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Insert new admin user
        $stmt = $conn->prepare("
            INSERT INTO admin_users (
                username, password, email, full_name, role_id, is_active, created_by
            ) VALUES (?, ?, ?, ?, ?, 1, ?)
        ");

        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $createdBy = $_SESSION['admin_id'];

        $stmt->bind_param(
            "ssssii",
            $data['username'],
            $passwordHash,
            $data['email'],
            $data['fullName'],
            $data['role'],
            $createdBy
        );

        if (!$stmt->execute()) {
            throw new Exception("Error creating admin user: " . $stmt->error);
        }

        $newAdminId = $stmt->insert_id;

        // Log the action
        logAuditTrail(
            'create',
            'admin_users',
            $newAdminId,
            json_encode([
                'username' => $data['username'],
                'email' => $data['email'],
                'full_name' => $data['fullName'],
                'role_id' => $data['role']
            ])
        );

        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Admin user created successfully'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
