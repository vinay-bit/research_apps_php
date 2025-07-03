-- Fix for missing project_mentors table
-- Run this on your production database: u527896677_research_apps

USE u527896677_research_apps;

-- Create the project_mentors table
CREATE TABLE IF NOT EXISTS `project_mentors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `assigned_date` date DEFAULT (CURDATE()),
  `role` varchar(100) DEFAULT 'Mentor',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_mentor` (`project_id`, `mentor_id`),
  KEY `idx_project_mentors_active` (`project_id`, `is_active`),
  KEY `idx_mentor_id` (`mentor_id`),
  CONSTRAINT `fk_project_mentors_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_mentors_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index for performance
CREATE INDEX IF NOT EXISTS `idx_project_mentors_lookup` ON `project_mentors` (`project_id`, `mentor_id`, `is_active`); 