-- =====================================================================
-- Remove Publications Tables Script
-- This script removes all tables related to the old publication feature
-- =====================================================================

USE research_apps_db;

-- Disable foreign key checks temporarily to avoid constraint errors
SET FOREIGN_KEY_CHECKS = 0;

-- Drop publication tables in reverse order of dependencies
DROP TABLE IF EXISTS `publication_audit_log`;
DROP TABLE IF EXISTS `publication_status_history`;
DROP TABLE IF EXISTS `publication_mentors`;
DROP TABLE IF EXISTS `publication_students`;
DROP TABLE IF EXISTS `publication_statuses`;
DROP TABLE IF EXISTS `publications`;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Show confirmation
SELECT 'All publication tables have been removed successfully' as status; 