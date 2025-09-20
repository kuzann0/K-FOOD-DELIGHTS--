<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Ensure only authenticated admins can access this endpoint
requireAdminLogin();

// Perform global search across different entities
function globalSearch($query) {
    global $conn;
    $results = [
        'orders' => [],
        'users' => [],
        'products' => [],
        'inventory' => []
    ];
    
    // Search in orders
    $stmt = $conn->prepare("
        SELECT o.order_id, 
               CONCAT(u.first_name, ' ', u.last_name) as customer_name,
               o.total_amount,
               o.status,
               o.created_at
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id LIKE CONCAT('%', ?, '%')
        OR u.first_name LIKE CONCAT('%', ?, '%')
        OR u.last_name LIKE CONCAT('%', ?, '%')
        LIMIT 5
    ");
    
    $stmt->bind_param("sss", $query, $query, $query);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results['orders'][] = $row;
    }
    
    // Search in users
    $stmt = $conn->prepare("
        SELECT user_id, username, first_name, last_name, email
        FROM users
        WHERE username LIKE CONCAT('%', ?, '%')
        OR first_name LIKE CONCAT('%', ?, '%')
        OR last_name LIKE CONCAT('%', ?, '%')
        OR email LIKE CONCAT('%', ?, '%')
        LIMIT 5
    ");
    
    $stmt->bind_param("ssss", $query, $query, $query, $query);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results['users'][] = $row;
    }
    
    // Search in menu items
    $stmt = $conn->prepare("
        SELECT mi.item_id, mi.name, mi.description, mi.price, pc.name as category
        FROM menu_items mi
        LEFT JOIN product_categories pc ON mi.category_id = pc.category_id
        WHERE mi.name LIKE CONCAT('%', ?, '%')
        OR mi.description LIKE CONCAT('%', ?, '%')
        OR pc.name LIKE CONCAT('%', ?, '%')
        LIMIT 5
    ");
    
    $stmt->bind_param("sss", $query, $query, $query);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results['products'][] = $row;
    }
    
    // Search in inventory
    $stmt = $conn->prepare("
        SELECT i.item_id, i.name, i.current_stock, i.unit, ic.name as category
        FROM inventory_items i
        LEFT JOIN inventory_categories ic ON i.category_id = ic.category_id
        WHERE i.name LIKE CONCAT('%', ?, '%')
        OR ic.name LIKE CONCAT('%', ?, '%')
        LIMIT 5
    ");
    
    $stmt->bind_param("ss", $query, $query);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results['inventory'][] = $row;
    }
    
    return $results;
}

// Handle request
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method !== 'GET') {
        http_response_code(405);
        throw new Exception('Method not allowed');
    }
    
    $query = trim($_GET['q'] ?? '');
    if (empty($query)) {
        throw new Exception('Search query is required');
    }
    
    $response = globalSearch($query);
    
} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'error' => 'Server error',
        'message' => $e->getMessage()
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
