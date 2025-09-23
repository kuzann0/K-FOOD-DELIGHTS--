-- Alert Rules Schema

-- Table for storing alert rules
CREATE TABLE IF NOT EXISTS alert_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type VARCHAR(50) NOT NULL,
    conditions TEXT NOT NULL,
    actions TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for storing triggered alerts
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    value TEXT,
    context_data JSON,
    status ENUM('new', 'acknowledged', 'resolved') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (rule_id) REFERENCES alert_rules(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for storing metric values
CREATE TABLE IF NOT EXISTS metric_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(255) NOT NULL,
    value FLOAT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_name (metric_name),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some default alert rules
INSERT INTO alert_rules (name, description, type, conditions, actions, severity) VALUES
(
    'High CPU Usage',
    'Alert when CPU usage exceeds 90% for 5 minutes',
    'system',
    '[{"operator": "threshold", "metric": "cpu_usage", "value": 90, "period": 300}]',
    '[{"type": "email", "recipients": ["admin@kfooddelights.com"]}]',
    'high'
),
(
    'Database Connection Errors',
    'Alert on multiple database connection failures',
    'database',
    '[{"operator": "threshold", "metric": "db_connection_errors", "value": 5, "period": 60}]',
    '[{"type": "email", "recipients": ["admin@kfooddelights.com"]}, {"type": "slack", "webhook": "YOUR_SLACK_WEBHOOK"}]',
    'critical'
),
(
    'Order Processing Time',
    'Alert when order processing takes more than 2 minutes',
    'order',
    '[{"operator": ">", "value": 120}]',
    '[{"type": "email", "recipients": ["manager@kfooddelights.com"]}]',
    'medium'
),
(
    'Failed Login Attempts',
    'Alert on multiple failed login attempts',
    'security',
    '[{"operator": "threshold", "metric": "failed_logins", "value": 10, "period": 300}]',
    '[{"type": "email", "recipients": ["security@kfooddelights.com"]}, {"type": "sms", "numbers": ["1234567890"]}]',
    'high'
);