<?php
require_once '../includes/auth.php';
require_once '../includes/csrf.php';

// Check for required permissions
if (!hasPermission('manage_menu')) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}
?>

<div class="menu-creation-container">
    <div class="menu-header">
        <h2><i class="fas fa-utensils"></i> Menu Creation</h2>
        <p class="section-description">Create and manage menu items for K Food Delight</p>
    </div>

    <form id="menuItemForm" class="menu-form" enctype="multipart/form-data">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="form-status">
            <div class="loading-indicator" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Creating menu item...</span>
            </div>
            <div id="formMessage" class="form-message"></div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select a category</option>
                    <?php
                    // Fetch categories from database
                    $stmt = $pdo->query("SELECT category_id, name FROM menu_categories ORDER BY name");
                    while ($row = $stmt->fetch()) {
                        echo "<option value=\"" . htmlspecialchars($row['category_id']) . "\">" 
                            . htmlspecialchars($row['name']) . "</option>";
                    }
                    ?>
                </select>
                <small class="field-hint">Select the category this item belongs to</small>
            </div>

            <div class="form-group">
                <label for="name">Item Name</label>
                <input type="text" id="name" name="name" required
                       pattern="^[a-zA-Z0-9\s\-']+$"
                       title="Please enter a valid item name using letters, numbers, spaces, hyphens, and apostrophes">
                <small class="field-hint">Enter the name of the menu item</small>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"
                      placeholder="Enter a detailed description of the menu item"></textarea>
            <small class="field-hint">Describe the item, including ingredients and preparation method</small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="price">Price (PHP)</label>
                <input type="number" id="price" name="price" required
                       min="0" step="0.01" placeholder="0.00">
                <small class="field-hint">Enter the price in Philippine Peso</small>
            </div>

            <div class="form-group">
                <label for="image_url">Image URL</label>
                <input type="url" id="image_url" name="image_url"
                       placeholder="https://example.com/image.jpg">
                <small class="field-hint">Enter a URL for the menu item image</small>
            </div>
        </div>

        <div class="form-group checkbox-group">
            <label class="checkbox-label">
                <input type="checkbox" id="is_available" name="is_available" checked>
                <span>Item is available for ordering</span>
            </label>
            <small class="field-hint">Uncheck if this item is currently unavailable</small>
        </div>

        <div class="form-actions">
            <button type="reset" class="btn-secondary">
                <i class="fas fa-undo"></i> Reset Form
            </button>
            <button type="submit" class="btn-primary">
                <i class="fas fa-plus"></i> Create Menu Item
            </button>
        </div>
    </form>

    <!-- Menu Items List -->
    <div class="menu-items-list">
        <h3>Current Menu Items</h3>
        <div class="menu-items-grid" id="menuItemsGrid">
            <!-- Items will be loaded dynamically via JavaScript -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuForm = document.getElementById('menuItemForm');
    const formMessage = document.getElementById('formMessage');
    const loadingIndicator = document.querySelector('.loading-indicator');

    // Function to show form messages
    function showFormMessage(message, type = 'success') {
        formMessage.textContent = message;
        formMessage.className = `form-message ${type}`;
        formMessage.style.display = 'block';
        setTimeout(() => {
            formMessage.style.display = 'none';
        }, 5000);
    }

    // Handle form submission
    menuForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        loadingIndicator.style.display = 'flex';
        const formData = new FormData(this);

        try {
            const response = await fetch('ajax/create-menu-item.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showFormMessage(result.message);
                menuForm.reset();
                // Refresh menu items list
                loadMenuItems();
            } else {
                showFormMessage(result.error, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showFormMessage('An error occurred while creating the menu item', 'error');
        } finally {
            loadingIndicator.style.display = 'none';
        }
    });

    // Function to load menu items
    async function loadMenuItems() {
        try {
            const response = await fetch('ajax/get-menu-items.php');
            const data = await response.json();

            if (data.success) {
                const grid = document.getElementById('menuItemsGrid');
                grid.innerHTML = data.items.map(item => `
                    <div class="menu-item-card">
                        <img src="${item.image_url || '../resources/images/default-food.png'}" 
                             alt="${item.name}" class="item-image">
                        <div class="item-details">
                            <h4>${item.name}</h4>
                            <p class="item-description">${item.description}</p>
                            <div class="item-meta">
                                <span class="price">₱${parseFloat(item.price).toFixed(2)}</span>
                                <span class="status ${item.is_available ? 'available' : 'unavailable'}">
                                    ${item.is_available ? 'Available' : 'Unavailable'}
                                </span>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
        } catch (error) {
            console.error('Error loading menu items:', error);
        }
    }

    // Load menu items on page load
    loadMenuItems();
});
</script>