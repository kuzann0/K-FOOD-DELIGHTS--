<?php
class AdminNotifications {
    private $conn;
    private $limit;
    
    public function __construct($conn, int $limit = 10) {
        $this->conn = $conn;
        $this->limit = $limit;
    }
    
    /**
     * Get unread notifications for display
     * 
     * @return array Array of notification data
     */
    public function getUnreadNotifications(): array {
        $sql = "SELECT * FROM admin_notifications 
                WHERE is_read = 0 
                ORDER BY created_at DESC 
                LIMIT ?";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $notifications = [];
        
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $this->formatNotification($row);
        }
        
        $stmt->close();
        return $notifications;
    }
    
    /**
     * Format notification for display
     */
    private function formatNotification(array $data): array {
        $timeAgo = $this->getTimeAgo(strtotime($data['created_at']));
        
        return [
            'id' => $data['notification_id'],
            'type' => $data['type'],
            'message' => $data['message'],
            'timeAgo' => $timeAgo,
            'isUrgent' => (bool)$data['is_urgent'],
            'details' => [
                'errorId' => $data['error_id'],
                'file' => $data['file_path'],
                'line' => $data['line_number']
            ]
        ];
    }
    
    /**
     * Convert timestamp to human-readable time difference
     */
    private function getTimeAgo(int $timestamp): string {
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } else {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead(string $notificationId): bool {
        $sql = "UPDATE admin_notifications 
                SET is_read = 1, read_at = CURRENT_TIMESTAMP 
                WHERE notification_id = ?";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $notificationId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Get notification count badge HTML
     */
    public function getNotificationBadge(): string {
        $sql = "SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0";
        $result = $this->conn->query($sql);
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            $urgentClass = $this->hasUrgentNotifications() ? 'badge-danger' : 'badge-warning';
            return '<span class="badge ' . $urgentClass . ' notification-badge">' . $count . '</span>';
        }
        
        return '';
    }
    
    /**
     * Check for urgent notifications
     */
    private function hasUrgentNotifications(): bool {
        $sql = "SELECT 1 FROM admin_notifications 
                WHERE is_read = 0 AND is_urgent = 1 
                LIMIT 1";
        $result = $this->conn->query($sql);
        return $result->num_rows > 0;
    }
    
    /**
     * Get the count of unread notifications
     * 
     * @return int Number of unread notifications
     */
    public function getUnreadCount(): int {
        $sql = "SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0";
        $result = $this->conn->query($sql);
        return (int)$result->fetch_assoc()['count'];
    }
}