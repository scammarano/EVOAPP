-- Agregar columna webhook_timestamp a la tabla evo_instances
-- Ejecutar este SQL en tu base de datos MySQL

ALTER TABLE evo_instances 
ADD COLUMN webhook_timestamp TIMESTAMP NULL DEFAULT NULL 
AFTER updated_at;

-- Crear índice para mejor rendimiento
CREATE INDEX idx_webhook_timestamp ON evo_instances(webhook_timestamp);

-- Confirmar que la columna fue agregada
DESCRIBE evo_instances;
