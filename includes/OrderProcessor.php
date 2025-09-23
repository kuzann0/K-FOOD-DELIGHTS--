<?php
namespace KFood\Order;

use PDO;
use Exception;

class OrderProcessor {
    private $db;
    private $websocket;
    
    public function __construct(PDO $db, $websocket = null) {
        $this->db = $db;
        $this->websocket = $websocket;
    }
    
    public function processOrder($orderData, $userId) {
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Validate order data
            $this->validateOrderData($orderData);
            
            // Check inventory availability
            $this->checkInventoryAvailability($orderData['items']);
            
            // Calculate totals
            $totals = $this->calculateTotals($orderData);
            
            // Create order record
            $orderId = $this->createOrderRecord($orderData, $userId, $totals);
            
            // Create order items
            $this->createOrderItems($orderId, $orderData['items']);
            
            // Update inventory
            $this->updateInventory($orderData['items']);
            
            // Commit transaction
            $this->db->commit();
            
            // Send notifications
            $this->sendNotifications($orderId, $orderData);
            
            return [
                'success' => true,
                'orderId' => $orderId,
                'message' => 'Order processed successfully'
            ];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollBack();
            
            error_log("Order processing error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to process order: ' . $e->getMessage()
            ];
        }
    }
    
    private function validateOrderData($orderData) {
        $requiredFields = ['items', 'customerName', 'phone', 'address'];
        foreach ($requiredFields as $field) {
            if (empty($orderData[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        if (empty($orderData['items'])) {
            throw new Exception("Order must contain at least one item");
        }
        
        foreach ($orderData['items'] as $item) {
            if (!isset($item['id']) || !isset($item['quantity']) || $item['quantity'] < 1) {
                throw new Exception("Invalid item data");
            }
        }
    }
    
    private function checkInventoryAvailability($items) {
        $stmt = $this->db->prepare("
            SELECT id, name, stock_quantity 
            FROM inventory 
            WHERE id = ? FOR UPDATE
        ");
        
        foreach ($items as $item) {
            $stmt->execute([$item['id']]);
            $inventory = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$inventory) {
                throw new Exception("Item not found: {$item['id']}");
            }
            
            if ($inventory['stock_quantity'] < $item['quantity']) {
                throw new Exception("Insufficient stock for {$inventory['name']}");
            }
        }
    }
    
    private function calculateTotals($orderData) {
        $subtotal = 0;
        $tax = 0;
        $discount = 0;
        
        foreach ($orderData['items'] as $item) {
            $stmt = $this->db->prepare("SELECT price FROM menu_items WHERE id = ?");
            $stmt->execute([$item['id']]);
            $menuItem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$menuItem) {
                throw new Exception("Invalid menu item: {$item['id']}");
            }
            
            $subtotal += $menuItem['price'] * $item['quantity'];
        }
        
        // Apply tax
        $tax = $subtotal * 0.12; // 12% VAT
        
        // Apply discounts if any
        if (!empty($orderData['discounts'])) {
            // Process discounts
            $discount = $this->calculateDiscounts($orderData['discounts'], $subtotal);
        }
        
        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $subtotal + $tax - $discount
        ];
    }
    
    private function createOrderRecord($orderData, $userId, $totals) {
        $stmt = $this->db->prepare("
            INSERT INTO orders (
                user_id, customer_name, phone, address, 
                subtotal, tax, discount, total_amount,
                status, payment_status, special_instructions,
                created_at
            ) VALUES (
                ?, ?, ?, ?, 
                ?, ?, ?, ?,
                'pending', 'pending', ?,
                NOW()
            )
        ");
        
        $stmt->execute([
            $userId,
            $orderData['customerName'],
            $orderData['phone'],
            $orderData['address'],
            $totals['subtotal'],
            $totals['tax'],
            $totals['discount'],
            $totals['total'],
            $orderData['instructions'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    private function createOrderItems($orderId, $items) {
        $stmt = $this->db->prepare("
            INSERT INTO order_items (
                order_id, menu_item_id, quantity, price,
                subtotal
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($items as $item) {
            // Get current price
            $priceStmt = $this->db->prepare("SELECT price FROM menu_items WHERE id = ?");
            $priceStmt->execute([$item['id']]);
            $menuItem = $priceStmt->fetch(PDO::FETCH_ASSOC);
            
            $subtotal = $menuItem['price'] * $item['quantity'];
            
            $stmt->execute([
                $orderId,
                $item['id'],
                $item['quantity'],
                $menuItem['price'],
                $subtotal
            ]);
        }
    }
    
    private function updateInventory($items) {
        $stmt = $this->db->prepare("
            UPDATE inventory 
            SET stock_quantity = stock_quantity - ? 
            WHERE id = ?
        ");
        
        foreach ($items as $item) {
            $stmt->execute([$item['quantity'], $item['id']]);
        }
    }
    
    private function sendNotifications($orderId, $orderData) {
        // Send WebSocket notification if available
        if ($this->websocket) {
            try {
                $this->websocket->broadcast('orders', [
                    'type' => 'new_order',
                    'orderId' => $orderId,
                    'status' => 'pending'
                ]);
            } catch (Exception $e) {
                error_log("WebSocket notification error: " . $e->getMessage());
            }
        }
        
        // Send email notification to customer
        $this->sendOrderConfirmationEmail($orderId, $orderData);
        
        // Send SMS notification if phone number is provided
        if (!empty($orderData['phone'])) {
            $this->sendOrderConfirmationSMS($orderId, $orderData);
        }
    }
    
    private function sendOrderConfirmationEmail($orderId, $orderData) {
        // Implementation of email notification
    }
    
    private function sendOrderConfirmationSMS($orderId, $orderData) {
        // Implementation of SMS notification
    }
    
    private function calculateDiscounts($discounts, $subtotal) {
        // Implementation of discount calculation
        return 0;
    }
}