-- Additional tables for instance profiles and status management
-- Evolution API v2.3.7 compatible

-- Table for instance profiles
CREATE TABLE IF NOT EXISTS `instance_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `profile_image_url` varchar(500) DEFAULT NULL,
  `profile_status_text` text DEFAULT NULL,
  `profile_status_type` enum('text','image') DEFAULT 'text',
  `profile_status_expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_instance_profile` (`instance_id`),
  FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE
);

-- Table for scheduled status updates
CREATE TABLE IF NOT EXISTS `instance_status_scheduled` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `content_type` enum('text','image') NOT NULL,
  `content` text NOT NULL,
  `caption` text DEFAULT NULL,
  `scheduled_at` datetime NOT NULL,
  `status` enum('scheduled','sent','failed') NOT NULL DEFAULT 'scheduled',
  `sent_at` datetime DEFAULT NULL,
  `error_text` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_instance_scheduled` (`instance_id`, `scheduled_at`),
  FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE
);
