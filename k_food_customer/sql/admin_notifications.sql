-- Create table for admin notifications
CREATE TABLE IF NOT EXISTS admin_notifications (
    notification_id VARCHAR(36) PRIMARY KEY,
    error_id VARCHAR(36) NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    user_message TEXT,
    file_path VARCHAR(255),
    line_number INT,
    stack_trace TEXT,
    context_data JSON,
    is_read TINYINT(1) DEFAULT 0,
    is_urgent TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    INDEX idx_error_id (error_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;