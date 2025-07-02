<?php
/**
 * Database Structure Analyzer & Production SQL Generator
 * This script analyzes the current database structure, tests relationships, and generates production-ready SQL
 */

require_once 'config/database.php';

class DatabaseAnalyzer {
    private $conn;
    private $db_name;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->db_name = 'research_apps_db';
    }
    
    /**
     * Get all tables in the database
     */
    public function getAllTables() {
        $query = "SELECT TABLE_NAME, TABLE_COMMENT, ENGINE, TABLE_COLLATION 
                  FROM information_schema.TABLES 
                  WHERE TABLE_SCHEMA = :db_name 
                  ORDER BY TABLE_NAME";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':db_name', $this->db_name);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get columns for a specific table
     */
    public function getTableColumns($table_name) {
        $query = "SELECT 
                    COLUMN_NAME,
                    COLUMN_TYPE,
                    DATA_TYPE,
                    IS_NULLABLE,
                    COLUMN_DEFAULT,
                    EXTRA,
                    COLUMN_KEY,
                    COLUMN_COMMENT,
                    CHARACTER_MAXIMUM_LENGTH,
                    NUMERIC_PRECISION,
                    NUMERIC_SCALE
                  FROM information_schema.COLUMNS 
                  WHERE TABLE_SCHEMA = :db_name 
                  AND TABLE_NAME = :table_name 
                  ORDER BY ORDINAL_POSITION";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':db_name', $this->db_name);
        $stmt->bindParam(':table_name', $table_name);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get foreign key relationships for a table
     */
    public function getForeignKeys($table_name) {
        // First try with UPDATE_RULE and DELETE_RULE (MySQL 5.7+)
        $query = "SELECT 
                    kcu.COLUMN_NAME,
                    kcu.REFERENCED_TABLE_NAME,
                    kcu.REFERENCED_COLUMN_NAME,
                    kcu.CONSTRAINT_NAME,
                    COALESCE(rc.UPDATE_RULE, 'RESTRICT') as UPDATE_RULE,
                    COALESCE(rc.DELETE_RULE, 'RESTRICT') as DELETE_RULE
                  FROM information_schema.KEY_COLUMN_USAGE kcu
                  LEFT JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                    ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                    AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
                  WHERE kcu.TABLE_SCHEMA = :db_name 
                  AND kcu.TABLE_NAME = :table_name 
                  AND kcu.REFERENCED_TABLE_NAME IS NOT NULL";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':db_name', $this->db_name);
            $stmt->bindParam(':table_name', $table_name);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Fallback for older MySQL versions
            $query = "SELECT 
                        COLUMN_NAME,
                        REFERENCED_TABLE_NAME,
                        REFERENCED_COLUMN_NAME,
                        CONSTRAINT_NAME,
                        'RESTRICT' as UPDATE_RULE,
                        'RESTRICT' as DELETE_RULE
                      FROM information_schema.KEY_COLUMN_USAGE 
                      WHERE TABLE_SCHEMA = :db_name 
                      AND TABLE_NAME = :table_name 
                      AND REFERENCED_TABLE_NAME IS NOT NULL";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':db_name', $this->db_name);
            $stmt->bindParam(':table_name', $table_name);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    /**
     * Get indexes for a table
     */
    public function getTableIndexes($table_name) {
        $query = "SELECT 
                    INDEX_NAME,
                    COLUMN_NAME,
                    NON_UNIQUE,
                    INDEX_TYPE,
                    SEQ_IN_INDEX
                  FROM information_schema.STATISTICS 
                  WHERE TABLE_SCHEMA = :db_name 
                  AND TABLE_NAME = :table_name 
                  ORDER BY INDEX_NAME, SEQ_IN_INDEX";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':db_name', $this->db_name);
        $stmt->bindParam(':table_name', $table_name);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Test table relationships and data integrity
     */
    public function testTableRelationships() {
        $results = [];
        $tables = $this->getAllTables();
        
        foreach ($tables as $table) {
            $table_name = $table['TABLE_NAME'];
            $foreign_keys = $this->getForeignKeys($table_name);
            
            foreach ($foreign_keys as $fk) {
                // Test if foreign key relationships are valid
                $test_query = "SELECT COUNT(*) as orphaned_records
                              FROM {$table_name} t1
                              LEFT JOIN {$fk['REFERENCED_TABLE_NAME']} t2 
                              ON t1.{$fk['COLUMN_NAME']} = t2.{$fk['REFERENCED_COLUMN_NAME']}
                              WHERE t1.{$fk['COLUMN_NAME']} IS NOT NULL 
                              AND t2.{$fk['REFERENCED_COLUMN_NAME']} IS NULL";
                
                try {
                    $stmt = $this->conn->prepare($test_query);
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $results[] = [
                        'table' => $table_name,
                        'foreign_key' => $fk['COLUMN_NAME'],
                        'references' => "{$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}",
                        'orphaned_records' => $result['orphaned_records'],
                        'status' => $result['orphaned_records'] == 0 ? 'PASS' : 'FAIL'
                    ];
                } catch (Exception $e) {
                    $results[] = [
                        'table' => $table_name,
                        'foreign_key' => $fk['COLUMN_NAME'],
                        'references' => "{$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}",
                        'orphaned_records' => 'ERROR',
                        'status' => 'ERROR',
                        'error' => $e->getMessage()
                    ];
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Generate CREATE TABLE statements for production
     */
    public function generateProductionSQL() {
        $sql = [];
        $sql[] = "-- Research Apps Database - Production Schema";
        $sql[] = "-- Generated on: " . date('Y-m-d H:i:s');
        $sql[] = "-- Database Version: MySQL 5.7+";
        $sql[] = "";
        $sql[] = "-- Create database";
        $sql[] = "CREATE DATABASE IF NOT EXISTS research_apps_production;";
        $sql[] = "USE research_apps_production;";
        $sql[] = "";
        $sql[] = "-- Set SQL mode for compatibility";
        $sql[] = "SET sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';";
        $sql[] = "";
        
        $tables = $this->getAllTables();
        $table_order = $this->getTableCreationOrder($tables);
        
        // Generate CREATE TABLE statements in dependency order
        foreach ($table_order as $table_name) {
            $sql[] = $this->generateCreateTableSQL($table_name);
            $sql[] = "";
        }
        
        // Generate INSERT statements for lookup/reference tables
        $sql[] = $this->generateInsertStatements();
        
        // Generate triggers
        $sql[] = $this->generateTriggers();
        
        return implode("\n", $sql);
    }
    
    /**
     * Determine table creation order based on dependencies
     */
    private function getTableCreationOrder($tables) {
        $order = [
            'organizations',
            'departments', 
            'boards',
            'users',
            'user_sessions',
            'students',
            'project_statuses',
            'subjects',
            'project_tags',
            'projects',
            'project_students',
            'project_tag_assignments',
            'project_activity_log',
            'ready_for_publication',
            'ready_for_publication_students',
            'conferences',
            'journals',
            'in_publication',
            'in_publication_students',
            'publication_conference_applications',
            'publication_journal_applications'
        ];
        
        $existing_tables = array_column($tables, 'TABLE_NAME');
        return array_intersect($order, $existing_tables);
    }
    
    /**
     * Generate CREATE TABLE SQL for a specific table
     */
    private function generateCreateTableSQL($table_name) {
        $columns = $this->getTableColumns($table_name);
        $foreign_keys = $this->getForeignKeys($table_name);
        $indexes = $this->getTableIndexes($table_name);
        
        $sql = [];
        $sql[] = "-- Table: {$table_name}";
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$table_name}` (";
        
        // Generate column definitions
        $column_definitions = [];
        $has_auto_increment_pk = false;
        
        foreach ($columns as $column) {
            $def = "  `{$column['COLUMN_NAME']}` {$column['COLUMN_TYPE']}";
            
            if ($column['IS_NULLABLE'] === 'NO') {
                $def .= " NOT NULL";
            }
            
            // Handle AUTO_INCREMENT
            if (strpos(strtolower($column['EXTRA']), 'auto_increment') !== false) {
                $def .= " AUTO_INCREMENT";
                $has_auto_increment_pk = true;
            }
            
            // Handle DEFAULT values
            if ($column['COLUMN_DEFAULT'] !== null) {
                if ($column['COLUMN_DEFAULT'] === 'CURRENT_TIMESTAMP') {
                    $def .= " DEFAULT CURRENT_TIMESTAMP";
                } elseif (strtolower($column['COLUMN_DEFAULT']) === 'current_timestamp()') {
                    $def .= " DEFAULT CURRENT_TIMESTAMP";
                } else {
                    // Clean up the default value
                    $default_val = str_replace(["'", '"'], "", $column['COLUMN_DEFAULT']);
                    
                    // Handle function calls like curdate()
                    if (strtolower($default_val) === 'curdate()' || strtolower($default_val) === 'curdate') {
                        $def .= " DEFAULT (CURDATE())";
                    } elseif (strtolower($default_val) === 'now()' || strtolower($default_val) === 'now') {
                        $def .= " DEFAULT (NOW())";
                    } elseif (is_numeric($default_val) || in_array(strtolower($default_val), ['null', 'true', 'false'])) {
                        $def .= " DEFAULT {$default_val}";
                    } else {
                        $def .= " DEFAULT '{$default_val}'";
                    }
                }
            }
            
            // Handle ON UPDATE CURRENT_TIMESTAMP
            if (strpos(strtolower($column['EXTRA']), 'on update current_timestamp') !== false) {
                $def .= " ON UPDATE CURRENT_TIMESTAMP";
            }
            
            $column_definitions[] = $def;
        }
        
        $all_parts = [];
        $all_parts = array_merge($all_parts, $column_definitions);
        
        // Add PRIMARY KEY for auto_increment columns
        if ($has_auto_increment_pk) {
            foreach ($columns as $column) {
                if ($column['COLUMN_KEY'] === 'PRI' && strpos(strtolower($column['EXTRA']), 'auto_increment') !== false) {
                    $all_parts[] = "  PRIMARY KEY (`{$column['COLUMN_NAME']}`)";
                    break;
                }
            }
        } else {
            // Add composite primary key if no auto_increment
            $primary_keys = [];
            foreach ($columns as $column) {
                if ($column['COLUMN_KEY'] === 'PRI') {
                    $primary_keys[] = "`{$column['COLUMN_NAME']}`";
                }
            }
            if (!empty($primary_keys)) {
                $all_parts[] = "  PRIMARY KEY (" . implode(", ", $primary_keys) . ")";
            }
        }
        
        // Add unique indexes (excluding PRIMARY)
        $unique_indexes = [];
        $unique_column_sets = []; // Track column combinations to avoid duplicates
        
        foreach ($indexes as $index) {
            if ($index['INDEX_NAME'] !== 'PRIMARY' && $index['NON_UNIQUE'] == 0) {
                $index_name = $index['INDEX_NAME'];
                if (!isset($unique_indexes[$index_name])) {
                    $unique_indexes[$index_name] = [];
                }
                $unique_indexes[$index_name][] = "`{$index['COLUMN_NAME']}`";
            }
        }
        
        foreach ($unique_indexes as $index_name => $columns_list) {
            $column_set = implode(", ", $columns_list);
            // Only add if we haven't seen this exact column combination
            if (!in_array($column_set, $unique_column_sets)) {
                $unique_column_sets[] = $column_set;
                $all_parts[] = "  UNIQUE KEY `{$index_name}` ({$column_set})";
            }
        }
        
        // Add regular indexes (excluding PRIMARY and UNIQUE)
        $regular_indexes = [];
        foreach ($indexes as $index) {
            if ($index['INDEX_NAME'] !== 'PRIMARY' && $index['NON_UNIQUE'] == 1) {
                $index_name = $index['INDEX_NAME'];
                if (!isset($regular_indexes[$index_name])) {
                    $regular_indexes[$index_name] = [];
                }
                $regular_indexes[$index_name][] = "`{$index['COLUMN_NAME']}`";
            }
        }
        
        foreach ($regular_indexes as $index_name => $columns_list) {
            $all_parts[] = "  KEY `{$index_name}` (" . implode(", ", $columns_list) . ")";
        }
        
        // Add foreign key constraints
        foreach ($foreign_keys as $fk) {
            $fk_def = "  CONSTRAINT `{$fk['CONSTRAINT_NAME']}` FOREIGN KEY (`{$fk['COLUMN_NAME']}`) REFERENCES `{$fk['REFERENCED_TABLE_NAME']}` (`{$fk['REFERENCED_COLUMN_NAME']}`)";
            if ($fk['UPDATE_RULE'] !== 'RESTRICT' && $fk['UPDATE_RULE'] !== 'NO ACTION') {
                $fk_def .= " ON UPDATE {$fk['UPDATE_RULE']}";
            }
            if ($fk['DELETE_RULE'] !== 'RESTRICT' && $fk['DELETE_RULE'] !== 'NO ACTION') {
                $fk_def .= " ON DELETE {$fk['DELETE_RULE']}";
            }
            $all_parts[] = $fk_def;
        }
        
        // Add missing foreign key constraints for common relationship patterns
        if ($table_name === 'project_students' && empty($foreign_keys)) {
            $all_parts[] = "  CONSTRAINT `fk_project_students_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE";
            $all_parts[] = "  CONSTRAINT `fk_project_students_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE";
        } elseif ($table_name === 'project_tag_assignments' && count($foreign_keys) < 2) {
            if (!$this->hasForeignKeyForColumn($foreign_keys, 'project_id')) {
                $all_parts[] = "  CONSTRAINT `fk_project_tag_assignments_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE";
            }
            if (!$this->hasForeignKeyForColumn($foreign_keys, 'tag_id')) {
                $all_parts[] = "  CONSTRAINT `fk_project_tag_assignments_tag` FOREIGN KEY (`tag_id`) REFERENCES `project_tags` (`id`) ON DELETE CASCADE";
            }
        }
        
        // Join all parts with commas
        $sql[] = implode(",\n", $all_parts);
        $sql[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        return implode("\n", $sql);
    }
    
    /**
     * Generate INSERT statements for reference data
     */
    private function generateInsertStatements() {
        $sql = [];
        $sql[] = "-- Reference Data Inserts";
        $sql[] = "";
        
        // Organizations
        $sql[] = "-- Organizations";
        $sql[] = "INSERT INTO organizations (name) VALUES";
        $sql[] = "('Tech Corp'), ('Innovation Hub'), ('Digital Solutions'), ('Future Systems'), ('Smart Technologies')";
        $sql[] = "ON DUPLICATE KEY UPDATE name = VALUES(name);";
        $sql[] = "";
        
        // Departments
        $sql[] = "-- Departments";
        $sql[] = "INSERT INTO departments (name) VALUES";
        $sql[] = "('Computer Science'), ('Information Technology'), ('Software Engineering'), ('Data Science'), ('Artificial Intelligence')";
        $sql[] = "ON DUPLICATE KEY UPDATE name = VALUES(name);";
        $sql[] = "";
        
        // Boards
        $sql[] = "-- Boards";
        $sql[] = "INSERT INTO boards (name) VALUES";
        $sql[] = "('IB'), ('IG'), ('ICSE'), ('CBSE'), ('State Board')";
        $sql[] = "ON DUPLICATE KEY UPDATE name = VALUES(name);";
        $sql[] = "";
        
        // Project Statuses
        $sql[] = "-- Project Statuses";
        $sql[] = "INSERT INTO project_statuses (status_name) VALUES";
        $sql[] = "('Project Execution - yet to start'),";
        $sql[] = "('Project Execution - in progress'),";
        $sql[] = "('Project Execution - completed'),";
        $sql[] = "('Research Paper - in progress'),";
        $sql[] = "('Research Paper - completed')";
        $sql[] = "ON DUPLICATE KEY UPDATE status_name = VALUES(status_name);";
        $sql[] = "";
        
        // Subjects (check if subject_code column exists)
        $sql[] = "-- Subjects";
        $sql[] = "INSERT INTO subjects (subject_name) VALUES";
        $sql[] = "('Computer Science'), ('Mathematics'), ('Physics'),";
        $sql[] = "('Chemistry'), ('Biology'), ('Engineering'),";
        $sql[] = "('Data Science'), ('Artificial Intelligence'),";
        $sql[] = "('Robotics'), ('Environmental Science')";
        $sql[] = "ON DUPLICATE KEY UPDATE subject_name = VALUES(subject_name);";
        $sql[] = "";
        
        // Project Tags (using 'color' column name for compatibility)
        $sql[] = "-- Project Tags";
        $sql[] = "INSERT INTO project_tags (tag_name, color) VALUES";
        $sql[] = "('Research', 'primary'), ('Innovation', 'success'), ('Technology', 'info'),";
        $sql[] = "('Science', 'warning'), ('Engineering', 'danger'), ('Data Analysis', 'secondary'),";
        $sql[] = "('Machine Learning', 'primary'), ('Prototype', 'success'),";
        $sql[] = "('Publication', 'info'), ('Experiment', 'warning')";
        $sql[] = "ON DUPLICATE KEY UPDATE tag_name = VALUES(tag_name);";
        $sql[] = "";
        
        // Default Admin User
        $sql[] = "-- Default Admin User (password: admin123)";
        $sql[] = "INSERT INTO users (user_type, full_name, username, password, department_id, status) VALUES";
        $sql[] = "('admin', 'System Administrator', 'admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'active')";
        $sql[] = "ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);";
        $sql[] = "";
        
        return implode("\n", $sql);
    }
    
    /**
     * Generate triggers
     */
    private function generateTriggers() {
        $sql = [];
        $sql[] = "-- Triggers";
        $sql[] = "";
        
        // Student ID trigger
        $sql[] = "-- Student ID generation trigger";
        $sql[] = "DELIMITER //";
        $sql[] = "DROP TRIGGER IF EXISTS generate_student_id//";
        $sql[] = "CREATE TRIGGER generate_student_id";
        $sql[] = "BEFORE INSERT ON students";
        $sql[] = "FOR EACH ROW";
        $sql[] = "BEGIN";
        $sql[] = "    DECLARE next_id INT;";
        $sql[] = "    DECLARE current_year INT;";
        $sql[] = "    ";
        $sql[] = "    SET current_year = YEAR(CURDATE());";
        $sql[] = "    ";
        $sql[] = "    SELECT COALESCE(MAX(CAST(SUBSTRING(student_id, 5) AS UNSIGNED)), 0) + 1 INTO next_id";
        $sql[] = "    FROM students";
        $sql[] = "    WHERE student_id LIKE CONCAT('STU', current_year, '%');";
        $sql[] = "    ";
        $sql[] = "    SET NEW.student_id = CONCAT('STU', current_year, LPAD(next_id, 4, '0'));";
        $sql[] = "END//";
        $sql[] = "";
        
        // Project ID trigger
        $sql[] = "-- Project ID generation trigger";
        $sql[] = "DROP TRIGGER IF EXISTS generate_project_id//";
        $sql[] = "CREATE TRIGGER generate_project_id";
        $sql[] = "BEFORE INSERT ON projects";
        $sql[] = "FOR EACH ROW";
        $sql[] = "BEGIN";
        $sql[] = "    DECLARE next_id INT;";
        $sql[] = "    DECLARE current_year INT;";
        $sql[] = "    ";
        $sql[] = "    SET current_year = YEAR(CURDATE());";
        $sql[] = "    ";
        $sql[] = "    SELECT COALESCE(MAX(CAST(SUBSTRING(project_id, 5) AS UNSIGNED)), 0) + 1 INTO next_id";
        $sql[] = "    FROM projects";
        $sql[] = "    WHERE project_id LIKE CONCAT('PRJ', current_year, '%');";
        $sql[] = "    ";
        $sql[] = "    SET NEW.project_id = CONCAT('PRJ', current_year, LPAD(next_id, 4, '0'));";
        $sql[] = "    ";
        $sql[] = "    IF NEW.start_date IS NOT NULL AND NEW.end_date IS NULL THEN";
        $sql[] = "        SET NEW.end_date = DATE_ADD(NEW.start_date, INTERVAL 4 MONTH);";
        $sql[] = "    END IF;";
        $sql[] = "END//";
        $sql[] = "DELIMITER ;";
        $sql[] = "";
        
        return implode("\n", $sql);
    }
    
    /**
     * Helper method to check if a foreign key exists for a specific column
     */
    private function hasForeignKeyForColumn($foreign_keys, $column_name) {
        foreach ($foreign_keys as $fk) {
            if ($fk['COLUMN_NAME'] === $column_name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate database statistics
     */
    public function getDatabaseStatistics() {
        $stats = [];
        $tables = $this->getAllTables();
        
        foreach ($tables as $table) {
            $table_name = $table['TABLE_NAME'];
            
            // Get row count
            try {
                $count_query = "SELECT COUNT(*) as row_count FROM `{$table_name}`";
                $stmt = $this->conn->prepare($count_query);
                $stmt->execute();
                $row_count = $stmt->fetch(PDO::FETCH_ASSOC)['row_count'];
            } catch (Exception $e) {
                $row_count = 'ERROR';
            }
            
            $stats[] = [
                'table' => $table_name,
                'engine' => $table['ENGINE'],
                'collation' => $table['TABLE_COLLATION'],
                'row_count' => $row_count,
                'columns' => count($this->getTableColumns($table_name)),
                'foreign_keys' => count($this->getForeignKeys($table_name)),
                'indexes' => count(array_unique(array_column($this->getTableIndexes($table_name), 'INDEX_NAME')))
            ];
        }
        
        return $stats;
    }
}

// Check if running from command line or web
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    // Web interface
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Database Structure Analysis</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
            .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
            h2 { color: #007bff; margin-top: 30px; }
            table { border-collapse: collapse; width: 100%; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #007bff; color: white; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .pass { color: green; font-weight: bold; }
            .fail { color: red; font-weight: bold; }
            .error { color: orange; font-weight: bold; }
            .download-btn { background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
            .download-btn:hover { background: #218838; }
            pre { background: #f8f9fa; padding: 15px; border: 1px solid #e9ecef; border-radius: 5px; overflow-x: auto; }
            .summary { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Research Apps - Database Structure Analysis</h1>
    <?php
}

try {
    // Initialize analyzer
    $analyzer = new DatabaseAnalyzer();
    
    // 1. Database Statistics
    if (!$is_cli) echo "<h2>📊 Database Statistics</h2>";
    else echo "=== Database Statistics ===\n";
    
    $stats = $analyzer->getDatabaseStatistics();
    
    if (!$is_cli) {
        echo "<table>";
        echo "<tr><th>Table Name</th><th>Engine</th><th>Rows</th><th>Columns</th><th>Foreign Keys</th><th>Indexes</th></tr>";
        foreach ($stats as $stat) {
            echo "<tr>";
            echo "<td><strong>{$stat['table']}</strong></td>";
            echo "<td>{$stat['engine']}</td>";
            echo "<td>" . number_format($stat['row_count']) . "</td>";
            echo "<td>{$stat['columns']}</td>";
            echo "<td>{$stat['foreign_keys']}</td>";
            echo "<td>{$stat['indexes']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        printf("%-30s %-10s %-10s %-8s %-5s %-8s\n", "Table", "Engine", "Rows", "Columns", "FKs", "Indexes");
        echo str_repeat("-", 80) . "\n";
        foreach ($stats as $stat) {
            printf("%-30s %-10s %-10s %-8s %-5s %-8s\n", 
                $stat['table'], $stat['engine'], number_format($stat['row_count']), 
                $stat['columns'], $stat['foreign_keys'], $stat['indexes']
            );
        }
    }
    
    // 2. Relationship Testing
    if (!$is_cli) echo "<h2>🔗 Foreign Key Relationship Tests</h2>";
    else echo "\n=== Foreign Key Relationship Tests ===\n";
    
    $relationship_tests = $analyzer->testTableRelationships();
    
    if (!$is_cli) {
        echo "<table>";
        echo "<tr><th>Table</th><th>Foreign Key</th><th>References</th><th>Orphaned Records</th><th>Status</th></tr>";
        foreach ($relationship_tests as $test) {
            $status_class = strtolower($test['status']);
            echo "<tr>";
            echo "<td><strong>{$test['table']}</strong></td>";
            echo "<td>{$test['foreign_key']}</td>";
            echo "<td>{$test['references']}</td>";
            echo "<td>{$test['orphaned_records']}</td>";
            echo "<td class='{$status_class}'>{$test['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        printf("%-25s %-15s %-30s %-10s %-6s\n", "Table", "Foreign Key", "References", "Orphaned", "Status");
        echo str_repeat("-", 90) . "\n";
        foreach ($relationship_tests as $test) {
            printf("%-25s %-15s %-30s %-10s %-6s\n", 
                $test['table'], $test['foreign_key'], $test['references'], 
                $test['orphaned_records'], $test['status']
            );
            if (isset($test['error'])) {
                echo "    Error: " . $test['error'] . "\n";
            }
        }
    }
    
    // 3. Generate Production SQL
    if (!$is_cli) echo "<h2>🏭 Production SQL Schema Generation</h2>";
    else echo "\n=== Production SQL Schema Generation ===\n";
    
    $production_sql = $analyzer->generateProductionSQL();
    
    // Save to file
    $filename = 'production_schema_' . date('Y-m-d_H-i-s') . '.sql';
    file_put_contents($filename, $production_sql);
    
    if (!$is_cli) {
        echo "<div class='summary'>";
        echo "<p><strong>✅ Production SQL Generated Successfully!</strong></p>";
        echo "<p><strong>File:</strong> <code>{$filename}</code></p>";
        echo "<p><strong>Size:</strong> " . number_format(strlen($production_sql)) . " bytes</p>";
        echo "<a href='{$filename}' class='download-btn' download>📥 Download Production SQL</a>";
        echo "</div>";
        
        echo "<h3>Schema Preview (First 2000 characters):</h3>";
        echo "<pre>" . htmlspecialchars(substr($production_sql, 0, 2000)) . "...</pre>";
    } else {
        echo "Production SQL saved to: {$filename}\n";
        echo "File size: " . number_format(strlen($production_sql)) . " bytes\n";
    }
    
    // 4. Summary Report
    if (!$is_cli) echo "<h2>📋 Summary Report</h2>";
    else echo "\n=== Summary Report ===\n";
    
    $total_tables = count($stats);
    $total_rows = is_numeric($stats[0]['row_count']) ? array_sum(array_filter(array_column($stats, 'row_count'), 'is_numeric')) : 'N/A';
    $failed_tests = count(array_filter($relationship_tests, function($test) { return $test['status'] === 'FAIL'; }));
    $error_tests = count(array_filter($relationship_tests, function($test) { return $test['status'] === 'ERROR'; }));
    
    if (!$is_cli) {
        echo "<div class='summary'>";
        echo "<ul>";
        echo "<li><strong>Total Tables:</strong> {$total_tables}</li>";
        echo "<li><strong>Total Records:</strong> " . (is_numeric($total_rows) ? number_format($total_rows) : $total_rows) . "</li>";
        echo "<li><strong>Relationship Tests:</strong> " . count($relationship_tests);
        if ($failed_tests > 0) {
            echo " (<span class='fail'>{$failed_tests} failed</span>)";
        }
        if ($error_tests > 0) {
            echo " (<span class='error'>{$error_tests} errors</span>)";
        }
        if ($failed_tests == 0 && $error_tests == 0) {
            echo " (<span class='pass'>all passed</span>)";
        }
        echo "</li>";
        echo "<li><strong>Production SQL:</strong> <span class='pass'>Generated successfully</span></li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<h3>🚀 Next Steps for Production Deployment:</h3>";
        echo "<ol>";
        echo "<li>Review the generated SQL file for any environment-specific modifications</li>";
        echo "<li>Update database connection credentials in production config</li>";
        echo "<li>Run the SQL file on your production MySQL server</li>";
        echo "<li>Test the application with production database</li>";
        echo "<li>Set up regular database backups</li>";
        echo "</ol>";
        
        echo "</div></body></html>";
    } else {
        echo "Total Tables: {$total_tables}\n";
        echo "Total Records: " . (is_numeric($total_rows) ? number_format($total_rows) : $total_rows) . "\n";
        echo "Relationship Tests: " . count($relationship_tests);
        if ($failed_tests > 0) echo " ({$failed_tests} failed)";
        if ($error_tests > 0) echo " ({$error_tests} errors)";
        if ($failed_tests == 0 && $error_tests == 0) echo " (all passed)";
        echo "\n";
        echo "Production SQL: Generated successfully\n";
        echo "\nNext Steps:\n";
        echo "1. Review the generated SQL file\n";
        echo "2. Update production database config\n";
        echo "3. Deploy to production server\n";
        echo "4. Test and verify\n";
    }
    
} catch (Exception $e) {
    if (!$is_cli) {
        echo "<div style='color: red; background: #ffe6e6; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ Error</h3>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
        echo "</div></body></html>";
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
?>
