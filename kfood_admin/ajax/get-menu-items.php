<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check for required permissions
if (!hasPermission('manage_menu')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

try {
    // Fetch menu items with category names
    $sql = "SELECT mi.*, mc.name as category_name 
            FROM menu_items mi 
            LEFT JOIN menu_categories mc ON mi.category_id = mc.category_id 
            ORDER BY mi.created_at DESC";
    
    $stmt = $pdo->query($sql);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch menu items'
    ]);
}