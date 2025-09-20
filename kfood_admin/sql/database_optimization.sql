-- Database Optimization Indexes

-- Users Table Indexes
ALTER TABLE users
ADD INDEX idx_email (email),
ADD INDEX idx_username (username),
ADD INDEX idx_status (status);

-- Products Table Indexes
ALTER TABLE products
ADD INDEX idx_category (category_id),
ADD INDEX idx_active_featured (is_active, is_featured),
ADD INDEX idx_price (price),
ADD INDEX idx_stock (stock_quantity);

-- Orders Table Indexes
ALTER TABLE orders
ADD INDEX idx_user (user_id),
ADD INDEX idx_status (status),
ADD INDEX idx_dates (order_date, created_at),
ADD INDEX idx_order_number (order_number);

-- Order Items Table Indexes
ALTER TABLE order_items
ADD INDEX idx_product (product_id),
ADD INDEX idx_order_product (order_id, product_id);

-- Inventory Indexes
ALTER TABLE raw_materials
ADD INDEX idx_supplier (supplier_id),
ADD INDEX idx_stock_level (current_stock),
ADD INDEX idx_expiration (expiration_date);

-- Payment Transactions Indexes
ALTER TABLE payment_transactions
ADD INDEX idx_method (method_id),
ADD INDEX idx_status_date (status, created_at),
ADD INDEX idx_reference (reference_number);

-- User Activity Indexes
ALTER TABLE user_activity_log
ADD INDEX idx_user_activity (user_id, activity_type, created_at);

-- Role Permissions Indexes
ALTER TABLE role_permissions
ADD INDEX idx_role_permission (role_id, permission_id);

-- Content Management Indexes
ALTER TABLE content_pages
ADD INDEX idx_slug (page_slug),
ADD INDEX idx_published (is_published);

-- Help Center Indexes
ALTER TABLE help_center
ADD INDEX idx_category (category),
ADD INDEX idx_published_date (is_published, created_at);
