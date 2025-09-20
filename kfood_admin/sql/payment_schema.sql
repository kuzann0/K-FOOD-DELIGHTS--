-- Payment and Receipt Management

-- Payment Methods
CREATE TABLE IF NOT EXISTS payment_methods (
    method_id INT PRIMARY KEY AUTO_INCREMENT,
    method_name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT true,
    requires_confirmation BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payment Transactions
CREATE TABLE IF NOT EXISTS payment_transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    method_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    reference_number VARCHAR(100),
    payment_details JSON,
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (method_id) REFERENCES payment_methods(method_id),
    FOREIGN KEY (processed_by) REFERENCES admin_users(admin_id)
);

-- GCash Integration Settings
CREATE TABLE IF NOT EXISTS gcash_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    merchant_id VARCHAR(100) NOT NULL,
    merchant_secret VARCHAR(255) NOT NULL,
    environment ENUM('sandbox', 'production') DEFAULT 'sandbox',
    webhook_url VARCHAR(255),
    is_active BOOLEAN DEFAULT false,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES admin_users(admin_id)
);

-- Insert default payment methods
INSERT INTO payment_methods (method_name, requires_confirmation) VALUES
('Cash', false),
('GCash', true);

-- Digital Receipts
CREATE TABLE IF NOT EXISTS digital_receipts (
    receipt_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    receipt_number VARCHAR(50) NOT NULL UNIQUE,
    receipt_content LONGTEXT NOT NULL,
    pdf_path VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (created_by) REFERENCES admin_users(admin_id)
);
