<?php
class NotificationHandler {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Create a new notification
     */
    public function createNotification($type, $referenceId, $message, $roleId = null, $userId = null) {
        $sql = "INSERT INTO notifications (notification_type, reference_id, user_id, role_id, message)
                VALUES (?, ?, ?, ?, ?)";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("siiss", $type, $referenceId, $userId, $roleId, $message);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notifications for a user or role
     */
    public function getNotifications($userId = null, $roleId = null, $limit = 10) {
        $conditions = [];
        $params = [];
        $types = "";

        if ($userId !== null) {
            $conditions[] = "user_id = ?";
            $params[] = $userId;
            $types .= "i";
        }

        if ($roleId !== null) {
            $conditions[] = "role_id = ?";
            $params[] = $roleId;
            $types .= "i";
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" OR ", $conditions) : "";
        
        $sql = "SELECT * FROM notifications 
                $whereClause 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $params[] = $limit;
        $types .= "i";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $notifications = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $notifications;
        } catch (Exception $e) {
            error_log("Error fetching notifications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark notifications as read
     */
    public function markAsRead($notificationIds, $userId = null) {
        if (!is_array($notificationIds)) {
            $notificationIds = [$notificationIds];
        }

        $placeholders = str_repeat("?,", count($notificationIds) - 1) . "?";
        $sql = "UPDATE notifications 
                SET status = 'read' 
                WHERE notification_id IN ($placeholders)";

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
        }

        try {
            $stmt = $this->conn->prepare($sql);
            $types = str_repeat("i", count($notificationIds)) . ($userId !== null ? "i" : "");
            $params = $notificationIds;
            if ($userId !== null) {
                $params[] = $userId;
            }
            $stmt->bind_param($types, ...$params);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            error_log("Error marking notifications as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notifications for a new order
     */
    public function sendOrderNotifications($orderId, $orderData) {
        // Format amount for display
        $formattedAmount = number_format($orderData['amounts']['total'], 2);
        $orderNumber = $orderData['orderNumber'];
        
        // Notify crew (role_id = 3)
        $crewMsg = sprintf(
            "New order #%s received! Total: ₱%s\nCustomer: %s\nPhone: %s",
            $orderNumber,
            $formattedAmount,
            $orderData['customerInfo']['fullName'],
            $orderData['customerInfo']['phone']
        );
        $this->createNotification('order', $orderId, $crewMsg, 3);
        
        // Notify admin (role_id = 2)
        $adminMsg = sprintf(
            "New order #%s placed. Amount: ₱%s\nItems: %s",
            $orderNumber,
            $formattedAmount,
            implode(", ", array_map(function($item) {
                return $item['quantity'] . 'x ' . $item['name'];
            }, $orderData['cartItems']))
        );
        $this->createNotification('order', $orderId, $adminMsg, 2);
    }

    /**
     * Send order status update notifications
     */
    public function sendOrderStatusNotification($orderId, $orderNumber, $status, $userId = null) {
        // Create message for user
        $userMsg = sprintf(
            "Your order #%s status has been updated to: %s",
            $orderNumber,
            $status
        );
        
        // Send to specific user
        if ($userId) {
            $this->createNotification('order_status', $orderId, $userMsg, null, $userId);
        }
        
        // Notify crew and admin
        $staffMsg = sprintf(
            "Order #%s has been marked as %s",
            $orderNumber,
            $status
        );
        
        // Notify crew (role_id = 3)
        $this->createNotification('order_status', $orderId, $staffMsg, 3);
        
        // Notify admin (role_id = 2)
        $this->createNotification('order_status', $orderId, $staffMsg, 2);
    }
}