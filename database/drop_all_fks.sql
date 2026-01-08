-- Eliminar TODAS las Foreign Keys de la base de datos
-- Esto permitirá eliminar cualquier tabla sin restricciones

SET FOREIGN_KEY_CHECKS = 0;

-- Eliminar todas las FKs (nombres generados automáticamente por MySQL)
-- Estas son las convenciones más comunes, pero podemos necesitar ajustar

-- Tabla: user_instances
ALTER TABLE `user_instances` DROP FOREIGN KEY IF EXISTS `user_instances_ibfk_1`;
ALTER TABLE `user_instances` DROP FOREIGN KEY IF EXISTS `user_instances_ibfk_2`;
ALTER TABLE `user_instances` DROP FOREIGN KEY IF EXISTS `fk_user_instances_user_id`;
ALTER TABLE `user_instances` DROP FOREIGN KEY IF EXISTS `fk_user_instances_instance_id`;

-- Tabla: chats
ALTER TABLE `chats` DROP FOREIGN KEY IF EXISTS `chats_ibfk_1`;
ALTER TABLE `chats` DROP FOREIGN KEY IF EXISTS `fk_chats_instance_id`;

-- Tabla: messages
ALTER TABLE `messages` DROP FOREIGN KEY IF EXISTS `messages_ibfk_1`;
ALTER TABLE `messages` DROP FOREIGN KEY IF EXISTS `messages_ibfk_2`;
ALTER TABLE `messages` DROP FOREIGN KEY IF EXISTS `fk_messages_instance_id`;
ALTER TABLE `messages` DROP FOREIGN KEY IF EXISTS `fk_messages_chat_id`;

-- Tabla: contacts
ALTER TABLE `contacts` DROP FOREIGN KEY IF EXISTS `contacts_ibfk_1`;
ALTER TABLE `contacts` DROP FOREIGN KEY IF EXISTS `fk_contacts_instance_id`;

-- Tabla: contact_lists
ALTER TABLE `contact_lists` DROP FOREIGN KEY IF EXISTS `contact_lists_ibfk_1`;
ALTER TABLE `contact_lists` DROP FOREIGN KEY IF EXISTS `fk_contact_lists_instance_id`;

-- Tabla: contact_list_items
ALTER TABLE `contact_list_items` DROP FOREIGN KEY IF EXISTS `contact_list_items_ibfk_1`;
ALTER TABLE `contact_list_items` DROP FOREIGN KEY IF EXISTS `contact_list_items_ibfk_2`;
ALTER TABLE `contact_list_items` DROP FOREIGN KEY IF EXISTS `fk_contact_list_items_list_id`;
ALTER TABLE `contact_list_items` DROP FOREIGN KEY IF EXISTS `fk_contact_list_items_contact_id`;

-- Tabla: campaigns
ALTER TABLE `campaigns` DROP FOREIGN KEY IF EXISTS `campaigns_ibfk_1`;
ALTER TABLE `campaigns` DROP FOREIGN KEY IF EXISTS `fk_campaigns_instance_id`;
ALTER TABLE `campaigns` DROP FOREIGN KEY IF EXISTS `fk_campaigns_created_by`;

-- Tabla: campaign_messages
ALTER TABLE `campaign_messages` DROP FOREIGN KEY IF EXISTS `campaign_messages_ibfk_1`;
ALTER TABLE `campaign_messages` DROP FOREIGN KEY IF EXISTS `fk_campaign_messages_campaign_id`;

-- Tabla: campaign_targets
ALTER TABLE `campaign_targets` DROP FOREIGN KEY IF EXISTS `campaign_targets_ibfk_1`;
ALTER TABLE `campaign_targets` DROP FOREIGN KEY IF EXISTS `fk_campaign_targets_campaign_id`;

-- Tabla: campaign_runs
ALTER TABLE `campaign_runs` DROP FOREIGN KEY IF EXISTS `campaign_runs_ibfk_1`;
ALTER TABLE `campaign_runs` DROP FOREIGN KEY IF EXISTS `fk_campaign_runs_campaign_id`;

-- Tabla: campaign_run_items
ALTER TABLE `campaign_run_items` DROP FOREIGN KEY IF EXISTS `campaign_run_items_ibfk_1`;
ALTER TABLE `campaign_run_items` DROP FOREIGN KEY IF EXISTS `campaign_run_items_ibfk_2`;
ALTER TABLE `campaign_run_items` DROP FOREIGN KEY IF EXISTS `fk_campaign_run_items_run_id`;
ALTER TABLE `campaign_run_items` DROP FOREIGN KEY IF EXISTS `fk_campaign_run_items_contact_id`;

-- Tabla: contact_candidates
ALTER TABLE `contact_candidates` DROP FOREIGN KEY IF EXISTS `contact_candidates_ibfk_1`;
ALTER TABLE `contact_candidates` DROP FOREIGN KEY IF EXISTS `fk_contact_candidates_instance_id`;

-- Tabla: chat_reads
ALTER TABLE `chat_reads` DROP FOREIGN KEY IF EXISTS `chat_reads_ibfk_1`;
ALTER TABLE `chat_reads` DROP FOREIGN KEY IF EXISTS `chat_reads_ibfk_2`;
ALTER TABLE `chat_reads` DROP FOREIGN KEY IF EXISTS `fk_chat_reads_chat_id`;
ALTER TABLE `chat_reads` DROP FOREIGN KEY IF EXISTS `fk_chat_reads_user_id`;

-- Tabla: webhook_events
ALTER TABLE `webhook_events` DROP FOREIGN KEY IF EXISTS `webhook_events_ibfk_1`;
ALTER TABLE `webhook_events` DROP FOREIGN KEY IF EXISTS `fk_webhook_events_instance_id`;

-- Tabla: audit_log
ALTER TABLE `audit_log` DROP FOREIGN KEY IF EXISTS `audit_log_ibfk_1`;
ALTER TABLE `audit_log` DROP FOREIGN KEY IF EXISTS `fk_audit_log_user_id`;

-- Tabla: user_roles
ALTER TABLE `user_roles` DROP FOREIGN KEY IF EXISTS `user_roles_ibfk_1`;
ALTER TABLE `user_roles` DROP FOREIGN KEY IF EXISTS `user_roles_ibfk_2`;
ALTER TABLE `user_roles` DROP FOREIGN KEY IF EXISTS `fk_user_roles_user_id`;
ALTER TABLE `user_roles` DROP FOREIGN KEY IF EXISTS `fk_user_roles_role_id`;

-- Tabla: role_permissions
ALTER TABLE `role_permissions` DROP FOREIGN KEY IF EXISTS `role_permissions_ibfk_1`;
ALTER TABLE `role_permissions` DROP FOREIGN KEY IF EXISTS `role_permissions_ibfk_2`;
ALTER TABLE `role_permissions` DROP FOREIGN KEY IF EXISTS `fk_role_permissions_role_id`;
ALTER TABLE `role_permissions` DROP FOREIGN KEY IF EXISTS `fk_role_permissions_permission_id`;

SET FOREIGN_KEY_CHECKS = 1;
