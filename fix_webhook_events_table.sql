-- Script completo para arreglar tabla webhook_events
-- Ejecutar este SQL directamente

-- Columna 1: error_message (si no existe)
ALTER TABLE webhook_events 
ADD COLUMN error_message TEXT NULL DEFAULT NULL 
AFTER retry_count;

-- Columna 2: processed_at (si no existe)
ALTER TABLE webhook_events 
ADD COLUMN processed_at TIMESTAMP NULL DEFAULT NULL 
AFTER error_message;

-- Columna 3: created_at (si no existe)
ALTER TABLE webhook_events 
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
AFTER id;

-- Columna 4: retry_count (si no existe)
ALTER TABLE webhook_events 
ADD COLUMN retry_count INT DEFAULT 0 
AFTER status;

-- Actualizar valores iniciales
UPDATE webhook_events 
SET retry_count = 0 
WHERE retry_count IS NULL;

UPDATE webhook_events 
SET created_at = NOW() 
WHERE created_at IS NULL;

-- Verificar estructura completa
DESCRIBE webhook_events;

-- Mostrar algunos registros para verificar
SELECT * FROM webhook_events LIMIT 5;
