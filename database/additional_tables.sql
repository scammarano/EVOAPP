-- Additional tables for EVOAPP functionality
-- Run this after quick_setup.sql

-- Chats table
CREATE TABLE IF NOT EXISTS chats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_id INT NOT NULL,
    remote_jid VARCHAR(255) NOT NULL,
    title VARCHAR(255),
    is_group TINYINT(1) DEFAULT 0,
    unread_count INT DEFAULT 0,
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instance_id) REFERENCES instances(id) ON DELETE CASCADE,
    UNIQUE KEY unique_chat (instance_id, remote_jid)
);

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_id INT NOT NULL,
    message_id VARCHAR(255) NOT NULL,
    remote_jid VARCHAR(255) NOT NULL,
    from_me TINYINT(1) DEFAULT 0,
    msg_type VARCHAR(50) DEFAULT 'text',
    body_text TEXT,
    media_url VARCHAR(255),
    media_mime_type VARCHAR(100),
    participant_jid VARCHAR(255),
    display_name VARCHAR(255),
    ts TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    UNIQUE KEY unique_message (chat_id, message_id),
    INDEX idx_chat_ts (chat_id, ts),
    INDEX idx_instance_chat (instance_id, chat_id)
);

-- Webhook logs table
CREATE TABLE IF NOT EXISTS webhook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_id INT NOT NULL,
    event_type VARCHAR(100),
    data_json TEXT,
    processed TINYINT(1) DEFAULT 0,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instance_id) REFERENCES instances(id) ON DELETE CASCADE,
    INDEX idx_processed (processed),
    INDEX idx_created (created_at)
);

-- Audit log table
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    before_json TEXT,
    after_json TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_action (user_id, action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at)
);

-- Cron logs table
CREATE TABLE IF NOT EXISTS cron_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_name VARCHAR(100) NOT NULL,
    status ENUM('started', 'completed', 'failed') NOT NULL,
    message TEXT,
    duration_ms INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_job_status (job_name, status),
    INDEX idx_created (created_at)
);

-- Insert sample instance for testing
INSERT IGNORE INTO instances (slug, description, api_key, is_active) 
VALUES ('main', 'Main WhatsApp Instance', 'your-api-key-here', 1);
