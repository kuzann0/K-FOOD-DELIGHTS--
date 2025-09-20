<?php
session_start();
require_once 'includes/DatabaseConnection.php';
require_once 'includes/CacheManager.php';
require_once 'includes/QueryOptimizer.php';

// Initialize the query optimizer
$optimizer = QueryOptimizer::getInstance();

// Get request parameters
$action = $_GET['action'] ?? 'list_products';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$category = isset($_GET['category']) ? (int)$_GET['category'] : null;

try {
    switch($action) {
        case 'list_products':
            // Get products with pagination
            $result = $optimizer->getProducts($page, $limit, $category);
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $result['products'],
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $result['pages'],
                    'total_items' => $result['total'],
                    'items_per_page' => $limit
                ]
            ]);
            break;

        case 'process_order':
            // Validate user is logged in
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('User must be logged in to place an order');
            }

            // Validate POST request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            // Get and validate order data
            $postData = json_decode(file_get_contents('php://input'), true);
            if (!$postData) {
                throw new Exception('Invalid order data');
            }

            $items = $postData['items'] ?? [];
            $address = $postData['delivery_address'] ?? '';
            $paymentMethod = $postData['payment_method'] ?? '';

            // Validate order items
            if (empty($items)) {
                throw new Exception('Order must contain at least one item');
            }

            // Process the order
            $orderId = $optimizer->processOrder(
                $_SESSION['user_id'],
                $items,
                $address,
                $paymentMethod
            );

            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'message' => 'Order processed successfully',
                'order_id' => $orderId
            ]);
            break;

        case 'check_inventory':
            // Validate admin/crew access
            if (!isset($_SESSION['user_role']) || 
                !in_array($_SESSION['user_role'], ['administrator', 'crew'])) {
                throw new Exception('Unauthorized access');
            }

            $threshold = isset($_GET['threshold']) ? (int)$_GET['threshold'] : 10;
            $lowStock = $optimizer->checkLowStock($threshold);

            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $lowStock
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
