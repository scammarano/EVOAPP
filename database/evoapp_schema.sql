-- EVOAPP Database Schema
-- Multi-instance WhatsApp management via EvolutionAPI

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Database: evoapp
-- --------------------------------------------------------

-- --------------------------------------------------------
-- Table structure for users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for roles
-- --------------------------------------------------------
CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for permissions
-- --------------------------------------------------------
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `key` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for role_permissions
-- --------------------------------------------------------
CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for user_roles
-- --------------------------------------------------------
CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for user_instances (ACL by instance)
-- --------------------------------------------------------
CREATE TABLE `user_instances` (
  `user_id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 1,
  `can_send` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for evo_instances
-- --------------------------------------------------------
CREATE TABLE `evo_instances` (
  `id` int(11) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `api_key` varchar(255) NOT NULL,
  `base_url` varchar(255) DEFAULT NULL,
  `webhook_token` varchar(255) DEFAULT NULL,
  `webhook_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `forward_webhook_url` varchar(255) DEFAULT NULL,
  `forward_webhook_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `last_webhook_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for chats
-- --------------------------------------------------------
CREATE TABLE `chats` (
  `id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `remote_jid` varchar(255) NOT NULL,
  `is_group` tinyint(1) NOT NULL DEFAULT 0,
  `title` varchar(255) DEFAULT NULL,
  `last_snippet` text DEFAULT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `unread_count` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for messages
-- --------------------------------------------------------
CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
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
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for chat_reads (per user read tracking)
-- --------------------------------------------------------
CREATE TABLE `chat_reads` (
  `user_id` int(11) NOT NULL,
  `chat_id` int(11) NOT NULL,
  `last_read_ts` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for webhook_events
-- --------------------------------------------------------
CREATE TABLE `webhook_events` (
  `id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `remote_jid` varchar(255) DEFAULT NULL,
  `message_id` varchar(255) DEFAULT NULL,
  `participant_jid` varchar(255) DEFAULT NULL,
  `payload_json` json NOT NULL,
  `received_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` datetime DEFAULT NULL,
  `status` enum('pending','processed','error') NOT NULL DEFAULT 'pending',
  `error_text` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for campaigns
-- --------------------------------------------------------
CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL,
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
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for campaign_messages
-- --------------------------------------------------------
CREATE TABLE `campaign_messages` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL,
  `text` text NOT NULL,
  `media_path` varchar(500) DEFAULT NULL,
  `media_type` varchar(50) DEFAULT NULL,
  `caption` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for campaign_targets
-- --------------------------------------------------------
CREATE TABLE `campaign_targets` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `target_type` enum('contact','list') NOT NULL,
  `target_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for campaign_runs
-- --------------------------------------------------------
CREATE TABLE `campaign_runs` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `run_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('running','completed','failed') NOT NULL DEFAULT 'running',
  `total` int(11) NOT NULL DEFAULT 0,
  `ok_count` int(11) NOT NULL DEFAULT 0,
  `fail_count` int(11) NOT NULL DEFAULT 0,
  `raw_log` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for campaign_run_items
-- --------------------------------------------------------
CREATE TABLE `campaign_run_items` (
  `id` int(11) NOT NULL,
  `run_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `response_json` json DEFAULT NULL,
  `error_text` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for contacts
-- --------------------------------------------------------
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `phone_e164` varchar(20) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for contact_lists
-- --------------------------------------------------------
CREATE TABLE `contact_lists` (
  `id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for contact_list_items
-- --------------------------------------------------------
CREATE TABLE `contact_list_items` (
  `list_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for contact_candidates
-- --------------------------------------------------------
CREATE TABLE `contact_candidates` (
  `id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `source_type` enum('group','chat') NOT NULL,
  `source_remote_jid` varchar(255) NOT NULL,
  `phone_e164` varchar(20) NOT NULL,
  `name_guess` varchar(255) DEFAULT NULL,
  `raw_json` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('new','saved','ignored') NOT NULL DEFAULT 'new'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for audit_log
-- --------------------------------------------------------
CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `before_json` json DEFAULT NULL,
  `after_json` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for cron_log
-- --------------------------------------------------------
CREATE TABLE `cron_log` (
  `id` int(11) NOT NULL,
  `job_key` varchar(100) NOT NULL,
  `started_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finished_at` datetime DEFAULT NULL,
  `ok` tinyint(1) NOT NULL DEFAULT 0,
  `summary` text DEFAULT NULL,
  `error_text` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Indexes for loaded tables
-- --------------------------------------------------------

-- Primary keys
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`);

ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`);

ALTER TABLE `user_instances`
  ADD PRIMARY KEY (`user_id`,`instance_id`);

ALTER TABLE `evo_instances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

ALTER TABLE `chats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `instance_remote` (`instance_id`,`remote_jid`);

ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `instance_message` (`instance_id`,`message_id`);

ALTER TABLE `webhook_events`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `campaign_messages`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `campaign_targets`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `campaign_runs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `campaign_run_items`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `instance_phone` (`instance_id`,`phone_e164`);

ALTER TABLE `contact_lists`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `contact_list_items`
  ADD PRIMARY KEY (`list_id`,`contact_id`);

ALTER TABLE `contact_candidates`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `cron_log`
  ADD PRIMARY KEY (`id`);

-- Auto increment for tables
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `evo_instances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `chats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `webhook_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `campaign_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `campaign_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `campaign_runs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `campaign_run_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `contact_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `contact_candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `cron_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Foreign keys
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_instances`
  ADD CONSTRAINT `user_instances_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_instances_ibfk_2` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

ALTER TABLE `chats`
  ADD CONSTRAINT `chats_ibfk_1` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE;

ALTER TABLE `chat_reads`
  ADD CONSTRAINT `chat_reads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_reads_ibfk_2` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE;

ALTER TABLE `webhook_events`
  ADD CONSTRAINT `webhook_events_ibfk_1` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

ALTER TABLE `campaigns`
  ADD CONSTRAINT `campaigns_ibfk_1` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campaigns_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `campaign_messages`
  ADD CONSTRAINT `campaign_messages_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;

ALTER TABLE `campaign_targets`
  ADD CONSTRAINT `campaign_targets_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;

ALTER TABLE `campaign_runs`
  ADD CONSTRAINT `campaign_runs_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;

ALTER TABLE `campaign_run_items`
  ADD CONSTRAINT `campaign_run_items_ibfk_1` FOREIGN KEY (`run_id`) REFERENCES `campaign_runs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campaign_run_items_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`);

ALTER TABLE `contacts`
  ADD CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

ALTER TABLE `contact_lists`
  ADD CONSTRAINT `contact_lists_ibfk_1` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

ALTER TABLE `contact_list_items`
  ADD CONSTRAINT `contact_list_items_ibfk_1` FOREIGN KEY (`list_id`) REFERENCES `contact_lists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contact_list_items_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE;

ALTER TABLE `contact_candidates`
  ADD CONSTRAINT `contact_candidates_ibfk_1` FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

-- Additional indexes for performance
CREATE INDEX `idx_chats_instance_last` ON `chats` (`instance_id`, `last_message_at` DESC);
CREATE INDEX `idx_messages_chat_ts` ON `messages` (`chat_id`, `ts` DESC);
CREATE INDEX `idx_webhook_events_status` ON `webhook_events` (`instance_id`, `status`, `received_at`);
CREATE INDEX `idx_campaigns_next_run` ON `campaigns` (`next_run_at`, `is_active`);
CREATE INDEX `idx_contacts_instance_phone` ON `contacts` (`instance_id`, `phone_e164`);

COMMIT;
