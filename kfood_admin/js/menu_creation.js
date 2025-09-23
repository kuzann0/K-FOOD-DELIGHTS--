document.addEventListener('DOMContentLoaded', function() {
    // Initialize menu creation functionality
    initMenuCreation();
});

function initMenuCreation() {
    const menuForm = document.getElementById('menuItemForm');
    const menuList = document.getElementById('menuItemsList');

    if (menuForm) {
        menuForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const loadingIndicator = this.querySelector('.loading-indicator');
            const messageElement = document.getElementById('menuFormMessage');

            try {
                // Show loading indicator
                loadingIndicator.style.display = 'flex';
                
                // Send the form data using fetch
                const response = await fetch('api/create_menu_item.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Menu item created successfully', 'success');
                    menuForm.reset();
                    
                    // Refresh the menu items list
                    loadMenuItems();
                } else {
                    showNotification(data.message || 'Failed to create menu item', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred while creating the menu item', 'error');
            } finally {
                loadingIndicator.style.display = 'none';
            }
        });
    }

    // Input validation
    const itemNameInput = document.getElementById('item_name');
    const itemPriceInput = document.getElementById('item_price');

    if (itemNameInput) {
        itemNameInput.addEventListener('input', function() {
            validateItemName(this);
        });
    }

    if (itemPriceInput) {
        itemPriceInput.addEventListener('input', function() {
            validatePrice(this);
        });
    }

    // Load existing menu items on page load
    loadMenuItems();
}

function validateItemName(input) {
    const namePattern = /^[a-zA-Z0-9\s\-']+$/;
    const validationMessage = input.nextElementSibling;
    
    if (!input.value) {
        validationMessage.textContent = 'Item name is required';
        return false;
    }
    
    if (!namePattern.test(input.value)) {
        validationMessage.textContent = 'Please use only letters, numbers, spaces, hyphens, and apostrophes';
        return false;
    }
    
    validationMessage.textContent = '';
    return true;
}

function validatePrice(input) {
    const price = parseFloat(input.value);
    const validationMessage = input.nextElementSibling;
    
    if (isNaN(price) || price < 0) {
        validationMessage.textContent = 'Please enter a valid positive price';
        return false;
    }
    
    validationMessage.textContent = '';
    return true;
}

async function loadMenuItems() {
    const menuList = document.getElementById('menuItemsList');
    if (!menuList) return;

    try {
        const response = await fetch('api/get_menu_items.php');
        const data = await response.json();

        if (data.success) {
            menuList.innerHTML = data.items.map(item => `
                <div class="menu-item-card">
                    <h4>${escapeHtml(item.name)}</h4>
                    <p class="price">₱${parseFloat(item.price).toFixed(2)}</p>
                </div>
            `).join('');
        } else {
            menuList.innerHTML = '<p class="error-message">Failed to load menu items</p>';
        }
    } catch (error) {
        console.error('Error loading menu items:', error);
        menuList.innerHTML = '<p class="error-message">Error loading menu items</p>';
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

// Add this function to show notifications
function showNotification(message, type = 'success') {
    const container = document.getElementById('notification-container') || createNotificationContainer();
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.5s forwards';
        setTimeout(() => {
            container.removeChild(notification);
            if (container.children.length === 0) {
                document.body.removeChild(container);
            }
        }, 500);
    }, 3000);
}

function createNotificationContainer() {
    const container = document.createElement('div');
    container.id = 'notification-container';
    document.body.appendChild(container);
    return container;
}