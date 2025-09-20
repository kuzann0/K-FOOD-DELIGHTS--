<?php
require_once 'includes/AdminNotifications.php';
$notifications = new AdminNotifications($conn);
$notificationCount = $notifications->getUnreadCount();
?>

<!-- Admin Header Component -->
<header class="admin-header">
    <div class="header-brand">
        <img src="../assets/images/logo.png" alt="K-Food Delight" class="brand-logo">
        <h1 class="brand-name">K-Food Delight Admin</h1>
    </div>
    
    <div class="header-controls">
        <!-- Notification Center -->
        <div class="notification-center">
            <button id="notificationButton" class="notification-toggle" aria-label="Notifications">
                <i class="fas fa-bell"></i>
                <?php echo $notifications->getNotificationBadge(); ?>
            </button>
            
            <!-- Notification Panel -->
            <div id="adminNotifications" class="notifications-container" style="display: none;">
                <div class="notifications-header">
                    <h3 class="notifications-title">Notifications</h3>
                    <button class="clear-all" onclick="notificationHandler.clearAllNotifications()">
                        Clear All
                    </button>
                </div>
                <div class="notifications-list"></div>
            </div>
        </div>
        
        <!-- Admin Controls -->
        <div class="admin-controls">
            <span class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
</header>

<!-- Include notification assets -->
<link rel="stylesheet" href="css/admin-notifications.css">
<script src="js/admin-notifications.js"></script>

<!-- Initialize notifications -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    window.notificationHandler = new AdminNotificationsHandler();
});
</script>