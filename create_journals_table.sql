-- =====================================================================
-- Journals - Create Table
-- Create journals table to manage journal information
-- =====================================================================

USE research_apps_db;

-- Create journals table
CREATE TABLE IF NOT EXISTS `journals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `journal_name` varchar(500) NOT NULL,
  `publisher` varchar(255) NOT NULL DEFAULT 'Other',
  `journal_link` varchar(500) DEFAULT NULL,
  `acceptance_frequency` enum('Rolling','Monthly','Quarterly','Semi-annually','Yearly') NOT NULL DEFAULT 'Rolling',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_publisher` (`publisher`),
  KEY `idx_acceptance` (`acceptance_frequency`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_journal_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create index for searching journals
CREATE INDEX `idx_journal_search` ON `journals` (`journal_name`);

-- Insert some default journal data (optional sample data)
INSERT IGNORE INTO `journals` 
(`journal_name`, `publisher`, `journal_link`, `acceptance_frequency`) 
VALUES 
('IEEE Transactions on Software Engineering', 'IEEE', 'https://ieeexplore.ieee.org/xpl/RecentIssue.jsp?punumber=32', 'Monthly'),
('Nature', 'Nature Publishing Group', 'https://www.nature.com/', 'Rolling'),
('Science', 'American Association for the Advancement of Science', 'https://science.sciencemag.org/', 'Rolling'),
('Communications of the ACM', 'ACM', 'https://cacm.acm.org/', 'Monthly'),
('Journal of Machine Learning Research', 'MIT Press', 'https://jmlr.org/', 'Rolling'),
('Computer Networks', 'Elsevier', 'https://www.journals.elsevier.com/computer-networks', 'Monthly'),
('Information Sciences', 'Elsevier', 'https://www.journals.elsevier.com/information-sciences', 'Monthly'),
('Artificial Intelligence', 'Elsevier', 'https://www.journals.elsevier.com/artificial-intelligence', 'Monthly'),
('Pattern Recognition', 'Elsevier', 'https://www.journals.elsevier.com/pattern-recognition', 'Monthly'),
('International Journal of Computer Vision', 'Springer', 'https://link.springer.com/journal/11263', 'Monthly');

-- Create additional indexes for performance
CREATE INDEX `idx_journal_publisher_acceptance` ON `journals` (`publisher`, `acceptance_frequency`); 