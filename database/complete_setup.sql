-- SETUP COMPLETO - Todo en un solo script
-- Ejecutar esto directamente

SET FOREIGN_KEY_CHECKS = 0;

-- Eliminar todo si existe
DROP TABLE IF EXISTS `campaign_run_items`;
DROP TABLE IF EXISTS `campaign_runs`;
DROP TABLE IF EXISTS `campaign_targets`;
DROP TABLE IF EXISTS `campaign_messages`;
DROP TABLE IF EXISTS `campaigns`;
DROP TABLE IF EXISTS `contact_list_items`;
DROP TABLE IF EXISTS `contact_lists`;
DROP TABLE IF EXISTS `contacts`;
DROP TABLE IF EXISTS `contact_candidates`;
DROP TABLE IF EXISTS `chat_reads`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `chats`;
DROP TABLE IF EXISTS `webhook_events`;
DROP TABLE IF EXISTS `user_instances`;
DROP TABLE IF EXISTS `instance_profiles`;
DROP TABLE IF EXISTS `instances`;
DROP TABLE IF EXISTS `evo_instances`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `user_roles`;
DROP TABLE IF EXISTS `audit_log`;
DROP TABLE IF EXISTS `cron_log`;

SET FOREIGN_KEY_CHECKS = 1;

-- Crear evo_instances
CREATE TABLE `evo_instances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `api_key` varchar(255) NOT NULL,
  `base_url` varchar(255) DEFAULT NULL,
  `webhook_token` varchar(255) DEFAULT NULL,
  `webhook_enabled` tinyint(1) DEFAULT 1,
  `forward_webhook_url` varchar(255) DEFAULT NULL,
  `forward_webhook_enabled` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restaurar evo_instances desde backup
INSERT INTO `evo_instances` (id, slug, description, api_key, base_url, webhook_token, webhook_enabled, forward_webhook_url, forward_webhook_enabled, is_active) 
SELECT id, slug, description, api_key, base_url, webhook_token, webhook_enabled, forward_webhook_url, forward_webhook_enabled, is_active 
FROM `backup_evo_instances`;

-- Crear sistema de usuarios
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_instances` (
  `user_id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 1,
  `can_send` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`instance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `instance_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `profile_image_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance_id` (`instance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tablas de aplicación
CREATE TABLE `chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `remote_jid` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `is_group` tinyint(1) NOT NULL DEFAULT 0,
  `last_message_at` datetime DEFAULT NULL,
  `last_message_id` int(11) DEFAULT NULL,
  `unread_count` int(11) NOT NULL DEFAULT 0,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_instance_chat` (`instance_id`,`remote_jid`),
  KEY `idx_instance_id` (`instance_id`),
  KEY `idx_last_message_at` (`last_message_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `message_id` varchar(100) DEFAULT NULL,
  `remote_message_id` varchar(100) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `media_type` varchar(50) DEFAULT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `media_caption` text DEFAULT NULL,
  `from_me` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `ts` datetime NOT NULL,
  `ack` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_message` (`chat_id`,`message_id`),
  KEY `idx_chat_id` (`chat_id`),
  KEY `idx_instance_id` (`instance_id`),
  KEY `idx_ts` (`ts`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `jid` varchar(255) NOT NULL,
  `is_contact` tinyint(1) NOT NULL DEFAULT 0,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `profile_pic_url` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_instance_contact` (`instance_id`,`jid`),
  KEY `idx_instance_id` (`instance_id`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contact_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_instance_id` (`instance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contact_list_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `list_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_list_contact` (`list_id`,`contact_id`),
  KEY `idx_contact_id` (`contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `message_template` text NOT NULL,
  `schedule_type` varchar(20) NOT NULL DEFAULT 'once',
  `schedule_date` date DEFAULT NULL,
  `schedule_time` time DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_instance_id` (`instance_id`),
  KEY `idx_status` (`status`),
  KEY `idx_schedule` (`schedule_date`,`schedule_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `campaign_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `message_type` varchar(50) NOT NULL DEFAULT 'text',
  `content` text NOT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `order_index` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_campaign_id` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `campaign_targets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `target_type` varchar(20) NOT NULL DEFAULT 'contact',
  `target_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_campaign_id` (`campaign_id`),
  KEY `idx_target` (`target_type`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `campaign_runs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `scheduled_at` datetime NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_campaign_id` (`campaign_id`),
  KEY `idx_status` (`status`),
  KEY `idx_scheduled_at` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `campaign_run_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `run_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `message_id` int(11) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_run_id` (`run_id`),
  KEY `idx_contact_id` (`contact_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contact_candidates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_instance_id` (`instance_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `webhook_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `event_data` json DEFAULT NULL,
  `processed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_instance_id` (`instance_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_processed` (`processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chat_reads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_read_message_id` int(11) DEFAULT NULL,
  `last_read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_chat_user` (`chat_id`,`user_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear usuario admin
INSERT INTO `users` (name, email, password_hash, is_active) 
VALUES ('Administrador', 'admin@evoapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Crear rol admin
INSERT INTO `roles` (name, description) VALUES ('admin', 'Administrador del sistema');

-- Asignar rol admin al usuario
INSERT INTO `user_roles` (user_id, role_id) 
SELECT u.id, r.id FROM `users` u, `roles` r 
WHERE u.email = 'admin@evoapp.com' AND r.name = 'admin';

-- Asignar todas las instancias al usuario admin
INSERT INTO `user_instances` (user_id, instance_id, can_view, can_send, is_active)
SELECT u.id, i.id, 1, 1, 1 
FROM `users` u, `evo_instances` i 
WHERE u.email = 'admin@evoapp.com';

-- Crear todas las FKs
ALTER TABLE `instance_profiles` ADD CONSTRAINT `fk_instance_profiles_instance_id` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;
ALTER TABLE `user_instances` ADD CONSTRAINT `fk_user_instances_instance_id` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;
ALTER TABLE `chats` ADD CONSTRAINT `fk_chats_instance_id` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;
ALTER TABLE `messages` ADD CONSTRAINT `fk_messages_instance_id` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;
ALTER TABLE `messages` ADD CONSTRAINT `fk_messages_chat_id` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE;
ALTER TABLE `contacts` ADD CONSTRAINT `fk_contacts_instance_id` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;
ALTER TABLE `contact_lists` ADD CONSTRAINT `fk_contact_lists_instance_id` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;
ALTER TABLE `contact_list_items` ADD CONSTRAINT `fk_contact_list_items_list_id` FOREIGN KEY (`list_id`) REFERENCES `contact_lists` (`id`) ON DELETE CASCADE;
ALTER TABLE `contact_list_items` ADD CONSTRAINT `fk_contact_list_items_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE;
ALTER TABLE `campaigns` ADD CONSTRAINT `fk_campaigns_instance_id` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;
ALTER TABLE `campaign_messages` ADD CONSTRAINT `fk_campaign_messages_campaign_id` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;
ALTER TABLE `campaign_targets` ADD CONSTRAINT `fk_campaign_targets_campaign_id` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;
ALTER TABLE `campaign_runs` ADD CONSTRAINT `fk_campaign_runs_campaign_id` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;
ALTER TABLE `campaign_run_items` ADD CONSTRAINT `fk_campaign_run_items_run_id` FOREIGN KEY (`run_id`) REFERENCES `campaign_runs` (`id`) ON DELETE CASCADE;
ALTER TABLE `campaign_run_items` ADD CONSTRAINT `fk_campaign_run_items_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE;
ALTER TABLE `contact_candidates` ADD CONSTRAINT `fk_contact_candidates_instance_id` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;
ALTER TABLE `webhook_events` ADD CONSTRAINT `fk_webhook_events_instance_id` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;
ALTER TABLE `chat_reads` ADD CONSTRAINT `fk_chat_reads_chat_id` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE;

SELECT 'Setup completo - Todo listo para usar' as resultado;
SELECT 'Email: admin@evoapp.com' as login;
SELECT 'Password: admin123' as password;
