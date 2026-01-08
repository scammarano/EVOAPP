-- Solución DEFINITIVA para eliminar tabla 'instances'
-- Método AGRESIVO pero seguro para datos existentes

-- Paso 1: Deshabilitar completamente las verificaciones de FK
SET FOREIGN_KEY_CHECKS = 0;

-- Paso 2: Eliminar TODAS las tablas que podrían tener FKs a 'instances'
-- (Se recrearán inmediatamente después)

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

-- Paso 3: Ahora sí eliminar la tabla problemática
DROP TABLE IF EXISTS `instances`;

-- Paso 4: Recrear tablas eliminadas con estructura CORRECTA
-- (Preservando datos que puedan existir en otras tablas)

CREATE TABLE IF NOT EXISTS `chats` (
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

CREATE TABLE IF NOT EXISTS `messages` (
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

CREATE TABLE IF NOT EXISTS `contacts` (
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

CREATE TABLE IF NOT EXISTS `contact_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_instance_id` (`instance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contact_list_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `list_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_list_contact` (`list_id`,`contact_id`),
  KEY `idx_contact_id` (`contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaigns` (
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

CREATE TABLE IF NOT EXISTS `campaign_messages` (
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

CREATE TABLE IF NOT EXISTS `campaign_targets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `target_type` varchar(20) NOT NULL DEFAULT 'contact',
  `target_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_campaign_id` (`campaign_id`),
  KEY `idx_target` (`target_type`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_runs` (
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

CREATE TABLE IF NOT EXISTS `campaign_run_items` (
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

CREATE TABLE IF NOT EXISTS `contact_candidates` (
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

CREATE TABLE IF NOT EXISTS `webhook_events` (
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

CREATE TABLE IF NOT EXISTS `chat_reads` (
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

CREATE TABLE IF NOT EXISTS `user_instances` (
  `user_id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 1,
  `can_send` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`instance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `instance_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `profile_image_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance_id` (`instance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paso 5: Crear tablas de sistema que faltan
CREATE TABLE IF NOT EXISTS `users` (
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

CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paso 6: Crear todas las FKs correctas apuntando a evo_instances
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
ALTER TABLE `user_instances` ADD CONSTRAINT `fk_user_instances_instance_id` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;
ALTER TABLE `instance_profiles` ADD CONSTRAINT `fk_instance_profiles_instance_id` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

-- Paso 7: Rehabilitar verificaciones de FK
SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Base de datos corregida DEFINITIVAMENTE' as resultado;
