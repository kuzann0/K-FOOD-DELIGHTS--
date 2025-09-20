-- User Roles and Permissions Schema

-- User Roles
CREATE TABLE IF NOT EXISTS user_roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default roles
INSERT INTO user_roles (role_name, description) VALUES
('administrator', 'Full system access with all privileges'),
('crew', 'Store staff with limited administrative access'),
('customer', 'Regular customer account');

-- Permissions
CREATE TABLE IF NOT EXISTS permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert core permissions
INSERT INTO permissions (permission_name, description) VALUES
-- Admin Permissions
('manage_users', 'Can manage user accounts'),
('manage_roles', 'Can manage user roles'),
('manage_products', 'Can manage products and categories'),
('manage_inventory', 'Can manage inventory and stock'),
('manage_orders', 'Can manage and process orders'),
('view_reports', 'Can view and generate reports'),
('manage_suppliers', 'Can manage supplier information'),
('manage_content', 'Can manage website content'),
-- Crew Permissions
('process_orders', 'Can process customer orders'),
('update_inventory', 'Can update inventory levels'),
('view_dashboard', 'Can view dashboard statistics'),
('manage_cash_drawer', 'Can manage assigned cash drawer'),
-- Customer Permissions
('place_orders', 'Can place new orders'),
('view_menu', 'Can view product menu'),
('manage_profile', 'Can manage own profile'),
('track_orders', 'Can track order status');

-- Role Permissions
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT,
    permission_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES user_roles(role_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id)
);

-- Cash Drawer Management
CREATE TABLE IF NOT EXISTS cash_drawers (
    drawer_id INT PRIMARY KEY AUTO_INCREMENT,
    assigned_to INT,
    opening_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    current_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('open', 'closed') DEFAULT 'closed',
    opened_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES admin_users(admin_id)
);

-- Cash Drawer Transactions
CREATE TABLE IF NOT EXISTS drawer_transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    drawer_id INT NOT NULL,
    transaction_type ENUM('sale', 'refund', 'adjustment', 'cash_in', 'cash_out') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reference_id VARCHAR(100),
    notes TEXT,
    performed_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (drawer_id) REFERENCES cash_drawers(drawer_id),
    FOREIGN KEY (performed_by) REFERENCES admin_users(admin_id)
);
