<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
$admin = getCurrentAdmin();
// Require admin login for dashboard access
if (!$admin) {
    header('Location: admin_login.php');
    exit();
}
$pageTitle = "Dashboard";
$currentModule = "dashboard";

// Include the common header
include 'includes/header_common.php';
?>
<head>
    <link rel="stylesheet" href="css/admin_dashboard.css">
    <link rel="stylesheet" href="css/user_roles.css">
    <link rel="stylesheet" href="css/menu_creation.css">
    <link rel="stylesheet" href="css/system-monitor.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../resources/images/logo.png" alt="K-Food Delights Logo" class="sidebar-logo">
                <h2>Admin Panel</h2>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </li>
                    
                    <?php if (hasPermission('manage_content')): ?>
                    <li>
                        <a href="modules/landing.php">
                            <i class="fas fa-paint-brush"></i>
                            Landing Settings
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('manage_roles')): ?>
                    <li>
                        <a href="#" id="userRolesLink">
                            <i class="fas fa-user-shield"></i>
                            User Roles
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('manage_users')): ?>
                    <li>
                        <a href="modules/users.php">
                            <i class="fas fa-users"></i>
                            User Accounts
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('manage_inventory')): ?>
                    <li>
                        <a href="modules/inventory.php">
                            <i class="fas fa-boxes"></i>
                            Inventory
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('manage_menu')): ?>
                    <li>
                        <a href="#" id="menuCreationLink">
                            <i class="fas fa-utensils"></i>
                            Menu Creation
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('view_reports')): ?>
                    <li>
                        <a href="modules/reports.php">
                            <i class="fas fa-chart-bar"></i>
                            Reports
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('manage_orders')): ?>
                    <li>
                        <a href="modules/orders.php">
                            <i class="fas fa-shopping-cart"></i>
                            Orders
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div class="header-search">
                    <input type="text" placeholder="Search...">
                </div>
                
                <div class="header-user">
                    <span class="user-name"><?php echo htmlspecialchars($admin['username']); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($admin['role_name']); ?></span>
                    <!-- Logout button: securely logs out admin and redirects to admin_login.php -->
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </header>
            
            <div class="dashboard-content" id="mainContent">
                <div class="dashboard-header">
                    <h1>Dashboard</h1>
                </div>

                <!-- Main Dashboard Panels -->
                <div class="dashboard-panels">
                    <!-- Administrator Module -->
                    <div class="module-section maintenance-module">
                        <h2><i class="fas fa-cogs"></i> Maintenance</h2>
                        <div class="panel-grid">
                            <div class="panel">
                                <div class="panel-icon">
                                    <i class="fas fa-paint-brush"></i>
                                </div>
                                <div class="panel-content">
                                    <h3>Landing Page Setting</h3>
                                    <p>Modify landing page elements, contact information, and business profiles.</p>
                                    <a href="modules/landing.php" class="panel-link">Manage Settings</a>
                                </div>
                            </div>
                            
                            <div class="panel">
                                <div class="panel-icon">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div class="panel-content">
                                    <h3>User Role Management</h3>
                                    <p>Add, update, and monitor user roles for account management.</p>
                                    <a href="#" class="panel-link" id="userRolesPanelLink">Manage Roles</a>
                                </div>
                            </div>

                            <div class="panel">
                                <div class="panel-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="panel-content">
                                    <h3>User Account Management</h3>
                                    <p>Manage user accounts and ensure smooth operations.</p>
                                    <a href="modules/users.php" class="panel-link">Manage Users</a>
                                </div>
                            </div>

                            <div class="panel">
                                <div class="panel-icon">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <div class="panel-content">
                                    <h3>Inventory Management</h3>
                                    <p>Add and manage products with necessary details.</p>
                                    <a href="modules/inventory.php" class="panel-link">Manage Inventory</a>
                                </div>
                            </div>

                            <div class="panel">
                                <div class="panel-icon">
                                    <i class="fas fa-th-list"></i>
                                </div>
                                <div class="panel-content">
                                    <h3>Product Category</h3>
                                    <p>Organize products into defined categories.</p>
                                    <a href="modules/categories.php" class="panel-link">Manage Categories</a>
                                </div>
                            </div>

                            <div class="panel">
                                <div class="panel-icon">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <div class="panel-content">
                                    <h3>Menu Creation</h3>
                                    <p>Generate finished goods from raw product data.</p>
                                    <a href="#" class="panel-link" id="menuCreationPanelLink">Create Menu</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monitoring Module -->
                    <div class="module-section monitoring-module">
                        <h2><i class="fas fa-chart-line"></i> Monitoring</h2>
                        <div class="panel-grid">
                            <div class="panel">
                                <div class="panel-icon">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div class="panel-content">
                                    <h3>Sales Reports</h3>
                                    <p>Generate daily, weekly, monthly, quarterly, and annual reports.</p>
                                    <a href="modules/sales-reports.php" class="panel-link">View Reports</a>
                                </div>
                            </div>

                            <div class="panel">
                                <div class="panel-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <div class="panel-content">
                                    <h3>Inventory Report</h3>
                                    <p>Track current stock levels and inventory movement history.</p>
                                    <a href="modules/inventory-reports.php" class="panel-link">View Reports</a>
                                </div>
                            </div>

                            <div class="panel">
                                <div class="panel-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="panel-content">
                                    <h3>Expiration Reports</h3>
                                    <p>Track expired goods and minimize waste.</p>
                                    <a href="modules/expiration-reports.php" class="panel-link">View Reports</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Status Module -->
                    <div class="module-section order-module">
                        <h2><i class="fas fa-shopping-cart"></i> Order Status</h2>
                        <div class="panel-grid">
                            <div class="panel">
                                <div class="panel-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="panel-content">
                                    <h3>Pending Orders</h3>
                                    <p>Orders being processed and prepared.</p>
                                    <a href="modules/orders.php?status=pending" class="panel-link">View Pending</a>
                                </div>
                            </div>

                            <div class="panel">
                                <div class="panel-icon">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div class="panel-content">
                                    <h3>Delivery Status</h3>
                                    <p>Track orders out for delivery.</p>
                                    <a href="modules/orders.php?status=delivered" class="panel-link">View Deliveries</a>
                                </div>
                            </div>

                            <div class="panel">
                                <div class="panel-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="panel-content">
                                    <h3>Completed Orders</h3>
                                    <p>Orders successfully delivered to customers.</p>
                                    <a href="modules/orders.php?status=completed" class="panel-link">View Completed</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Roles Section -->
                <div class="user-roles-section" id="userRolesSection" style="display: none;">
                    <div class="user-roles-header">
                        <h2>User Management</h2>
                        <p class="section-description">Create and manage user accounts for different roles</p>
                    </div>
                    
                    <div class="user-roles-container">
                        <!-- Role Selection Tabs -->
                        <div class="role-tabs">
                            <button type="button" class="role-tab active" data-role="admin" data-role-id="2">
                                <div class="role-icon">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <h3>Administrator</h3>
                                <p>System management with full access to administrative features</p>
                            </button>
                            <button type="button" class="role-tab" data-role="crew" data-role-id="3">
                                <div class="role-icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <h3>Crew Member</h3>
                                <p>Staff account for order processing and customer service</p>
                            </button>
                            <button type="button" class="role-tab" data-role="customer" data-role-id="4">
                                <div class="role-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h3>Customer</h3>
                                <p>Regular user account for placing orders and tracking deliveries</p>
                            </button>
                        </div>
                        
                        <!-- Role Description -->
                        <div class="role-description" id="roleDescription">
                            <div class="role-icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="role-info">
                                <h3>Administrator Account</h3>
                                <p>Create an administrator account with full access to system features including user management, 
                                inventory control, order processing, and system configuration.</p>
                                <ul class="role-permissions">
                                    <li><i class="fas fa-check"></i> Full system access</li>
                                    <li><i class="fas fa-check"></i> User management</li>
                                    <li><i class="fas fa-check"></i> Order processing</li>
                                    <li><i class="fas fa-check"></i> Inventory control</li>
                                    <li><i class="fas fa-check"></i> System configuration</li>
                                </ul>
                            </div>
                        </div>

                        <!-- User Creation Form -->
                        <div class="user-form-container">
                            <h2>Create New User</h2>
                            <form id="userCreateForm" class="user-form" enctype="multipart/form-data" action="api/create_user.php" method="POST">
                                <div class="form-status">
                                    <div class="loading-indicator" style="display: none;">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        Creating account...
                                    </div>
                                    <div id="formMessage" class="form-message"></div>
                                </div>
                                <!-- Hidden input for role_id based on admin_roles table -->
                                <input type="hidden" id="role_id" name="role_id" value="2">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="first_name">First Name</label>
                                        <input type="text" id="first_name" name="first_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="last_name">Last Name</label>
                                        <input type="text" id="last_name" name="last_name" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input type="text" id="username" name="username" required>
                                        <small class="validation-message"></small>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" id="email" name="email" required>
                                        <small class="validation-message"></small>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="password">Password</label>
                                        <input type="password" id="password" name="password" required>
                                        <div class="password-strength"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm Password</label>
                                        <input type="password" id="confirm_password" name="confirm_password" required>
                                        <small class="validation-message"></small>
                                    </div>
                                </div>

                                <div class="form-row" data-field="phone">
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" id="phone" name="phone" pattern="[0-9+\-\s]+" title="Please enter a valid phone number">
                                        <small class="field-hint">Format: +63 XXX XXX XXXX (required for crew and customers)</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="status">Account Status</label>
                                        <select id="status" name="status" required>
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                        <small class="field-hint">Determines if the account can be used</small>
                                    </div>
                                </div>

                                <div class="form-group full-width" data-field="address">
                                    <label for="address">Delivery Address</label>
                                    <textarea id="address" name="address" rows="3" placeholder="Enter complete delivery address (required for customers)"></textarea>
                                    <small class="field-hint">Full address including street, building, city, and landmarks</small>
                                </div>
                                
                                <!-- Role-specific fields -->
                                <div class="form-row role-fields" data-role="admin">
                                    <div class="form-group">
                                        <label>Admin Permissions</label>
                                        <div class="checkbox-group">
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="permissions[]" value="manage_system"> System Management
                                            </label>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="permissions[]" value="manage_users"> User Management
                                            </label>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="permissions[]" value="manage_inventory"> Inventory Control
                                            </label>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="permissions[]" value="manage_orders"> Order Processing
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row role-fields" data-role="crew" style="display: none;">
                                    <div class="form-group">
                                        <label for="shift">Work Shift</label>
                                        <select id="shift" name="shift">
                                            <option value="morning">Morning (6 AM - 2 PM)</option>
                                            <option value="afternoon">Afternoon (2 PM - 10 PM)</option>
                                            <option value="evening">Evening (10 PM - 6 AM)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Crew Responsibilities</label>
                                        <div class="checkbox-group">
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="duties[]" value="order_processing"> Order Processing
                                            </label>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="duties[]" value="customer_service"> Customer Service
                                            </label>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="duties[]" value="inventory"> Inventory Management
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Customer preferences (only shown for customer role) -->
                                <div class="form-row role-fields" data-role="customer" style="display: none;">
                                    <div class="form-group">
                                        <label>Communication Preferences</label>
                                        <div class="checkbox-group">
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="preferences[]" value="email_notifications"> Email Notifications
                                            </label>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="preferences[]" value="sms_notifications"> SMS Notifications
                                            </label>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="preferences[]" value="promotional_emails"> Promotional Emails
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group profile-upload">
                                    <label>Profile Picture</label>
                                    <div class="profile-preview-container">
                                        <img id="profile_preview" src="../resources/images/default-profile.png" alt="Profile Preview" class="profile-preview">
                                        <div class="upload-controls">
                                            <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif" style="display: none;">
                                            <button type="button" class="upload-trigger">
                                                <i class="fas fa-camera"></i>
                                                Choose Image
                                            </button>
                                            <p class="upload-hint">Maximum size: 2MB. Formats: JPEG, PNG, GIF</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <div class="form-status">
                                        <div class="loading-indicator">
                                            <div class="loading-spinner"></div>
                                            <span>Creating account...</span>
                                        </div>
                                        <div id="formMessage" class="form-message"></div>
                                    </div>
                                    <div class="action-buttons">
                                        <button type="reset" class="btn-secondary">
                                            <i class="fas fa-undo"></i>
                                            Reset Form
                                        </button>
                                        <button type="submit" class="btn-primary">
                                            <i class="fas fa-user-plus"></i>
                                            Create Account
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="recent-users-section">
                            <h3>Recently Created Users</h3>
                            <div class="user-cards" id="recentUsers">
                                <!-- Users will be loaded here dynamically -->
                            </div>
                        </div>

                        <!-- User List Section -->
                        <div class="user-list-container">
                            <h2>Existing Users</h2>
                            <div class="user-list" id="userList">
                                <!-- Users will be loaded here dynamically -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-stats">
                    <!-- System Health Overview -->
                    <div class="stat-section system-health">
                        <h2>System Health</h2>
                        <div class="stat-grid">
                            <div class="stat-card">
                                <h3>System Load</h3>
                                <div class="stat-value" id="systemLoad">
                                    <span class="value">Loading...</span>
                                    <div class="progress-bar" id="loadProgressBar"></div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <h3>Memory Usage</h3>
                                <div class="stat-value" id="memoryUsage">
                                    <span class="value">Loading...</span>
                                    <div class="progress-bar" id="memoryProgressBar"></div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <h3>Error Rate</h3>
                                <div class="stat-value" id="errorRate">
                                    <span class="value">Loading...</span>
                                    <div class="trend-indicator"></div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <h3>Response Time</h3>
                                <div class="stat-value" id="responseTime">
                                    <span class="value">Loading...</span>
                                    <div class="trend-indicator"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Business Metrics -->
                    <div class="stat-section business-metrics">
                        <h2>Business Metrics</h2>
                        <div class="stat-grid">
                            <div class="stat-card">
                                <h3>Today's Orders</h3>
                                <div class="stat-value" id="todayOrders">
                                    <span class="value">Loading...</span>
                                    <span class="trend"></span>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <h3>Today's Revenue</h3>
                                <div class="stat-value" id="todayRevenue">
                                    <span class="value">Loading...</span>
                                    <span class="trend"></span>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <h3>Low Stock Items</h3>
                                <div class="stat-value" id="lowStockCount">
                                    <span class="value">Loading...</span>
                                    <div class="alert-indicator"></div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <h3>Active Users</h3>
                                <div class="stat-value" id="activeUsers">
                                    <span class="value">Loading...</span>
                                    <div class="trend-indicator"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Alerts and Notifications -->
                <div class="monitoring-section">
                    <div class="dashboard-alerts">
                        <div class="section-header">
                            <h2>System Alerts</h2>
                            <div class="alert-controls">
                                <select id="alertSeverityFilter">
                                    <option value="all">All Severities</option>
                                    <option value="critical">Critical</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                                <button id="refreshAlerts" class="btn-refresh">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div id="alertsList" class="alerts-list">
                            Loading alerts...
                        </div>
                    </div>

                    <!-- System Health Checks -->
                    <div class="health-checks">
                        <h2>Health Checks</h2>
                        <div class="health-checks-grid" id="healthChecksGrid">
                            <div class="health-check-card" id="dbHealth">
                                <i class="fas fa-database"></i>
                                <span class="check-name">Database</span>
                                <span class="status">Checking...</span>
                            </div>
                            
                            <div class="health-check-card" id="websocketHealth">
                                <i class="fas fa-plug"></i>
                                <span class="check-name">WebSocket</span>
                                <span class="status">Checking...</span>
                            </div>
                            
                            <div class="health-check-card" id="cacheHealth">
                                <i class="fas fa-memory"></i>
                                <span class="check-name">Cache</span>
                                <span class="status">Checking...</span>
                            </div>
                            
                            <div class="health-check-card" id="queueHealth">
                                <i class="fas fa-tasks"></i>
                                <span class="check-name">Job Queue</span>
                                <span class="status">Checking...</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-charts">
                    <div class="chart-container">
                        <h2>Sales Trend</h2>
                        <canvas id="salesChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h2>Popular Items</h2>
                        <canvas id="popularItemsChart"></canvas>
                    </div>
                </div>

                <!-- Menu Creation Panel -->
                <div class="menu-creation-panel" id="menuCreationPanel" style="display: none;">
                    <div class="panel-header">
                        <h2><i class="fas fa-utensils"></i> Create Menu Item</h2>
                        <button type="button" class="btn-close" id="closeMenuPanel">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form id="menuItemForm" class="menu-form" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="category_id">Category</label>
                                <select id="category_id" name="category_id" required>
                                    <!-- Categories will be loaded dynamically -->
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="name">Item Name</label>
                                <input type="text" id="name" name="name" required
                                       placeholder="Enter item name">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      placeholder="Enter item description"></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Price (PHP)</label>
                                <input type="number" id="price" name="price" required
                                       min="0" step="0.01" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label for="is_available">Availability</label>
                                <select id="is_available" name="is_available">
                                    <option value="1">Available</option>
                                    <option value="0">Not Available</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="item_image">Item Image</label>
                            <div class="image-upload-container">
                                <img id="imagePreview" src="../resources/images/default-food.png" 
                                     alt="Preview" class="image-preview">
                                <div class="upload-controls">
                                    <input type="file" id="item_image" name="item_image" 
                                           accept="image/*" style="display: none;">
                                    <button type="button" class="btn-upload" id="triggerUpload">
                                        <i class="fas fa-camera"></i> Choose Image
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-secondary" id="resetMenuForm">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-plus"></i> Create Item
                            </button>
                        </div>
                    </form>

                    <!-- Menu Items List -->
                    <div class="menu-items-list">
                        <h3>Current Menu Items</h3>
                        <div class="menu-items-grid" id="menuItemsGrid">
                            <!-- Items will be loaded dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Menu Creation Section -->
                <div class="menu-creation-section" id="menuCreationSection" style="display: none;">
                    <div class="section-header">
                        <h2>Menu Creation</h2>
                        <p class="section-description">Create and manage menu items</p>
                    </div>

                    <div class="menu-form-container">
                        <form id="menuItemForm" class="menu-form">
                            <div class="form-status">
                                <div class="loading-indicator" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    Creating menu item...
                                </div>
                                <div id="menuFormMessage" class="form-message"></div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="item_name">Item Name</label>
                                    <input type="text" id="item_name" name="item_name" required
                                           pattern="^[a-zA-Z0-9\s\-']+$"
                                           title="Please enter a valid item name using letters, numbers, spaces, hyphens, and apostrophes">
                                    <small class="validation-message"></small>
                                </div>

                                <div class="form-group">
                                    <label for="item_price">Price (PHP)</label>
                                    <input type="number" id="item_price" name="item_price" required
                                           min="0" step="0.01"
                                           placeholder="0.00"
                                           title="Please enter a valid price">
                                    <small class="validation-message"></small>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="reset" class="btn-secondary">
                                    <i class="fas fa-undo"></i>
                                    Reset Form
                                </button>
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-plus"></i>
                                    Create Menu Item
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Menu Items List -->
                    <div class="menu-items-list">
                        <h3>Available Menu Items</h3>
                        <div class="menu-items-grid" id="menuItemsList">
                            <!-- Menu items will be loaded here dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/admin_script.js"></script>
    <script src="js/dashboard.js"></script>
    <script src="js/user_roles.js"></script>
    <script src="js/menu_creation.js"></script>
    <script src="js/system-monitor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize role management
            initRoleManagement();

            // Handle form submission
            document.getElementById('userCreateForm').addEventListener('submit', async function(e) {
                e.preventDefault(); // Prevent form from submitting normally
                
                // Debug notification to ensure our code is running
                showNotification('Processing your request...', 'success');
                
                const form = this;
                const formData = new FormData(form);
                const loadingIndicator = form.querySelector('.loading-indicator');
                
                try {
                    // Show loading indicator
                    loadingIndicator.style.display = 'flex';
                    
                    // Send the form data using fetch
                    const response = await fetch('api/create_user.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Show success notification
                        showNotification('Account created successfully', 'success');
                        
                        // Reset form
                        form.reset();
                        
                        // Reset profile picture preview if it exists
                        const preview = document.getElementById('profile_preview');
                        if (preview) {
                            preview.src = '../resources/images/default-profile.png';
                        }
                    } else {
                        showNotification(data.message || 'Failed to create account', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('An error occurred while creating the account', 'error');
                } finally {
                    // Hide loading indicator
                    loadingIndicator.style.display = 'none';
                }
            });

            // Function to show notifications
            function showNotification(message, type = 'success') {
                // Get or create notification container
                let container = document.getElementById('notification-container');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'notification-container';
                    document.body.appendChild(container);
                }

                // Create notification element
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                
                // Add icon based on type
                const icon = document.createElement('i');
                icon.className = `fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}`;
                notification.appendChild(icon);
                
                // Add message text
                const text = document.createElement('span');
                text.textContent = message;
                notification.appendChild(text);
                
                // Add to container
                container.appendChild(notification);
                
                // Remove after 3 seconds with animation
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.5s forwards';
                    setTimeout(() => {
                        container.removeChild(notification);
                        // Remove container if empty
                        if (container.children.length === 0) {
                            document.body.removeChild(container);
                        }
                    }, 500);
                }, 3000);
            }

            // Setup user roles link
            // Setup sidebar link handlers
            function setupSidebarLink(linkId, sectionId) {
                const link = document.getElementById(linkId);
                if (link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        // Hide all content sections
                        document.querySelectorAll('.dashboard-content > div').forEach(el => {
                            el.style.display = 'none';
                        });
                        // Show requested section
                        const section = document.getElementById(sectionId);
                        if (section) {
                            section.style.display = 'block';
                        }
                        // Update active state in sidebar
                        document.querySelectorAll('.sidebar-nav li').forEach(li => {
                            li.classList.remove('active');
                        });
                        this.parentElement.classList.add('active');
                    });
                }
            }

            // Setup event handlers for sidebar links
            setupSidebarLink('userRolesLink', 'userRolesSection');
            setupSidebarLink('menuCreationLink', 'menuCreationSection');

            // Original user roles link handler for compatibility
            const userRolesLink = document.getElementById('userRolesLink');
            if (userRolesLink) {
                userRolesLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Get the user roles section
                    const userRolesSection = document.getElementById('userRolesSection');
                    
                    // Only proceed if the section exists
                    if (userRolesSection) {
                        // Hide dashboard content
                        document.querySelectorAll('.dashboard-content > div:not(#userRolesSection)').forEach(el => {
                            el.style.display = 'none';
                        });
                        // Show user roles section
                        userRolesSection.style.display = 'block';
                    }
                    
                    // Update active state in sidebar
                    document.querySelectorAll('.sidebar-nav li').forEach(li => {
                        li.classList.remove('active');
                    });
                    this.parentElement.classList.add('active');
                });
            }
        });
    </script>
</body>
</html>
