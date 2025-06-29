-- =====================================================================
-- Publication Workflow Database Schema
-- Creates tables for in-publication management and venue applications
-- =====================================================================

USE research_apps_db;

-- Table for publications that are approved and ready for submission
CREATE TABLE IF NOT EXISTS `in_publication` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ready_publication_id` int(11) NOT NULL,
    `project_id` int(11) NOT NULL,
    `paper_title` varchar(500) NOT NULL,
    `mentor_affiliation` varchar(255) DEFAULT NULL,
    `first_draft_link` text,
    `plagiarism_report_link` text,
    `final_paper_link` text,
    `ai_detection_link` text,
    `notes` text,
    `moved_date` timestamp DEFAULT CURRENT_TIMESTAMP,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_ready_publication` (`ready_publication_id`),
    KEY `idx_project_id` (`project_id`),
    FOREIGN KEY (`ready_publication_id`) REFERENCES `ready_for_publication` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for tracking applications to conferences
CREATE TABLE IF NOT EXISTS `publication_conference_applications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `in_publication_id` int(11) NOT NULL,
    `conference_id` int(11) NOT NULL,
    `application_date` date NOT NULL,
    `submission_deadline` date DEFAULT NULL,
    `status` enum('applied', 'under_review', 'accepted', 'rejected', 'withdrawn') DEFAULT 'applied',
    `submission_link` text,
    `feedback` text,
    `response_date` date DEFAULT NULL,
    `notes` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_in_publication_id` (`in_publication_id`),
    KEY `idx_conference_id` (`conference_id`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`in_publication_id`) REFERENCES `in_publication` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`conference_id`) REFERENCES `conferences` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for tracking applications to journals
CREATE TABLE IF NOT EXISTS `publication_journal_applications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `in_publication_id` int(11) NOT NULL,
    `journal_id` int(11) NOT NULL,
    `application_date` date NOT NULL,
    `submission_deadline` date DEFAULT NULL,
    `status` enum('applied', 'under_review', 'accepted', 'rejected', 'withdrawn') DEFAULT 'applied',
    `submission_link` text,
    `feedback` text,
    `response_date` date DEFAULT NULL,
    `manuscript_id` varchar(100) DEFAULT NULL,
    `notes` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_in_publication_id` (`in_publication_id`),
    KEY `idx_journal_id` (`journal_id`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`in_publication_id`) REFERENCES `in_publication` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`journal_id`) REFERENCES `journals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for linking students to in_publication entries (copied from ready_for_publication_students)
CREATE TABLE IF NOT EXISTS `in_publication_students` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `in_publication_id` int(11) NOT NULL,
    `student_id` int(11) NOT NULL,
    `student_affiliation` varchar(255) DEFAULT NULL,
    `student_address` text,
    `author_order` int(11) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_in_publication_id` (`in_publication_id`),
    KEY `idx_student_id` (`student_id`),
    FOREIGN KEY (`in_publication_id`) REFERENCES `in_publication` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add a status field to ready_for_publication to track if it's moved to in_publication
ALTER TABLE `ready_for_publication` 
ADD COLUMN `workflow_status` enum('active', 'moved_to_publication') DEFAULT 'active' AFTER `status`;

-- Add AI detection link field to ready_for_publication
ALTER TABLE `ready_for_publication` 
ADD COLUMN `ai_detection_link` text AFTER `plagiarism_report_link`;

-- DO NOT ENABLE - This would automatically migrate approved publications
-- INSERT INTO `in_publication` 
-- (`ready_publication_id`, `project_id`, `paper_title`, `mentor_affiliation`, `first_draft_link`, `plagiarism_report_link`, `final_paper_link`, `ai_detection_link`, `notes`)
-- SELECT 
--     rfp.id as ready_publication_id,
--     rfp.project_id,
--     rfp.paper_title,
--     rfp.mentor_affiliation,
--     rfp.first_draft_link,
--     rfp.plagiarism_report_link,
--     NULL as final_paper_link,
--     NULL as ai_detection_link,
--     'Sample data - automatically migrated' as notes
-- FROM `ready_for_publication` rfp 
-- WHERE rfp.status = 'approved' 
-- AND rfp.first_draft_link IS NOT NULL 
-- AND rfp.first_draft_link != ''
-- LIMIT 0; -- This prevents automatic migration

-- Create indexes for better performance
CREATE INDEX `idx_publication_workflow_status` ON `ready_for_publication` (`workflow_status`);
CREATE INDEX `idx_application_dates` ON `publication_conference_applications` (`application_date`);
CREATE INDEX `idx_application_dates_journal` ON `publication_journal_applications` (`application_date`);

SHOW TABLES LIKE '%publication%'; 