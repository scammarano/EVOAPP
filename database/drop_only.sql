-- Solo eliminar tablas con FKs en orden correcto
SET FOREIGN_KEY_CHECKS = 0;

-- Eliminar en orden jerárquico (hijas primero)
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

SET FOREIGN_KEY_CHECKS = 1;
