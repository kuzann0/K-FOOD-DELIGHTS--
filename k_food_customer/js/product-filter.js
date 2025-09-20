// Menu and Product Filter Functionality
let currentFilters = {
    search: '',
    category: ''
};

let menuItems = [];
let selectedItem = null;

function setupFilters() {
    // Search filter
    const searchInput = document.getElementById('menuSearch');
    searchInput.addEventListener('input', debounce(function() {
        currentFilters.search = this.value.toLowerCase();
        filterMenuItems();
    }, 300));
    
    // Category filter
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilters.category = this.dataset.category;
            filterMenuItems();
        });
    });
}

async function loadMenuItems() {
    try {
        const response = await fetch('api/product_operations.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            menuItems = data.data;
            filterMenuItems();
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Error loading menu items:', error);
        showNotification('Failed to load menu items', 'error');
    }
}

function filterMenuItems() {
    const filtered = menuItems.filter(item => {
        const matchesSearch = !currentFilters.search || 
            item.name.toLowerCase().includes(currentFilters.search) ||
            item.description.toLowerCase().includes(currentFilters.search);
            
        const matchesCategory = !currentFilters.category || 
            item.category_id === currentFilters.category;
            
        return matchesSearch && matchesCategory;
    });
    
    displayMenuItems(filtered);
}

function displayMenuItems(items) {
    const grid = document.getElementById('menuGrid');
    
    if (!items.length) {
        grid.innerHTML = '<div class="no-items">No menu items found</div>';
        return;
    }
    
    grid.innerHTML = items.map(item => `
        <div class="menu-item-card" onclick="showOrderModal(${item.item_id})">
            <div class="item-image">
                <img src="${item.image_url ? item.image_url : 'resources/images/default-food.png'}" 
                     alt="${escapeHtml(item.name)}"
                     onerror="this.src='resources/images/default-food.png'">
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
                    <span class="prep-time">
                        <i class="fas fa-clock"></i> ${item.preparation_time} mins
                    </span>
                </div>
            </div>
        </div>
    `).join('');
}

function showOrderModal(itemId) {
    selectedItem = menuItems.find(item => item.item_id === itemId);
    if (!selectedItem) return;
    
    document.getElementById('itemName').textContent = selectedItem.name;
    document.getElementById('itemDescription').textContent = selectedItem.description || '';
    document.getElementById('itemPrice').textContent = parseFloat(selectedItem.price).toFixed(2);
    document.getElementById('prepTime').textContent = selectedItem.preparation_time;
    document.getElementById('quantity').value = 1;
    
    const itemImage = document.getElementById('itemImage');
    itemImage.src = selectedItem.image_url || 'resources/images/default-food.png';
    itemImage.onerror = () => itemImage.src = 'resources/images/default-food.png';
    
    document.getElementById('orderModal').classList.add('show');
}

document.addEventListener('DOMContentLoaded', function() {
    // Close modal when clicking outside
    document.getElementById('orderModal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('show');
        }
    });
    
    // Close button functionality
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('orderModal').classList.remove('show');
        });
    });
    
    // Add to cart functionality
    document.getElementById('addToCart').addEventListener('click', function() {
        if (!selectedItem) return;
        
        const quantity = parseInt(document.getElementById('quantity').value);
        if (quantity < 1) return;
        
        addToCart({
            item_id: selectedItem.item_id,
            name: selectedItem.name,
            price: selectedItem.price,
            image_url: selectedItem.image_url,
            quantity: quantity
        });
        
        document.getElementById('orderModal').classList.remove('show');
    });
});

function incrementQuantity() {
    const input = document.getElementById('quantity');
    const currentValue = parseInt(input.value);
    if (currentValue < 99) {
        input.value = currentValue + 1;
    }
}

function decrementQuantity() {
    const input = document.getElementById('quantity');
    const currentValue = parseInt(input.value);
    if (currentValue > 1) {
        input.value = currentValue - 1;
    }
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
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
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
