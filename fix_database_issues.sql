-- Database Fix Script
USE research_apps_db; 

-- =====================================================================
-- Fix Database References and IDs - Comprehensive Solution
-- This script fixes ID duplication issues and ensures proper references
-- =====================================================================

-- =====================================================================
-- 1. FIX MISSING FOREIGN KEY CONSTRAINTS
-- =====================================================================

-- Add missing foreign key constraints to projects table
-- First check if constraints exist and drop them safely
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing constraints if they exist
ALTER TABLE projects DROP FOREIGN KEY IF EXISTS fk_projects_status;
ALTER TABLE projects DROP FOREIGN KEY IF EXISTS fk_projects_lead_mentor;
ALTER TABLE projects DROP FOREIGN KEY IF EXISTS fk_projects_subject;
ALTER TABLE projects DROP FOREIGN KEY IF EXISTS fk_projects_rbm;

-- Add the missing foreign key constraints
ALTER TABLE projects 
ADD CONSTRAINT fk_projects_status 
    FOREIGN KEY (status_id) REFERENCES project_statuses(id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE projects 
ADD CONSTRAINT fk_projects_lead_mentor 
    FOREIGN KEY (lead_mentor_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE projects 
ADD CONSTRAINT fk_projects_subject 
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE projects 
ADD CONSTRAINT fk_projects_rbm 
    FOREIGN KEY (rbm_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 2. IMPROVE PROJECT ID GENERATION TRIGGER
-- =====================================================================

-- Drop existing trigger and recreate with better logic
DROP TRIGGER IF EXISTS generate_project_id;

DELIMITER //
CREATE TRIGGER generate_project_id 
BEFORE INSERT ON projects 
FOR EACH ROW 
BEGIN
    DECLARE next_id INT;
    DECLARE current_year INT;
    DECLARE project_id_exists INT DEFAULT 1;
    DECLARE attempt_count INT DEFAULT 0;
    DECLARE max_attempts INT DEFAULT 100;
    
    -- Only generate if project_id is not provided or empty
    IF NEW.project_id IS NULL OR NEW.project_id = '' THEN
        SET current_year = YEAR(CURDATE());
        
        -- Keep trying until we get a unique ID
        WHILE project_id_exists > 0 AND attempt_count < max_attempts DO
            -- Get the next sequence number for this year
            SELECT COALESCE(MAX(CAST(SUBSTRING(project_id, 8) AS UNSIGNED)), 0) + 1 INTO next_id
            FROM projects 
            WHERE project_id LIKE CONCAT('PRJ', current_year, '%');
            
            -- Generate project ID: PRJ2025XXXX format
            SET NEW.project_id = CONCAT('PRJ', current_year, LPAD(next_id, 4, '0'));
            
            -- Check if this ID already exists
            SELECT COUNT(*) INTO project_id_exists 
            FROM projects 
            WHERE project_id = NEW.project_id;
            
            SET attempt_count = attempt_count + 1;
        END WHILE;
        
        -- If we couldn't generate a unique ID, throw an error
        IF project_id_exists > 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unable to generate unique project ID';
        END IF;
    END IF;
    
    -- Auto-generate end date (4 months from start date) if not provided
    IF NEW.start_date IS NOT NULL AND NEW.end_date IS NULL THEN
        SET NEW.end_date = DATE_ADD(NEW.start_date, INTERVAL 4 MONTH);
    END IF;
END//
DELIMITER ;

-- =====================================================================
-- 3. CREATE STUDENT ID GENERATION TRIGGER
-- =====================================================================

-- Create a trigger for student ID generation to prevent duplicates
DROP TRIGGER IF EXISTS generate_student_id;

DELIMITER //
CREATE TRIGGER generate_student_id 
BEFORE INSERT ON students 
FOR EACH ROW 
BEGIN
    DECLARE next_id INT;
    DECLARE current_year INT;
    DECLARE student_id_exists INT DEFAULT 1;
    DECLARE attempt_count INT DEFAULT 0;
    DECLARE max_attempts INT DEFAULT 100;
    
    -- Only generate if student_id is not provided or empty
    IF NEW.student_id IS NULL OR NEW.student_id = '' THEN
        SET current_year = YEAR(CURDATE());
        
        -- Keep trying until we get a unique ID
        WHILE student_id_exists > 0 AND attempt_count < max_attempts DO
            -- Get the next sequence number for this year
            SELECT COALESCE(MAX(CAST(SUBSTRING(student_id, 8) AS UNSIGNED)), 0) + 1 INTO next_id
            FROM students 
            WHERE student_id LIKE CONCAT('STU', current_year, '%');
            
            -- Generate student ID: STU2025XXXX format
            SET NEW.student_id = CONCAT('STU', current_year, LPAD(next_id, 4, '0'));
            
            -- Check if this ID already exists
            SELECT COUNT(*) INTO student_id_exists 
            FROM students 
            WHERE student_id = NEW.student_id;
            
            SET attempt_count = attempt_count + 1;
        END WHILE;
        
        -- If we couldn't generate a unique ID, throw an error
        IF student_id_exists > 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unable to generate unique student ID';
        END IF;
    END IF;
END//
DELIMITER ;

-- =====================================================================
-- 4. ADD DUPLICATE PREVENTION CONSTRAINTS
-- =====================================================================

-- Ensure unique constraints exist on critical tables
ALTER TABLE project_students 
DROP INDEX IF EXISTS unique_project_student;
ALTER TABLE project_students 
ADD UNIQUE INDEX unique_project_student (project_id, student_id);

ALTER TABLE project_tag_assignments 
DROP INDEX IF EXISTS unique_project_tag;
ALTER TABLE project_tag_assignments 
ADD UNIQUE INDEX unique_project_tag (project_id, tag_id);

-- Check if project_mentors table exists before adding constraint
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
                    WHERE table_schema = 'research_apps_db' AND table_name = 'project_mentors');

SET @sql = IF(@table_exists > 0,
    'ALTER TABLE project_mentors DROP INDEX IF EXISTS unique_project_mentor',
    'SELECT "project_mentors table does not exist" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@table_exists > 0,
    'ALTER TABLE project_mentors ADD UNIQUE INDEX unique_project_mentor (project_id, mentor_id)',
    'SELECT "project_mentors constraint skipped" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if ready_for_publication_students table exists
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
                    WHERE table_schema = 'research_apps_db' AND table_name = 'ready_for_publication_students');

SET @sql = IF(@table_exists > 0,
    'ALTER TABLE ready_for_publication_students DROP INDEX IF EXISTS unique_publication_student',
    'SELECT "ready_for_publication_students table does not exist" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@table_exists > 0,
    'ALTER TABLE ready_for_publication_students ADD UNIQUE INDEX unique_publication_student (ready_for_publication_id, student_id)',
    'SELECT "ready_for_publication_students constraint skipped" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================================
-- 5. ADD PERFORMANCE INDEXES
-- =====================================================================

-- Add indexes for common query patterns
CREATE INDEX IF NOT EXISTS idx_projects_status_date ON projects(status_id, start_date);
CREATE INDEX IF NOT EXISTS idx_projects_mentor_rbm ON projects(lead_mentor_id, rbm_id);
CREATE INDEX IF NOT EXISTS idx_students_year_board ON students(application_year, board_id);
CREATE INDEX IF NOT EXISTS idx_students_counselor_rbm ON students(counselor_id, rbm_id);
CREATE INDEX IF NOT EXISTS idx_project_students_active ON project_students(project_id, is_active);

-- Add index for ready_for_publication table if it exists
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
                    WHERE table_schema = 'research_apps_db' AND table_name = 'ready_for_publication');

SET @sql = IF(@table_exists > 0,
    'CREATE INDEX IF NOT EXISTS idx_ready_pub_status_workflow ON ready_for_publication(status, workflow_status)',
    'SELECT "ready_for_publication index skipped" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================================
-- 6. CLEAN UP ORPHANED DATA
-- =====================================================================

-- Fix any projects with invalid status_id by setting to default
UPDATE projects p 
LEFT JOIN project_statuses ps ON p.status_id = ps.id 
SET p.status_id = (SELECT id FROM project_statuses ORDER BY id LIMIT 1)
WHERE p.status_id IS NOT NULL AND ps.id IS NULL;

-- Fix any projects with invalid mentor IDs by setting to NULL
UPDATE projects p 
LEFT JOIN users u ON p.lead_mentor_id = u.id 
SET p.lead_mentor_id = NULL
WHERE p.lead_mentor_id IS NOT NULL AND u.id IS NULL;

-- Fix any students with invalid counselor IDs by setting to NULL
UPDATE students s 
LEFT JOIN users u ON s.counselor_id = u.id 
SET s.counselor_id = NULL
WHERE s.counselor_id IS NOT NULL AND u.id IS NULL;

-- Fix any students with invalid RBM IDs by setting to NULL
UPDATE students s 
LEFT JOIN users u ON s.rbm_id = u.id 
SET s.rbm_id = NULL
WHERE s.rbm_id IS NOT NULL AND u.id IS NULL;

-- Fix any students with invalid board IDs by setting to NULL
UPDATE students s 
LEFT JOIN boards b ON s.board_id = b.id 
SET s.board_id = NULL
WHERE s.board_id IS NOT NULL AND b.id IS NULL;

-- =====================================================================
-- 7. DATA INTEGRITY CHECK FUNCTION
-- =====================================================================

DROP PROCEDURE IF EXISTS CheckDataIntegrity;

DELIMITER //
CREATE PROCEDURE CheckDataIntegrity()
BEGIN
    -- Check for projects with invalid status_id
    SELECT 'Projects with invalid status_id:' as CheckType, COUNT(*) as Count
    FROM projects p 
    LEFT JOIN project_statuses ps ON p.status_id = ps.id 
    WHERE p.status_id IS NOT NULL AND ps.id IS NULL;
    
    -- Check for projects with invalid mentor IDs
    SELECT 'Projects with invalid mentor_id:' as CheckType, COUNT(*) as Count
    FROM projects p 
    LEFT JOIN users u ON p.lead_mentor_id = u.id 
    WHERE p.lead_mentor_id IS NOT NULL AND u.id IS NULL;
    
    -- Check for students with invalid counselor IDs
    SELECT 'Students with invalid counselor_id:' as CheckType, COUNT(*) as Count
    FROM students s 
    LEFT JOIN users u ON s.counselor_id = u.id 
    WHERE s.counselor_id IS NOT NULL AND u.id IS NULL;
    
    -- Check for duplicate project IDs
    SELECT 'Duplicate project IDs:' as CheckType, COUNT(*) as Count
    FROM (
        SELECT project_id, COUNT(*) as cnt 
        FROM projects 
        GROUP BY project_id 
        HAVING COUNT(*) > 1
    ) duplicates;
    
    -- Check for duplicate student IDs
    SELECT 'Duplicate student IDs:' as CheckType, COUNT(*) as Count
    FROM (
        SELECT student_id, COUNT(*) as cnt 
        FROM students 
        GROUP BY student_id 
        HAVING COUNT(*) > 1
    ) duplicates;
END //
DELIMITER ;

-- =====================================================================
-- 8. SAFE SAMPLE DATA INSERTION PROCEDURE
-- =====================================================================

DROP PROCEDURE IF EXISTS InsertSafeSampleData;

DELIMITER //
CREATE PROCEDURE InsertSafeSampleData()
BEGIN
    -- Insert organizations if they don't exist
    INSERT IGNORE INTO organizations (name) VALUES 
    ('Tech Corp'), 
    ('Innovation Hub'), 
    ('Digital Solutions'), 
    ('Future Systems'), 
    ('Smart Technologies'),
    ('Research Institute'),
    ('Academic Excellence');

    -- Insert departments if they don't exist
    INSERT IGNORE INTO departments (name) VALUES 
    ('Computer Science'), 
    ('Information Technology'), 
    ('Software Engineering'), 
    ('Data Science'), 
    ('Artificial Intelligence'),
    ('Cybersecurity');

    -- Insert boards if they don't exist
    INSERT IGNORE INTO boards (name) VALUES 
    ('IB'), ('IG'), ('ICSE'), ('CBSE'), ('State Board'), ('Cambridge');

    -- Insert project statuses if they don't exist (without status_order column)
    INSERT IGNORE INTO project_statuses (status_name) VALUES
    ('Project Execution - yet to start'),
    ('Project Execution - in progress'),
    ('Project Execution - completed'),
    ('Research Paper - in progress'),
    ('Research Paper - completed'),
    ('Ready for Publication');

    -- Insert subjects if they don't exist
    INSERT IGNORE INTO subjects (subject_name, subject_code) VALUES
    ('Computer Science', 'CS'),
    ('Mathematics', 'MATH'),
    ('Physics', 'PHY'),
    ('Chemistry', 'CHEM'),
    ('Biology', 'BIO'),
    ('Engineering', 'ENG'),
    ('Data Science', 'DS'),
    ('Artificial Intelligence', 'AI'),
    ('Robotics', 'ROB'),
    ('Environmental Science', 'ENV');

    -- Insert project tags if they don't exist
    INSERT IGNORE INTO project_tags (tag_name, tag_color) VALUES
    ('Research', '#007bff'),
    ('Innovation', '#28a745'),
    ('Technology', '#17a2b8'),
    ('Science', '#ffc107'),
    ('Engineering', '#dc3545'),
    ('Data Analysis', '#6f42c1'),
    ('Machine Learning', '#fd7e14'),
    ('Prototype', '#20c997'),
    ('Publication', '#6c757d'),
    ('Experiment', '#e83e8c');

    -- Create admin user if it doesn't exist (using proper department reference)
    SET @dept_id = (SELECT id FROM departments WHERE name = 'Computer Science' LIMIT 1);
    INSERT IGNORE INTO users (user_type, full_name, username, password, department_id, status) 
    VALUES ('admin', 'System Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', @dept_id, 'active');

END //
DELIMITER ;

-- Execute the safe sample data insertion
CALL InsertSafeSampleData();

-- =====================================================================
-- COMPLETION MESSAGE AND INTEGRITY CHECK
-- =====================================================================

SELECT 'Database references and ID generation have been fixed successfully!' as Status;
SELECT 'Running integrity check...' as Status;

-- Run integrity check
CALL CheckDataIntegrity(); 