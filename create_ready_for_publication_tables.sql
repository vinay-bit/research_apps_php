-- =====================================================================
-- Ready for Publication - Create Empty Tables
-- Clean script to create only the required tables without dummy data
-- =====================================================================

USE research_apps_db;

-- 1. Add "Ready for Publication" status to project_statuses table
INSERT IGNORE INTO project_statuses (status_name, status_order, is_active) 
VALUES ('Ready for Publication', 6, TRUE);

-- 2. Create main table for ready for publication entries
CREATE TABLE IF NOT EXISTS `ready_for_publication` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `paper_title` varchar(500) NOT NULL,
  `mentor_affiliation` varchar(255) DEFAULT NULL,
  `first_draft_link` varchar(500) DEFAULT NULL,
  `plagiarism_report_link` varchar(500) DEFAULT NULL,
  `status` enum('pending','in_review','approved','published') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_publication` (`project_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_ready_publication_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Create table for student details in ready for publication entries
CREATE TABLE IF NOT EXISTS `ready_for_publication_students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ready_for_publication_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_affiliation` varchar(255) DEFAULT NULL,
  `student_address` text DEFAULT NULL,
  `author_order` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_publication_student` (`ready_for_publication_id`, `student_id`),
  KEY `idx_author_order` (`author_order`),
  KEY `idx_student_id` (`student_id`),
  CONSTRAINT `fk_ready_pub_students_publication` FOREIGN KEY (`ready_for_publication_id`) REFERENCES `ready_for_publication` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ready_pub_students_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Create performance indexes (with error handling)
-- Drop indexes if they exist, then recreate them
DROP INDEX IF EXISTS `idx_ready_publication_project_status` ON `ready_for_publication`;
DROP INDEX IF EXISTS `idx_ready_publication_student_order` ON `ready_for_publication_students`;

CREATE INDEX `idx_ready_publication_project_status` ON `ready_for_publication` (`project_id`, `status`);
CREATE INDEX `idx_ready_publication_student_order` ON `ready_for_publication_students` (`ready_for_publication_id`, `author_order`); 