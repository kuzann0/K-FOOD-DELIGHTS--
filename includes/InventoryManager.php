<?php
/**
 * Inventory Manager
 * Handles real-time inventory tracking and notifications
 */
class InventoryManager {
    private static $instance = null;
    private $conn;
    private $lowStockThreshold = 10;
    private $criticalStockThreshold = 5;
    
    private function __construct() {
        $this->conn = DatabaseConnectionPool::getInstance()->getConnection();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new InventoryManager();
        }
        return self::$instance;
    }
    
    public function updateStock($itemId, $quantity, $operation = 'subtract') {
        $transactionManager = new TransactionManager($this->conn);
        
        try {
            $transactionManager->beginTransaction("inventory_$itemId");
            
            // Get current stock
            $stmt = $this->conn->prepare("
                SELECT stock_quantity, item_name, reorder_point 
                FROM menu_items 
                WHERE id = ? 
                FOR UPDATE
            ");
            $stmt->bind_param("i", $itemId);
            $stmt->execute();
            $result = $stmt->get_result();
            $item = $result->fetch_assoc();
            $stmt->close();
            
            if (!$item) {
                throw new Exception("Item not found");
            }
            
            // Calculate new quantity
            $newQuantity = $operation === 'add' 
                ? $item['stock_quantity'] + $quantity
                : $item['stock_quantity'] - $quantity;
            
            if ($newQuantity < 0) {
                throw new Exception("Insufficient stock");
            }
            
            // Update stock
            $stmt = $this->conn->prepare("
                UPDATE menu_items 
                SET stock_quantity = ?, 
                    last_updated = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $newQuantity, $itemId);
            $stmt->execute();
            $stmt->close();
            
            $transactionManager->commit();
            
            // Check stock levels and send notifications
            $this->checkStockLevels($itemId, $newQuantity, $item);
            
            return $newQuantity;
        } catch (Exception $e) {
            $transactionManager->rollback();
            throw $e;
        }
    }
    
    private function checkStockLevels($itemId, $currentStock, $item) {
        // Check if stock is below thresholds
        if ($currentStock <= $this->criticalStockThreshold) {
            $this->sendStockAlert($item['item_name'], $currentStock, 'critical');
            $this->createRestockOrder($itemId, $item);
        } elseif ($currentStock <= $this->lowStockThreshold) {
            $this->sendStockAlert($item['item_name'], $currentStock, 'low');
        }
        
        // Check if stock is below reorder point
        if ($currentStock <= $item['reorder_point']) {
            $this->createRestockOrder($itemId, $item);
        }
    }
    
    private function sendStockAlert($itemName, $currentStock, $level) {
        $message = [
            'type' => 'stock_alert',
            'level' => $level,
            'item_name' => $itemName,
            'current_stock' => $currentStock,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Insert into notifications table
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (
                type, message, level, created_at, is_read
            ) VALUES (
                'stock_alert',
                ?,
                ?,
                NOW(),
                0
            )
        ");
        
        $messageText = "Stock Alert: $itemName has $currentStock units remaining";
        $stmt->bind_param("ss", $messageText, $level);
        $stmt->execute();
        $stmt->close();
        
        // Send real-time notification if WebSocket server is available
        try {
            $this->sendWebSocketNotification($message);
        } catch (Exception $e) {
            error_log("Failed to send WebSocket notification: " . $e->getMessage());
        }
    }
    
    private function createRestockOrder($itemId, $item) {
        $stmt = $this->conn->prepare("
            INSERT INTO restock_orders (
                item_id,
                quantity_requested,
                status,
                created_at
            ) VALUES (
                ?,
                ?,
                'pending',
                NOW()
            )
            ON DUPLICATE KEY UPDATE
            updated_at = NOW()
        ");
        
        // Calculate restock quantity (restore to max capacity)
        $restockQuantity = $item['reorder_point'] * 2 - $item['stock_quantity'];
        
        $stmt->bind_param("ii", $itemId, $restockQuantity);
        $stmt->execute();
        $stmt->close();
    }
    
    private function sendWebSocketNotification($message) {
        $wsClient = new WebSocket\Client("ws://localhost:8080");
        $wsClient->send(json_encode($message));
        $wsClient->close();
    }
    
    public function batchUpdateStock($updates) {
        $transactionManager = new TransactionManager($this->conn);
        
        try {
            $transactionManager->beginTransaction("batch_inventory_update");
            
            foreach ($updates as $update) {
                $this->updateStock(
                    $update['item_id'],
                    $update['quantity'],
                    $update['operation'] ?? 'subtract'
                );
            }
            
            $transactionManager->commit();
        } catch (Exception $e) {
            $transactionManager->rollback();
            throw $e;
        }
    }
    
    public function getStockStatus($itemId = null) {
        if ($itemId) {
            $stmt = $this->conn->prepare("
                SELECT 
                    id,
                    item_name,
                    stock_quantity,
                    reorder_point,
                    last_updated
                FROM menu_items
                WHERE id = ?
            ");
            $stmt->bind_param("i", $itemId);
        } else {
            $stmt = $this->conn->prepare("
                SELECT 
                    id,
                    item_name,
                    stock_quantity,
                    reorder_point,
                    last_updated
                FROM menu_items
                ORDER BY stock_quantity ASC
            ");
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $items;
    }
}