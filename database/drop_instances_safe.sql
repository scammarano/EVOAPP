-- Eliminar tabla 'instances' de forma segura
-- Primero eliminar FKs que apuntan a 'instances', luego la tabla

SET FOREIGN_KEY_CHECKS = 0;

-- Paso 1: Eliminar las FKs que apuntan a 'instances'
-- (Estos son los nombres comunes, ajustar según sea necesario)

-- Si user_instances apunta a 'instances'
ALTER TABLE `user_instances` DROP FOREIGN KEY IF EXISTS `user_instances_ibfk_2`;
ALTER TABLE `user_instances` DROP FOREIGN KEY IF EXISTS `fk_user_instances_instance_id`;

-- Si chats apunta a 'instances'
ALTER TABLE `chats` DROP FOREIGN KEY IF EXISTS `chats_ibfk_1`;
ALTER TABLE `chats` DROP FOREIGN KEY IF EXISTS `fk_chats_instance_id`;

-- Si contacts apunta a 'instances'
ALTER TABLE `contacts` DROP FOREIGN KEY IF EXISTS `contacts_ibfk_1`;
ALTER TABLE `contacts` DROP FOREIGN KEY IF EXISTS `fk_contacts_instance_id`;

-- Si contact_lists apunta a 'instances'
ALTER TABLE `contact_lists` DROP FOREIGN KEY IF EXISTS `contact_lists_ibfk_1`;
ALTER TABLE `contact_lists` DROP FOREIGN KEY IF EXISTS `fk_contact_lists_instance_id`;

-- Si campaigns apunta a 'instances'
ALTER TABLE `campaigns` DROP FOREIGN KEY IF EXISTS `campaigns_ibfk_1`;
ALTER TABLE `campaigns` DROP FOREIGN KEY IF EXISTS `fk_campaigns_instance_id`;

-- Si contact_candidates apunta a 'instances'
ALTER TABLE `contact_candidates` DROP FOREIGN KEY IF EXISTS `contact_candidates_ibfk_1`;
ALTER TABLE `contact_candidates` DROP FOREIGN KEY IF EXISTS `fk_contact_candidates_instance_id`;

-- Si webhook_events apunta a 'instances'
ALTER TABLE `webhook_events` DROP FOREIGN KEY IF EXISTS `webhook_events_ibfk_1`;
ALTER TABLE `webhook_events` DROP FOREIGN KEY IF EXISTS `fk_webhook_events_instance_id`;

-- Paso 2: Ahora eliminar la tabla 'instances'
DROP TABLE IF EXISTS `instances`;

SET FOREIGN_KEY_CHECKS = 1;
