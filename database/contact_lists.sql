-- Contact Lists Table
CREATE TABLE IF NOT EXISTS `contact_lists` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `instance_id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_instance_id` (`instance_id`),
    KEY `idx_name` (`name`),
    KEY `idx_is_active` (`is_active`),
    FOREIGN KEY (`instance_id`) REFERENCES `evo_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contact List Members Table
CREATE TABLE IF NOT EXISTS `contact_list_members` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `list_id` int(11) NOT NULL,
    `contact_id` int(11) NOT NULL,
    `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_list_contact` (`list_id`, `contact_id`),
    KEY `idx_list_id` (`list_id`),
    KEY `idx_contact_id` (`contact_id`),
    FOREIGN KEY (`list_id`) REFERENCES `contact_lists` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
