-- Verificar webhooks y actualización de instancias
SELECT '=== evo_instances - last_webhook_at ===' as info;
SELECT id, slug, last_webhook_at, is_active, 
       CASE 
           WHEN last_webhook_at IS NULL THEN 'Nunca'
           WHEN last_webhook_at < NOW() - INTERVAL 1 HOUR THEN 'Desactualizado'
           ELSE 'Activo'
       END as status
FROM evo_instances 
ORDER BY last_webhook_at DESC;

SELECT '=== chats totales por instancia ===' as info;
SELECT i.slug, COUNT(c.id) as chat_count
FROM evo_instances i
LEFT JOIN chats c ON c.instance_id = i.id
GROUP BY i.id, i.slug
ORDER BY chat_count DESC;

SELECT '=== mensajes totales por instancia ===' as info;
SELECT i.slug, COUNT(m.id) as message_count
FROM evo_instances i
LEFT JOIN chats c ON c.instance_id = i.id
LEFT JOIN messages m ON m.chat_id = c.id
GROUP BY i.id, i.slug
ORDER BY message_count DESC;
