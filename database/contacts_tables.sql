-- Estructura de tablas para el sistema de contactos EVOAPP
-- Fecha: 2026-01-10

-- Tabla principal de contactos
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL COMMENT 'Nombre del contacto',
  `phone` varchar(20) NOT NULL COMMENT 'Número de teléfono',
  `phone_e164` varchar(20) NOT NULL COMMENT 'Número en formato E164',
  `email` varchar(255) DEFAULT NULL COMMENT 'Correo electrónico',
  `company` varchar(255) DEFAULT NULL COMMENT 'Empresa',
  `address` text DEFAULT NULL COMMENT 'Dirección',
  `notes` text DEFAULT NULL COMMENT 'Notas adicionales',
  `profile_pic_url` varchar(500) DEFAULT NULL COMMENT 'URL de foto de perfil',
  `source` varchar(50) DEFAULT 'manual' COMMENT 'Origen del contacto',
  `group_name` varchar(255) DEFAULT NULL COMMENT 'Nombre del grupo si viene de grupo',
  `instance_id` int(11) DEFAULT NULL COMMENT 'ID de la instancia',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Estado activo',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_phone` (`phone_e164`),
  KEY `idx_name` (`name`),
  KEY `idx_phone` (`phone`),
  KEY `idx_email` (`email`),
  KEY `idx_company` (`company`),
  KEY `idx_source` (`source`),
  KEY `idx_instance_id` (`instance_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla principal de contactos';

-- Tabla de listas de contactos
CREATE TABLE IF NOT EXISTS `contact_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Nombre de la lista',
  `description` text DEFAULT NULL COMMENT 'Descripción de la lista',
  `instance_id` int(11) NOT NULL COMMENT 'ID de la instancia',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Estado activo',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_instance_id` (`instance_id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Listas de contactos';

-- Tabla de relación entre contactos y listas
CREATE TABLE IF NOT EXISTS `contact_list_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `list_id` int(11) NOT NULL COMMENT 'ID de la lista',
  `contact_id` int(11) NOT NULL COMMENT 'ID del contacto',
  `added_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_list_contact` (`list_id`, `contact_id`),
  KEY `idx_list_id` (`list_id`),
  KEY `idx_contact_id` (`contact_id`),
  FOREIGN KEY (`list_id`) REFERENCES `contact_lists`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`contact_id`) REFERENCES `contacts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relación entre listas y contactos';

-- Tabla de etiquetas para contactos
CREATE TABLE IF NOT EXISTS `contact_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Nombre de la etiqueta',
  `color` varchar(7) DEFAULT '#007bff' COMMENT 'Color hexadecimal',
  `instance_id` int(11) NOT NULL COMMENT 'ID de la instancia',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_instance_id` (`instance_id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Etiquetas para contactos';

-- Tabla de relación entre contactos y etiquetas
CREATE TABLE IF NOT EXISTS `contact_tag_relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_id` int(11) NOT NULL COMMENT 'ID del contacto',
  `tag_id` int(11) NOT NULL COMMENT 'ID de la etiqueta',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_contact_tag` (`contact_id`, `tag_id`),
  KEY `idx_contact_id` (`contact_id`),
  KEY `idx_tag_id` (`tag_id`),
  FOREIGN KEY (`contact_id`) REFERENCES `contacts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `contact_tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relación entre contactos y etiquetas';

-- Insertar datos de ejemplo para pruebas
INSERT INTO `contacts` (
  `name`, `phone`, `phone_e164`, `email`, `company`, `source`, `created_at`
) VALUES 
(
  'Juan Pérez', 
  '5841234567', 
  '+5841234567', 
  'juan.perez@email.com', 
  'Empresa ABC', 
  'manual', 
  NOW()
),
(
  'María González', 
  '5841234568', 
  '+5841234568', 
  'maria.gonzalez@email.com', 
  'Empresa XYZ', 
  'chat_extraction', 
  NOW()
),
(
  'Carlos Rodríguez', 
  '5841234569', 
  '+5841234569', 
  NULL, 
  NULL, 
  'group_extraction', 
  NOW()
);

-- Insertar etiquetas de ejemplo
INSERT INTO `contact_tags` (`name`, `color`, `instance_id`) VALUES
('Cliente', '#28a745', 1),
('Prospecto', '#ffc107', 1),
('VIP', '#dc3545', 1),
('Proveedor', '#6f42c1', 1);

-- Insertar lista de ejemplo
INSERT INTO `contact_lists` (`name`, `description`, `instance_id`) VALUES
('Clientes Activos', 'Lista de clientes con compras recientes', 1),
('Prospectos 2026', 'Lista de prospectos para este año', 1);

-- Relacionar contactos con etiquetas
INSERT INTO `contact_tag_relations` (`contact_id`, `tag_id`) VALUES
(1, 1), -- Juan Pérez -> Cliente
(1, 3), -- Juan Pérez -> VIP
(2, 2), -- María González -> Prospecto
(3, 1); -- Carlos Rodríguez -> Cliente
