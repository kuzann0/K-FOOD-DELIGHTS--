<?php
session_start();
require_once '../includes/DatabaseConnection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }

    $userId = $_SESSION['user_id'];
    $language = $data['language'];
    $notifications = json_encode($data['notifications']);

    $db = new DatabaseConnection();
    $conn = $db->getConnection();

    // Check if preferences exist
    $stmt = $conn->prepare("SELECT id FROM user_preferences WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing preferences
        $stmt = $conn->prepare("UPDATE user_preferences SET language = ?, notifications = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $language, $notifications, $userId);
    } else {
        // Insert new preferences
        $stmt = $conn->prepare("INSERT INTO user_preferences (user_id, language, notifications) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userId, $language, $notifications);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update preferences');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>