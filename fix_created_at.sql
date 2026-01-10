-- CORRECCIÓN: Agregar columna created_at a la tabla chats
-- Ejecutar este SQL en tu base de datos MySQL
-- Nota: Este script reemplaza el anterior con mejoras

-- Paso 1: Agregar columna si no existe
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'chats' 
AND COLUMN_NAME = 'created_at';

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE chats ADD COLUMN created_at TIMESTAMP NULL DEFAULT NULL AFTER last_message_at',
    'SELECT "Column created_at already exists"');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Paso 2: Crear índice si no existe
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'chats' 
AND INDEX_NAME = 'idx_created_at';

SET @sql = IF(@index_exists = 0, 
    'CREATE INDEX idx_created_at ON chats(created_at)',
    'SELECT "Index idx_created_at already exists"');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Paso 3: Actualizar registros existentes (solo si la columna se agregó)
UPDATE chats 
SET created_at = NOW() 
WHERE created_at IS NULL;

-- Paso 4: Confirmar resultados
SELECT 
    'Column created_at' as status,
    COUNT(*) as updated_rows
FROM chats 
WHERE created_at IS NOT NULL;
