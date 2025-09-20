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
            <li class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            
            <?php if (hasPermission('manage_system')): ?>
            <li class="<?php echo $currentPage === 'maintenance.php' ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/modules/maintenance.php">
                    <i class="fas fa-tools"></i>
                    Maintenance
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasPermission('manage_content')): ?>
            <li class="<?php echo $currentPage === 'landing.php' ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/modules/landing.php">
                    <i class="fas fa-paint-brush"></i>
                    Landing Settings
                </a>
            </li>
            <?php endif; ?>
            
            <?php /* User roles temporarily disabled
            if (hasPermission('manage_roles')): ?>
            <li class="<?php echo $currentPage === 'roles.php' ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/modules/roles.php">
                    <i class="fas fa-user-shield"></i>
                    User Roles
                </a>
            </li>
            <?php endif;
            */ ?>
            
            <?php if (isAdmin()): ?>
            <li class="<?php echo $currentPage === 'register_admin.php' ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/register_admin.php">
                    <i class="fas fa-user-plus"></i>
                    Register Admin
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasPermission('manage_users')): ?>
            <li class="<?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/modules/users.php">
                    <i class="fas fa-users"></i>
                    User Accounts
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasPermission('manage_inventory')): ?>
            <li class="<?php echo $currentPage === 'inventory.php' ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/modules/inventory.php">
                    <i class="fas fa-boxes"></i>
                    Inventory
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasPermission('manage_menu')): ?>
            <li class="<?php echo $currentPage === 'menu.php' ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/modules/menu.php">
                    <i class="fas fa-utensils"></i>
                    Menu Items
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasPermission('view_reports')): ?>
            <li class="<?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/modules/reports.php">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasPermission('manage_orders')): ?>
            <li class="<?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/modules/orders.php">
                    <i class="fas fa-shopping-cart"></i>
                    Orders
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
