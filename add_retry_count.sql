-- Agregar columna retry_count a tabla webhook_events
-- Ejecutar este SQL directamente

-- Paso 1: Agregar columna
ALTER TABLE webhook_events 
ADD COLUMN retry_count INT DEFAULT 0 
AFTER status;

-- Paso 2: Actualizar registros existentes
UPDATE webhook_events 
SET retry_count = 0 
WHERE retry_count IS NULL;

-- Paso 3: Verificar
DESCRIBE webhook_events;
