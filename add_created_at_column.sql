-- Agregar columna created_at a la tabla chats
-- Ejecutar este SQL en tu base de datos MySQL

ALTER TABLE chats 
ADD COLUMN created_at TIMESTAMP NULL DEFAULT NULL 
AFTER last_message_at;

-- Actualizar registros existentes con la fecha actual
UPDATE chats 
SET created_at = NOW() 
WHERE created_at IS NULL;

-- Crear índice para mejor rendimiento
CREATE INDEX idx_created_at ON chats(created_at);

-- Confirmar que la columna fue agregada
DESCRIBE chats;
