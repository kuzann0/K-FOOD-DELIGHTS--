<?php
// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <img src="<?php echo BASE_URL; ?>/../resources/images/logo.png" alt="K-Food Delights Logo" class="sidebar-logo">
        <h2>Admin Panel</h2>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <!-- Dashboard -->
            <li class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            
            <!-- Maintenance Section -->
            <?php if (hasPermission('manage_system')): ?>
            <li class="nav-section">
                <span class="nav-section-title">
                    <i class="fas fa-cogs"></i>
                    Maintenance
                </span>
                <ul class="submenu">
                    <li class="<?php echo $currentPage === 'landing_settings.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/modules/landing_settings.php">
                            <i class="fas fa-home"></i>
                            Landing Page Setting
                        </a>
                    </li>
                    
                    <li class="<?php echo $currentPage === 'user_roles.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/modules/user_roles.php">
                            <i class="fas fa-users-cog"></i>
                            User Role Management
                        </a>
                    </li>
                    
                    <li class="<?php echo $currentPage === 'user_accounts.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/modules/user_accounts.php">
                            <i class="fas fa-user-shield"></i>
                            User Account Management
                        </a>
                    </li>
                    
                    <li class="<?php echo $currentPage === 'inventory.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/modules/inventory.php">
                            <i class="fas fa-boxes"></i>
                            Inventory Management
                        </a>
                    </li>
                    
                    <li class="<?php echo $currentPage === 'product_categories.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/modules/product_categories.php">
                            <i class="fas fa-tags"></i>
                            Product Category
                        </a>
                    </li>
                    
                    <li class="<?php echo $currentPage === 'menu_creation.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/modules/menu_creation.php">
                            <i class="fas fa-utensils"></i>
                            Menu Creation
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
            
            <!-- Monitoring Section -->
            <?php if (hasPermission('view_reports')): ?>
            <li class="nav-section">
                <span class="nav-section-title">
                    <i class="fas fa-chart-line"></i>
                    Monitoring
                </span>
                <ul class="submenu">
                    <li class="<?php echo $currentPage === 'sales_reports.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/modules/sales_reports.php">
                            <i class="fas fa-chart-bar"></i>
                            Sales Reports
                        </a>
                    </li>
                    
                    <li class="<?php echo $currentPage === 'inventory_reports.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/modules/inventory_reports.php">
                            <i class="fas fa-clipboard-list"></i>
                            Inventory Reports
                        </a>
                    </li>
                    
                    <li class="<?php echo $currentPage === 'expiration_reports.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/modules/expiration_reports.php">
                            <i class="fas fa-calendar-times"></i>
                            Expiration Reports
                        </a>
                    </li>
                    
                    <li class="<?php echo $currentPage === 'expiration_tracking.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/modules/expiration_tracking.php">
                            <i class="fas fa-hourglass-half"></i>
                            Expiration Tracking
                        </a>
                    </li>
                    
                    <li class="<?php echo $currentPage === 'system_monitor.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/modules/system_monitor.php">
                            <i class="fas fa-desktop"></i>
                            System Monitor
                        </a>
                    </li>
                    
                    <li class="<?php echo $currentPage === 'alert_rules.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/modules/alert_rules.php">
                            <i class="fas fa-bell"></i>
                            Alert Rules
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
            
            <!-- Order Management Section -->
            <?php if (hasPermission('manage_orders')): ?>
            <li class="nav-section">
                <span class="nav-section-title">
                    <i class="fas fa-shopping-cart"></i>
                    Order Status
                </span>
                <ul class="submenu">
                    <li class="<?php echo $currentPage === 'order_processing.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/modules/order_processing.php">
                            <i class="fas fa-tasks"></i>
                            Order Processing
                        </a>
                    </li>
                    
                    <li class="<?php echo $currentPage === 'pending_orders.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/modules/pending_orders.php">
                            <i class="fas fa-clock"></i>
                            Pending Orders
                        </a>
                    </li>
                    
                    <li class="<?php echo $currentPage === 'delivery_status.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/modules/delivery_status.php">
                            <i class="fas fa-truck"></i>
                            Delivery Status
                        </a>
                    </li>
                    
                    <li class="<?php echo $currentPage === 'completed_orders.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/modules/completed_orders.php">
                            <i class="fas fa-check-circle"></i>
                            Completed Orders
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
            
            <!-- Settings -->
            <?php if (hasPermission('manage_settings')): ?>
            <li class="<?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/settings.php">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Logout -->
            <li>
                <a href="<?php echo BASE_URL; ?>/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </li>
        </ul>
    </nav>
</aside>
