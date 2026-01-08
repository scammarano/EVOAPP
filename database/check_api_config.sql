-- Verificar configuración API de SCAMMARANO
SELECT id, slug, api_key, base_url, webhook_enabled, webhook_token
FROM evo_instances 
WHERE slug = 'SCAMMARANO';

-- Verificar todas las instancias con base_url vacío
SELECT id, slug, 
       CASE 
           WHEN base_url IS NULL OR base_url = '' THEN 'VACÍO'
           ELSE base_url
       END as base_url_status
FROM evo_instances;
