-- Restauración SIMPLE - Solo datos esenciales
-- Ejecutar después del reset completo

-- Restaurar evo_instances (solo columnas que sabemos que existen)
INSERT INTO `evo_instances` (id, slug, description, api_key, base_url, webhook_token, webhook_enabled, forward_webhook_url, forward_webhook_enabled, is_active) 
SELECT id, slug, description, api_key, base_url, webhook_token, webhook_enabled, forward_webhook_url, forward_webhook_enabled, is_active 
FROM `backup_evo_instances`;

-- Crear usuario admin directamente
INSERT INTO `users` (name, email, password_hash, is_active) 
VALUES ('Administrador', 'admin@evoapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Crear rol admin
INSERT INTO `roles` (name, description) VALUES ('admin', 'Administrador del sistema');

-- Asignar rol admin al usuario
INSERT INTO `user_roles` (user_id, role_id) 
SELECT u.id, r.id FROM `users` u, `roles` r 
WHERE u.email = 'admin@evoapp.com' AND r.name = 'admin';

-- Asignar todas las instancias al usuario admin
INSERT INTO `user_instances` (user_id, instance_id, can_view, can_send, is_active)
SELECT u.id, i.id, 1, 1, 1 
FROM `users` u, `evo_instances` i 
WHERE u.email = 'admin@evoapp.com';

SELECT 'Restauración simple completada' as resultado;
SELECT 'Email: admin@evoapp.com' as login;
SELECT 'Password: admin123' as password;
