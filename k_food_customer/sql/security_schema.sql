-- User Security and Preferences

-- Password Reset Tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    token_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(100) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Two-Factor Authentication
CREATE TABLE IF NOT EXISTS user_2fa (
    user_id INT PRIMARY KEY,
    secret_key VARCHAR(100) NOT NULL,
    is_enabled BOOLEAN DEFAULT false,
    backup_codes JSON,
    enabled_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Security Questions
CREATE TABLE IF NOT EXISTS security_questions (
    question_id INT PRIMARY KEY AUTO_INCREMENT,
    question_text VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT true
);

-- User Security Answers
CREATE TABLE IF NOT EXISTS user_security_answers (
    user_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, question_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (question_id) REFERENCES security_questions(question_id)
);

-- User Preferences
CREATE TABLE IF NOT EXISTS user_preferences (
    user_id INT PRIMARY KEY,
    language VARCHAR(10) DEFAULT 'en',
    notification_email BOOLEAN DEFAULT true,
    notification_sms BOOLEAN DEFAULT false,
    theme VARCHAR(20) DEFAULT 'light',
    currency VARCHAR(3) DEFAULT 'PHP',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Insert default security questions
INSERT INTO security_questions (question_text) VALUES
('What was the name of your first pet?'),
('In what city were you born?'),
('What was your mother''s maiden name?'),
('What was the name of your elementary school?'),
('What was your childhood nickname?');
