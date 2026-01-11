-- Agregar columna created_at a tabla chats (versión simple)
-- Ejecutar este SQL directamente

-- Paso 1: Agregar columna
ALTER TABLE chats 
ADD COLUMN created_at TIMESTAMP NULL DEFAULT NULL 
AFTER last_message_at;

-- Paso 2: Actualizar registros existentes
UPDATE chats 
SET created_at = NOW() 
WHERE created_at IS NULL;

-- Paso 3: Crear índice
CREATE INDEX idx_created_at ON chats(created_at);

-- Paso 4: Verificar
DESCRIBE chats;
