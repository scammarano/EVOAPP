-- Complete Database Reset and Recreate Script
-- This script will drop all existing tables and recreate them with the correct structure

SET FOREIGN_KEY_CHECKS = 0;

-- Drop all existing tables
DROP TABLE IF EXISTS `campaign_run_items`;
DROP TABLE IF EXISTS `campaign_runs`;
DROP TABLE IF EXISTS `campaign_targets`;
DROP TABLE IF EXISTS `campaign_messages`;
DROP TABLE IF EXISTS `campaigns`;
DROP TABLE IF EXISTS `contact_list_items`;
DROP TABLE IF EXISTS `contact_lists`;
DROP TABLE IF EXISTS `contacts`;
DROP TABLE IF EXISTS `contact_candidates`;
DROP TABLE IF EXISTS `cron_log`;
DROP TABLE IF EXISTS `audit_log`;
DROP TABLE IF EXISTS `chat_reads`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `chats`;
DROP TABLE IF EXISTS `webhook_events`;
DROP TABLE IF EXISTS `user_instances`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `user_roles`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `instance_profiles`;
DROP TABLE IF EXISTS `evo_instances`;

-- Recreate all tables with correct structure
-- (Copy and paste the content of evoapp_schema.sql here)
-- Then add the instance_profiles table

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

SET FOREIGN_KEY_CHECKS = 1;
