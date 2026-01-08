-- PASO 4: Recrear FKs correctas (versión limpia)
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE user_instances 
ADD CONSTRAINT fk_user_instances_instance_id 
FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE;

ALTER TABLE chats 
ADD CONSTRAINT fk_chats_instance_id 
FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE;

ALTER TABLE messages 
ADD CONSTRAINT fk_messages_instance_id 
FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE;

ALTER TABLE messages 
ADD CONSTRAINT fk_messages_chat_id 
FOREIGN KEY (chat_id) REFERENCES chats (id) ON DELETE CASCADE;

ALTER TABLE contacts 
ADD CONSTRAINT fk_contacts_instance_id 
FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE;

ALTER TABLE contact_lists 
ADD CONSTRAINT fk_contact_lists_instance_id 
FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE;

ALTER TABLE campaigns 
ADD CONSTRAINT fk_campaigns_instance_id 
FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE;

ALTER TABLE contact_candidates 
ADD CONSTRAINT fk_contact_candidates_instance_id 
FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE;

ALTER TABLE webhook_events 
ADD CONSTRAINT fk_webhook_events_instance_id 
FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;
