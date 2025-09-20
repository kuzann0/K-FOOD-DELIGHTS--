<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

class DashboardHandler {
    private $db;
    private $auth;

    public function __construct() {
        global $mysqli;
        $this->db = $mysqli;
        $this->auth = new Auth();
    }

    public function getMetrics() {
        try {
            // Verify admin authentication
            if (!$this->auth->isAdminAuthenticated()) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }

            $metrics = [
                'orders' => $this->getOrderMetrics(),
                'users' => $this->getUserMetrics(),
                'inventory' => $this->getInventoryMetrics(),
                'revenue' => $this->getRevenueMetrics()
            ];

            return ['success' => true, 'data' => $metrics];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to fetch dashboard metrics: ' . $e->getMessage()];
        }
    }

    private function getOrderMetrics() {
        $today = date('Y-m-d');
        $thisMonth = date('Y-m');
        
        $query = "SELECT 
            (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?) as today_orders,
            (SELECT COUNT(*) FROM orders WHERE DATE(created_at) LIKE ?) as month_orders,
            (SELECT COUNT(*) FROM orders WHERE status = 'pending') as pending_orders,
            (SELECT COUNT(*) FROM orders WHERE status = 'processing') as processing_orders";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $today, $thisMonth . '%');
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result;
    }

    private function getUserMetrics() {
        $today = date('Y-m-d');
        $lastMonth = date('Y-m-d', strtotime('-30 days'));
        
        $query = "SELECT 
            (SELECT COUNT(*) FROM users WHERE active = 1) as active_users,
            (SELECT COUNT(*) FROM users WHERE DATE(created_at) >= ?) as new_users,
            (SELECT COUNT(*) FROM users) as total_users,
            (SELECT COUNT(DISTINCT user_id) FROM orders WHERE DATE(created_at) >= ?) as returning_customers";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $lastMonth, $lastMonth);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result;
    }

    private function getInventoryMetrics() {
        $query = "SELECT 
            (SELECT COUNT(*) FROM menu_items WHERE is_available = 1) as available_items,
            (SELECT COUNT(*) FROM menu_items WHERE is_available = 0) as unavailable_items,
            (SELECT COUNT(*) FROM menu_items WHERE preparation_time <= 15) as quick_prep_items,
            (SELECT COUNT(*) FROM categories WHERE active = 1) as active_categories";
        
        $result = $this->db->query($query)->fetch_assoc();
        
        return $result;
    }

    private function getRevenueMetrics() {
        $today = date('Y-m-d');
        $thisMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('-1 month'));
        
        $query = "SELECT 
            (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = ?) as today_revenue,
            (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) LIKE ?) as month_revenue,
            (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) LIKE ?) as last_month_revenue,
            (SELECT COALESCE(AVG(total_amount), 0) FROM orders WHERE DATE(created_at) LIKE ?) as avg_order_value";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssss", $today, $thisMonth . '%', $lastMonth . '%', $thisMonth . '%');
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        // Calculate month-over-month growth
        if ($result['last_month_revenue'] > 0) {
            $result['mom_growth'] = (($result['month_revenue'] - $result['last_month_revenue']) / $result['last_month_revenue']) * 100;
        } else {
            $result['mom_growth'] = 0;
        }
        
        return $result;
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $handler = new DashboardHandler();
    header('Content-Type: application/json');
    echo json_encode($handler->getMetrics());
}
?>
