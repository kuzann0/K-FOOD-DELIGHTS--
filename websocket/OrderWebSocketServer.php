<?php
namespace KFoodDelights\WebSocket;

use Ratchet\ConnectionInterface;

/**
 * Handles real-time order updates and notifications
 */
class OrderWebSocketServer extends BaseWebSocketServer {
    private $orderManager;
    private $userConnections;
    
    public function __construct($logger) {
        parent::__construct($logger);
        $this->orderManager = new \KFoodDelights\Order\OrderManager();
        $this->userConnections = [];
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['type'])) {
                throw new \Exception("Invalid message format");
            }
            
            switch ($data['type']) {
                case 'authenticate':
                    $this->handleAuthentication($from, $data);
                    break;
                    
                case 'subscribe_orders':
                    $this->handleOrderSubscription($from, $data);
                    break;
                    
                case 'order_update':
                    $this->handleOrderUpdate($from, $data);
                    break;
                    
                case 'ping':
                    $this->handlePing($from);
                    break;

                case 'new_order':
                    $this->broadcastToRole('crew', [
                        'type' => 'new_order',
                        'order' => $this->orderManager->getOrderDetails($data['orderId'])
                    ]);
                    break;

                case 'status_update':
                    $this->handleStatusUpdate($from, $data);
                    break;
                    
                default:
                    $this->handleMessage($from, $data);
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in OrderWebSocketServer: " . $e->getMessage());
            $this->sendMessage($from, [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    protected function handleMessage(ConnectionInterface $from, $payload) {
        // Implementation for other message types
        $this->logger->debug("Received message", ['payload' => $payload]);
    }
    
    private function handleAuthentication($from, $data) {
        if (!isset($data['token'])) {
            throw new \Exception("Authentication token required");
        }
        
        // Validate token and get user info
        $userId = $this->validateUserToken($data['token']);
        
        if ($userId) {
            $this->userConnections[$from->resourceId] = $userId;
            $this->authTokens[$from->resourceId] = $data['token'];
            
            $this->sendMessage($from, [
                'type' => 'auth_success',
                'userId' => $userId
            ]);
        } else {
            throw new \Exception("Invalid authentication token");
        }
    }
    
    private function handleOrderSubscription($from, $data) {
        if (!isset($this->userConnections[$from->resourceId])) {
            throw new \Exception("Authentication required");
        }
        
        $userId = $this->userConnections[$from->resourceId];
        
        // Subscribe to order updates
        $this->orderManager->subscribeToUpdates($userId, function($update) use ($from) {
            $this->sendMessage($from, [
                'type' => 'order_update',
                'data' => $update
            ]);
        });
    }
    
    private function handleOrderUpdate($from, $data) {
        if (!isset($this->userConnections[$from->resourceId])) {
            throw new \Exception("Authentication required");
        }
        
        if (!isset($data['orderId']) || !isset($data['status'])) {
            throw new \Exception("Invalid order update data");
        }
        
        // Process order update
        $this->orderManager->updateOrder(
            $data['orderId'],
            $data['status'],
            $this->userConnections[$from->resourceId]
        );
        
        // Broadcast update to relevant clients
        $this->broadcastOrderUpdate($data['orderId'], $data['status']);
    }
    
    private function broadcastOrderUpdate($orderId, $status) {
        $orderDetails = $this->orderManager->getOrderDetails($orderId);
        
        // Find relevant connections to notify
        foreach ($this->userConnections as $connectionId => $userId) {
            if ($userId == $orderDetails['user_id'] || $userId == $orderDetails['staff_id']) {
                foreach ($this->clients as $client) {
                    if ($client->resourceId == $connectionId) {
                        $this->sendMessage($client, [
                            'type' => 'order_status_changed',
                            'orderId' => $orderId,
                            'status' => $status,
                            'timestamp' => time()
                        ]);
                    }
                }
            }
        }
    }
    
    private function validateUserToken($token) {
        // Implement proper token validation
        // For now, return a dummy user ID
        return 1;
    }

    /**
     * Broadcasts a message to all connected users with a specific role
     * @param string $role The role to broadcast to (e.g., 'crew', 'customer', 'admin')
     * @param array $message The message to broadcast
     */
    private function broadcastToRole($role, $message) {
        try {
            // Get all users with the specified role
            $userIds = $this->getUsersByRole($role);
            
            // Find connections for these users and send the message
            foreach ($this->userConnections as $connectionId => $userId) {
                if (in_array($userId, $userIds)) {
                    foreach ($this->clients as $client) {
                        if ($client->resourceId === $connectionId) {
                            $this->sendMessage($client, $message);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Error broadcasting to role: " . $e->getMessage());
        }
    }

    /**
     * Gets all user IDs with a specific role
     * @param string $role The role to look up
     * @return array Array of user IDs
     */
    private function getUsersByRole($role) {
        try {
            $conn = \KFoodDelights\Database\DatabaseConnection::getInstance()->getConnection();
            
            $query = "SELECT user_id FROM users WHERE role = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $role);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $userIds = [];
            
            while ($row = $result->fetch_assoc()) {
                $userIds[] = $row['user_id'];
            }
            
            return $userIds;
        } catch (\Exception $e) {
            $this->logger->error("Error getting users by role: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Handles order status update messages
     * @param ConnectionInterface $from The connection that sent the message
     * @param array $data The message data
     * @throws \Exception If validation fails or update is not permitted
     */
    private function handleStatusUpdate(ConnectionInterface $from, $data) {
        if (!isset($this->userConnections[$from->resourceId])) {
            throw new \Exception("Authentication required");
        }

        if (!isset($data['orderId']) || !isset($data['status'])) {
            throw new \Exception("Missing orderId or status in update request");
        }

        $userId = $this->userConnections[$from->resourceId];
        $orderId = $data['orderId'];
        $newStatus = $data['status'];

        // Validate the status transition
        if (!$this->isValidStatusTransition($orderId, $newStatus)) {
            throw new \Exception("Invalid status transition");
        }

        // Update the order status
        $updateResult = $this->orderManager->updateOrder($orderId, $newStatus, $userId);

        if ($updateResult['success']) {
            // Get updated order details
            $orderDetails = $this->orderManager->getOrderDetails($orderId);

            // Notify crew members
            $this->broadcastToRole('crew', [
                'type' => 'order_status_changed',
                'orderId' => $orderId,
                'status' => $newStatus,
                'orderDetails' => $orderDetails,
                'timestamp' => time()
            ]);

            // Notify the customer
            $this->broadcastToUser($orderDetails['customer_id'], [
                'type' => 'order_status_update',
                'orderId' => $orderId,
                'status' => $newStatus,
                'message' => $this->getStatusMessage($newStatus),
                'timestamp' => time()
            ]);

            // Log the status change
            $this->logger->info("Order status updated", [
                'orderId' => $orderId,
                'newStatus' => $newStatus,
                'updatedBy' => $userId
            ]);
        } else {
            throw new \Exception($updateResult['message'] ?? "Failed to update order status");
        }
    }

    /**
     * Broadcasts a message to a specific user
     * @param int $userId The user ID to send to
     * @param array $message The message to send
     */
    private function broadcastToUser($userId, $message) {
        foreach ($this->userConnections as $connectionId => $connectedUserId) {
            if ($connectedUserId === $userId) {
                foreach ($this->clients as $client) {
                    if ($client->resourceId === $connectionId) {
                        $this->sendMessage($client, $message);
                        break;
                    }
                }
            }
        }
    }

    /**
     * Validates if a status transition is allowed
     * @param int $orderId The order ID
     * @param string $newStatus The new status
     * @return bool Whether the transition is valid
     */
    private function isValidStatusTransition($orderId, $newStatus) {
        $validTransitions = [
            'pending' => ['accepted', 'cancelled'],
            'accepted' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready' => ['delivered', 'cancelled'],
            'delivered' => [],
            'cancelled' => []
        ];

        try {
            $currentStatus = $this->orderManager->getOrderStatus($orderId);
            return in_array($newStatus, $validTransitions[$currentStatus] ?? []);
        } catch (\Exception $e) {
            $this->logger->error("Error validating status transition: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets a user-friendly message for status updates
     * @param string $status The new status
     * @return string A user-friendly message
     */
    private function getStatusMessage($status) {
        $messages = [
            'pending' => 'Your order has been received and is pending confirmation.',
            'accepted' => 'Your order has been accepted! We\'ll start preparing it soon.',
            'preparing' => 'Your delicious food is being prepared!',
            'ready' => 'Your order is ready for delivery!',
            'delivered' => 'Your order has been delivered. Enjoy your meal!',
            'cancelled' => 'Your order has been cancelled.'
        ];

        return $messages[$status] ?? 'Your order status has been updated.';
    }
}