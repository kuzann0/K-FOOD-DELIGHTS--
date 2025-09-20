<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Ensure only authenticated admins can access this endpoint
requireAdminLogin();

// Get dashboard statistics
function getDashboardStats() {
    global $conn;
    
    // Get orders and revenue stats
    $today = date('Y-m-d');
    $thisMonth = date('Y-m');
    $lastMonth = date('Y-m', strtotime('-1 month'));
    
    $orderStats = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = '$today') as today_orders,
            (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = '$today') as today_revenue,
            (SELECT COUNT(*) FROM orders WHERE DATE(created_at) LIKE '$thisMonth%') as month_orders,
            (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) LIKE '$thisMonth%') as month_revenue,
            (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) LIKE '$lastMonth%') as last_month_revenue,
            (SELECT COALESCE(AVG(total_amount), 0) FROM orders WHERE DATE(created_at) LIKE '$thisMonth%') as avg_order_value,
            (SELECT COUNT(*) FROM orders WHERE status = 'pending') as pending_orders,
            (SELECT COUNT(*) FROM orders WHERE status = 'processing') as processing_orders,
            (SELECT COUNT(*) FROM orders WHERE status = 'completed') as completed_orders,
            (SELECT COUNT(*) FROM orders WHERE status = 'cancelled') as cancelled_orders
        FROM dual
    ")->fetch_assoc();
    
    // Get inventory stats
    $inventoryStats = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM menu_items WHERE is_available = 1) as available_items,
            (SELECT COUNT(*) FROM menu_items WHERE is_available = 0) as unavailable_items,
            (SELECT COUNT(*) FROM menu_items WHERE preparation_time <= 15) as quick_prep_items,
            (SELECT COUNT(*) FROM categories WHERE active = 1) as active_categories,
            (SELECT COUNT(*) FROM inventory_items WHERE current_stock <= minimum_stock) as low_stock_count
        FROM dual
    ")->fetch_assoc();
    
    // Get user stats
    $lastMonth = date('Y-m-d', strtotime('-30 days'));
    $userStats = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE is_active = 1) as active_users,
            (SELECT COUNT(*) FROM users WHERE DATE(created_at) >= '$lastMonth') as new_users,
            (SELECT COUNT(*) FROM users) as total_users,
            (SELECT COUNT(DISTINCT user_id) FROM orders WHERE DATE(created_at) >= '$lastMonth') as returning_customers
        FROM dual
    ")->fetch_assoc();
    
    // Calculate revenue growth
    $growth = 0;
    if ($orderStats['last_month_revenue'] > 0) {
        $growth = (($orderStats['month_revenue'] - $orderStats['last_month_revenue']) / $orderStats['last_month_revenue']) * 100;
    }
    
    return [
        'orders' => [
            'today_orders' => $orderStats['today_orders'],
            'month_orders' => $orderStats['month_orders'],
            'pending_orders' => $orderStats['pending_orders'],
            'processing_orders' => $orderStats['processing_orders'],
            'completed_orders' => $orderStats['completed_orders'],
            'cancelled_orders' => $orderStats['cancelled_orders']
        ],
        'revenue' => [
            'today_revenue' => $orderStats['today_revenue'],
            'month_revenue' => $orderStats['month_revenue'],
            'avg_order_value' => $orderStats['avg_order_value'],
            'growth' => $growth
        ],
        'inventory' => [
            'available_items' => $inventoryStats['available_items'],
            'unavailable_items' => $inventoryStats['unavailable_items'],
            'quick_prep_items' => $inventoryStats['quick_prep_items'],
            'active_categories' => $inventoryStats['active_categories'],
            'low_stock_count' => $inventoryStats['low_stock_count']
        ],
        'users' => [
            'active_users' => $userStats['active_users'],
            'new_users' => $userStats['new_users'],
            'total_users' => $userStats['total_users'],
            'returning_customers' => $userStats['returning_customers']
        ]
    ];
}

// Get sales chart data
function getSalesChartData() {
    global $conn;
    
    // Get last 30 days sales with hourly breakdown for today
    $today = date('Y-m-d');
    $result = $conn->query("
        SELECT 
            CASE 
                WHEN DATE(created_at) = CURDATE() 
                THEN CONCAT(DATE(created_at), ' ', HOUR(created_at), ':00')
                ELSE DATE(created_at)
            END as date_point,
            COUNT(*) as orders,
            COALESCE(SUM(total_amount), 0) as revenue,
            COALESCE(AVG(total_amount), 0) as avg_order_value,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY date_point
        ORDER BY created_at
    ");
    
    $data = [
        'labels' => [],
        'revenue' => [],
        'orders' => [],
        'avg_value' => [],
        'completion_rate' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        $date = strtotime($row['date_point']);
        $label = date('H:i', $date) === '00:00' ? date('M j', $date) : date('M j H:i', $date);
        
        $data['labels'][] = $label;
        $data['revenue'][] = round($row['revenue'], 2);
        $data['orders'][] = (int)$row['orders'];
        $data['avg_value'][] = round($row['avg_order_value'], 2);
        $data['completion_rate'][] = $row['orders'] > 0 ? round(($row['completed_orders'] / $row['orders']) * 100, 1) : 0;
    }
    
    return $data;
}

// Get popular items data
function getPopularItemsData() {
    global $conn;
    
    // Get top 10 popular items from last 30 days
    $result = $conn->query("
        SELECT mi.name,
               COUNT(*) as order_count
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.item_id
        JOIN orders o ON oi.order_id = o.order_id
        WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY mi.item_id
        ORDER BY order_count DESC
        LIMIT 10
    ");
    
    $labels = [];
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['name'];
        $data[] = $row['order_count'];
    }
    
    return [
        'labels' => $labels,
        'data' => $data
    ];
}

// Get system alerts
function getSystemAlerts() {
    global $conn;
    
    $alerts = [];
    
    // Check for low stock items
    $lowStockItems = $conn->query("
        SELECT name, current_stock, minimum_stock
        FROM inventory_items
        WHERE current_stock <= minimum_stock
        LIMIT 5
    ");
    
    while ($item = $lowStockItems->fetch_assoc()) {
        $alerts[] = [
            'type' => 'warning',
            'title' => 'Low Stock Alert',
            'message' => "'{$item['name']}' is running low on stock ({$item['current_stock']} remaining)",
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    // Check for items nearing expiration
    $expiringItems = $conn->query("
        SELECT name, expiration_date
        FROM inventory_items
        WHERE expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        LIMIT 5
    ");
    
    while ($item = $expiringItems->fetch_assoc()) {
        $alerts[] = [
            'type' => 'danger',
            'title' => 'Expiration Alert',
            'message' => "'{$item['name']}' will expire on {$item['expiration_date']}",
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    // Add system maintenance notices if any
    $maintenanceNotices = $conn->query("
        SELECT message, created_at
        FROM system_settings
        WHERE setting_key = 'maintenance_notice'
        AND is_active = 1
        LIMIT 1
    ");
    
    if ($notice = $maintenanceNotices->fetch_assoc()) {
        $alerts[] = [
            'type' => 'info',
            'title' => 'System Maintenance',
            'message' => $notice['message'],
            'timestamp' => $notice['created_at']
        ];
    }
    
    return $alerts;
}

// Get recent orders
function getRecentOrders() {
    global $conn;
    
    $result = $conn->query("
        SELECT o.order_id,
               CONCAT(u.first_name, ' ', u.last_name) as customer_name,
               o.total_amount,
               o.status,
               o.created_at as order_time
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    return $orders;
}

// Compile all dashboard data
$dashboardData = [
    'stats' => getDashboardStats(),
    'charts' => [
        'sales' => getSalesChartData(),
        'popularItems' => getPopularItemsData()
    ],
    'alerts' => getSystemAlerts(),
    'recentOrders' => getRecentOrders()
];

// Return JSON response
header('Content-Type: application/json');
echo json_encode($dashboardData);
