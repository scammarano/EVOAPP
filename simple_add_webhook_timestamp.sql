-- Agregar columna webhook_timestamp a tabla evo_instances (versión simple)
-- Ejecutar este SQL directamente

-- Paso 1: Agregar columna
ALTER TABLE evo_instances 
ADD COLUMN webhook_timestamp TIMESTAMP NULL DEFAULT NULL 
AFTER updated_at;

-- Paso 2: Crear índice
CREATE INDEX idx_webhook_timestamp ON evo_instances(webhook_timestamp);

-- Paso 3: Verificar
DESCRIBE evo_instances;
