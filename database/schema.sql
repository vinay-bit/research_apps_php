-- Create database
CREATE DATABASE IF NOT EXISTS research_apps_db;
USE research_apps_db;

-- Create organizations table for dropdown
CREATE TABLE organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample organizations
INSERT INTO organizations (name) VALUES 
('Tech Corp'), 
('Innovation Hub'), 
('Digital Solutions'), 
('Future Systems'), 
('Smart Technologies');

-- Create departments table for dropdown
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample departments
INSERT INTO departments (name) VALUES 
('Computer Science'), 
('Information Technology'), 
('Software Engineering'), 
('Data Science'), 
('Artificial Intelligence');

-- Create users table for all user types
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('admin', 'mentor', 'councillor', 'rbm') NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    
    -- Admin & Mentor fields
    department_id INT NULL,
    
    -- Mentor specific fields
    specialization VARCHAR(255) NULL,
    organization_id INT NULL,
    
    -- Councillor specific fields
    organization_name VARCHAR(255) NULL,
    mou_signed BOOLEAN DEFAULT FALSE,
    mou_drive_link VARCHAR(500) NULL,
    contact_no VARCHAR(20) NULL,
    email_id VARCHAR(255) NULL,
    address TEXT NULL,
    primary_contact_id INT NULL,
    councillor_rbm_id INT NULL,
    
    -- RBM specific fields
    branch VARCHAR(255) NULL,
    
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    FOREIGN KEY (primary_contact_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (councillor_rbm_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create sessions table for user authentication
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create boards table
CREATE TABLE boards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default boards
INSERT INTO boards (name) VALUES 
('IB'), ('IG'), ('ICSE'), ('CBSE'), ('State Board');

-- Create students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    affiliation VARCHAR(255) NULL,
    grade VARCHAR(50) NULL,
    counselor_id INT NULL,
    rbm_id INT NULL,
    board_id INT NULL,
    contact_no VARCHAR(20) NULL,
    email_address VARCHAR(255) NULL,
    application_year YEAR NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (counselor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (rbm_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE SET NULL
);

-- Insert sample admin user (password: admin123)
INSERT INTO users (user_type, full_name, username, password, department_id) VALUES 
('admin', 'System Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1); 