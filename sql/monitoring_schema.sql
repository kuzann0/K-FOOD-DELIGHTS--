-- System Logs Table
CREATE TABLE IF NOT EXISTS system_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    data JSON,
    INDEX idx_timestamp (timestamp),
    INDEX idx_type (type)
) ENGINE=InnoDB;

-- System Alerts Table
CREATE TABLE IF NOT EXISTS system_alerts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status ENUM('unread', 'read', 'acknowledged') NOT NULL DEFAULT 'unread',
    created_at DATETIME NOT NULL,
    acknowledged_at DATETIME,
    acknowledged_by INT,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Performance Metrics Table
CREATE TABLE IF NOT EXISTS performance_metrics (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME NOT NULL,
    metric_name VARCHAR(100) NOT NULL,
    metric_value FLOAT NOT NULL,
    context JSON,
    INDEX idx_timestamp_metric (timestamp, metric_name)
) ENGINE=InnoDB;

-- Error Tracking Table
CREATE TABLE IF NOT EXISTS error_tracking (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME NOT NULL,
    error_type VARCHAR(100) NOT NULL,
    error_message TEXT NOT NULL,
    stack_trace TEXT,
    request_data JSON,
    user_id INT,
    resolved BOOLEAN DEFAULT FALSE,
    resolution_notes TEXT,
    INDEX idx_timestamp (timestamp),
    INDEX idx_error_type (error_type),
    INDEX idx_resolved (resolved)
) ENGINE=InnoDB;

-- System Health Checks Table
CREATE TABLE IF NOT EXISTS system_health_checks (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    check_time DATETIME NOT NULL,
    check_name VARCHAR(100) NOT NULL,
    status ENUM('pass', 'warn', 'fail') NOT NULL,
    details JSON,
    INDEX idx_check_time (check_time),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Audit Trail Table
CREATE TABLE IF NOT EXISTS audit_trail (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME NOT NULL,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id VARCHAR(100) NOT NULL,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    INDEX idx_timestamp (timestamp),
    INDEX idx_user_action (user_id, action),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB;