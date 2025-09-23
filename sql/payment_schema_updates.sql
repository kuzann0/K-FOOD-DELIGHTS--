-- Payment system database schema updates

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_amount_positive CHECK (amount > 0),
    CONSTRAINT fk_order_payment FOREIGN KEY (order_id) REFERENCES orders(id),
    INDEX idx_status_date (status, created_at),
    INDEX idx_transaction (transaction_id),
    UNIQUE INDEX idx_transaction_unique (transaction_id)
) ENGINE=InnoDB;

-- Payment failures tracking
CREATE TABLE IF NOT EXISTS payment_failures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    error_message TEXT NOT NULL,
    attempt_number INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_failure FOREIGN KEY (order_id) REFERENCES orders(id),
    INDEX idx_order_attempts (order_id, attempt_number)
) ENGINE=InnoDB;

-- Payment logs for audit trail
CREATE TABLE IF NOT EXISTS payment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT,
    order_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_log FOREIGN KEY (payment_id) REFERENCES payments(id),
    CONSTRAINT fk_order_log FOREIGN KEY (order_id) REFERENCES orders(id),
    INDEX idx_payment_action (payment_id, action),
    INDEX idx_order_action (order_id, action)
) ENGINE=InnoDB;

-- Refunds tracking
CREATE TABLE IF NOT EXISTS refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    transaction_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_refund FOREIGN KEY (payment_id) REFERENCES payments(id),
    CONSTRAINT chk_refund_amount_positive CHECK (amount > 0),
    INDEX idx_payment_status (payment_id, status)
) ENGINE=InnoDB;

-- Payment gateway configurations
CREATE TABLE IF NOT EXISTS payment_gateway_configs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT true,
    config_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_gateway_name (gateway_name)
) ENGINE=InnoDB;