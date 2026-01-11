-- Schema para Webhook Controller Integrated - Evolution API
-- Todas las tablas necesarias para procesar los 26 eventos

-- Tabla principal de instancias (actualizada)
CREATE TABLE IF NOT EXISTS evo_instances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_name VARCHAR(100) NOT NULL UNIQUE,
    connection_status VARCHAR(50) DEFAULT 'disconnected',
    is_connected TINYINT(1) DEFAULT 0,
    qr_code TEXT,
    jwt_token TEXT,
    webhook_url VARCHAR(500),
    webhook_enabled TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de mensajes
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_name VARCHAR(100) NOT NULL,
    message_id VARCHAR(100) NOT NULL,
    remote_jid VARCHAR(100) NOT NULL,
    from_me TINYINT(1) DEFAULT 0,
    message_content JSON,
    message_timestamp BIGINT,
    pushname VARCHAR(255),
    status VARCHAR(50) DEFAULT 'received',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_instance_message (instance_name, message_id),
    INDEX idx_remote_jid (remote_jid),
    INDEX idx_created_at (created_at)
);

-- Tabla de contactos
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_name VARCHAR(100) NOT NULL,
    remote_jid VARCHAR(100) NOT NULL,
    pushname VARCHAR(255),
    profile_pic_url VARCHAR(500),
    is_blocked TINYINT(1) DEFAULT 0,
    is_whatsapp_business TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_instance_contact (instance_name, remote_jid),
    INDEX idx_remote_jid (remote_jid)
);

-- Tabla de chats
CREATE TABLE IF NOT EXISTS chats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_name VARCHAR(100) NOT NULL,
    chat_id VARCHAR(100) NOT NULL,
    name VARCHAR(255),
    unread_messages INT DEFAULT 0,
    last_message_timestamp BIGINT,
    is_group TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_instance_chat (instance_name, chat_id),
    INDEX idx_unread_messages (unread_messages),
    INDEX idx_last_message (last_message_timestamp)
);

-- Tabla de grupos
CREATE TABLE IF NOT EXISTS groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_name VARCHAR(100) NOT NULL,
    group_id VARCHAR(100) NOT NULL,
    subject VARCHAR(255),
    description TEXT,
    owner VARCHAR(100),
    is_announce_group TINYINT(1) DEFAULT 0,
    is_restricted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_instance_group (instance_name, group_id),
    INDEX idx_owner (owner)
);

-- Tabla de participantes de grupos
CREATE TABLE IF NOT EXISTS group_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_name VARCHAR(100) NOT NULL,
    group_id VARCHAR(100) NOT NULL,
    user_jid VARCHAR(100) NOT NULL,
    name VARCHAR(255),
    is_admin TINYINT(1) DEFAULT 0,
    is_super_admin TINYINT(1) DEFAULT 0,
    action VARCHAR(50), -- add, remove, promote, demote
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_group_participants (group_id, user_jid),
    INDEX idx_instance_group (instance_name, group_id)
);

-- Tabla de sesiones de Typebot
CREATE TABLE IF NOT EXISTS typebot_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_name VARCHAR(100) NOT NULL,
    typebot_id VARCHAR(100) NOT NULL,
    remote_jid VARCHAR(100) NOT NULL,
    typebot_name VARCHAR(255),
    status VARCHAR(50) DEFAULT 'started', -- started, active, finished, error
    flow_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_typebot_sessions (typebot_id, remote_jid),
    INDEX idx_status (status)
);

-- Tabla de actualizaciones de presencia
CREATE TABLE IF NOT EXISTS presence_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_name VARCHAR(100) NOT NULL,
    remote_jid VARCHAR(100) NOT NULL,
    presence VARCHAR(50), -- available, unavailable, offline
    last_seen BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_presence_updates (remote_jid, created_at),
    INDEX idx_instance_presence (instance_name)
);

-- Tabla de mensajes de estado
CREATE TABLE IF NOT EXISTS status_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_name VARCHAR(100) NOT NULL,
    status_id VARCHAR(100) NOT NULL,
    remote_jid VARCHAR(100) NOT NULL,
    message TEXT,
    type VARCHAR(50), -- text, image, video, audio
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status_messages (remote_jid, created_at),
    INDEX idx_instance_status (instance_name)
);

-- Tabla de etiquetas
CREATE TABLE IF NOT EXISTS labels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_name VARCHAR(100) NOT NULL,
    label_id VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    color VARCHAR(7), -- hex color
    remote_jid VARCHAR(100), -- si es etiqueta específica de un contacto
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_labels (instance_name, label_id),
    INDEX idx_remote_jid (remote_jid)
);

-- Tabla de asociaciones de etiquetas
CREATE TABLE IF NOT EXISTS label_associations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_name VARCHAR(100) NOT NULL,
    label_id VARCHAR(100) NOT NULL,
    remote_jid VARCHAR(100) NOT NULL,
    action VARCHAR(50), -- add, remove
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_label_associations (label_id, remote_jid),
    INDEX idx_instance_label (instance_name)
);

-- Tabla de eventos de llamada
CREATE TABLE IF NOT EXISTS call_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_name VARCHAR(100) NOT NULL,
    call_id VARCHAR(100) NOT NULL,
    remote_jid VARCHAR(100) NOT NULL,
    status VARCHAR(50), -- ringing, ongoing, missed, completed
    type VARCHAR(50), -- audio, video
    duration INT DEFAULT 0, -- segundos
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_call_events (call_id),
    INDEX idx_remote_jid (remote_jid),
    INDEX idx_instance_call (instance_name)
);

-- Tabla de logs de webhooks
CREATE TABLE IF NOT EXISTS webhook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_name VARCHAR(100) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    data JSON,
    processed TINYINT(1) DEFAULT 1,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_webhook_logs (instance_name, event_type),
    INDEX idx_created_at (created_at)
);

-- Tabla de logs de aplicación
CREATE TABLE IF NOT EXISTS application_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_name VARCHAR(100) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    data JSON,
    level VARCHAR(20) DEFAULT 'info', -- info, warning, error
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_application_logs (instance_name, event_type),
    INDEX idx_level (level),
    INDEX idx_created_at (created_at)
);

-- Tabla de eventos desconocidos (para debugging)
CREATE TABLE IF NOT EXISTS unknown_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_name VARCHAR(100) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_unknown_events (instance_name, event_type)
);

-- Insertar instancias existentes si no existen
INSERT IGNORE INTO evo_instances (instance_name, connection_status) VALUES 
('SCAMMARANO', 'disconnected'),
('2CAMCARGO', 'disconnected'),
('CASAMIA', 'disconnected'),
('2CAMSERVICES', 'disconnected');

-- Crear vista para estadísticas
CREATE OR REPLACE VIEW webhook_stats AS
SELECT 
    instance_name,
    COUNT(*) as total_events,
    COUNT(CASE WHEN event_type LIKE 'MESSAGES_%' THEN 1 END) as message_events,
    COUNT(CASE WHEN event_type LIKE 'CONTACTS_%' THEN 1 END) as contact_events,
    COUNT(CASE WHEN event_type LIKE 'CHATS_%' THEN 1 END) as chat_events,
    COUNT(CASE WHEN event_type LIKE 'GROUPS_%' THEN 1 END) as group_events,
    COUNT(CASE WHEN event_type LIKE 'TYPEBOT_%' THEN 1 END) as typebot_events,
    MAX(created_at) as last_event,
    MIN(created_at) as first_event
FROM webhook_logs 
GROUP BY instance_name;

-- Crear vista para mensajes recientes
CREATE OR REPLACE VIEW recent_messages AS
SELECT 
    m.instance_name,
    m.message_id,
    m.remote_jid,
    c.pushname as contact_name,
    m.from_me,
    JSON_UNQUOTE(JSON_EXTRACT(m.message_content, '$.conversation')) as message_text,
    m.status,
    m.created_at
FROM messages m
LEFT JOIN contacts c ON m.instance_name = c.instance_name AND m.remote_jid = c.remote_jid
ORDER BY m.created_at DESC;

-- Crear vista para chats con mensajes no leídos
CREATE OR REPLACE VIEW unread_chats AS
SELECT 
    ch.instance_name,
    ch.chat_id,
    ch.name as chat_name,
    ch.unread_messages,
    ch.last_message_timestamp,
    c.pushname as contact_name,
    ch.is_group
FROM chats ch
LEFT JOIN contacts c ON ch.instance_name = c.instance_name AND ch.chat_id = c.remote_jid
WHERE ch.unread_messages > 0
ORDER BY ch.last_message_timestamp DESC;
