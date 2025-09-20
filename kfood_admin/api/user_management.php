<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Ensure only authorized users can access this endpoint
if (!hasPermission('manage_roles')) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

header('Content-Type: application/json');

// Create new user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    
    // Validate required fields
    $required = ['username', 'email', 'password', 'roleId'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            exit(json_encode([
                'success' => false,
                'message' => "Missing required field: $field"
            ]));
        }
    }
    
    // Sanitize input
    $username = filter_var($data['username'], FILTER_SANITIZE_STRING);
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $roleId = (int)$data['roleId'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            exit(json_encode([
                'success' => false,
                'message' => 'Username or email already exists'
            ]));
        }
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$username, $email, $password, $roleId]);
        
        // Handle additional fields based on role
        $userId = $pdo->lastInsertId();
        handleRoleSpecificData($pdo, $roleId, $userId, $data);
        
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'userId' => $userId
        ]);
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred'
        ]);
    }
}

// Get users by role
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $roleId = filter_input(INPUT_GET, 'role_id', FILTER_VALIDATE_INT);
    
    if (!$roleId) {
        exit(json_encode([
            'success' => false,
            'message' => 'Invalid role ID'
        ]));
    }
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("
            SELECT id, username, email, created_at
            FROM users
            WHERE role_id = ?
            ORDER BY created_at DESC
        ");
        
        $stmt->execute([$roleId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred'
        ]);
    }
}

// Delete user
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$userId) {
        exit(json_encode([
            'success' => false,
            'message' => 'Invalid user ID'
        ]));
    }
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if user exists and is not a super admin
        $stmt = $pdo->prepare("
            SELECT role_id FROM users WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            exit(json_encode([
                'success' => false,
                'message' => 'User not found'
            ]));
        }
        
        if ($user['role_id'] === 1) {
            exit(json_encode([
                'success' => false,
                'message' => 'Cannot delete super admin account'
            ]));
        }
        
        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred'
        ]);
    }
}

function handleRoleSpecificData($pdo, $roleId, $userId, $data) {
    switch ($roleId) {
        case 2: // Admin
            if (isset($data['permissions'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO admin_permissions (user_id, permission)
                    VALUES (?, ?)
                ");
                
                foreach ($data['permissions'] as $permission) {
                    $stmt->execute([$userId, $permission]);
                }
            }
            break;
            
        case 3: // Crew
            if (isset($data['shift'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO crew_details (user_id, shift)
                    VALUES (?, ?)
                ");
                $stmt->execute([$userId, $data['shift']]);
            }
            break;
            
        case 4: // Customer
            if (isset($data['phone']) && isset($data['address'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO customer_details (user_id, phone, address)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $userId,
                    filter_var($data['phone'], FILTER_SANITIZE_STRING),
                    filter_var($data['address'], FILTER_SANITIZE_STRING)
                ]);
            }
            break;
    }
}
