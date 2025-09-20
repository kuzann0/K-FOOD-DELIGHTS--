<?php

use WebSocket\Client;

/**
 * Admin Notification Handler trait
 * Handles sending notifications to administrators through multiple channels
 */
trait AdminNotificationHandler {
    /**
     * Send notifications to admin through multiple channels (email and admin interface)
     * 
     * @param array $errorData The error data to send
     * @return void
     */
    private function notifyAdmin(array $errorData): void {
        try {
            // Send email notification
            $this->sendAdminEmail($errorData);
            
            // Store notification in database for admin interface
            $this->storeAdminNotification($errorData);
            
            // Send real-time notification if WebSocket server is available
            $this->sendRealTimeNotification($errorData);
            
        } catch (Throwable $e) {
            error_log("Failed to send admin notification: " . $e->getMessage());
            // Attempt to log to file as last resort
            $this->logToFile([
                'type' => 'ADMIN_NOTIFICATION_FAILED',
                'originalError' => $errorData,
                'notificationError' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email notification to admin
     * 
     * @param array $errorData The error data to send
     * @return void
     */
    private function sendAdminEmail(array $errorData): void {
        $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@kfood-delight.com';
        $subject = "Critical Error Alert - K-Food Delight";
        
        $message = "A critical error occurred:\n\n";
        $message .= "Error ID: {$errorData['errorId']}\n";
        $message .= "Type: {$errorData['type']}\n";
        $message .= "Message: {$errorData['message']}\n";
        $message .= "User Message: {$errorData['userMessage']}\n";
        $message .= "File: {$errorData['file']}\n";
        $message .= "Line: {$errorData['line']}\n";
        $message .= "Time: {$errorData['timestamp']}\n\n";
        $message .= "Stack Trace:\n{$errorData['trace']}";
        
        if (!mail($adminEmail, $subject, $message)) {
            throw new Exception("Failed to send admin email notification");
        }
    }

    /**
     * Store notification in database for admin interface
     * 
     * @param array $errorData The error data to store
     * @return void
     */
    private function storeAdminNotification(array $errorData): void {
        $sql = "INSERT INTO admin_notifications (
            notification_id,
            error_id,
            type,
            message,
            user_message,
            file_path,
            line_number,
            stack_trace,
            context_data,
            is_urgent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $notificationId = uniqid('notif_', true);
        $isUrgent = in_array($errorData['type'], $this->criticalErrorTypes) ? 1 : 0;
        
        try {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare notification statement: " . $this->conn->error);
            }

            $stmt->bind_param("sssssssssi",
                $notificationId,
                $errorData['errorId'],
                $errorData['type'],
                $errorData['message'],
                $errorData['userMessage'],
                $errorData['file'],
                $errorData['line'],
                $errorData['trace'],
                $errorData['context'],
                $isUrgent
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to store admin notification: " . $stmt->error);
            }

            $stmt->close();
        } catch (Throwable $e) {
            // Log but continue - email notification might still work
            error_log("Failed to store admin notification: " . $e->getMessage());
            throw $e; // Re-throw to be handled by caller
        }
    }

    /**
     * Send real-time notification via WebSocket if available
     * 
     * @param array $errorData The error data to send
     * @return void
     */
    private function sendRealTimeNotification(array $errorData): void {
        // Only attempt WebSocket notification if the server is configured
        if (!defined('WEBSOCKET_ENABLED') || !WEBSOCKET_ENABLED) {
            return;
        }

        try {
            $wsClient = new WebSocket\Client("ws://localhost:8080");
            
            $notification = [
                'type' => 'admin_error_notification',
                'data' => [
                    'errorId' => $errorData['errorId'],
                    'type' => $errorData['type'],
                    'message' => $errorData['message'],
                    'timestamp' => $errorData['timestamp'],
                    'is_urgent' => in_array($errorData['type'], $this->criticalErrorTypes)
                ]
            ];

            $wsClient->send(json_encode($notification));
            $wsClient->close();
        } catch (Throwable $e) {
            // Log but don't throw - WebSocket notification is optional
            error_log("Failed to send WebSocket notification: " . $e->getMessage());
        }
    }
}