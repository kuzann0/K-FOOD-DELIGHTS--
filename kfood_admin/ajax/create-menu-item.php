<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check for required permissions
if (!hasPermission('manage_menu')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

// Validate required fields
$required_fields = ['category_id', 'name', 'price'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'error' => ucfirst($field) . ' is required']);
        exit();
    }
}

// Sanitize and validate input
$category_id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
$name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
$description = filter_var($_POST['description'] ?? '', FILTER_SANITIZE_STRING);
$price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
$image_url = filter_var($_POST['image_url'] ?? '', FILTER_VALIDATE_URL);
$is_available = isset($_POST['is_available']) ? 1 : 0;

// Additional validation
if ($category_id === false || $category_id < 1) {
    echo json_encode(['success' => false, 'error' => 'Invalid category']);
    exit();
}

if ($price === false || $price < 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid price']);
    exit();
}

try {
    // Prepare the SQL statement
    $sql = "INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, created_at, updated_at) 
            VALUES (:category_id, :name, :description, :price, :image_url, :is_available, NOW(), NOW())";
    
    $stmt = $pdo->prepare($sql);
    
    // Execute with parameters
    $success = $stmt->execute([
        ':category_id' => $category_id,
        ':name' => $name,
        ':description' => $description,
        ':price' => $price,
        ':image_url' => $image_url,
        ':is_available' => $is_available
    ]);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Menu item created successfully',
            'item_id' => $pdo->lastInsertId()
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create menu item'
        ]);
    }

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'A database error occurred'
    ]);
}