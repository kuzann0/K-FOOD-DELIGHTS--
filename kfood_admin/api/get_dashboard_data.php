<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Ensure the request is from an authenticated admin
requireAdminLogin();

header('Content-Type: application/json');

try {
    $section = $_GET['section'] ?? '';
    $response = ['success' => true];

    switch ($section) {
        case 'alerts':
            $response['data'] = getRecentAlerts(5);
            break;

        case 'stock':
            $response['data'] = checkLowStock();
            break;

        case 'stats':
            $response['data'] = getTodayStats();
            break;

        case 'sales_trend':
            $days = isset($_GET['days']) ? intval($_GET['days']) : 7;
            $response['data'] = getSalesTrend($days);
            break;

        case 'popular_items':
            $response['data'] = getPopularItems(5);
            break;

        default:
            // Get all dashboard data
            $response['data'] = [
                'stats' => getTodayStats(),
                'alerts' => getRecentAlerts(5),
                'stock' => checkLowStock(),
                'sales_trend' => getSalesTrend(7),
                'popular_items' => getPopularItems(5)
            ];
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while fetching dashboard data'
    ]);
    
    // Log the error
    error_log("Dashboard API Error: " . $e->getMessage());
}
?>
