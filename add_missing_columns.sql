-- Agregar todas las columnas faltantes a tabla evo_instances
-- Ejecutar este SQL directamente

-- Columna 1: name (nombre de la instancia)
ALTER TABLE evo_instances 
ADD COLUMN name VARCHAR(100) NULL DEFAULT NULL 
AFTER id;

-- Columna 2: slug (identificador único)
ALTER TABLE evo_instances 
ADD COLUMN slug VARCHAR(50) NULL DEFAULT NULL 
AFTER name;

-- Columna 3: base_url (URL de Evolution API)
ALTER TABLE evo_instances 
ADD COLUMN base_url VARCHAR(255) NULL DEFAULT NULL 
AFTER api_key;

-- Columna 4: webhook_url (URL para recibir webhooks)
ALTER TABLE evo_instances 
ADD COLUMN webhook_url VARCHAR(500) NULL DEFAULT NULL 
AFTER base_url;

-- Columna 5: webhook_token (token de seguridad)
ALTER TABLE evo_instances 
ADD COLUMN webhook_token VARCHAR(100) NULL DEFAULT NULL 
AFTER webhook_url;

-- Columna 6: forward_webhook_enabled (habilitar reenvío)
ALTER TABLE evo_instances 
ADD COLUMN forward_webhook_enabled TINYINT(1) DEFAULT 0 
AFTER webhook_token;

-- Columna 7: forward_webhook_url (URL para reenviar)
ALTER TABLE evo_instances 
ADD COLUMN forward_webhook_url VARCHAR(500) NULL DEFAULT NULL 
AFTER forward_webhook_enabled;

-- Columna 8: is_active (estado activo)
ALTER TABLE evo_instances 
ADD COLUMN is_active TINYINT(1) DEFAULT 1 
AFTER forward_webhook_url;

-- Columna 9: webhook_timestamp (último webhook)
ALTER TABLE evo_instances 
ADD COLUMN webhook_timestamp TIMESTAMP NULL DEFAULT NULL 
AFTER is_active;

-- Actualizar datos existentes si hay instancias sin nombre
UPDATE evo_instances 
SET name = slug, slug = LOWER(REPLACE(name, ' ', '_')) 
WHERE name IS NULL AND slug IS NOT NULL;

-- Verificar estructura
DESCRIBE evo_instances;
