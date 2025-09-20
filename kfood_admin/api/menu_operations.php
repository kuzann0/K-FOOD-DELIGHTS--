<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Ensure user is logged in and has permission
if (!isAdminLoggedIn() || !hasPermission('manage_menu')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

function validateMenuItem($data) {
    $errors = [];
    
    if (empty($data['name'])) {
        $errors[] = "Item name is required";
    }
    
    if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
        $errors[] = "Valid price is required";
    }
    
    if (empty($data['category_id'])) {
        $errors[] = "Category is required";
    }
    
    return $errors;
}

try {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'price' => floatval($_POST['price'] ?? 0),
                'category_id' => intval($_POST['category_id'] ?? 0),
                'is_available' => isset($_POST['is_available']) ? 1 : 0,
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'preparation_time' => intval($_POST['preparation_time'] ?? 15)
            ];
            
            $errors = validateMenuItem($data);
            
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'message' => implode(", ", $errors)]);
                exit();
            }
            
            // Handle image upload if present
            $image_url = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/menu_items/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (!in_array($file_extension, $allowed_types)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid image format']);
                    exit();
                }
                
                $filename = uniqid() . '.' . $file_extension;
                $destination = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $image_url = 'uploads/menu_items/' . $filename;
                }
            }
            
            $stmt = $conn->prepare("
                INSERT INTO menu_items (name, description, price, category_id, image_url, 
                                      is_available, is_featured, preparation_time)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param("ssdisiii", 
                $data['name'], 
                $data['description'],
                $data['price'],
                $data['category_id'],
                $image_url,
                $data['is_available'],
                $data['is_featured'],
                $data['preparation_time']
            );
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu item created successfully',
                    'item_id' => $stmt->insert_id
                ]);
            } else {
                throw new Exception("Error creating menu item");
            }
            break;
            
        case 'update':
            $item_id = intval($_POST['item_id'] ?? 0);
            if (!$item_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
                exit();
            }
            
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'price' => floatval($_POST['price'] ?? 0),
                'category_id' => intval($_POST['category_id'] ?? 0),
                'is_available' => isset($_POST['is_available']) ? 1 : 0,
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'preparation_time' => intval($_POST['preparation_time'] ?? 15)
            ];
            
            $errors = validateMenuItem($data);
            
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'message' => implode(", ", $errors)]);
                exit();
            }
            
            // Handle image upload if present
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/menu_items/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (!in_array($file_extension, $allowed_types)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid image format']);
                    exit();
                }
                
                $filename = uniqid() . '.' . $file_extension;
                $destination = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    // Delete old image if exists
                    $stmt = $conn->prepare("SELECT image_url FROM menu_items WHERE item_id = ?");
                    $stmt->bind_param("i", $item_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($old_image = $result->fetch_assoc()) {
                        if ($old_image['image_url'] && file_exists('../' . $old_image['image_url'])) {
                            unlink('../' . $old_image['image_url']);
                        }
                    }
                    
                    $image_url = 'uploads/menu_items/' . $filename;
                    $data['image_url'] = $image_url;
                }
            }
            
            $sql = "UPDATE menu_items SET 
                    name = ?, 
                    description = ?, 
                    price = ?, 
                    category_id = ?, 
                    is_available = ?, 
                    is_featured = ?, 
                    preparation_time = ?";
            
            $params = [
                $data['name'],
                $data['description'],
                $data['price'],
                $data['category_id'],
                $data['is_available'],
                $data['is_featured'],
                $data['preparation_time']
            ];
            $types = "ssdiiiii";
            
            if (isset($data['image_url'])) {
                $sql .= ", image_url = ?";
                $params[] = $data['image_url'];
                $types .= "s";
            }
            
            $sql .= " WHERE item_id = ?";
            $params[] = $item_id;
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu item updated successfully'
                ]);
            } else {
                throw new Exception("Error updating menu item");
            }
            break;
            
        case 'delete':
            $item_id = intval($_POST['item_id'] ?? 0);
            if (!$item_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
                exit();
            }
            
            // Delete associated image first
            $stmt = $conn->prepare("SELECT image_url FROM menu_items WHERE item_id = ?");
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($item = $result->fetch_assoc()) {
                if ($item['image_url'] && file_exists('../' . $item['image_url'])) {
                    unlink('../' . $item['image_url']);
                }
            }
            
            $stmt = $conn->prepare("DELETE FROM menu_items WHERE item_id = ?");
            $stmt->bind_param("i", $item_id);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu item deleted successfully'
                ]);
            } else {
                throw new Exception("Error deleting menu item");
            }
            break;
            
        case 'get':
            $item_id = intval($_GET['item_id'] ?? 0);
            if (!$item_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
                exit();
            }
            
            $stmt = $conn->prepare("
                SELECT m.*, c.name as category_name 
                FROM menu_items m 
                LEFT JOIN categories c ON m.category_id = c.category_id 
                WHERE m.item_id = ?
            ");
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($item = $result->fetch_assoc()) {
                echo json_encode([
                    'success' => true,
                    'data' => $item
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Item not found'
                ]);
            }
            break;
            
        case 'list':
            $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            
            $sql = "
                SELECT m.*, c.name as category_name 
                FROM menu_items m 
                LEFT JOIN categories c ON m.category_id = c.category_id 
                WHERE 1=1
            ";
            $params = [];
            $types = "";
            
            if ($category_id) {
                $sql .= " AND m.category_id = ?";
                $params[] = $category_id;
                $types .= "i";
            }
            
            if ($search) {
                $sql .= " AND (m.name LIKE ? OR m.description LIKE ?)";
                $search = "%$search%";
                $params[] = $search;
                $params[] = $search;
                $types .= "ss";
            }
            
            $sql .= " ORDER BY m.name ASC";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $items = [];
            
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $items
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request'
    ]);
}
