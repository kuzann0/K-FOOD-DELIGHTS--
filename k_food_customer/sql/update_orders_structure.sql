-- Update orders table structure
ALTER TABLE orders
MODIFY order_id INT AUTO_INCREMENT,
MODIFY user_id INT NOT NULL,
MODIFY customer_name VARCHAR(100) NOT NULL,
MODIFY order_number VARCHAR(50) NULL,
ADD UNIQUE INDEX idx_order_number (order_number),
MODIFY order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
MODIFY total_amount DECIMAL(10,2) NOT NULL,
MODIFY status ENUM('Pending', 'Processing', 'Out for Delivery', 'Delivered', 'Cancelled') DEFAULT 'Pending',
MODIFY payment_status ENUM('Pending', 'Paid', 'Failed') DEFAULT 'Pending',
MODIFY delivery_address TEXT NOT NULL,
MODIFY contact_number VARCHAR(20) NOT NULL,
MODIFY special_instructions TEXT NULL,
MODIFY created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
MODIFY updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add foreign key constraint for user_id if not exists
ALTER TABLE orders
ADD CONSTRAINT fk_orders_user
FOREIGN KEY (user_id) REFERENCES users(user_id)
ON DELETE CASCADE
ON UPDATE CASCADE;