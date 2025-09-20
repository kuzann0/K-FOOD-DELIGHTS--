// Menu Items Management JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Cache DOM elements
    const menuItemsGrid = document.getElementById('menuItemsGrid');
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const availabilityFilter = document.getElementById('availabilityFilter');
    const addItemBtn = document.getElementById('addItemBtn');
    const itemModal = document.getElementById('itemModal');
    const deleteModal = document.getElementById('deleteModal');
    const itemForm = document.getElementById('itemForm');
    const imagePreview = document.getElementById('imagePreview');
    
    let currentItemId = null;
    
    // Initialize event listeners
    searchInput.addEventListener('input', debounce(loadMenuItems, 300));
    categoryFilter.addEventListener('change', loadMenuItems);
    availabilityFilter.addEventListener('change', loadMenuItems);
    addItemBtn.addEventListener('click', () => showItemModal());
    
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            itemModal.classList.remove('show');
            deleteModal.classList.remove('show');
        });
    });
    
    itemForm.addEventListener('submit', handleItemSubmit);
    
    document.getElementById('itemImage').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" style="max-width: 200px;">
                    <button type="button" class="btn btn-sm btn-danger" onclick="clearImage()">
                        <i class="fas fa-times"></i> Remove
                    </button>
                `;
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Initial load
    loadMenuItems();
    
    // Functions
    async function loadMenuItems() {
        showLoading();
        
        try {
            const params = new URLSearchParams({
                action: 'list',
                search: searchInput.value,
                category_id: categoryFilter.value,
                availability: availabilityFilter.value
            });
            
            const response = await fetch(`../api/menu_handler.php?${params}`);
            const data = await response.json();
            
            if (data.success) {
                displayMenuItems(data.data);
            } else {
                showError('Failed to load menu items');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('An error occurred while loading menu items');
        }
    }
    
    function displayMenuItems(items) {
        menuItemsGrid.innerHTML = items.length ? '' : '<div class="no-items">No menu items found</div>';
        
        items.forEach(item => {
            const card = document.createElement('div');
            card.className = 'menu-item-card';
            card.innerHTML = `
                <div class="item-image">
                    <img src="${item.image_url || '../resources/images/default-food.png'}" 
                         alt="${escapeHtml(item.name)}"
                         onerror="this.src='../resources/images/default-food.png'">
                    ${item.is_featured ? '<span class="featured-badge">Featured</span>' : ''}
                </div>
                <div class="item-details">
                    <h3>${escapeHtml(item.name)}</h3>
                    <p class="item-description">${escapeHtml(item.description || '')}</p>
                    <div class="item-meta">
                        <span class="price">₱${parseFloat(item.price).toFixed(2)}</span>
                        <span class="category">${escapeHtml(item.category_name)}</span>
                    </div>
                    <div class="item-status">
                        <span class="status-badge ${item.is_available ? 'available' : 'unavailable'}">
                            ${item.is_available ? 'Available' : 'Not Available'}
                        </span>
                        <span class="prep-time">
                            <i class="fas fa-clock"></i> ${item.preparation_time} mins
                        </span>
                    </div>
                </div>
                <div class="item-actions">
                    <button class="btn btn-sm btn-edit" onclick="editItem(${item.item_id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-delete" onclick="deleteItem(${item.item_id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            menuItemsGrid.appendChild(card);
        });
    }
    
    async function handleItemSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(itemForm);
        formData.append('action', currentItemId ? 'update' : 'create');
        if (currentItemId) {
            formData.append('item_id', currentItemId);
        }
        
        try {
            const response = await fetch('../api/menu_handler.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification(data.message, 'success');
                itemModal.classList.remove('show');
                loadMenuItems();
            } else {
                showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('An error occurred while saving the item', 'error');
        }
    }
    
    window.editItem = async function(itemId) {
        currentItemId = itemId;
        
        try {
            const response = await fetch(`../api/menu_handler.php?action=get&item_id=${itemId}`);
            const data = await response.json();
            
            if (data.success) {
                const item = data.data;
                document.getElementById('modalTitle').textContent = 'Edit Menu Item';
                document.getElementById('itemId').value = item.item_id;
                document.getElementById('itemName').value = item.name;
                document.getElementById('itemDescription').value = item.description || '';
                document.getElementById('itemPrice').value = item.price;
                document.getElementById('itemCategory').value = item.category_id;
                document.getElementById('prepTime').value = item.preparation_time;
                document.getElementById('isAvailable').checked = item.is_available == 1;
                document.getElementById('isFeatured').checked = item.is_featured == 1;
                
                if (item.image_url) {
                    imagePreview.innerHTML = `
                        <img src="../${item.image_url}" alt="Preview" style="max-width: 200px;">
                        <button type="button" class="btn btn-sm btn-danger" onclick="clearImage()">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    `;
                } else {
                    imagePreview.innerHTML = '';
                }
                
                itemModal.classList.add('show');
            } else {
                showNotification('Failed to load item details', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('An error occurred while loading item details', 'error');
        }
    };
    
    window.deleteItem = function(itemId) {
        currentItemId = itemId;
        deleteModal.classList.add('show');
        
        document.getElementById('confirmDelete').onclick = async function() {
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('item_id', itemId);
                
                const response = await fetch('../api/menu_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    deleteModal.classList.remove('show');
                    loadMenuItems();
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred while deleting the item', 'error');
            }
        };
    };
    
    window.clearImage = function() {
        document.getElementById('itemImage').value = '';
        imagePreview.innerHTML = '';
    };
    
    // Utility functions
    function showLoading() {
        menuItemsGrid.innerHTML = '<div class="loading">Loading items...</div>';
    }
    
    function showError(message) {
        menuItemsGrid.innerHTML = `<div class="error">${message}</div>`;
    }
    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }, 100);
    }
    
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});
