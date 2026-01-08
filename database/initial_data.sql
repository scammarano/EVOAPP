-- EVOAPP Initial Data
-- Default roles, permissions, and admin user

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Insert default roles
INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Administrador con acceso completo'),
(2, 'supervisor', 'Supervisor con acceso a la mayoría de funciones'),
(3, 'agent', 'Agente con acceso básico a inbox y envíos'),
(4, 'readonly', 'Solo lectura sin permisos de escritura');

-- Insert permissions
INSERT INTO `permissions` (`id`, `key`, `description`) VALUES
(1, 'dashboard.view', 'Ver dashboard'),
(2, 'instances.manage', 'Gestionar instancias'),
(3, 'instances.view', 'Ver instancias'),
(4, 'inbox.view', 'Ver inbox'),
(5, 'inbox.send_text', 'Enviar mensajes de texto'),
(6, 'inbox.send_media', 'Enviar mensajes con archivos'),
(7, 'contacts.view', 'Ver contactos'),
(8, 'contacts.edit', 'Editar contactos'),
(9, 'contacts.import', 'Importar contactos'),
(10, 'contacts.export', 'Exportar contactos'),
(11, 'lists.manage', 'Gestionar listas de contactos'),
(12, 'campaigns.view', 'Ver campañas'),
(13, 'campaigns.edit', 'Editar campañas'),
(14, 'campaigns.execute', 'Ejecutar campañas'),
(15, 'groups.view', 'Ver grupos'),
(16, 'groups.extract', 'Extraer participantes de grupos'),
(17, 'logs.view', 'Ver logs de depuración'),
(18, 'debug.test', 'Probar funcionalidades de depuración'),
(19, 'users.manage', 'Gestionar usuarios'),
(20, 'audit.view', 'Ver log de auditoría'),

(21, 'users.view', 'Ver usuarios'),
(22, 'users.create', 'Crear usuarios'),
(23, 'users.edit', 'Editar usuarios'),
(24, 'users.toggle_active', 'Activar/Desactivar usuarios'),

(25, 'roles.view', 'Ver roles'),
(26, 'roles.create', 'Crear roles'),
(27, 'roles.edit', 'Editar roles'),
(28, 'roles.assign_permissions', 'Asignar permisos a roles');

-- Assign permissions to roles
-- Admin: all permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10),
(1, 11), (1, 12), (1, 13), (1, 14), (1, 15), (1, 16), (1, 17), (1, 18), (1, 19), (1, 20),
(1, 21), (1, 22), (1, 23), (1, 24), (1, 25), (1, 26), (1, 27), (1, 28);

-- Supervisor: most permissions except user management
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 6), (2, 7), (2, 8), (2, 9), (2, 10),
(2, 11), (2, 12), (2, 13), (2, 14), (2, 15), (2, 16), (2, 17), (2, 18);

-- Agent: basic inbox and sending permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(3, 1), (3, 3), (3, 4), (3, 5), (3, 6), (3, 7), (3, 8), (3, 10), (3, 11), (3, 12);

-- Readonly: view permissions only
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(4, 1), (4, 3), (4, 4), (4, 7), (4, 10), (4, 11), (4, 12), (4, 15), (4, 17), (4, 20);

-- Create default admin user (password: admin123)
INSERT INTO `users` (`id`, `email`, `password_hash`, `name`, `is_active`) VALUES
(1, 'admin@evoapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 1);

-- Assign admin role to default user
INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES (1, 1);

COMMIT;
