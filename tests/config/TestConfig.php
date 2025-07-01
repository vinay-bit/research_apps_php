<?php
/**
 * Test Configuration File
 * Contains all configuration settings for the test suite
 */

class TestConfig {
    
    // Test Database Configuration
    const DB_HOST = 'localhost';
    const DB_NAME = 'research_apps_test_db';
    const DB_USERNAME = 'root';
    const DB_PASSWORD = '';
    const DB_CHARSET = 'utf8mb4';
    
    // Test Settings
    const TEST_TIMEOUT = 30; // seconds
    const VERBOSE_OUTPUT = true;
    const CLEANUP_AFTER_TESTS = true;
    
    // Test Data Settings
    const TEST_USER_EMAIL = 'test@example.com';
    const TEST_USER_PASSWORD = 'test123';
    const TEST_STUDENT_NAME = 'Test Student';
    const TEST_PROJECT_NAME = 'Test Project';
    
    // Test File Paths
    const TEST_UPLOADS_DIR = 'tests/uploads/';
    const TEST_LOGS_DIR = 'tests/logs/';
    
    /**
     * Get test database connection
     */
    public static function getTestDatabaseConnection() {
        try {
            $dsn = "mysql:host=" . self::DB_HOST . ";dbname=" . self::DB_NAME . ";charset=" . self::DB_CHARSET;
            $pdo = new PDO($dsn, self::DB_USERNAME, self::DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            return $pdo;
        } catch (PDOException $e) {
            // If database doesn't exist, try to create it first
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                self::createTestDatabase();
                // Try again after creating database
                $dsn = "mysql:host=" . self::DB_HOST . ";dbname=" . self::DB_NAME . ";charset=" . self::DB_CHARSET;
                $pdo = new PDO($dsn, self::DB_USERNAME, self::DB_PASSWORD);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
                return $pdo;
            }
            throw new Exception("Test database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Create test database if it doesn't exist
     */
    public static function createTestDatabase() {
        try {
            $dsn = "mysql:host=" . self::DB_HOST . ";charset=" . self::DB_CHARSET;
            $pdo = new PDO($dsn, self::DB_USERNAME, self::DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create test database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS " . self::DB_NAME);
            
            // Try to copy structure from main database, but don't fail if it doesn't work
            try {
                self::copyDatabaseStructure($pdo);
            } catch (Exception $e) {
                // If copying fails, just log it and continue
                error_log("Warning: Could not copy database structure: " . $e->getMessage());
                // Create basic test tables manually if needed
                self::createBasicTestTables($pdo);
            }
            
            return true;
        } catch (PDOException $e) {
            throw new Exception("Failed to create test database: " . $e->getMessage());
        }
    }
    
    /**
     * Copy database structure from main database
     */
    private static function copyDatabaseStructure($pdo) {
        try {
            // Disable foreign key checks during structure copy
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Get all tables from main database
            $stmt = $pdo->query("SHOW TABLES FROM research_apps_db");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tables as $table) {
                try {
                    // Get CREATE TABLE statement
                    $stmt = $pdo->query("SHOW CREATE TABLE research_apps_db.$table");
                    $createStmt = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Check for different possible column names
                    $createTableSql = null;
                    if (isset($createStmt['Create Table'])) {
                        $createTableSql = $createStmt['Create Table'];
                    } elseif (isset($createStmt['Create View'])) {
                        $createTableSql = $createStmt['Create View'];
                    } else {
                        // Skip if we can't find the create statement
                        continue;
                    }
                    
                    if (empty($createTableSql)) {
                        continue;
                    }
                    
                    // Create table in test database
                    $createSql = str_replace(
                        "CREATE TABLE `$table`",
                        "CREATE TABLE IF NOT EXISTS " . self::DB_NAME . ".$table",
                        $createTableSql
                    );
                    
                    // Also handle CREATE VIEW if it's a view
                    $createSql = str_replace(
                        "CREATE VIEW `$table`",
                        "CREATE VIEW " . self::DB_NAME . ".$table",
                        $createSql
                    );
                    
                    if (!empty($createSql)) {
                        $pdo->exec($createSql);
                    }
                } catch (PDOException $tableError) {
                    // Log the error but continue with other tables
                    error_log("Failed to copy table $table: " . $tableError->getMessage());
                    continue;
                }
            }
            
            // Re-enable foreign key checks
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
        } catch (PDOException $e) {
            // Re-enable foreign key checks even if there was an error
            try {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            } catch (Exception $cleanupEx) {
                // Ignore cleanup errors
            }
            throw new Exception("Failed to copy database structure: " . $e->getMessage());
        }
    }
    
    /**
     * Clean test database
     */
    public static function cleanTestDatabase() {
        try {
            $pdo = self::getTestDatabaseConnection();
            
            // Get all tables
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Disable foreign key checks
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Truncate all tables
            foreach ($tables as $table) {
                $pdo->exec("TRUNCATE TABLE $table");
            }
            
            // Re-enable foreign key checks
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            return true;
        } catch (PDOException $e) {
            throw new Exception("Failed to clean test database: " . $e->getMessage());
        }
    }
    
    /**
     * Drop test database
     */
    public static function dropTestDatabase() {
        try {
            $dsn = "mysql:host=" . self::DB_HOST . ";charset=" . self::DB_CHARSET;
            $pdo = new PDO($dsn, self::DB_USERNAME, self::DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $pdo->exec("DROP DATABASE IF EXISTS " . self::DB_NAME);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Failed to drop test database: " . $e->getMessage());
        }
    }
    
    /**
     * Get test file upload directory
     */
    public static function getTestUploadsDir() {
        $dir = self::TEST_UPLOADS_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }
    
    /**
     * Get test logs directory
     */
    public static function getTestLogsDir() {
        $dir = self::TEST_LOGS_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }
    
    /**
     * Create basic test tables if structure copy fails
     */
    private static function createBasicTestTables($pdo) {
        try {
            // Switch to test database
            $pdo->exec("USE " . self::DB_NAME);
            
            // Create basic tables needed for tests
            $basicTables = [
                "CREATE TABLE IF NOT EXISTS organizations (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(255) NOT NULL
                )",
                "CREATE TABLE IF NOT EXISTS boards (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(255) NOT NULL
                )",
                "CREATE TABLE IF NOT EXISTS subjects (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(255) NOT NULL
                )",
                "CREATE TABLE IF NOT EXISTS project_statuses (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    status_name VARCHAR(255) NOT NULL
                )",
                "CREATE TABLE IF NOT EXISTS departments (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(255) NOT NULL
                )",
                "CREATE TABLE IF NOT EXISTS users (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    full_name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    user_type ENUM('admin', 'mentor', 'rbm', 'councillor') NOT NULL,
                    organization_id INT,
                    primary_contact_id INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS students (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    student_id VARCHAR(20) UNIQUE,
                    full_name VARCHAR(255) NOT NULL,
                    email_address VARCHAR(255),
                    grade VARCHAR(20),
                    board_id INT,
                    counselor_id INT,
                    rbm_id INT,
                    application_year YEAR,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS projects (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    project_id VARCHAR(20) UNIQUE,
                    project_name VARCHAR(255) NOT NULL,
                    description TEXT,
                    status_id INT,
                    lead_mentor_id INT,
                    subject_id INT,
                    rbm_id INT,
                    created_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS project_students (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    project_id INT NOT NULL,
                    student_id INT NOT NULL,
                    assigned_date DATE,
                    UNIQUE KEY unique_project_student (project_id, student_id)
                )",
                "CREATE TABLE IF NOT EXISTS ready_for_publication (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    project_id INT UNIQUE NOT NULL,
                    paper_title VARCHAR(500),
                    mentor_affiliation VARCHAR(255),
                    first_draft_link VARCHAR(500),
                    plagiarism_report_link VARCHAR(500),
                    ai_detection_link VARCHAR(500),
                    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS ready_for_publication_students (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    ready_for_publication_id INT NOT NULL,
                    student_id INT NOT NULL,
                    student_affiliation VARCHAR(255),
                    author_order INT,
                    UNIQUE KEY unique_publication_student (ready_for_publication_id, student_id)
                )"
            ];
            
            foreach ($basicTables as $tableSQL) {
                $pdo->exec($tableSQL);
            }
            
        } catch (PDOException $e) {
            // If even basic table creation fails, just continue
            error_log("Warning: Could not create basic test tables: " . $e->getMessage());
        }
    }
    
    /**
     * Log test message
     */
    public static function log($message, $level = 'INFO') {
        if (self::VERBOSE_OUTPUT) {
            $timestamp = date('Y-m-d H:i:s');
            echo "[$timestamp] [$level] $message\n";
        }
        
        // Also write to log file
        $logFile = self::getTestLogsDir() . 'test_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, "[$timestamp] [$level] $message\n", FILE_APPEND);
    }
}
?> 