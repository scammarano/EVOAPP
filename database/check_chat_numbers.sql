-- Verificar formato de números en chats existentes
SELECT id, remote_jid, title, is_group, last_message_at
FROM chats 
WHERE instance_id = 1  -- SCAMMARANO
ORDER BY last_message_at DESC
LIMIT 5;

-- Verificar si hay algún patrón en los números
SELECT 
    CASE 
        WHEN remote_jid LIKE '%@g.us' THEN 'Grupo'
        WHEN remote_jid LIKE '%@c.us' THEN 'Contacto'
        WHEN remote_jid LIKE '%@s.whatsapp.net' THEN 'Business'
        ELSE 'Otro'
    END as tipo,
    COUNT(*) as cantidad
FROM chats 
WHERE instance_id = 1
GROUP BY tipo;
