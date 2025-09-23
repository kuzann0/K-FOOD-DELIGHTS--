<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Ensure only authorized admins can access
if (!isAdmin() || !hasPermission('manage_menu')) {
    header('Location: ../unauthorized.php');
    exit();
}

$pageTitle = "Menu Creation";
$currentModule = "menu_creation";

include '../includes/header_common.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Creation - K-Food Admin</title>
    <link rel="stylesheet" href="../css/admin_common.css">
    <link rel="stylesheet" href="../css/menu_creation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="content-header">
                <h1><i class="fas fa-utensils"></i> Menu Creation</h1>
                <button id="addNewItem" class="btn-primary">
                    <i class="fas fa-plus"></i> Add New Item
                </button>
            </header>

            <!-- Search and Filter Bar -->
            <div class="search-filter-bar">
                <div class="search-box">
                    <input type="text" id="searchMenu" placeholder="Search menu items...">
                    <i class="fas fa-search"></i>
                </div>
                <div class="filter-options">
                    <select id="categoryFilter">
                        <option value="">All Categories</option>
                        <option value="main">Main Dishes</option>
                        <option value="sides">Side Dishes</option>
                        <option value="beverages">Beverages</option>
                        <option value="desserts">Desserts</option>
                    </select>
                    <select id="statusFilter">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Menu Items Grid -->
            <div class="menu-items-grid" id="menuItemsGrid">
                <!-- Items will be loaded dynamically -->
            </div>

            <!-- Add/Edit Menu Item Modal -->
            <div id="menuItemModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><i class="fas fa-utensils"></i> <span id="modalTitle">Add Menu Item</span></h2>
                        <button class="close-modal">&times;</button>
                    </div>
                    <form id="menuItemForm">
                        <input type="hidden" id="itemId" name="itemId">
                        
                        <div class="form-group">
                            <label for="itemName">Item Name</label>
                            <input type="text" id="itemName" name="itemName" required
                                   placeholder="Enter item name">
                            <small class="validation-message"></small>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="itemPrice">Price (PHP)</label>
                                <input type="number" id="itemPrice" name="itemPrice" required
                                       min="0" step="0.01" placeholder="0.00">
                                <small class="validation-message"></small>
                            </div>
                            <div class="form-group">
                                <label for="itemCategory">Category</label>
                                <select id="itemCategory" name="itemCategory" required>
                                    <option value="main">Main Dishes</option>
                                    <option value="sides">Side Dishes</option>
                                    <option value="beverages">Beverages</option>
                                    <option value="desserts">Desserts</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="itemStatus">Status</label>
                                <select id="itemStatus" name="itemStatus" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="itemImage">Item Image</label>
                                <input type="file" id="itemImage" name="itemImage" 
                                       accept="image/jpeg,image/png,image/gif">
                                <small class="field-hint">Max size: 2MB. Formats: JPEG, PNG, GIF</small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Save Item
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../js/menu_creation.js"></script>
</body>
</html>