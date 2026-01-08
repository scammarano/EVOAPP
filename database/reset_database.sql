-- Reset Database Script
-- Drop all tables and recreate with correct structure

SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables
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

-- Recreate tables with correct structure
-- (Run evoapp_schema.sql and instance_profiles.sql after this)

SET FOREIGN_KEY_CHECKS = 1;
