-- Fix Instances Tables Script
-- Eliminar tabla 'instances' incorrecta y sus relaciones
-- Crear tablas correctas relacionadas con 'evo_instances'

SET FOREIGN_KEY_CHECKS = 0;

-- Eliminar tablas en orden correcto (hijas primero, padres después)
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

-- Volver a crear las tablas correctas relacionadas con 'evo_instances'
-- (asumiendo que 'evo_instances' ya existe)

-- --------------------------------------------------------
-- Table structure for instance_profiles
-- --------------------------------------------------------
CREATE TABLE `instance_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `profile_image_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance_id` (`instance_id`),
  FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for chats
-- --------------------------------------------------------
CREATE TABLE `chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `remote_jid` varchar(255) NOT NULL,
  `is_group` tinyint(1) NOT NULL DEFAULT 0,
  `title` varchar(255) DEFAULT NULL,
  `last_snippet` text DEFAULT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `unread_count` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_instance_chat` (`instance_id`, `remote_jid`),
  FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for messages
-- --------------------------------------------------------
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `chat_id` int(11) NOT NULL,
  `message_id` varchar(255) NOT NULL,
  `from_me` tinyint(1) NOT NULL DEFAULT 0,
  `ts` datetime NOT NULL,
  `msg_type` varchar(50) NOT NULL DEFAULT 'text',
  `body_text` text DEFAULT NULL,
  `participant_jid` varchar(255) DEFAULT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `local_path` varchar(500) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `raw_json` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_message` (`instance_id`, `message_id`),
  FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for chat_reads
-- --------------------------------------------------------
CREATE TABLE `chat_reads` (
  `user_id` int(11) NOT NULL,
  `chat_id` int(11) NOT NULL,
  `last_read_ts` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`chat_id`),
  FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for contacts
-- --------------------------------------------------------
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `phone_e164` varchar(20) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_instance_contact` (`instance_id`, `phone_e164`),
  FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for contact_lists
-- --------------------------------------------------------
CREATE TABLE `contact_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for contact_list_items
-- --------------------------------------------------------
CREATE TABLE `contact_list_items` (
  `list_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  PRIMARY KEY (`list_id`,`contact_id`),
  FOREIGN KEY (`list_id`) REFERENCES `contact_lists` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for campaigns
-- --------------------------------------------------------
CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `schedule_type` enum('once','weekly','monthly','cron') NOT NULL DEFAULT 'once',
  `start_at` datetime NOT NULL,
  `end_at` datetime DEFAULT NULL,
  `timezone` varchar(50) NOT NULL DEFAULT 'America/Bogota',
  `weekly_days` varchar(20) DEFAULT NULL,
  `monthly_day` int(2) DEFAULT NULL,
  `next_run_at` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for campaign_messages
-- --------------------------------------------------------
CREATE TABLE `campaign_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL,
  `text` text NOT NULL,
  `media_path` varchar(500) DEFAULT NULL,
  `media_type` varchar(50) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for campaign_targets
-- --------------------------------------------------------
CREATE TABLE `campaign_targets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `target_type` enum('contact','list') NOT NULL,
  `target_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for campaign_runs
-- --------------------------------------------------------
CREATE TABLE `campaign_runs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `run_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('running','completed','failed') NOT NULL DEFAULT 'running',
  `total` int(11) NOT NULL DEFAULT 0,
  `ok_count` int(11) NOT NULL DEFAULT 0,
  `fail_count` int(11) NOT NULL DEFAULT 0,
  `raw_log` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for campaign_run_items
-- --------------------------------------------------------
CREATE TABLE `campaign_run_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `run_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `response_json` json DEFAULT NULL,
  `error_text` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`run_id`) REFERENCES `campaign_runs` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for contact_candidates
-- --------------------------------------------------------
CREATE TABLE `contact_candidates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `source_type` enum('group','chat') NOT NULL,
  `source_remote_jid` varchar(255) NOT NULL,
  `phone_e164` varchar(20) NOT NULL,
  `name_guess` varchar(255) DEFAULT NULL,
  `raw_json` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('new','saved','ignored') NOT NULL DEFAULT 'new',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for webhook_events
-- --------------------------------------------------------
CREATE TABLE `webhook_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `remote_jid` varchar(255) DEFAULT NULL,
  `message_id` varchar(255) DEFAULT NULL,
  `participant_jid` varchar(255) DEFAULT NULL,
  `payload_json` json NOT NULL,
  `received_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` datetime DEFAULT NULL,
  `status` enum('pending','processed','error') NOT NULL DEFAULT 'pending',
  `error_text` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
