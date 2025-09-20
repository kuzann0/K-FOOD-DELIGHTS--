-- Create roles table
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create admin_users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    crew_admin_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (crew_admin_id) REFERENCES admin_users(id)
);

-- Create permissions table
CREATE TABLE IF NOT EXISTS permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create role_permissions table
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id)
);

-- Insert default roles
INSERT INTO roles (id, name, description) VALUES
(1, 'Super Admin', 'Full system access and control'),
(2, 'Crew Admin', 'Manages crew members and assigned operations'),
(3, 'Crew Member', 'Basic operational access'),
(4, 'Customer', 'Regular customer account')
ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    description = VALUES(description);

-- Insert default permissions
INSERT INTO permissions (name, description) VALUES
('manage_system', 'System maintenance and configuration'),
('manage_content', 'Content management'),
('manage_roles', 'Role and permission management'),
('manage_users', 'User account management'),
('manage_inventory', 'Inventory management'),
('manage_menu', 'Menu management'),
('view_reports', 'View system reports'),
('manage_orders', 'Order management'),
('place_orders', 'Place customer orders')
ON DUPLICATE KEY UPDATE
    description = VALUES(description);

-- Assign permissions to roles
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.id = 1 -- Super Admin gets all permissions
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Crew Admin permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions 
WHERE name IN ('manage_inventory', 'manage_menu', 'manage_orders', 'view_reports')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Crew Member permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions 
WHERE name IN ('manage_inventory', 'manage_orders')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Customer permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions 
WHERE name IN ('place_orders')
ON DUPLICATE KEY UPDATE role_id = role_id;
