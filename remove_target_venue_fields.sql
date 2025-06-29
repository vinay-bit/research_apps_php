-- =====================================================================
-- Remove Target Conference and Journal Fields
-- This script removes conference_id and journal_id from ready_for_publication table
-- =====================================================================

USE research_apps_db;

-- Remove foreign key constraints first
SET @constraint_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'research_apps_db' 
    AND TABLE_NAME = 'ready_for_publication' 
    AND CONSTRAINT_NAME = 'fk_ready_publication_conference'
);

SET @sql = IF(@constraint_exists > 0, 
    'ALTER TABLE `ready_for_publication` DROP FOREIGN KEY `fk_ready_publication_conference`',
    'SELECT "Foreign key constraint fk_ready_publication_conference does not exist" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'research_apps_db' 
    AND TABLE_NAME = 'ready_for_publication' 
    AND CONSTRAINT_NAME = 'fk_ready_publication_journal'
);

SET @sql = IF(@constraint_exists > 0, 
    'ALTER TABLE `ready_for_publication` DROP FOREIGN KEY `fk_ready_publication_journal`',
    'SELECT "Foreign key constraint fk_ready_publication_journal does not exist" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove indexes if they exist
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'research_apps_db' 
    AND TABLE_NAME = 'ready_for_publication' 
    AND INDEX_NAME = 'idx_ready_publication_conference'
);

SET @sql = IF(@index_exists > 0, 
    'DROP INDEX `idx_ready_publication_conference` ON `ready_for_publication`',
    'SELECT "Index idx_ready_publication_conference does not exist" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'research_apps_db' 
    AND TABLE_NAME = 'ready_for_publication' 
    AND INDEX_NAME = 'idx_ready_publication_journal'
);

SET @sql = IF(@index_exists > 0, 
    'DROP INDEX `idx_ready_publication_journal` ON `ready_for_publication`',
    'SELECT "Index idx_ready_publication_journal does not exist" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove columns if they exist
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'research_apps_db' 
    AND TABLE_NAME = 'ready_for_publication' 
    AND COLUMN_NAME = 'conference_id'
);

SET @sql = IF(@column_exists > 0, 
    'ALTER TABLE `ready_for_publication` DROP COLUMN `conference_id`',
    'SELECT "Column conference_id does not exist" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'research_apps_db' 
    AND TABLE_NAME = 'ready_for_publication' 
    AND COLUMN_NAME = 'journal_id'
);

SET @sql = IF(@column_exists > 0, 
    'ALTER TABLE `ready_for_publication` DROP COLUMN `journal_id`',
    'SELECT "Column journal_id does not exist" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verification: Show the final table structure
DESCRIBE `ready_for_publication`; 