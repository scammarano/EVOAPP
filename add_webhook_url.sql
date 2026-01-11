-- Agregar columna webhook_url a tabla evo_instances
-- Ejecutar este SQL directamente

-- Paso 1: Agregar columna webhook_url
ALTER TABLE evo_instances 
ADD COLUMN webhook_url VARCHAR(500) NULL DEFAULT NULL 
AFTER api_key;

-- Paso 2: Agregar columna webhook_token si no existe
ALTER TABLE evo_instances 
ADD COLUMN webhook_token VARCHAR(100) NULL DEFAULT NULL 
AFTER webhook_url;

-- Paso 3: Agregar columna forward_webhook_enabled si no existe
ALTER TABLE evo_instances 
ADD COLUMN forward_webhook_enabled TINYINT(1) DEFAULT 0 
AFTER webhook_token;

-- Paso 4: Agregar columna forward_webhook_url si no existe
ALTER TABLE evo_instances 
ADD COLUMN forward_webhook_url VARCHAR(500) NULL DEFAULT NULL 
AFTER forward_webhook_enabled;

-- Verificar estructura
DESCRIBE evo_instances;
