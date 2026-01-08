-- Fix Mínimo para BD con datos existentes
-- SOLO elimina tabla incorrecta y agrega FKs faltantes

SET FOREIGN_KEY_CHECKS = 0;

-- Paso 1: Eliminar tabla incorrecta 'instances'
DROP TABLE IF EXISTS `instances`;

-- Paso 2: Crear tablas de sistema que faltan
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_instances` (
  `user_id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 1,
  `can_send` tinyint(1) NOT NULL DEFAULT 1,
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

-- Paso 3: Agregar FKs faltantes a tablas existentes
-- (Solo si no existen ya)

ALTER TABLE `chats` 
ADD CONSTRAINT IF NOT EXISTS `fk_chats_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

ALTER TABLE `messages` 
ADD CONSTRAINT IF NOT EXISTS `fk_messages_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

ALTER TABLE `messages` 
ADD CONSTRAINT IF NOT EXISTS `fk_messages_chat_id` 
FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE;

ALTER TABLE `contacts` 
ADD CONSTRAINT IF NOT EXISTS `fk_contacts_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

ALTER TABLE `contact_lists` 
ADD CONSTRAINT IF NOT EXISTS `fk_contact_lists_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

ALTER TABLE `contact_list_items` 
ADD CONSTRAINT IF NOT EXISTS `fk_contact_list_items_list_id` 
FOREIGN KEY (`list_id`) REFERENCES `contact_lists` (`id`) ON DELETE CASCADE;

ALTER TABLE `contact_list_items` 
ADD CONSTRAINT IF NOT EXISTS `fk_contact_list_items_contact_id` 
FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE;

ALTER TABLE `campaigns` 
ADD CONSTRAINT IF NOT EXISTS `fk_campaigns_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

ALTER TABLE `campaign_messages` 
ADD CONSTRAINT IF NOT EXISTS `fk_campaign_messages_campaign_id` 
FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;

ALTER TABLE `campaign_targets` 
ADD CONSTRAINT IF NOT EXISTS `fk_campaign_targets_campaign_id` 
FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;

ALTER TABLE `campaign_runs` 
ADD CONSTRAINT IF NOT EXISTS `fk_campaign_runs_campaign_id` 
FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;

ALTER TABLE `campaign_run_items` 
ADD CONSTRAINT IF NOT EXISTS `fk_campaign_run_items_run_id` 
FOREIGN KEY (`run_id`) REFERENCES `campaign_runs` (`id`) ON DELETE CASCADE;

ALTER TABLE `campaign_run_items` 
ADD CONSTRAINT IF NOT EXISTS `fk_campaign_run_items_contact_id` 
FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE;

ALTER TABLE `contact_candidates` 
ADD CONSTRAINT IF NOT EXISTS `fk_contact_candidates_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

ALTER TABLE `webhook_events` 
ADD CONSTRAINT IF NOT EXISTS `fk_webhook_events_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

ALTER TABLE `chat_reads` 
ADD CONSTRAINT IF NOT EXISTS `fk_chat_reads_chat_id` 
FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_instances` 
ADD CONSTRAINT IF NOT EXISTS `fk_user_instances_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

ALTER TABLE `instance_profiles` 
ADD CONSTRAINT IF NOT EXISTS `fk_instance_profiles_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Base de datos corregida correctamente' as resultado;
