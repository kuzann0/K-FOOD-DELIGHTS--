-- Crew Management Schema

-- Cash Drawer Table
CREATE TABLE IF NOT EXISTS cash_drawers (
    drawer_id INT PRIMARY KEY AUTO_INCREMENT,
    drawer_code VARCHAR(50) UNIQUE NOT NULL,
    assigned_to INT,
    current_balance DECIMAL(10,2) DEFAULT 0.00,
    last_counted_at TIMESTAMP NULL,
    status ENUM('open', 'closed', 'counting') DEFAULT 'closed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES admin_users(admin_id)
);

-- Cash Drawer Transactions
CREATE TABLE IF NOT EXISTS cash_drawer_transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    drawer_id INT NOT NULL,
    transaction_type ENUM('open', 'close', 'sale', 'refund', 'adjustment') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    order_id INT,
    notes TEXT,
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (drawer_id) REFERENCES cash_drawers(drawer_id),
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (recorded_by) REFERENCES admin_users(admin_id)
);

-- Insert Crew role if not exists
INSERT IGNORE INTO admin_roles (role_name, description) VALUES 
('Crew', 'Handles sales, orders, and basic inventory operations');
('Admin', 'Handles inventory management, stock monitoring, overall system performance.');

-- Add Crew permissions
INSERT IGNORE INTO permissions (permission_name, description) VALUES
('manage_cash_drawer', 'Open, close, and manage cash drawer'),
('process_sales', 'Process customer orders and payments'),
('view_inventory', 'View inventory levels and stock'),
('update_order_status', 'Update order preparation and delivery status');

-- Assign permissions to Crew role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT role_id FROM admin_roles WHERE role_name = 'Crew'),
    permission_id
FROM permissions 
WHERE permission_name IN (
    'manage_cash_drawer',
    'process_sales',
    'view_inventory',
    'update_order_status'
);
