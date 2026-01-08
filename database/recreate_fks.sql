-- Recrear Foreign Keys correctas apuntando a 'evo_instances'
-- Este script se ejecuta DESPUÉS de eliminar 'instances' y crear tablas faltantes

SET FOREIGN_KEY_CHECKS = 0;

-- Recrear FKs para user_instances (si la tabla existe)
ALTER TABLE `user_instances` 
ADD CONSTRAINT `fk_user_instances_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

-- Recrear FKs para chats (si la tabla existe)
ALTER TABLE `chats` 
ADD CONSTRAINT `fk_chats_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

-- Recrear FKs para messages (si la tabla existe)
ALTER TABLE `messages` 
ADD CONSTRAINT `fk_messages_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

-- Recrear FKs para contacts (si la tabla existe)
ALTER TABLE `contacts` 
ADD CONSTRAINT `fk_contacts_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

-- Recrear FKs para contact_lists (si la tabla existe)
ALTER TABLE `contact_lists` 
ADD CONSTRAINT `fk_contact_lists_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

-- Recrear FKs para campaigns (si la tabla existe)
ALTER TABLE `campaigns` 
ADD CONSTRAINT `fk_campaigns_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

-- Recrear FKs para contact_candidates (si la tabla existe)
ALTER TABLE `contact_candidates` 
ADD CONSTRAINT `fk_contact_candidates_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

-- Recrear FKs para webhook_events (si la tabla existe)
ALTER TABLE `webhook_events` 
ADD CONSTRAINT `fk_webhook_events_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;

-- Recrear FKs adicionales si es necesario
-- Para campaign_messages (hija de campaigns)
ALTER TABLE `campaign_messages` 
ADD CONSTRAINT `fk_campaign_messages_campaign_id` 
FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;

-- Para campaign_targets (hija de campaigns)
ALTER TABLE `campaign_targets` 
ADD CONSTRAINT `fk_campaign_targets_campaign_id` 
FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;

-- Para campaign_runs (hija de campaigns)
ALTER TABLE `campaign_runs` 
ADD CONSTRAINT `fk_campaign_runs_campaign_id` 
FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;

-- Para campaign_run_items (hija de campaign_runs)
ALTER TABLE `campaign_run_items` 
ADD CONSTRAINT `fk_campaign_run_items_run_id` 
FOREIGN KEY (`run_id`) REFERENCES `campaign_runs` (`id`) ON DELETE CASCADE;

ALTER TABLE `campaign_run_items` 
ADD CONSTRAINT `fk_campaign_run_items_contact_id` 
FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE;

-- Para contact_list_items
ALTER TABLE `contact_list_items` 
ADD CONSTRAINT `fk_contact_list_items_list_id` 
FOREIGN KEY (`list_id`) REFERENCES `contact_lists` (`id`) ON DELETE CASCADE;

ALTER TABLE `contact_list_items` 
ADD CONSTRAINT `fk_contact_list_items_contact_id` 
FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE;

-- Para messages (hija de chats)
ALTER TABLE `messages` 
ADD CONSTRAINT `fk_messages_chat_id` 
FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE;

-- Para chat_reads (hija de chats)
ALTER TABLE `chat_reads` 
ADD CONSTRAINT `fk_chat_reads_chat_id` 
FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE;

-- Para audit_log (hija de users)
ALTER TABLE `audit_log` 
ADD CONSTRAINT `fk_audit_log_user_id` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- Para user_roles (hija de users y roles)
ALTER TABLE `user_roles` 
ADD CONSTRAINT `fk_user_roles_user_id` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_roles` 
ADD CONSTRAINT `fk_user_roles_role_id` 
FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;
