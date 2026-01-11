-- Script para agregar todas las columnas faltantes
-- Ejecutar este SQL directamente

-- Columna 1: retry_count en webhook_events
ALTER TABLE webhook_events 
ADD COLUMN retry_count INT DEFAULT 0 
AFTER status;

-- Columna 2: processed_at en webhook_events (si no existe)
ALTER TABLE webhook_events 
ADD COLUMN processed_at TIMESTAMP NULL DEFAULT NULL 
AFTER error_message;

-- Columna 3: created_at en webhook_events (si no existe)
ALTER TABLE webhook_events 
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
AFTER id;

-- Actualizar valores iniciales
UPDATE webhook_events 
SET retry_count = 0 
WHERE retry_count IS NULL;

UPDATE webhook_events 
SET created_at = NOW() 
WHERE created_at IS NULL;

-- Verificar estructura
DESCRIBE webhook_events;
