-- PASO 1: Eliminar todas las FKs (versión limpia)
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE user_instances DROP FOREIGN KEY IF EXISTS user_instances_ibfk_1;
ALTER TABLE user_instances DROP FOREIGN KEY IF EXISTS user_instances_ibfk_2;
ALTER TABLE user_instances DROP FOREIGN KEY IF EXISTS fk_user_instances_user_id;
ALTER TABLE user_instances DROP FOREIGN KEY IF EXISTS fk_user_instances_instance_id;

ALTER TABLE chats DROP FOREIGN KEY IF EXISTS chats_ibfk_1;
ALTER TABLE chats DROP FOREIGN KEY IF EXISTS fk_chats_instance_id;

ALTER TABLE messages DROP FOREIGN KEY IF EXISTS messages_ibfk_1;
ALTER TABLE messages DROP FOREIGN KEY IF EXISTS messages_ibfk_2;
ALTER TABLE messages DROP FOREIGN KEY IF EXISTS fk_messages_instance_id;
ALTER TABLE messages DROP FOREIGN KEY IF EXISTS fk_messages_chat_id;

ALTER TABLE contacts DROP FOREIGN KEY IF EXISTS contacts_ibfk_1;
ALTER TABLE contacts DROP FOREIGN KEY IF EXISTS fk_contacts_instance_id;

ALTER TABLE contact_lists DROP FOREIGN KEY IF EXISTS contact_lists_ibfk_1;
ALTER TABLE contact_lists DROP FOREIGN KEY IF EXISTS fk_contact_lists_instance_id;

ALTER TABLE campaigns DROP FOREIGN KEY IF EXISTS campaigns_ibfk_1;
ALTER TABLE campaigns DROP FOREIGN KEY IF EXISTS fk_campaigns_instance_id;
ALTER TABLE campaigns DROP FOREIGN KEY IF EXISTS fk_campaigns_created_by;

ALTER TABLE contact_candidates DROP FOREIGN KEY IF EXISTS contact_candidates_ibfk_1;
ALTER TABLE contact_candidates DROP FOREIGN KEY IF EXISTS fk_contact_candidates_instance_id;

ALTER TABLE webhook_events DROP FOREIGN KEY IF EXISTS webhook_events_ibfk_1;
ALTER TABLE webhook_events DROP FOREIGN KEY IF EXISTS fk_webhook_events_instance_id;

SET FOREIGN_KEY_CHECKS = 1;
