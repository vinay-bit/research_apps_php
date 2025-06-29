-- =====================================================================
-- Add Submission Due Date Field to Conferences Table
-- Adds a submission_due_date field to track paper submission deadlines
-- =====================================================================

USE research_apps_db;

-- Add submission_due_date column to conferences table
ALTER TABLE `conferences` 
ADD COLUMN `submission_due_date` date DEFAULT NULL AFTER `conference_date`;

-- Create index for better performance
CREATE INDEX `idx_submission_due_date` ON `conferences` (`submission_due_date`);

-- Show the updated table structure
DESCRIBE `conferences`;

-- Optional: Show conferences with their new submission due date field
SELECT 
    id,
    conference_name,
    conference_shortform,
    conference_date,
    submission_due_date,
    affiliation,
    conference_type
FROM conferences 
ORDER BY conference_date ASC; 