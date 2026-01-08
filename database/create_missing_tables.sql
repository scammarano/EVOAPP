-- Crear tablas faltantes para el inbox

CREATE TABLE IF NOT EXISTS `instance_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `profile_image_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance_id` (`instance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear FK (sin IF NOT EXISTS)
SET FOREIGN_KEY_CHECKS = 0;
ALTER TABLE `instance_profiles` 
ADD CONSTRAINT `fk_instance_profiles_instance_id` 
FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE;
SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Tabla instance_profiles creada' as resultado;
