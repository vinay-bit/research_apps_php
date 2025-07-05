-- Time Sheet Database Schema
-- This file contains all tables needed for the time sheet feature

USE research_apps_production;

-- Table: timesheet_activities (for categorizing time entries)
CREATE TABLE IF NOT EXISTS `timesheet_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(20) DEFAULT '#007bff',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `activity_name` (`activity_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default activities
INSERT INTO `timesheet_activities` (`activity_name`, `description`, `color`) VALUES
('Research', 'Conducting research and literature review', '#28a745'),
('Development', 'Coding and technical development', '#007bff'),
('Review', 'Code review and documentation', '#ffc107'),
('Meetings', 'Team meetings and discussions', '#6f42c1'),
('Planning', 'Project planning and architecture', '#17a2b8'),
('Testing', 'Testing and quality assurance', '#fd7e14'),
('Documentation', 'Writing documentation and reports', '#20c997'),
('Training', 'Training and mentoring students', '#e83e8c'),
('Administration', 'Administrative tasks', '#6c757d');

-- Table: timesheet_entries (main time tracking table)
CREATE TABLE IF NOT EXISTS `timesheet_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `entry_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `activity_id` int(11) NOT NULL,
  `task_description` text NOT NULL,
  `hours_worked` decimal(4,2) GENERATED ALWAYS AS (
    TIMESTAMPDIFF(MINUTE, start_time, end_time) / 60.0
  ) STORED,
  `notes` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_mentor_date` (`project_id`, `mentor_id`, `entry_date`),
  KEY `idx_mentor_date` (`mentor_id`, `entry_date`),
  KEY `idx_project_date` (`project_id`, `entry_date`),
  KEY `idx_activity` (`activity_id`),
  KEY `idx_approved` (`is_approved`),
  CONSTRAINT `fk_timesheet_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_timesheet_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_timesheet_activity` FOREIGN KEY (`activity_id`) REFERENCES `timesheet_activities` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_timesheet_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: timesheet_approvals (for approval workflow)
CREATE TABLE IF NOT EXISTS `timesheet_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_id` int(11) NOT NULL,
  `approver_id` int(11) NOT NULL,
  `action` enum('approve', 'reject') NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entry_id` (`entry_id`),
  KEY `idx_approver_id` (`approver_id`),
  CONSTRAINT `fk_approval_entry` FOREIGN KEY (`entry_id`) REFERENCES `timesheet_entries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_approval_approver` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: timesheet_settings (for system configuration)
CREATE TABLE IF NOT EXISTS `timesheet_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO `timesheet_settings` (`setting_key`, `setting_value`, `description`) VALUES
('max_hours_per_day', '10', 'Maximum hours allowed per day'),
('require_approval', '1', 'Whether time sheets require approval (1=yes, 0=no)'),
('allow_weekend_entries', '1', 'Allow time entries on weekends (1=yes, 0=no)'),
('reminder_days', '3', 'Days before reminder for missing time sheets'),
('auto_approve_after_days', '7', 'Auto-approve entries after this many days (0=never)');

-- Create indexes for better performance
CREATE INDEX `idx_timesheet_date_range` ON `timesheet_entries` (`entry_date`, `project_id`);
CREATE INDEX `idx_timesheet_mentor_month` ON `timesheet_entries` (`mentor_id`, `entry_date`);
CREATE INDEX `idx_timesheet_project_month` ON `timesheet_entries` (`project_id`, `entry_date`);
CREATE INDEX `idx_timesheet_activity_date` ON `timesheet_entries` (`activity_id`, `entry_date`); 