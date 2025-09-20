<?php
require_once 'config.php';
require_once 'CacheManager.php';

class Cart {
    private $db;
    private $cache;
    
    public function __construct($db) {
        $this->db = $db;
        $this->cache = CacheManager::getInstance();
    }
    
    /**
     * Check if a product is available and in stock
     */
    public function checkProductAvailability($productId) {
        $sql = "SELECT id as product_id, name as product_name, price, stock_quantity, active as is_active 
                FROM products 
                WHERE id = ? AND active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    /**
     * Add an item to the cart
     */
    public function addToCart($userId, $productId, $quantity = 1) {
        try {
            $this->db->begin_transaction();

            // Ensure quantity is at least 1
            $quantity = max(1, intval($quantity));

            // Check if item already exists in cart
            $stmt = $this->db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $userId, $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // If item exists, increment quantity by 1
                $row = $result->fetch_assoc();
                $newQuantity = $row['quantity'] + 1;
                $stmt = $this->db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $stmt->bind_param("ii", $newQuantity, $row['id']);
                $success = $stmt->execute();
            } else {
                // Add new item with quantity 1
                $initialQuantity = 1;
                $stmt = $this->db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $userId, $productId, $initialQuantity);
                $success = $stmt->execute();
            }

            if ($success) {
                $this->db->commit();
                $this->cache->remove('cart_' . $userId);
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateQuantity($cartId, $quantity) {
        $stmt = $this->db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $cartId);
        $success = $stmt->execute();
        
        if ($success) {
            // Get user_id for cache invalidation
            $stmt = $this->db->prepare("SELECT user_id FROM cart WHERE id = ?");
            $stmt->bind_param("i", $cartId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $this->cache->remove('cart_' . $row['user_id']);
            }
        }
        
        return $success;
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart($cartId) {
        // Get user_id for cache invalidation before deleting
        $stmt = $this->db->prepare("SELECT user_id FROM cart WHERE id = ?");
        $stmt->bind_param("i", $cartId);
        $stmt->execute();
        $result = $stmt->get_result();
        $userId = null;
        if ($row = $result->fetch_assoc()) {
            $userId = $row['user_id'];
        }

        $stmt = $this->db->prepare("DELETE FROM cart WHERE id = ?");
        $stmt->bind_param("i", $cartId);
        $success = $stmt->execute();

        if ($success && $userId) {
            $this->cache->remove('cart_' . $userId);
        }

        return $success;
    }

    /**
     * Verify cart item belongs to user
     */
    public function verifyCartOwnership($userId, $cartId) {
        $stmt = $this->db->prepare("SELECT 1 FROM cart WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $cartId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    /**
     * Get specific cart item
     */
    public function getCartItem($cartId) {
        $stmt = $this->db->prepare("
            SELECT c.*, p.name as product_name, p.price, p.stock_quantity 
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.id = ?
        ");
        $stmt->bind_param("i", $cartId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get complete cart summary with items
     */
    public function getCartSummary($userId) {
        $cacheKey = 'cart_' . $userId;
        $cartData = $this->cache->get($cacheKey);
        
        if ($cartData === null) {
            $stmt = $this->db->prepare("
                SELECT 
                    c.id as cart_id,
                    c.product_id,
                    c.quantity,
                    p.name as product_name,
                    p.price,
                    p.stock_quantity,
                    p.image_url,
                    (p.price * c.quantity) as subtotal
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $items = [];
            $total = 0;
            $itemCount = 0;
            
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
                $total += $row['subtotal'];
                $itemCount += $row['quantity'];
            }
            
            $cartData = [
                'items' => $items,
                'total' => $total,
                'item_count' => $itemCount
            ];
            
            $this->cache->set($cacheKey, $cartData, 300); // Cache for 5 minutes
        }
        
        return $cartData;
    }

    /**
     * Clear entire cart
     */
    public function clearCart($userId) {
        $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $success = $stmt->execute();
        
        if ($success) {
            $this->cache->remove('cart_' . $userId);
        }
        
        return $success;
    }

    /**
     * Calculate cart total
     */
    public function getCartTotal($userId) {
        $cartData = $this->getCartSummary($userId);
        return $cartData['total'];
    }
}
