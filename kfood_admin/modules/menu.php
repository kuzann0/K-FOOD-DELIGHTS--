<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check permissions
if (!isAdminLoggedIn() || !hasPermission('manage_menu')) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = "Menu Items Management";
$currentModule = "menu";

// Get categories for the dropdown
$categories = [];
$stmt = $conn->prepare("SELECT category_id, name FROM categories WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

include '../includes/header_common.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Items Management - K-Food Delights Admin</title>
    <link rel="stylesheet" href="../css/admin_style.css">
    <link rel="stylesheet" href="../css/menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-header">
            <h1>Menu Items Management</h1>
            <button class="btn btn-primary" id="addItemBtn">
                <i class="fas fa-plus"></i> Add New Item
            </button>
        </div>
        
        <div class="filters">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search menu items...">
                <i class="fas fa-search"></i>
            </div>
            
            <select id="categoryFilter">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_id']; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select id="availabilityFilter">
                <option value="">All Status</option>
                <option value="1">Available</option>
                <option value="0">Not Available</option>
            </select>
        </div>
        
        <div class="menu-items-grid" id="menuItemsGrid">
            <!-- Items will be loaded here via JavaScript -->
            <div class="loading">Loading items...</div>
        </div>
    </div>
    
    <!-- Add/Edit Item Modal -->
    <div class="modal" id="itemModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Menu Item</h2>
                <button class="close-modal">&times;</button>
            </div>
            
            <form id="itemForm" enctype="multipart/form-data">
                <input type="hidden" name="item_id" id="itemId">
                
                <div class="form-group">
                    <label for="itemName">Item Name *</label>
                    <input type="text" id="itemName" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="itemDescription">Description</label>
                    <textarea id="itemDescription" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="itemPrice">Price (₱) *</label>
                    <input type="number" id="itemPrice" name="price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="itemCategory">Category *</label>
                    <select id="itemCategory" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="itemImage">Image</label>
                    <input type="file" id="itemImage" name="image" accept="image/*">
                    <div id="imagePreview"></div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="prepTime">Preparation Time (minutes)</label>
                        <input type="number" id="prepTime" name="preparation_time" min="1" value="15">
                    </div>
                    
                    <div class="form-group checkboxes">
                        <label class="checkbox-container">
                            <input type="checkbox" id="isAvailable" name="is_available" checked>
                            <span class="checkmark"></span>
                            Available
                        </label>
                        
                        <label class="checkbox-container">
                            <input type="checkbox" id="isFeatured" name="is_featured">
                            <span class="checkmark"></span>
                            Featured Item
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Item</button>
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete Menu Item</h2>
                <button class="close-modal">&times;</button>
            </div>
            
            <div class="modal-body">
                <p>Are you sure you want to delete this menu item? This action cannot be undone.</p>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-danger" id="confirmDelete">Delete</button>
                <button class="btn btn-secondary close-modal">Cancel</button>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="../js/menu.js"></script>
</body>
</html>
