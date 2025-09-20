<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check for valid session and permissions
if (!isAdminLoggedIn() || !hasPermission('manage_menu')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit();
}

header('Content-Type: application/json');

class MenuItemHandler {
    private $conn;
    private $upload_dir = '../uploads/menu_items/';
    
    public function __construct($conn) {
        $this->conn = $conn;
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        try {
            switch ($action) {
                case 'create':
                    return $this->createMenuItem();
                case 'update':
                    return $this->updateMenuItem();
                case 'delete':
                    return $this->deleteMenuItem();
                case 'get':
                    return $this->getMenuItem();
                case 'list':
                    return $this->listMenuItems();
                default:
                    throw new Exception('Invalid action');
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function createMenuItem() {
        $this->validateInput();
        
        $image_url = $this->handleImageUpload();
        
        $stmt = $this->conn->prepare("
            INSERT INTO menu_items (
                name, description, price, category_id, 
                image_url, is_available, is_featured, 
                preparation_time
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $params = $this->getMenuItemParams($image_url);
        $stmt->bind_param(
            "sssissii",
            $params['name'],
            $params['description'],
            $params['price'],
            $params['category_id'],
            $params['image_url'],
            $params['is_available'],
            $params['is_featured'],
            $params['preparation_time']
        );
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Menu item created successfully',
                'item_id' => $stmt->insert_id
            ];
        }
        
        throw new Exception('Failed to create menu item');
    }
    
    private function updateMenuItem() {
        if (!isset($_POST['item_id'])) {
            throw new Exception('Item ID is required');
        }
        
        $this->validateInput();
        $item_id = intval($_POST['item_id']);
        
        // Get existing item data
        $stmt = $this->conn->prepare("SELECT image_url FROM menu_items WHERE item_id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing_item = $result->fetch_assoc();
        
        // Handle image upload if new image is provided
        $image_url = $this->handleImageUpload();
        if ($image_url && $existing_item['image_url']) {
            // Delete old image
            $old_image_path = '../' . $existing_item['image_url'];
            if (file_exists($old_image_path)) {
                unlink($old_image_path);
            }
        } else if (!$image_url) {
            $image_url = $existing_item['image_url'];
        }
        
        $stmt = $this->conn->prepare("
            UPDATE menu_items SET 
                name = ?, description = ?, price = ?,
                category_id = ?, image_url = ?, 
                is_available = ?, is_featured = ?,
                preparation_time = ?
            WHERE item_id = ?
        ");
        
        $params = $this->getMenuItemParams($image_url);
        $stmt->bind_param(
            "sssissiii",
            $params['name'],
            $params['description'],
            $params['price'],
            $params['category_id'],
            $params['image_url'],
            $params['is_available'],
            $params['is_featured'],
            $params['preparation_time'],
            $item_id
        );
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Menu item updated successfully'
            ];
        }
        
        throw new Exception('Failed to update menu item');
    }
    
    private function deleteMenuItem() {
        if (!isset($_POST['item_id'])) {
            throw new Exception('Item ID is required');
        }
        
        $item_id = intval($_POST['item_id']);
        
        // Get image URL before deleting
        $stmt = $this->conn->prepare("SELECT image_url FROM menu_items WHERE item_id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        
        // Delete the item
        $stmt = $this->conn->prepare("DELETE FROM menu_items WHERE item_id = ?");
        $stmt->bind_param("i", $item_id);
        
        if ($stmt->execute()) {
            // Delete associated image if exists
            if ($item['image_url']) {
                $image_path = '../' . $item['image_url'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            return [
                'success' => true,
                'message' => 'Menu item deleted successfully'
            ];
        }
        
        throw new Exception('Failed to delete menu item');
    }
    
    private function getMenuItem() {
        if (!isset($_GET['item_id'])) {
            throw new Exception('Item ID is required');
        }
        
        $item_id = intval($_GET['item_id']);
        
        $stmt = $this->conn->prepare("
            SELECT m.*, c.name as category_name 
            FROM menu_items m 
            LEFT JOIN categories c ON m.category_id = c.category_id 
            WHERE m.item_id = ?
        ");
        
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($item = $result->fetch_assoc()) {
            return [
                'success' => true,
                'data' => $item
            ];
        }
        
        throw new Exception('Menu item not found');
    }
    
    private function listMenuItems() {
        $where_clauses = [];
        $params = [];
        $types = "";
        
        // Handle search
        if (!empty($_GET['search'])) {
            $search = "%" . $_GET['search'] . "%";
            $where_clauses[] = "(m.name LIKE ? OR m.description LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $types .= "ss";
        }
        
        // Handle category filter
        if (!empty($_GET['category_id'])) {
            $where_clauses[] = "m.category_id = ?";
            $params[] = intval($_GET['category_id']);
            $types .= "i";
        }
        
        // Handle availability filter
        if (isset($_GET['availability']) && $_GET['availability'] !== '') {
            $where_clauses[] = "m.is_available = ?";
            $params[] = intval($_GET['availability']);
            $types .= "i";
        }
        
        $sql = "
            SELECT m.*, c.name as category_name 
            FROM menu_items m 
            LEFT JOIN categories c ON m.category_id = c.category_id
        ";
        
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }
        
        $sql .= " ORDER BY m.name ASC";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        return [
            'success' => true,
            'data' => $items
        ];
    }
    
    private function validateInput() {
        if (empty($_POST['name'])) {
            throw new Exception('Item name is required');
        }
        
        if (!isset($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] < 0) {
            throw new Exception('Valid price is required');
        }
        
        if (empty($_POST['category_id'])) {
            throw new Exception('Category is required');
        }
    }
    
    private function handleImageUpload() {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        
        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Image upload failed');
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Invalid image type. Only JPG, PNG and WebP are allowed.');
        }
        
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_size) {
            throw new Exception('Image size too large. Maximum 5MB allowed.');
        }
        
        $filename = uniqid() . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $destination = $this->upload_dir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to save image');
        }
        
        return 'uploads/menu_items/' . $filename;
    }
    
    private function getMenuItemParams($image_url = null) {
        return [
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'price' => floatval($_POST['price']),
            'category_id' => intval($_POST['category_id']),
            'image_url' => $image_url,
            'is_available' => isset($_POST['is_available']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'preparation_time' => intval($_POST['preparation_time'] ?? 15)
        ];
    }
}

// Handle the request
$handler = new MenuItemHandler($conn);
echo json_encode($handler->handleRequest());
