<?php
require_once 'config.php';
require_once 'includes/SessionManager.php';

$sessionManager = new SessionManager();
$sessionManager->startSecureSession();

// Verify customer access
if (!$sessionManager->isCustomer()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid security token']);
    exit();
}

// Function to validate product ID
function validateProductId($id) {
    return is_numeric($id) && $id > 0;
}

// Handle cart operations
$action = $_POST['action'] ?? '';
$productId = $_POST['product_id'] ?? 0;

if (!validateProductId($productId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit();
}

// Use prepared statements for all database operations
switch ($action) {
    case 'add':
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1) 
                              ON DUPLICATE KEY UPDATE quantity = quantity + 1");
        $stmt->bind_param("ii", $_SESSION['user_id'], $productId);
        break;
        
    case 'remove':
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $productId);
        break;
        
    case 'update':
        $quantity = $_POST['quantity'] ?? 0;
        if ($quantity < 1) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid quantity']);
            exit();
        }
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("iii", $quantity, $_SESSION['user_id'], $productId);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit();
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
$stmt->close();