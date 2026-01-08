-- Actualizar last_webhook_at para todas las instancias para pruebas
UPDATE evo_instances 
SET last_webhook_at = NOW() 
WHERE last_webhook_at IS NULL OR last_webhook_at < NOW() - INTERVAL 1 HOUR;

-- Verificar resultado
SELECT id, slug, last_webhook_at, is_active,
       CASE 
           WHEN last_webhook_at IS NULL THEN 'Nunca'
           WHEN last_webhook_at < NOW() - INTERVAL 1 HOUR THEN 'Desactualizado'
           ELSE 'Activo'
       END as status
FROM evo_instances 
ORDER BY last_webhook_at DESC;

SELECT 'Webhooks actualizados para pruebas' as resultado;
