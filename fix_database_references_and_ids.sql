-- =====================================================================
-- Fix Database References and IDs - Comprehensive Solution
-- This script fixes ID duplication issues and ensures proper references
-- =====================================================================

USE research_apps_db;

-- =====================================================================
-- 1. FIX MISSING FOREIGN KEY CONSTRAINTS
-- =====================================================================

-- Add missing foreign key constraints to projects table
-- (These may have been missed in the original schema)
ALTER TABLE projects 
DROP FOREIGN KEY IF EXISTS fk_projects_status,
DROP FOREIGN KEY IF EXISTS fk_projects_lead_mentor,
DROP FOREIGN KEY IF EXISTS fk_projects_subject,
DROP FOREIGN KEY IF EXISTS fk_projects_rbm;

ALTER TABLE projects 
ADD CONSTRAINT fk_projects_status 
    FOREIGN KEY (status_id) REFERENCES project_statuses(id) ON DELETE RESTRICT ON UPDATE CASCADE,
ADD CONSTRAINT fk_projects_lead_mentor 
    FOREIGN KEY (lead_mentor_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT fk_projects_subject 
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT fk_projects_rbm 
    FOREIGN KEY (rbm_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- =====================================================================
-- 2. FIX SAMPLE DATA INSERTS WITH PROPER ID HANDLING
-- =====================================================================

-- Create procedure to safely insert sample data without ID conflicts
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS SafeInsertSampleData()
BEGIN
    -- Insert organizations if they don't exist
    INSERT IGNORE INTO organizations (name) VALUES 
    ('Tech Corp'), 
    ('Innovation Hub'), 
    ('Digital Solutions'), 
    ('Future Systems'), 
    ('Smart Technologies'),
    ('Research Institute'),
    ('Academic Excellence'),
    ('Innovation Labs');

    -- Insert departments if they don't exist
    INSERT IGNORE INTO departments (name) VALUES 
    ('Computer Science'), 
    ('Information Technology'), 
    ('Software Engineering'), 
    ('Data Science'), 
    ('Artificial Intelligence'),
    ('Cybersecurity'),
    ('Machine Learning');

    -- Insert boards if they don't exist
    INSERT IGNORE INTO boards (name) VALUES 
    ('IB'), ('IG'), ('ICSE'), ('CBSE'), ('State Board'), ('Cambridge'), ('Edexcel');

    -- Insert project statuses if they don't exist
    INSERT IGNORE INTO project_statuses (status_name, status_order) VALUES
    ('Project Execution - yet to start', 1),
    ('Project Execution - in progress', 2),
    ('Project Execution - completed', 3),
    ('Research Paper - in progress', 4),
    ('Research Paper - completed', 5),
    ('Ready for Publication', 6);

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

-- Execute the safe insert procedure
CALL SafeInsertSampleData();
DROP PROCEDURE SafeInsertSampleData;

-- =====================================================================
-- 3. IMPROVE PROJECT ID GENERATION TRIGGER
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
-- 4. CREATE STUDENT ID GENERATION TRIGGER
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
-- 5. ADD DUPLICATE PREVENTION CONSTRAINTS
-- =====================================================================

-- Ensure unique constraints exist on critical tables
ALTER TABLE project_students 
DROP INDEX IF EXISTS unique_project_student,
ADD UNIQUE INDEX unique_project_student (project_id, student_id);

ALTER TABLE project_tag_assignments 
DROP INDEX IF EXISTS unique_project_tag,
ADD UNIQUE INDEX unique_project_tag (project_id, tag_id);

ALTER TABLE project_mentors 
DROP INDEX IF EXISTS unique_project_mentor,
ADD UNIQUE INDEX unique_project_mentor (project_id, mentor_id);

ALTER TABLE ready_for_publication_students 
DROP INDEX IF EXISTS unique_publication_student,
ADD UNIQUE INDEX unique_publication_student (ready_for_publication_id, student_id);

-- =====================================================================
-- 6. CREATE SAFE ASSIGNMENT PROCEDURES
-- =====================================================================

-- Procedure to safely assign students to projects
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS SafeAssignStudentsToProject(
    IN p_project_id INT,
    IN p_student_ids TEXT
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE student_id INT;
    DECLARE student_cursor CURSOR FOR 
        SELECT CAST(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p_student_ids, ',', numbers.n), ',', -1)) AS UNSIGNED) as id
        FROM (
            SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 
            UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
        ) numbers 
        WHERE CHAR_LENGTH(p_student_ids) - CHAR_LENGTH(REPLACE(p_student_ids, ',', '')) >= numbers.n - 1
        AND TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p_student_ids, ',', numbers.n), ',', -1)) != '';
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    DECLARE CONTINUE HANDLER FOR 1062 BEGIN END; -- Ignore duplicate key errors
    
    -- Remove existing assignments for this project
    DELETE FROM project_students WHERE project_id = p_project_id;
    
    -- Add new assignments
    IF p_student_ids IS NOT NULL AND p_student_ids != '' THEN
        OPEN student_cursor;
        read_loop: LOOP
            FETCH student_cursor INTO student_id;
            IF done THEN
                LEAVE read_loop;
            END IF;
            
            -- Insert only if student exists and isn't already assigned
            INSERT IGNORE INTO project_students (project_id, student_id, assigned_date, is_active)
            SELECT p_project_id, student_id, CURDATE(), 1
            WHERE EXISTS (SELECT 1 FROM students WHERE id = student_id);
            
        END LOOP;
        CLOSE student_cursor;
    END IF;
END //
DELIMITER ;

-- =====================================================================
-- 7. ADD INDEXES FOR BETTER PERFORMANCE
-- =====================================================================

-- Add indexes for common query patterns
CREATE INDEX IF NOT EXISTS idx_projects_status_date ON projects(status_id, start_date);
CREATE INDEX IF NOT EXISTS idx_projects_mentor_rbm ON projects(lead_mentor_id, rbm_id);
CREATE INDEX IF NOT EXISTS idx_students_year_board ON students(application_year, board_id);
CREATE INDEX IF NOT EXISTS idx_students_counselor_rbm ON students(counselor_id, rbm_id);
CREATE INDEX IF NOT EXISTS idx_project_students_active ON project_students(project_id, is_active);
CREATE INDEX IF NOT EXISTS idx_ready_pub_status_workflow ON ready_for_publication(status, workflow_status);

-- =====================================================================
-- 8. CREATE DATA INTEGRITY CHECK FUNCTIONS
-- =====================================================================

-- Function to check for orphaned records
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS CheckDataIntegrity()
BEGIN
    -- Check for projects with invalid status_id
    SELECT COUNT(*) as orphaned_projects_status
    FROM projects p 
    LEFT JOIN project_statuses ps ON p.status_id = ps.id 
    WHERE p.status_id IS NOT NULL AND ps.id IS NULL;
    
    -- Check for projects with invalid mentor IDs
    SELECT COUNT(*) as orphaned_projects_mentor
    FROM projects p 
    LEFT JOIN users u ON p.lead_mentor_id = u.id 
    WHERE p.lead_mentor_id IS NOT NULL AND u.id IS NULL;
    
    -- Check for students with invalid counselor IDs
    SELECT COUNT(*) as orphaned_students_counselor
    FROM students s 
    LEFT JOIN users u ON s.counselor_id = u.id 
    WHERE s.counselor_id IS NOT NULL AND u.id IS NULL;
    
    -- Check for duplicate project IDs
    SELECT COUNT(*) as duplicate_project_ids
    FROM (
        SELECT project_id, COUNT(*) as cnt 
        FROM projects 
        GROUP BY project_id 
        HAVING COUNT(*) > 1
    ) duplicates;
    
    -- Check for duplicate student IDs
    SELECT COUNT(*) as duplicate_student_ids
    FROM (
        SELECT student_id, COUNT(*) as cnt 
        FROM students 
        GROUP BY student_id 
        HAVING COUNT(*) > 1
    ) duplicates;
END //
DELIMITER ;

-- =====================================================================
-- 9. CLEAN UP ANY EXISTING ORPHANED DATA
-- =====================================================================

-- Fix any projects with invalid status_id by setting to default
UPDATE projects p 
LEFT JOIN project_statuses ps ON p.status_id = ps.id 
SET p.status_id = (SELECT id FROM project_statuses WHERE status_name = 'Project Execution - yet to start' LIMIT 1)
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
-- 10. CREATE VIEWS FOR SAFE DATA ACCESS
-- =====================================================================

-- Create view for projects with all related data
CREATE OR REPLACE VIEW v_projects_complete AS
SELECT 
    p.*,
    ps.status_name,
    s.subject_name,
    u1.full_name as lead_mentor_name,
    u1.specialization as mentor_specialization,
    u2.full_name as rbm_name,
    u2.branch as rbm_branch,
    (SELECT COUNT(*) FROM project_students WHERE project_id = p.id AND is_active = 1) as student_count,
    (SELECT COUNT(*) FROM project_mentors WHERE project_id = p.id AND is_active = 1) as mentor_count,
    (SELECT GROUP_CONCAT(pt.tag_name) FROM project_tags pt 
     JOIN project_tag_assignments pta ON pt.id = pta.tag_id 
     WHERE pta.project_id = p.id) as tags
FROM projects p
LEFT JOIN project_statuses ps ON p.status_id = ps.id
LEFT JOIN subjects s ON p.subject_id = s.id
LEFT JOIN users u1 ON p.lead_mentor_id = u1.id
LEFT JOIN users u2 ON p.rbm_id = u2.id;

-- Create view for students with all related data
CREATE OR REPLACE VIEW v_students_complete AS
SELECT 
    s.*,
    u1.full_name as counselor_name,
    u2.full_name as rbm_name,
    u2.branch as rbm_branch,
    b.name as board_name,
    (SELECT COUNT(*) FROM project_students WHERE student_id = s.id AND is_active = 1) as project_count
FROM students s
LEFT JOIN users u1 ON s.counselor_id = u1.id
LEFT JOIN users u2 ON s.rbm_id = u2.id
LEFT JOIN boards b ON s.board_id = b.id;

-- =====================================================================
-- COMPLETION MESSAGE
-- =====================================================================

SELECT 'Database references and ID generation have been fixed successfully!' as Status;

-- Run integrity check
CALL CheckDataIntegrity(); 