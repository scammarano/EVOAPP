-- PASO 2: Forzar eliminación de tabla instances (versión limpia)
SET FOREIGN_KEY_CHECKS = 0;

-- Primero eliminar la tabla instances directamente
DROP TABLE IF EXISTS instances;

-- Si hay error, intentar eliminar tablas que podrían tener FKs a instances
DROP TABLE IF EXISTS user_instances;
DROP TABLE IF EXISTS chats;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS contacts;
DROP TABLE IF EXISTS contact_lists;
DROP TABLE IF EXISTS campaigns;
DROP TABLE IF EXISTS contact_candidates;
DROP TABLE IF EXISTS webhook_events;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Tabla instances eliminada correctamente' as resultado;
