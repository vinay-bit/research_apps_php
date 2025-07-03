-- Fix for Project ID Generation Trigger
-- Issue: SUBSTRING position was incorrect (5 instead of 8)
-- This caused wrong sequence number extraction from project IDs

USE research_apps_db;

-- Drop and recreate the corrected project ID generation trigger
DELIMITER //

DROP TRIGGER IF EXISTS generate_project_id//

CREATE TRIGGER generate_project_id
BEFORE INSERT ON projects
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    DECLARE current_year INT;
    
    -- Only generate project_id if not already provided
    IF NEW.project_id IS NULL OR NEW.project_id = '' THEN
        SET current_year = YEAR(CURDATE());
        
        -- FIXED: Use position 8 (after PRJ + year) instead of position 5
        -- Project ID format: PRJ2025XXXX (PRJ=3chars + YEAR=4chars + SEQUENCE=4chars)
        -- Position 8 starts after "PRJ2025" to get just the sequence number
        SELECT COALESCE(MAX(CAST(SUBSTRING(project_id, 8) AS UNSIGNED)), 0) + 1 INTO next_id
        FROM projects
        WHERE project_id LIKE CONCAT('PRJ', current_year, '%');
        
        SET NEW.project_id = CONCAT('PRJ', current_year, LPAD(next_id, 4, '0'));
    END IF;
    
    -- Auto-generate end date if not provided
    IF NEW.start_date IS NOT NULL AND NEW.end_date IS NULL THEN
        SET NEW.end_date = DATE_ADD(NEW.start_date, INTERVAL 4 MONTH);
    END IF;
END//

-- Also fix the student ID generation trigger (same issue)
DROP TRIGGER IF EXISTS generate_student_id//

CREATE TRIGGER generate_student_id
BEFORE INSERT ON students
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    DECLARE current_year INT;
    
    -- Only generate student_id if not already provided
    IF NEW.student_id IS NULL OR NEW.student_id = '' THEN
        SET current_year = YEAR(CURDATE());
        
        -- FIXED: Use position 8 (after STU + year) instead of position 5
        -- Student ID format: STU2025XXXX (STU=3chars + YEAR=4chars + SEQUENCE=4chars)
        SELECT COALESCE(MAX(CAST(SUBSTRING(student_id, 8) AS UNSIGNED)), 0) + 1 INTO next_id
        FROM students
        WHERE student_id LIKE CONCAT('STU', current_year, '%');
        
        SET NEW.student_id = CONCAT('STU', current_year, LPAD(next_id, 4, '0'));
    END IF;
END//

DELIMITER ;

-- Verify the triggers were created successfully
SHOW TRIGGERS LIKE 'generate_%_id'; 