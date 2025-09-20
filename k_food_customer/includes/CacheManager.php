<?php
class CacheManager {
    private static $instance = null;
    private $cache = [];
    private $expiration = [];
    private $defaultTTL = 3600; // 1 hour default TTL

    private function __construct() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get($key) {
        if ($this->has($key)) {
            if (time() < $this->expiration[$key]) {
                return $this->cache[$key];
            }
            $this->remove($key);
        }
        return null;
    }

    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTTL;
        $this->cache[$key] = $value;
        $this->expiration[$key] = time() + $ttl;
    }

    public function has($key) {
        return isset($this->cache[$key]);
    }

    public function remove($key) {
        unset($this->cache[$key], $this->expiration[$key]);
    }

    public function clear() {
        $this->cache = [];
        $this->expiration = [];
    }

    // Cache frequently accessed product data
    public function getCachedProducts($category = null) {
        $key = 'products_' . ($category ?? 'all');
        $products = $this->get($key);
        
        if ($products === null) {
            $db = DatabaseConnection::getInstance();
            $sql = "SELECT p.*, c.category_name 
                   FROM products p 
                   LEFT JOIN product_categories c ON p.category_id = c.category_id 
                   WHERE p.is_active = 1";
            
            if ($category) {
                $sql .= " AND p.category_id = ?";
                $stmt = $db->executeQuery($sql, [$category], "i");
            } else {
                $stmt = $db->executeQuery($sql);
            }
            
            $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $this->set($key, $products, 300); // Cache for 5 minutes
        }
        
        return $products;
    }

    // Cache user permissions
    public function getCachedUserPermissions($userId) {
        $key = 'user_permissions_' . $userId;
        $permissions = $this->get($key);
        
        if ($permissions === null) {
            $db = DatabaseConnection::getInstance();
            $sql = "SELECT DISTINCT p.permission_name 
                   FROM users u 
                   JOIN role_permissions rp ON u.role_id = rp.role_id 
                   JOIN permissions p ON rp.permission_id = p.permission_id 
                   WHERE u.user_id = ?";
            
            $stmt = $db->executeQuery($sql, [$userId], "i");
            $permissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $this->set($key, $permissions, 1800); // Cache for 30 minutes
        }
        
        return $permissions;
    }
}
