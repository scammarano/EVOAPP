-- RECERAR TODAS LAS TABLAS CORRECTAS
SET FOREIGN_KEY_CHECKS = 0;

-- Tabla principal (ya debería existir)
CREATE TABLE IF NOT EXISTS evo_instances (
  id int(11) NOT NULL AUTO_INCREMENT,
  slug varchar(50) NOT NULL,
  description varchar(255) DEFAULT NULL,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  api_key varchar(255) NOT NULL,
  base_url varchar(255) DEFAULT NULL,
  webhook_token varchar(255) DEFAULT NULL,
  webhook_enabled tinyint(1) NOT NULL DEFAULT 1,
  forward_webhook_url varchar(255) DEFAULT NULL,
  forward_webhook_enabled tinyint(1) NOT NULL DEFAULT 0,
  last_webhook_at datetime DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tablas de usuarios
CREATE TABLE IF NOT EXISTS users (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  email varchar(255) NOT NULL,
  password_hash varchar(255) NOT NULL,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(50) NOT NULL,
  description text DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(100) NOT NULL,
  description text DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
  role_id int(11) NOT NULL,
  permission_id int(11) NOT NULL,
  PRIMARY KEY (role_id,permission_id),
  FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_roles (
  user_id int(11) NOT NULL,
  role_id int(11) NOT NULL,
  PRIMARY KEY (user_id,role_id),
  FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_instances (
  user_id int(11) NOT NULL,
  instance_id int(11) NOT NULL,
  can_view tinyint(1) NOT NULL DEFAULT 1,
  can_send tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id,instance_id),
  FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Perfiles de instancias
CREATE TABLE IF NOT EXISTS instance_profiles (
  id int(11) NOT NULL AUTO_INCREMENT,
  instance_id int(11) NOT NULL,
  profile_image_url varchar(500) DEFAULT NULL,
  description text DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY instance_id (instance_id),
  FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tablas de chats y mensajes
CREATE TABLE IF NOT EXISTS chats (
  id int(11) NOT NULL AUTO_INCREMENT,
  instance_id int(11) NOT NULL,
  remote_jid varchar(255) NOT NULL,
  is_group tinyint(1) NOT NULL DEFAULT 0,
  title varchar(255) DEFAULT NULL,
  last_snippet text DEFAULT NULL,
  last_message_at datetime DEFAULT NULL,
  unread_count int(11) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_instance_chat (instance_id, remote_jid),
  FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
  id int(11) NOT NULL AUTO_INCREMENT,
  instance_id int(11) NOT NULL,
  chat_id int(11) NOT NULL,
  message_id varchar(255) NOT NULL,
  from_me tinyint(1) NOT NULL DEFAULT 0,
  ts datetime NOT NULL,
  msg_type varchar(50) NOT NULL DEFAULT 'text',
  body_text text DEFAULT NULL,
  participant_jid varchar(255) DEFAULT NULL,
  media_url varchar(500) DEFAULT NULL,
  local_path varchar(500) DEFAULT NULL,
  status varchar(20) DEFAULT NULL,
  raw_json json DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_message (instance_id, message_id),
  FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE,
  FOREIGN KEY (chat_id) REFERENCES chats (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_reads (
  user_id int(11) NOT NULL,
  chat_id int(11) NOT NULL,
  last_read_ts datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id,chat_id),
  FOREIGN KEY (chat_id) REFERENCES chats (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contactos
CREATE TABLE IF NOT EXISTS contacts (
  id int(11) NOT NULL AUTO_INCREMENT,
  instance_id int(11) NOT NULL,
  phone_e164 varchar(20) NOT NULL,
  name varchar(255) DEFAULT NULL,
  company varchar(255) DEFAULT NULL,
  email varchar(255) DEFAULT NULL,
  birthday date DEFAULT NULL,
  notes text DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_instance_contact (instance_id, phone_e164),
  FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_lists (
  id int(11) NOT NULL AUTO_INCREMENT,
  instance_id int(11) NOT NULL,
  name varchar(255) NOT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_list_items (
  list_id int(11) NOT NULL,
  contact_id int(11) NOT NULL,
  PRIMARY KEY (list_id,contact_id),
  FOREIGN KEY (list_id) REFERENCES contact_lists (id) ON DELETE CASCADE,
  FOREIGN KEY (contact_id) REFERENCES contacts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_candidates (
  id int(11) NOT NULL AUTO_INCREMENT,
  instance_id int(11) NOT NULL,
  source_type enum('group','chat') NOT NULL,
  source_remote_jid varchar(255) NOT NULL,
  phone_e164 varchar(20) NOT NULL,
  name_guess varchar(255) DEFAULT NULL,
  raw_json json DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status enum('new','saved','ignored') NOT NULL DEFAULT 'new',
  PRIMARY KEY (id),
  FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campañas
CREATE TABLE IF NOT EXISTS campaigns (
  id int(11) NOT NULL AUTO_INCREMENT,
  instance_id int(11) NOT NULL,
  name varchar(255) NOT NULL,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  schedule_type enum('once','weekly','monthly','cron') NOT NULL DEFAULT 'once',
  start_at datetime NOT NULL,
  end_at datetime DEFAULT NULL,
  timezone varchar(50) NOT NULL DEFAULT 'America/Bogota',
  weekly_days varchar(20) DEFAULT NULL,
  monthly_day int(2) DEFAULT NULL,
  next_run_at datetime DEFAULT NULL,
  created_by int(11) NOT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaign_messages (
  id int(11) NOT NULL AUTO_INCREMENT,
  campaign_id int(11) NOT NULL,
  sort_order int(11) NOT NULL,
  text text NOT NULL,
  media_path varchar(500) DEFAULT NULL,
  media_type varchar(50) DEFAULT NULL,
  caption text DEFAULT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (campaign_id) REFERENCES campaigns (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaign_targets (
  id int(11) NOT NULL AUTO_INCREMENT,
  campaign_id int(11) NOT NULL,
  target_type enum('contact','list') NOT NULL,
  target_id int(11) NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (campaign_id) REFERENCES campaigns (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaign_runs (
  id int(11) NOT NULL AUTO_INCREMENT,
  campaign_id int(11) NOT NULL,
  run_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status enum('running','completed','failed') NOT NULL DEFAULT 'running',
  total int(11) NOT NULL DEFAULT 0,
  ok_count int(11) NOT NULL DEFAULT 0,
  fail_count int(11) NOT NULL DEFAULT 0,
  raw_log text DEFAULT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (campaign_id) REFERENCES campaigns (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaign_run_items (
  id int(11) NOT NULL AUTO_INCREMENT,
  run_id int(11) NOT NULL,
  contact_id int(11) NOT NULL,
  status enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  response_json json DEFAULT NULL,
  error_text text DEFAULT NULL,
  sent_at datetime DEFAULT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (run_id) REFERENCES campaign_runs (id) ON DELETE CASCADE,
  FOREIGN KEY (contact_id) REFERENCES contacts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhooks y auditoría
CREATE TABLE IF NOT EXISTS webhook_events (
  id int(11) NOT NULL AUTO_INCREMENT,
  instance_id int(11) NOT NULL,
  event_type varchar(50) NOT NULL,
  remote_jid varchar(255) DEFAULT NULL,
  message_id varchar(255) DEFAULT NULL,
  participant_jid varchar(255) DEFAULT NULL,
  payload_json json NOT NULL,
  received_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at datetime DEFAULT NULL,
  status enum('pending','processed','error') NOT NULL DEFAULT 'pending',
  error_text text DEFAULT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  action varchar(100) NOT NULL,
  entity_type varchar(50) DEFAULT NULL,
  entity_id int(11) DEFAULT NULL,
  before_json json DEFAULT NULL,
  after_json json DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cron_log (
  id int(11) NOT NULL AUTO_INCREMENT,
  job_key varchar(100) NOT NULL,
  started_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at datetime DEFAULT NULL,
  ok tinyint(1) NOT NULL DEFAULT 0,
  summary text DEFAULT NULL,
  error_text text DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Todas las tablas recreadas correctamente' as resultado;
