-- Project Management System Database Schema
-- This file contains all tables needed for the project management system

-- 1. Project Status table (for dropdown options)
CREATE TABLE IF NOT EXISTS project_statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status_name VARCHAR(100) NOT NULL UNIQUE,
    status_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default project statuses
INSERT INTO project_statuses (status_name, status_order) VALUES
('Project Execution - yet to start', 1),
('Project Execution - in progress', 2),
('Project Execution - completed', 3),
('Research Paper - in progress', 4),
('Research Paper - completed', 5)
ON DUPLICATE KEY UPDATE status_name = VALUES(status_name);

-- 2. Subjects table (for dropdown options)
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL UNIQUE,
    subject_code VARCHAR(20),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default subjects
INSERT INTO subjects (subject_name, subject_code) VALUES
('Computer Science', 'CS'),
('Mathematics', 'MATH'),
('Physics', 'PHY'),
('Chemistry', 'CHEM'),
('Biology', 'BIO'),
('Engineering', 'ENG'),
('Data Science', 'DS'),
('Artificial Intelligence', 'AI'),
('Robotics', 'ROB'),
('Environmental Science', 'ENV')
ON DUPLICATE KEY UPDATE subject_name = VALUES(subject_name);

-- 3. Tags table (for multi-select tags)
CREATE TABLE IF NOT EXISTS project_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(50) NOT NULL UNIQUE,
    tag_color VARCHAR(7) DEFAULT '#007bff',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default tags
INSERT INTO project_tags (tag_name, tag_color) VALUES
('Research', '#007bff'),
('Innovation', '#28a745'),
('Technology', '#17a2b8'),
('Science', '#ffc107'),
('Engineering', '#dc3545'),
('Data Analysis', '#6f42c1'),
('Machine Learning', '#fd7e14'),
('Prototype', '#20c997'),
('Publication', '#6c757d'),
('Experiment', '#e83e8c')
ON DUPLICATE KEY UPDATE tag_name = VALUES(tag_name);

-- 4. Main Projects table
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id VARCHAR(20) NOT NULL UNIQUE,
    project_name VARCHAR(255) NOT NULL,
    status_id INT NOT NULL,
    lead_mentor_id INT,
    subject_id INT,
    has_prototype ENUM('Yes', 'No') DEFAULT 'No',
    start_date DATE,
    end_date DATE,
    assigned_date DATE,
    completion_date DATE,
    drive_link TEXT,
    rbm_id INT,
    description TEXT,
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (status_id) REFERENCES project_statuses(id) ON DELETE RESTRICT,
    FOREIGN KEY (lead_mentor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    FOREIGN KEY (rbm_id) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes for better performance
    INDEX idx_project_id (project_id),
    INDEX idx_status (status_id),
    INDEX idx_lead_mentor (lead_mentor_id),
    INDEX idx_rbm (rbm_id),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date)
);

-- 5. Project-Student assignment table (many-to-many relationship)
CREATE TABLE IF NOT EXISTS project_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    student_id INT NOT NULL,
    assigned_date DATE DEFAULT (CURRENT_DATE),
    role VARCHAR(100) DEFAULT 'Team Member',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    
    -- Prevent duplicate assignments
    UNIQUE KEY unique_project_student (project_id, student_id),
    
    -- Indexes
    INDEX idx_project (project_id),
    INDEX idx_student (student_id)
);

-- 6. Project-Mentors assignment table (many-to-many relationship)
CREATE TABLE IF NOT EXISTS project_mentors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    mentor_id INT NOT NULL,
    assigned_date DATE DEFAULT (CURRENT_DATE),
    role VARCHAR(100) DEFAULT 'Mentor',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Prevent duplicate assignments
    UNIQUE KEY unique_project_mentor (project_id, mentor_id),
    
    -- Indexes
    INDEX idx_project (project_id),
    INDEX idx_mentor (mentor_id),
    INDEX idx_active (is_active)
);

-- 7. Project-Tags assignment table (many-to-many relationship)
CREATE TABLE IF NOT EXISTS project_tag_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES project_tags(id) ON DELETE CASCADE,
    
    -- Prevent duplicate tag assignments
    UNIQUE KEY unique_project_tag (project_id, tag_id),
    
    -- Indexes
    INDEX idx_project (project_id),
    INDEX idx_tag (tag_id)
);

-- 8. Project Activity Log (for tracking changes)
CREATE TABLE IF NOT EXISTS project_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT,
    activity_type ENUM('created', 'updated', 'status_changed', 'student_assigned', 'student_removed', 'completed') NOT NULL,
    activity_description TEXT,
    old_value TEXT,
    new_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_project (project_id),
    INDEX idx_user (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
);

-- Create trigger to auto-generate Project ID
DELIMITER //
CREATE TRIGGER IF NOT EXISTS generate_project_id 
BEFORE INSERT ON projects 
FOR EACH ROW 
BEGIN
    DECLARE next_id INT;
    DECLARE current_year INT;
    
    SET current_year = YEAR(CURDATE());
    
    -- Get the next sequence number for this year
    SELECT COALESCE(MAX(CAST(SUBSTRING(project_id, 5) AS UNSIGNED)), 0) + 1 INTO next_id
    FROM projects 
    WHERE project_id LIKE CONCAT('PRJ', current_year, '%');
    
    -- Generate project ID: PRJ2025XXXX format
    SET NEW.project_id = CONCAT('PRJ', current_year, LPAD(next_id, 4, '0'));
    
    -- Auto-generate end date (4 months from start date)
    IF NEW.start_date IS NOT NULL AND NEW.end_date IS NULL THEN
        SET NEW.end_date = DATE_ADD(NEW.start_date, INTERVAL 4 MONTH);
    END IF;
END//
DELIMITER ;

-- Create trigger to log project activities
DELIMITER //
CREATE TRIGGER IF NOT EXISTS log_project_activity 
AFTER INSERT ON projects 
FOR EACH ROW 
BEGIN
    INSERT INTO project_activity_log (project_id, activity_type, activity_description)
    VALUES (NEW.id, 'created', CONCAT('Project "', NEW.project_name, '" created'));
END//
DELIMITER ;

-- Create trigger to log project updates
DELIMITER //
CREATE TRIGGER IF NOT EXISTS log_project_updates 
AFTER UPDATE ON projects 
FOR EACH ROW 
BEGIN
    -- Log status changes
    IF OLD.status_id != NEW.status_id THEN
        INSERT INTO project_activity_log (project_id, activity_type, activity_description, old_value, new_value)
        SELECT NEW.id, 'status_changed', 
               CONCAT('Status changed from "', os.status_name, '" to "', ns.status_name, '"'),
               os.status_name, ns.status_name
        FROM project_statuses os, project_statuses ns
        WHERE os.id = OLD.status_id AND ns.id = NEW.status_id;
    END IF;
    
    -- Log completion
    IF OLD.completion_date IS NULL AND NEW.completion_date IS NOT NULL THEN
        INSERT INTO project_activity_log (project_id, activity_type, activity_description)
        VALUES (NEW.id, 'completed', CONCAT('Project completed on ', NEW.completion_date));
    END IF;
END//
DELIMITER ; 