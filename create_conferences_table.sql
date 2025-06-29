-- =====================================================================
-- Conferences - Create Table
-- Create conferences table to manage conference information
-- =====================================================================

USE research_apps_db;

-- Create conferences table
CREATE TABLE IF NOT EXISTS `conferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conference_name` varchar(500) NOT NULL,
  `conference_shortform` varchar(100) DEFAULT NULL,
  `conference_link` varchar(500) DEFAULT NULL,
  `affiliation` enum('IEEE','Springer','ACM','Elsevier','Taylor & Francis','Wiley','MDPI','Nature','Oxford Academic','Cambridge University Press','Other') NOT NULL DEFAULT 'Other',
  `conference_type` enum('National','International') NOT NULL DEFAULT 'National',
  `conference_date` date NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_conference_date` (`conference_date`),
  KEY `idx_affiliation` (`affiliation`),
  KEY `idx_type` (`conference_type`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_conference_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create index for searching conferences
CREATE INDEX `idx_conference_search` ON `conferences` (`conference_name`, `conference_shortform`);

-- Insert some default affiliations data (optional sample data)
INSERT IGNORE INTO `conferences` 
(`conference_name`, `conference_shortform`, `conference_link`, `affiliation`, `conference_type`, `conference_date`) 
VALUES 
('International Conference on Computer Science and Engineering', 'ICCSE', 'https://iccse2024.org', 'IEEE', 'International', '2024-12-15'),
('National Conference on Artificial Intelligence', 'NCAI', 'https://ncai2024.org', 'Springer', 'National', '2024-11-20'),
('IEEE International Conference on Machine Learning', 'IEEE ICML', 'https://icml.ieee.org', 'IEEE', 'International', '2025-01-25'); 