<?php
// session_start();
?>
<header class="main-header">
    <div class="header-search">
        <input type="text" placeholder="Search..." id="globalSearch">
    </div>
    
    <div class="header-user">
        <div class="notifications">
            <button type="button" class="notification-btn" id="notificationBtn">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="notificationCount">0</span>
            </button>
            <div class="notification-dropdown" id="notificationDropdown">
                <div class="notification-header">
                    <h3>Notifications</h3>
                    <button type="button" class="mark-all-read">Mark all as read</button>
                </div>
                <div class="notification-list" id="notificationList">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>
        
        <div class="user-info">
            <?php 
            // Initialize admin data from session
            if (isset($_SESSION['admin'])) {
                $admin = $_SESSION['admin'];
            ?>
            <span class="user-name"><?php echo htmlspecialchars($admin['username']); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($admin['role_name']); ?></span>
            <?php } else { ?>
            <span class="user-name">Guest</span>
            <span class="user-role">Not logged in</span>
            <?php } ?>
            <div class="user-dropdown">
                <button type="button" class="user-dropdown-btn" id="userDropdownBtn">
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="dropdown-menu" id="userDropdownMenu">
                    <a href="<?php echo BASE_URL; ?>/profile.php">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="<?php echo BASE_URL; ?>/change-password.php">
                        <i class="fas fa-key"></i> Change Password
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo BASE_URL; ?>/logout.php" class="text-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
// Header functionality
document.addEventListener('DOMContentLoaded', function() {
    // Notification dropdown
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    notificationBtn?.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown.classList.toggle('show');
        loadNotifications();
    });
    
    // User dropdown
    const userDropdownBtn = document.getElementById('userDropdownBtn');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    
    userDropdownBtn?.addEventListener('click', function(e) {
        e.stopPropagation();
        userDropdownMenu.classList.toggle('show');
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.notifications')) {
            notificationDropdown?.classList.remove('show');
        }
        if (!e.target.closest('.user-dropdown')) {
            userDropdownMenu?.classList.remove('show');
        }
    });
    
    // Global search
    const globalSearch = document.getElementById('globalSearch');
    globalSearch?.addEventListener('input', debounce(function(e) {
        const searchTerm = e.target.value.trim();
        if (searchTerm.length >= 2) {
            performGlobalSearch(searchTerm);
        }
    }, 500));
});

// Load notifications
async function loadNotifications() {
    try {
        const response = await fetch(BASE_URL + '/api/notifications.php');
        const data = await response.json();
        
        updateNotificationBadge(data.unread);
        updateNotificationList(data.notifications);
        
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

// Update notification badge
function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationCount');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'block' : 'none';
    }
}

// Update notification list
function updateNotificationList(notifications) {
    const list = document.getElementById('notificationList');
    if (!list) return;
    
    if (notifications.length === 0) {
        list.innerHTML = '<div class="no-notifications">No new notifications</div>';
        return;
    }
    
    list.innerHTML = notifications.map(notification => `
        <div class="notification-item ${notification.read ? '' : 'unread'}">
            <div class="notification-icon">
                <i class="fas fa-${getNotificationIcon(notification.type)}"></i>
            </div>
            <div class="notification-content">
                <div class="notification-text">${notification.message}</div>
                <div class="notification-time">${formatTimeAgo(notification.created_at)}</div>
            </div>
            ${!notification.read ? `
                <button type="button" class="mark-read" onclick="markNotificationRead(${notification.id})">
                    <i class="fas fa-check"></i>
                </button>
            ` : ''}
        </div>
    `).join('');
}

// Get notification icon based on type
function getNotificationIcon(type) {
    switch (type) {
        case 'order': return 'shopping-bag';
        case 'inventory': return 'box';
        case 'system': return 'cog';
        case 'user': return 'user';
        default: return 'bell';
    }
}

// Mark notification as read
async function markNotificationRead(notificationId) {
    try {
        const response = await fetch(BASE_URL + '/api/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'mark_read',
                notification_id: notificationId
            })
        });
        
        const data = await response.json();
        if (data.success) {
            loadNotifications();
        }
        
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
}

// Global search
async function performGlobalSearch(term) {
    try {
        const response = await fetch(BASE_URL + '/api/search.php?q=' + encodeURIComponent(term));
        const results = await response.json();
        
        // Implementation depends on how you want to display results
        console.log('Search results:', results);
        
    } catch (error) {
        console.error('Error performing search:', error);
    }
}
</script>
