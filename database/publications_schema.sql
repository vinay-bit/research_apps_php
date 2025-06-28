-- Publications Tracker Database Schema
-- This script creates the necessary tables for the publication tracking module

-- Main publications table
CREATE TABLE IF NOT EXISTS publications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publication_id VARCHAR(50) UNIQUE NOT NULL,
    project_id INT NOT NULL,
    paper_title VARCHAR(500) NOT NULL,
    venue_type ENUM('Conference', 'Journal') NOT NULL,
    
    -- Conference specific fields
    conference_acceptance_date DATE NULL,
    conference_reviewer_comments TEXT NULL,
    conference_presentation_date DATE NULL,
    conference_camera_ready_submission_date DATE NULL,
    conference_copyright_submission_date DATE NULL,
    conference_doi_link VARCHAR(255) NULL,
    conference_publisher VARCHAR(255) NULL,
    
    -- Journal specific fields
    journal_acceptance_date DATE NULL,
    journal_reviewer_comments TEXT NULL,
    journal_link VARCHAR(255) NULL,
    journal_publishing_date DATE NULL,
    journal_doi_link VARCHAR(255) NULL,
    journal_publisher VARCHAR(255) NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key to projects table
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_publication_id (publication_id),
    INDEX idx_project_id (project_id),
    FULLTEXT INDEX idx_paper_title (paper_title)
);

-- Publication students association table (many-to-many)
CREATE TABLE IF NOT EXISTS publication_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publication_id INT NOT NULL,
    student_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_publication_student (publication_id, student_id),
    INDEX idx_publication_id (publication_id),
    INDEX idx_student_id (student_id)
);

-- Publication mentors association table (many-to-many)
CREATE TABLE IF NOT EXISTS publication_mentors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publication_id INT NOT NULL,
    mentor_id INT NOT NULL,
    is_lead_mentor BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_publication_mentor (publication_id, mentor_id),
    INDEX idx_publication_id (publication_id),
    INDEX idx_mentor_id (mentor_id)
);

-- Publication status history table
CREATE TABLE IF NOT EXISTS publication_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publication_id INT NOT NULL,
    status VARCHAR(100) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by INT NOT NULL,
    
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_publication_id (publication_id),
    INDEX idx_timestamp (timestamp)
);

-- Publication audit log table
CREATE TABLE IF NOT EXISTS publication_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publication_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by INT NOT NULL,
    
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_publication_id (publication_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_field_name (field_name)
);

-- Publication statuses lookup table
CREATE TABLE IF NOT EXISTS publication_statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default publication statuses
INSERT IGNORE INTO publication_statuses (status_name, description) VALUES
('Draft', 'Publication is in draft stage'),
('Under Review', 'Paper submitted and under review'),
('Revision Required', 'Reviewers requested revisions'),
('Accepted', 'Paper has been accepted'),
('Published', 'Paper has been published'),
('Rejected', 'Paper was rejected'),
('Withdrawn', 'Paper was withdrawn by authors'),
('In Press', 'Paper accepted and in press'),
('Presented', 'Paper presented at conference'); 