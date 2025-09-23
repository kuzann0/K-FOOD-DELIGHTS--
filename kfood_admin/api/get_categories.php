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

try {
    $stmt = $pdo->query("
        SELECT c.*, COUNT(m.item_id) as item_count 
        FROM menu_categories c 
        LEFT JOIN menu_items m ON c.category_id = m.category_id 
        GROUP BY c.category_id 
        ORDER BY c.name
    ");
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching categories: ' . $e->getMessage()
    ]);
}