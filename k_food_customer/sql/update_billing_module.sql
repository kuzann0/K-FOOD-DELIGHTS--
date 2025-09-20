-- Add promotions table
CREATE TABLE IF NOT EXISTS promotions (
    promo_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE,
    type ENUM('percentage', 'fixed', 'buy_x_get_y', 'senior_pwd'),
    discount_value DECIMAL(10,2),
    min_purchase DECIMAL(10,2),
    max_discount DECIMAL(10,2),
    start_date DATETIME,
    end_date DATETIME,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add table for buy X get Y items
CREATE TABLE IF NOT EXISTS promo_items (
    promo_item_id INT AUTO_INCREMENT PRIMARY KEY,
    promo_id INT,
    buy_quantity INT,
    free_quantity INT,
    product_category VARCHAR(50),
    FOREIGN KEY (promo_id) REFERENCES promotions(promo_id)
);

-- Add discount fields to orders table
ALTER TABLE orders 
ADD COLUMN promo_id INT NULL,
ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN senior_pwd_discount DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN senior_pwd_id VARCHAR(50),
ADD FOREIGN KEY (promo_id) REFERENCES promotions(promo_id);

-- Update order status enum to include new statuses
ALTER TABLE orders 
MODIFY COLUMN status ENUM('Pending', 'Preparing', 'Delivered', 'Received', 'Cancelled') DEFAULT 'Pending';

-- Add customer receipt table
CREATE TABLE IF NOT EXISTS order_receipts (
    receipt_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    receipt_number VARCHAR(50) UNIQUE,
    pdf_path VARCHAR(255),
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);