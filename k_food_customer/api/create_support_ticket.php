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
    $userId = $_SESSION['user_id'];
    $subject = $_POST['subject'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    
    // Basic validation
    if (empty($subject) || empty($category) || empty($description)) {
        throw new Exception('All fields are required');
    }

    $db = new DatabaseConnection();
    $conn = $db->getConnection();

    // Create support ticket
    $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, subject, category, description, status, created_at) VALUES (?, ?, ?, ?, 'open', NOW())");
    $stmt->bind_param("isss", $userId, $subject, $category, $description);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create support ticket');
    }

    $ticketId = $conn->insert_id;

    // Handle file uploads if any
    if (!empty($_FILES['attachments'])) {
        $uploadDir = '../uploads/support_attachments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $files = $_FILES['attachments'];
        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $files['tmp_name'][$i];
                $fileName = uniqid() . '_' . basename($files['name'][$i]);
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($tmpName, $filePath)) {
                    // Save file reference in database
                    $stmt = $conn->prepare("INSERT INTO support_attachments (ticket_id, file_name, file_path) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $ticketId, $files['name'][$i], $fileName);
                    $stmt->execute();
                }
            }
        }
    }

    echo json_encode(['success' => true, 'ticketId' => $ticketId]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>