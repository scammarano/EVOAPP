-- PASO 3: Crear tablas faltantes (versión limpia)
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS instance_profiles (
  id int(11) NOT NULL AUTO_INCREMENT,
  instance_id int(11) NOT NULL,
  profile_image_url varchar(500) DEFAULT NULL,
  description text DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY instance_id (instance_id),
  FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  email varchar(255) NOT NULL,
  password_hash varchar(255) NOT NULL,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_instances (
  user_id int(11) NOT NULL,
  instance_id int(11) NOT NULL,
  can_view tinyint(1) NOT NULL DEFAULT 1,
  can_send tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id,instance_id),
  FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  FOREIGN KEY (instance_id) REFERENCES evo_instances (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
