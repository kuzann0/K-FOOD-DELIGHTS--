<?php
require_once '../config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    
    if (strlen($username) < 3) {
        echo json_encode(['available' => false, 'message' => 'Username must be at least 3 characters']);
        exit();
    }
    
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode([
        'available' => $result->num_rows === 0,
        'message' => $result->num_rows === 0 ? 'Username is available' : 'Username is already taken'
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
