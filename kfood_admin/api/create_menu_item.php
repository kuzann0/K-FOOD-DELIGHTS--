<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Ensure only authorized admins can access
if (!isAdmin() || !hasPermission('manage_menu')) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Validate required fields
$category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
$is_available = filter_input(INPUT_POST, 'is_available', FILTER_VALIDATE_BOOLEAN);

if (!$category_id || empty($name) || $price === false) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Missing or invalid required fields'
    ]);
    exit();
}

try {
    // Handle image upload
    $image_url = null;
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/menu/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES['item_image']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['item_image']['tmp_name'], $targetPath)) {
            $image_url = 'uploads/menu/' . $fileName;
        }
    }

    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO menu_items (category_id, name, description, price, image_url, is_available)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([
        $category_id,
        $name,
        $description,
        $price,
        $image_url,
        $is_available ?? true
    ])) {
        $newItemId = $pdo->lastInsertId();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Menu item created successfully',
            'item' => [
                'item_id' => $newItemId,
                'category_id' => $category_id,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'image_url' => $image_url,
                'is_available' => $is_available
            ]
        ]);
    } else {
        throw new Exception('Failed to create menu item');
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error creating menu item: ' . $e->getMessage()
    ]);
}