-- Backup de datos importantes ANTES del reset
-- Ejecutar ESTO PRIMERO para guardar datos críticos

-- Crear tablas de backup
CREATE TABLE IF NOT EXISTS `backup_evo_instances` LIKE `evo_instances`;
INSERT INTO `backup_evo_instances` SELECT * FROM `evo_instances`;

-- Si existen users/roles, hacer backup también
CREATE TABLE IF NOT EXISTS `backup_users` LIKE `users`;
INSERT IGNORE INTO `backup_users` SELECT * FROM `users`;

CREATE TABLE IF NOT EXISTS `backup_roles` LIKE `roles`;
INSERT IGNORE INTO `backup_roles` SELECT * FROM `roles`;

CREATE TABLE IF NOT EXISTS `backup_permissions` LIKE `permissions`;
INSERT IGNORE INTO `backup_permissions` SELECT * FROM `permissions`;

CREATE TABLE IF NOT EXISTS `backup_role_permissions` LIKE `role_permissions`;
INSERT IGNORE INTO `backup_role_permissions` SELECT * FROM `role_permissions`;

CREATE TABLE IF NOT EXISTS `backup_user_roles` LIKE `user_roles`;
INSERT IGNORE INTO `backup_user_roles` SELECT * FROM `user_roles`;

-- Verificar backup
SELECT 'Backup completado - Datos guardados en tablas backup_*' as resultado;
SELECT COUNT(*) as evo_instances_backup FROM `backup_evo_instances`;

-- Verificar usuarios si la tabla existe
SELECT COUNT(*) as users_backup FROM `backup_users` 
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'users' AND table_schema = DATABASE());

-- Verificar roles si la tabla existe  
SELECT COUNT(*) as roles_backup FROM `backup_roles`
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'roles' AND table_schema = DATABASE());
