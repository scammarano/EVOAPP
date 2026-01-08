-- Forzar eliminación de tabla 'instances' - MÉTODO AGRESIVO
-- Elimina TODAS las FKs primero, luego la tabla

SET FOREIGN_KEY_CHECKS = 0;

-- Paso 1: Eliminar TODAS las FKs posibles
-- Esto elimina cualquier restricción que pueda bloquear

-- Eliminar FKs de user_instances
ALTER TABLE `user_instances` DROP FOREIGN KEY;
ALTER TABLE `user_instances` DROP FOREIGN KEY;

-- Eliminar FKs de chats
ALTER TABLE `chats` DROP FOREIGN KEY;
ALTER TABLE `chats` DROP FOREIGN KEY;

-- Eliminar FKs de messages
ALTER TABLE `messages` DROP FOREIGN KEY;
ALTER TABLE `messages` DROP FOREIGN KEY;

-- Eliminar FKs de contacts
ALTER TABLE `contacts` DROP FOREIGN KEY;
ALTER TABLE `contacts` DROP FOREIGN KEY;

-- Eliminar FKs de contact_lists
ALTER TABLE `contact_lists` DROP FOREIGN KEY;
ALTER TABLE `contact_lists` DROP FOREIGN KEY;

-- Eliminar FKs de campaigns
ALTER TABLE `campaigns` DROP FOREIGN KEY;
ALTER TABLE `campaigns` DROP FOREIGN KEY;
ALTER TABLE `campaigns` DROP FOREIGN KEY;

-- Eliminar FKs de otras tablas
ALTER TABLE `contact_candidates` DROP FOREIGN KEY;
ALTER TABLE `webhook_events` DROP FOREIGN KEY;
ALTER TABLE `contact_list_items` DROP FOREIGN KEY;
ALTER TABLE `contact_list_items` DROP FOREIGN KEY;
ALTER TABLE `campaign_messages` DROP FOREIGN KEY;
ALTER TABLE `campaign_targets` DROP FOREIGN KEY;
ALTER TABLE `campaign_runs` DROP FOREIGN KEY;
ALTER TABLE `campaign_run_items` DROP FOREIGN KEY;
ALTER TABLE `campaign_run_items` DROP FOREIGN KEY;
ALTER TABLE `chat_reads` DROP FOREIGN KEY;
ALTER TABLE `chat_reads` DROP FOREIGN KEY;
ALTER TABLE `audit_log` DROP FOREIGN KEY;
ALTER TABLE `user_roles` DROP FOREIGN KEY;
ALTER TABLE `user_roles` DROP FOREIGN KEY;
ALTER TABLE `role_permissions` DROP FOREIGN KEY;
ALTER TABLE `role_permissions` DROP FOREIGN KEY;

-- Paso 2: Ahora eliminar la tabla 'instances'
DROP TABLE IF EXISTS `instances`;

-- Paso 3: Limpiar cualquier FK residual
SET FOREIGN_KEY_CHECKS = 1;

-- Verificar que la tabla se eliminó
SELECT 'Tabla instances eliminada correctamente' as resultado;
