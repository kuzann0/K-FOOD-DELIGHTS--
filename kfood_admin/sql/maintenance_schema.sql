-- System Logs Table
CREATE TABLE IF NOT EXISTS system_logs (
    log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    log_type VARCHAR(50) NOT NULL,
    log_level ENUM('INFO', 'WARNING', 'ERROR', 'CRITICAL') NOT NULL,
    message TEXT NOT NULL,
    context JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    source VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    admin_id INT UNSIGNED,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- System Backups Table
CREATE TABLE IF NOT EXISTS system_backups (
    backup_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    backup_name VARCHAR(255) NOT NULL,
    backup_type ENUM('FULL', 'DIFFERENTIAL', 'LOG') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size BIGINT UNSIGNED,
    checksum VARCHAR(64),
    status ENUM('PENDING', 'IN_PROGRESS', 'COMPLETED', 'FAILED') NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    created_by INT UNSIGNED,
    notes TEXT,
    FOREIGN KEY (created_by) REFERENCES admins(admin_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- System Updates Table
CREATE TABLE IF NOT EXISTS system_updates (
    update_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(50) NOT NULL,
    update_type ENUM('SECURITY', 'FEATURE', 'BUGFIX', 'PATCH') NOT NULL,
    description TEXT,
    changelog TEXT,
    status ENUM('PENDING', 'IN_PROGRESS', 'COMPLETED', 'FAILED', 'ROLLED_BACK') NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    executed_by INT UNSIGNED,
    rollback_script TEXT,
    is_automatic BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (executed_by) REFERENCES admins(admin_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Add new indexes
ALTER TABLE system_logs ADD INDEX idx_log_type_level (log_type, log_level);
ALTER TABLE system_logs ADD INDEX idx_created_at (created_at);
ALTER TABLE system_backups ADD INDEX idx_backup_type_status (backup_type, status);
ALTER TABLE system_updates ADD INDEX idx_version_status (version, status);
