<?php
require_once '../config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

try {
    // Get user's profile picture
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Delete user's profile picture if it's not the default
    if ($user['profile_picture'] !== 'default.jpg') {
        $picturePath = '../uploads/profile/' . $user['profile_picture'];
        if (file_exists($picturePath)) {
            unlink($picturePath);
        }
    }
    
    // Delete user from database
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        // Clear session
        session_destroy();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting account']);
    }
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
