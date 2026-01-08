-- Actualizar todas las instancias con la URL correcta del script
UPDATE evo_instances 
SET base_url = 'https://evolutionapi-evolution-api.xs639b.easypanel.host'
WHERE base_url IS NULL OR base_url = '';

-- Verificar resultado
SELECT id, slug, 
       CASE 
           WHEN base_url IS NULL OR base_url = '' THEN 'VACÍO'
           ELSE base_url
       END as base_url_status,
       api_key
FROM evo_instances
ORDER BY id;

SELECT 'URLs actualizadas con la configuración del script de diagnóstico' as resultado;
