-- Crear usuario administrador para acceder al sistema
-- Ejecutar después del reset completo

-- Insertar usuario admin (password: admin123)
INSERT INTO `users` (name, email, password_hash, is_active) 
VALUES ('Administrador', 'admin@evoapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Crear rol admin
INSERT INTO `roles` (name, description) VALUES ('admin', 'Administrador del sistema');

-- Crear permisos básicos
INSERT INTO `permissions` (name, description) VALUES 
('view_dashboard', 'Ver dashboard'),
('view_instances', 'Ver instancias'),
('manage_instances', 'Gestionar instancias'),
('view_inbox', 'Ver inbox'),
('send_messages', 'Enviar mensajes'),
('view_campaigns', 'Ver campañas'),
('manage_campaigns', 'Gestionar campañas'),
('view_contacts', 'Ver contactos'),
('manage_contacts', 'Gestionar contactos');

-- Asignar todos los permisos al rol admin
INSERT INTO `role_permissions` (role_id, permission_id) 
SELECT r.id, p.id FROM `roles` r, `permissions` p WHERE r.name = 'admin';

-- Asignar rol admin al usuario admin
INSERT INTO `user_roles` (user_id, role_id) 
SELECT u.id, r.id FROM `users` u, `roles` r 
WHERE u.email = 'admin@evoapp.com' AND r.name = 'admin';

-- Asignar todas las instancias al usuario admin
INSERT INTO `user_instances` (user_id, instance_id, can_view, can_send, is_active)
SELECT u.id, i.id, 1, 1, 1 
FROM `users` u, `evo_instances` i 
WHERE u.email = 'admin@evoapp.com';

SELECT 'Usuario administrador creado correctamente' as resultado;
SELECT 'Email: admin@evoapp.com' as login_email;
SELECT 'Password: admin123' as login_password;
