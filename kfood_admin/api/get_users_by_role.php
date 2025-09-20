<?php
header('Content-Type: application/json');
require_once('../includes/DatabaseConnection.php');

// Check if role_id is provided
if (!isset($_GET['role_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Role ID is required']);
    exit;
}

$roleId = intval($_GET['role_id']);

try {
    $db = DatabaseConnection::getInstance()->getConnection();
    
    $query = "SELECT u.*, ar.role_name 
              FROM users u 
              INNER JOIN admin_roles ar ON u.role_id = ar.role_id 
              WHERE u.role_id = :role_id 
              ORDER BY u.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':role_id', $roleId, PDO::PARAM_INT);
    $stmt->execute();
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sanitize sensitive data
    foreach ($users as &$user) {
        unset($user['password']);
        unset($user['remember_token']);
        unset($user['password_reset_token']);
        unset($user['password_reset_expires']);
        
        // Format dates
        $user['created_at'] = date('Y-m-d H:i:s', strtotime($user['created_at']));
        $user['updated_at'] = date('Y-m-d H:i:s', strtotime($user['updated_at']));
    }
    
    echo json_encode($users);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>