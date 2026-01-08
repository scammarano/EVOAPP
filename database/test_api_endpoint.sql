-- Este es un ejemplo de cómo probar el endpoint manualmente
-- Pero necesitamos hacerlo con curl o Postman

-- API Key de SCAMMARANO (la única con datos)
SELECT 'API Key SCAMMARANO: A79547CFC2D0-47B7-BE47-226EE81AE1BB' as info;

-- API Keys de otras instancias (para comparar)
SELECT id, slug, api_key FROM evo_instances ORDER BY id;
