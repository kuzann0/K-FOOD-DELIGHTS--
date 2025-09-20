<?php
class QueryOptimizer {
    private static $instance = null;
    private $db;
    private $cache;

    private function __construct() {
        $this->db = DatabaseConnection::getInstance();
        $this->cache = CacheManager::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Optimized product listing with pagination
    public function getProducts($page = 1, $limit = 10, $category = null, $search = null) {
        $offset = ($page - 1) * $limit;
        $cacheKey = "products_${category}_${page}_${limit}_" . md5($search ?? '');
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $params = [];
        $types = "";
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS 
                p.*, c.category_name,
                (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image
                FROM products p
                LEFT JOIN product_categories c ON p.category_id = c.category_id
                WHERE p.is_active = 1";

        if ($category) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category;
            $types .= "i";
        }

        if ($search) {
            $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types .= "ss";
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->db->executeQuery($sql, $params, $types);
        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get total count
        $total = $this->db->executeQuery("SELECT FOUND_ROWS()")->get_result()->fetch_row()[0];
        
        $result = [
            'products' => $products,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];

        $this->cache->set($cacheKey, $result, 300); // Cache for 5 minutes
        return $result;
    }

    // Optimized order processing
    public function processOrder($userId, $items, $address, $paymentMethod) {
        try {
            $this->db->beginTransaction();

            // Create order
            $sql = "INSERT INTO orders (user_id, delivery_address, status) VALUES (?, ?, 'pending')";
            $stmt = $this->db->executeQuery($sql, [$userId, $address], "is");
            $orderId = $stmt->insert_id;

            $totalAmount = 0;
            
            // Batch insert order items and update inventory
            foreach ($items as $item) {
                // Check stock availability in one query
                $sql = "SELECT product_id, price, stock_quantity 
                       FROM products 
                       WHERE product_id = ? AND stock_quantity >= ? AND is_active = 1
                       FOR UPDATE";
                $stmt = $this->db->executeQuery($sql, [$item['product_id'], $item['quantity']], "ii");
                $product = $stmt->get_result()->fetch_assoc();

                if (!$product) {
                    throw new Exception("Insufficient stock for product ID: " . $item['product_id']);
                }

                // Update stock and insert order item in one transaction
                $sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?";
                $this->db->executeQuery($sql, [$item['quantity'], $item['product_id']], "ii");

                $subtotal = $product['price'] * $item['quantity'];
                $totalAmount += $subtotal;

                $sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) 
                       VALUES (?, ?, ?, ?, ?)";
                $this->db->executeQuery($sql, [
                    $orderId, 
                    $item['product_id'], 
                    $item['quantity'], 
                    $product['price'], 
                    $subtotal
                ], "iiidd");
            }

            // Update order total
            $sql = "UPDATE orders SET total_amount = ? WHERE order_id = ?";
            $this->db->executeQuery($sql, [$totalAmount, $orderId], "di");

            // Create payment transaction
            $sql = "INSERT INTO payment_transactions (order_id, method_id, amount, status) 
                   VALUES (?, ?, ?, 'pending')";
            $this->db->executeQuery($sql, [$orderId, $paymentMethod, $totalAmount], "iid");

            $this->db->commit();
            
            // Clear relevant caches
            $this->cache->remove('products_all');
            $this->cache->remove("user_orders_$userId");

            return $orderId;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    // Optimized inventory tracking
    public function checkLowStock($threshold = 10) {
        $sql = "SELECT p.product_id, p.product_name, p.stock_quantity,
                c.category_name, s.supplier_name
                FROM products p
                LEFT JOIN product_categories c ON p.category_id = c.category_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                WHERE p.stock_quantity <= ? AND p.is_active = 1
                ORDER BY p.stock_quantity ASC";
        
        $stmt = $this->db->executeQuery($sql, [$threshold], "i");
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
