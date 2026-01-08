-- Asignar todas las instancias al usuario admin
INSERT IGNORE INTO `user_instances` (user_id, instance_id, can_view, can_send, is_active)
SELECT u.id, i.id, 1, 1, 1 
FROM `users` u, `evo_instances` i 
WHERE u.email = 'admin@evoapp.com';

SELECT 'user_instances actualizadas' as resultado;
SELECT COUNT(*) as total FROM user_instances;
