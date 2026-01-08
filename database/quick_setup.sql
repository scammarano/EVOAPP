-- EVOAPP Quick Setup SQL
-- Run this in phpMyAdmin to create necessary tables

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Roles table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User roles junction table
CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT,
    role_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- Instances table
CREATE TABLE IF NOT EXISTS instances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    api_key VARCHAR(255) NOT NULL,
    base_url VARCHAR(255),
    webhook_token VARCHAR(255),
    webhook_enabled TINYINT(1) DEFAULT 1,
    forward_webhook_url VARCHAR(255),
    forward_webhook_enabled TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    last_webhook_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default roles
INSERT IGNORE INTO roles (id, name, description) VALUES 
(1, 'admin', 'System administrator'),
(2, 'user', 'Regular user');

-- Insert default instance
INSERT IGNORE INTO instances (slug, description, api_key, is_active) VALUES 
('main', 'Main WhatsApp Instance', 'your-api-key-here', 1);
