-- Research Apps Database - Production Schema
-- Generated on: 2025-07-02 08:06:27
-- Database Version: MySQL 5.7+

-- Create database
CREATE DATABASE IF NOT EXISTS u527896677_research_apps;
USE u527896677_research_apps;

-- Set SQL mode for compatibility
SET sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';

-- Table: organizations
CREATE TABLE IF NOT EXISTS `organizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: departments
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: boards
CREATE TABLE IF NOT EXISTS `boards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_type` enum('admin','mentor','councillor','rbm') NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `organization_name` varchar(255) DEFAULT NULL,
  `mou_signed` tinyint(1) DEFAULT 0,
  `mou_drive_link` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `branch` varchar(255) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `email_id` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `primary_contact_id` int(11) DEFAULT NULL,
  `councillor_rbm_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `department_id` (`department_id`),
  KEY `fk_users_councillor_rbm` (`councillor_rbm_id`),
  KEY `fk_users_primary_contact` (`primary_contact_id`),
  KEY `organization_id` (`organization_id`),
  CONSTRAINT `fk_users_councillor_rbm` FOREIGN KEY (`councillor_rbm_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_primary_contact` FOREIGN KEY (`primary_contact_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: user_sessions
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: students
CREATE TABLE IF NOT EXISTS `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `affiliation` varchar(255) DEFAULT NULL,
  `grade` varchar(50) DEFAULT NULL,
  `counselor_id` int(11) DEFAULT NULL,
  `rbm_id` int(11) DEFAULT NULL,
  `board_id` int(11) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `application_year` year(4) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `board_id` (`board_id`),
  KEY `counselor_id` (`counselor_id`),
  KEY `idx_students_counselor_rbm` (`counselor_id`, `rbm_id`),
  KEY `idx_students_year_board` (`application_year`, `board_id`),
  KEY `rbm_id` (`rbm_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`rbm_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_ibfk_3` FOREIGN KEY (`board_id`) REFERENCES `boards` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: project_statuses
CREATE TABLE IF NOT EXISTS `project_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `status_name` (`status_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: subjects
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject_name` (`subject_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: project_tags
CREATE TABLE IF NOT EXISTS `project_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(50) NOT NULL,
  `color` varchar(20) DEFAULT 'primary',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_name` (`tag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: projects
CREATE TABLE IF NOT EXISTS `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` varchar(20) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `status_id` int(11) DEFAULT NULL,
  `lead_mentor_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `has_prototype` enum('Yes','No') DEFAULT 'No',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `drive_link` text DEFAULT NULL,
  `rbm_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_id` (`project_id`),
  KEY `fk_projects_rbm` (`rbm_id`),
  KEY `fk_projects_subject` (`subject_id`),
  KEY `idx_projects_mentor_rbm` (`lead_mentor_id`, `rbm_id`),
  KEY `idx_projects_status_date` (`status_id`, `start_date`),
  CONSTRAINT `fk_projects_lead_mentor` FOREIGN KEY (`lead_mentor_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_projects_rbm` FOREIGN KEY (`rbm_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_projects_status` FOREIGN KEY (`status_id`) REFERENCES `project_statuses` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_projects_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: project_students
CREATE TABLE IF NOT EXISTS `project_students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT 1,
  `assigned_date` date DEFAULT (CURDATE()),
  `role` varchar(100) DEFAULT 'Team Member',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment` (`project_id`, `student_id`),
  KEY `idx_project_students_active` (`project_id`, `is_active`),
  CONSTRAINT `fk_project_students_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_students_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: project_mentors
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

-- Table: project_tag_assignments
CREATE TABLE IF NOT EXISTS `project_tag_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_tag` (`project_id`, `tag_id`),
  CONSTRAINT `fk_project_tag_assignments_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_tag_assignments_tag` FOREIGN KEY (`tag_id`) REFERENCES `project_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: ready_for_publication
CREATE TABLE IF NOT EXISTS `ready_for_publication` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `paper_title` varchar(500) NOT NULL,
  `mentor_affiliation` varchar(255) DEFAULT NULL,
  `first_draft_link` varchar(500) DEFAULT NULL,
  `plagiarism_report_link` varchar(500) DEFAULT NULL,
  `ai_detection_link` text DEFAULT NULL,
  `status` enum('pending','in_review','approved','published') NOT NULL DEFAULT 'pending',
  `workflow_status` enum('active','moved_to_publication') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_publication` (`project_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_publication_workflow_status` (`workflow_status`),
  KEY `idx_ready_publication_project_status` (`project_id`, `status`),
  KEY `idx_ready_pub_status_workflow` (`status`, `workflow_status`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_ready_publication_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: ready_for_publication_students
CREATE TABLE IF NOT EXISTS `ready_for_publication_students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ready_for_publication_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_affiliation` varchar(255) DEFAULT NULL,
  `student_address` text DEFAULT NULL,
  `author_order` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_publication_student` (`ready_for_publication_id`, `student_id`),
  KEY `idx_author_order` (`author_order`),
  KEY `idx_ready_publication_student_order` (`ready_for_publication_id`, `author_order`),
  KEY `idx_student_id` (`student_id`),
  CONSTRAINT `fk_ready_pub_students_publication` FOREIGN KEY (`ready_for_publication_id`) REFERENCES `ready_for_publication` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ready_pub_students_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: conferences
CREATE TABLE IF NOT EXISTS `conferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conference_name` varchar(500) NOT NULL,
  `conference_shortform` varchar(100) DEFAULT NULL,
  `conference_link` varchar(500) DEFAULT NULL,
  `affiliation` enum('IEEE','Springer','ACM','Elsevier','Taylor & Francis','Wiley','MDPI','Nature','Oxford Academic','Cambridge University Press','Other') NOT NULL DEFAULT 'Other',
  `conference_type` enum('National','International') NOT NULL DEFAULT 'National',
  `conference_date` date NOT NULL,
  `submission_due_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_affiliation` (`affiliation`),
  KEY `idx_conference_date` (`conference_date`),
  KEY `idx_conference_search` (`conference_name`, `conference_shortform`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_submission_due_date` (`submission_due_date`),
  KEY `idx_type` (`conference_type`),
  CONSTRAINT `fk_conference_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: journals
CREATE TABLE IF NOT EXISTS `journals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `journal_name` varchar(500) NOT NULL,
  `publisher` varchar(255) NOT NULL DEFAULT 'Other',
  `journal_link` varchar(500) DEFAULT NULL,
  `acceptance_frequency` enum('Rolling','Monthly','Quarterly','Semi-annually','Yearly') NOT NULL DEFAULT 'Rolling',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_acceptance` (`acceptance_frequency`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_journal_publisher_acceptance` (`publisher`, `acceptance_frequency`),
  KEY `idx_journal_search` (`journal_name`),
  KEY `idx_publisher` (`publisher`),
  CONSTRAINT `fk_journal_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: in_publication
CREATE TABLE IF NOT EXISTS `in_publication` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ready_publication_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `paper_title` varchar(500) NOT NULL,
  `mentor_affiliation` varchar(255) DEFAULT NULL,
  `first_draft_link` text DEFAULT NULL,
  `plagiarism_report_link` text DEFAULT NULL,
  `final_paper_link` text DEFAULT NULL,
  `ai_detection_link` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `moved_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ready_publication` (`ready_publication_id`),
  KEY `idx_project_id` (`project_id`),
  CONSTRAINT `in_publication_ibfk_1` FOREIGN KEY (`ready_publication_id`) REFERENCES `ready_for_publication` (`id`) ON DELETE CASCADE,
  CONSTRAINT `in_publication_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: in_publication_students
CREATE TABLE IF NOT EXISTS `in_publication_students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `in_publication_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_affiliation` varchar(255) DEFAULT NULL,
  `student_address` text DEFAULT NULL,
  `author_order` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_in_publication_id` (`in_publication_id`),
  KEY `idx_student_id` (`student_id`),
  CONSTRAINT `in_publication_students_ibfk_1` FOREIGN KEY (`in_publication_id`) REFERENCES `in_publication` (`id`) ON DELETE CASCADE,
  CONSTRAINT `in_publication_students_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: publication_conference_applications
CREATE TABLE IF NOT EXISTS `publication_conference_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `in_publication_id` int(11) NOT NULL,
  `conference_id` int(11) NOT NULL,
  `application_date` date NOT NULL,
  `submission_deadline` date DEFAULT NULL,
  `status` enum('applied','under_review','accepted','rejected','withdrawn') DEFAULT 'applied',
  `submission_link` text DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `reviewer_changes` text DEFAULT NULL,
  `formatted_paper_link` text DEFAULT NULL,
  `presentation_link` text DEFAULT NULL,
  `attended` tinyint(1) DEFAULT NULL,
  `certificate_received` tinyint(1) DEFAULT NULL,
  `response_date` date DEFAULT NULL,
  `acceptance_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_acceptance_date` (`acceptance_date`),
  KEY `idx_application_dates` (`application_date`),
  KEY `idx_attended` (`attended`),
  KEY `idx_certificate_received` (`certificate_received`),
  KEY `idx_conference_id` (`conference_id`),
  KEY `idx_in_publication_id` (`in_publication_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `publication_conference_applications_ibfk_1` FOREIGN KEY (`in_publication_id`) REFERENCES `in_publication` (`id`) ON DELETE CASCADE,
  CONSTRAINT `publication_conference_applications_ibfk_2` FOREIGN KEY (`conference_id`) REFERENCES `conferences` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: publication_journal_applications
CREATE TABLE IF NOT EXISTS `publication_journal_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `in_publication_id` int(11) NOT NULL,
  `journal_id` int(11) NOT NULL,
  `application_date` date NOT NULL,
  `submission_deadline` date DEFAULT NULL,
  `status` enum('applied','under_review','accepted','rejected','withdrawn') DEFAULT 'applied',
  `submission_link` text DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `response_date` date DEFAULT NULL,
  `manuscript_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_application_dates_journal` (`application_date`),
  KEY `idx_in_publication_id` (`in_publication_id`),
  KEY `idx_journal_id` (`journal_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `publication_journal_applications_ibfk_1` FOREIGN KEY (`in_publication_id`) REFERENCES `in_publication` (`id`) ON DELETE CASCADE,
  CONSTRAINT `publication_journal_applications_ibfk_2` FOREIGN KEY (`journal_id`) REFERENCES `journals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reference Data Inserts

-- Organizations
INSERT INTO organizations (name) VALUES
('Tech Corp'), ('Innovation Hub'), ('Digital Solutions'), ('Future Systems'), ('Smart Technologies')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Departments
INSERT INTO departments (name) VALUES
('Computer Science'), ('Information Technology'), ('Software Engineering'), ('Data Science'), ('Artificial Intelligence')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Boards
INSERT INTO boards (name) VALUES
('IB'), ('IG'), ('ICSE'), ('CBSE'), ('State Board')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Project Statuses
INSERT INTO project_statuses (status_name) VALUES
('Project Execution - yet to start'),
('Project Execution - in progress'),
('Project Execution - completed'),
('Research Paper - in progress'),
('Research Paper - completed')
ON DUPLICATE KEY UPDATE status_name = VALUES(status_name);

-- Subjects
INSERT INTO subjects (subject_name) VALUES
('Computer Science'), ('Mathematics'), ('Physics'),
('Chemistry'), ('Biology'), ('Engineering'),
('Data Science'), ('Artificial Intelligence'),
('Robotics'), ('Environmental Science')
ON DUPLICATE KEY UPDATE subject_name = VALUES(subject_name);

-- Project Tags
INSERT INTO project_tags (tag_name, color) VALUES
('Research', 'primary'), ('Innovation', 'success'), ('Technology', 'info'),
('Science', 'warning'), ('Engineering', 'danger'), ('Data Analysis', 'secondary'),
('Machine Learning', 'primary'), ('Prototype', 'success'),
('Publication', 'info'), ('Experiment', 'warning')
ON DUPLICATE KEY UPDATE tag_name = VALUES(tag_name);

-- Default Admin User (password: admin123)
INSERT INTO users (user_type, full_name, username, password, department_id, status) VALUES
('admin', 'System Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'active')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);

-- Triggers

-- Student ID generation trigger
DELIMITER //
DROP TRIGGER IF EXISTS generate_student_id//
CREATE TRIGGER generate_student_id
BEFORE INSERT ON students
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    DECLARE current_year INT;
    
    SET current_year = YEAR(CURDATE());
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(student_id, 5) AS UNSIGNED)), 0) + 1 INTO next_id
    FROM students
    WHERE student_id LIKE CONCAT('STU', current_year, '%');
    
    SET NEW.student_id = CONCAT('STU', current_year, LPAD(next_id, 4, '0'));
END//

-- Project ID generation trigger
DROP TRIGGER IF EXISTS generate_project_id//
CREATE TRIGGER generate_project_id
BEFORE INSERT ON projects
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    DECLARE current_year INT;
    
    SET current_year = YEAR(CURDATE());
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(project_id, 5) AS UNSIGNED)), 0) + 1 INTO next_id
    FROM projects
    WHERE project_id LIKE CONCAT('PRJ', current_year, '%');
    
    SET NEW.project_id = CONCAT('PRJ', current_year, LPAD(next_id, 4, '0'));
    
    IF NEW.start_date IS NOT NULL AND NEW.end_date IS NULL THEN
        SET NEW.end_date = DATE_ADD(NEW.start_date, INTERVAL 4 MONTH);
    END IF;
END//
DELIMITER ;
