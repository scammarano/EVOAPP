-- Verificar tablas necesarias para el inbox
SHOW TABLES LIKE 'chats';
SHOW TABLES LIKE 'messages';
SHOW TABLES LIKE 'chat_reads';

-- Verificar estructura de chats
DESCRIBE chats;

-- Verificar estructura de messages
DESCRIBE messages;

-- Verificar datos
SELECT COUNT(*) as total_chats FROM chats;
SELECT COUNT(*) as total_messages FROM messages;
