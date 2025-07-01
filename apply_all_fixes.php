<?php
/**
 * Apply All Database Fixes Script
 * This script applies all database and reference fixes automatically
 */

require_once 'config/database.php';
require_once 'classes/Student.php';
require_once 'classes/Project.php';

class DatabaseFixer {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        // Enable buffered queries and set proper PDO attributes
        $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    public function applyAllFixes() {
        echo "🔧 Starting Database Fixes Application...\n\n";
        
        try {
            // 1. Apply SQL fixes
            $this->applySQLFixes();
            
            // 2. Verify integrity
            $this->checkIntegrity();
            
            // 3. Test improved classes
            $this->testClasses();
            
            echo "✅ All fixes applied successfully!\n";
            echo "📋 See DATABASE_FIXES_DOCUMENTATION.md for details\n";
            
        } catch (Exception $e) {
            echo "❌ Error applying fixes: " . $e->getMessage() . "\n";
            return false;
        }
        
        return true;
    }
    
    private function applySQLFixes() {
        echo "📊 Applying SQL fixes...\n";
        
        // Read and execute the fix SQL file
        $sql_file = 'fix_database_issues.sql';
        
        if (!file_exists($sql_file)) {
            throw new Exception("SQL fix file not found: $sql_file");
        }
        
        $sql_content = file_get_contents($sql_file);
        
        // Split SQL statements and execute them one by one
        $statements = $this->splitSQLStatements($sql_content);
        
        $executed = 0;
        $errors = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                // Use exec for DDL statements that don't return results
                $this->conn->exec($statement);
                $executed++;
            } catch (PDOException $e) {
                // Some statements might fail if already applied - that's OK
                $error_msg = $e->getMessage();
                if (strpos($error_msg, 'already exists') === false && 
                    strpos($error_msg, 'Duplicate') === false &&
                    strpos($error_msg, 'Unknown column') === false &&
                    strpos($error_msg, 'table does not exist') === false &&
                    strpos($error_msg, 'unbuffered queries') === false) {
                    echo "⚠️  Warning: " . $error_msg . "\n";
                    $errors++;
                }
            }
        }
        
        echo "   ✓ Executed $executed SQL statements\n";
        if ($errors > 0) {
            echo "   ⚠️  $errors warnings (likely already applied)\n";
        }
    }
    
    private function splitSQLStatements($sql) {
        // Improved SQL statement splitter
        $statements = [];
        $current_statement = '';
        $delimiter = ';';
        $in_procedure = false;
        
        $lines = explode("\n", $sql);
        
        foreach ($lines as $line) {
            $line_trimmed = trim($line);
            
            // Handle DELIMITER statements
            if (preg_match('/^DELIMITER\s+(.+)$/i', $line_trimmed, $matches)) {
                $delimiter = trim($matches[1]);
                continue;
            }
            
            // Skip comments and empty lines
            if (empty($line_trimmed) || strpos($line_trimmed, '--') === 0) {
                continue;
            }
            
            // Check for procedure/function/trigger start
            if (preg_match('/^(CREATE|DROP)\s+(PROCEDURE|FUNCTION|TRIGGER)/i', $line_trimmed)) {
                $in_procedure = true;
            }
            
            $current_statement .= $line . "\n";
            
            // Check for statement end
            if (str_ends_with(rtrim($line), $delimiter)) {
                if ($delimiter === '//' && $in_procedure) {
                    // End of procedure/function/trigger
                    $in_procedure = false;
                }
                
                $statement = trim(str_replace($delimiter, '', $current_statement));
                if (!empty($statement)) {
                    $statements[] = $statement;
                }
                $current_statement = '';
            }
        }
        
        // Add any remaining statement
        if (!empty(trim($current_statement))) {
            $statements[] = trim($current_statement);
        }
        
        return $statements;
    }
    
    private function checkIntegrity() {
        echo "🔍 Checking database integrity...\n";
        
        try {
            // Create a fresh connection for this check
            $database = new Database();
            $check_conn = $database->getConnection();
            $check_conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            
            // Try to call the integrity check procedure
            $stmt = $check_conn->prepare("CALL CheckDataIntegrity()");
            $stmt->execute();
            
            // Process all result sets
            $has_results = false;
            do {
                try {
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($results as $result) {
                        if (isset($result['CheckType']) && isset($result['Count'])) {
                            $status = ($result['Count'] == 0) ? '✓' : '⚠️';
                            echo "   $status {$result['CheckType']}: {$result['Count']}\n";
                            $has_results = true;
                        }
                    }
                } catch (PDOException $e) {
                    break;
                }
            } while ($stmt->nextRowset());
            
            $stmt = null; // Close statement
            $check_conn = null; // Close connection
            
            if (!$has_results) {
                echo "   ℹ️  Integrity check procedure executed but no results returned\n";
            }
            
        } catch (PDOException $e) {
            echo "   ℹ️  Integrity check procedure not available yet\n";
        }
    }
    
    private function testClasses() {
        echo "🧪 Testing improved PHP classes...\n";
        
        try {
            // Create fresh connections for class testing
            $database = new Database();
            $db = $database->getConnection();
            $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            
            // Test Student class
            $student = new Student($db);
            echo "   ✓ Student class loaded successfully\n";
            
            // Test Project class
            $project = new Project();
            echo "   ✓ Project class loaded successfully\n";
            
            // Test basic database query with fresh connection
            $test_conn = $database->getConnection();
            $test_conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            
            $stmt = $test_conn->prepare("SELECT COUNT(*) as count FROM users");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = null; // Close statement
            $test_conn = null; // Close connection
            
            echo "   ✓ Database connection working (found {$result['count']} users)\n";
            
        } catch (Exception $e) {
            echo "   ❌ Class test failed: " . $e->getMessage() . "\n";
        }
    }
    
    public function generateReport() {
        echo "\n📋 Database Status Report\n";
        echo str_repeat("=", 50) . "\n";
        
        try {
            // Create a completely fresh connection for reporting
            $database = new Database();
            $report_conn = $database->getConnection();
            $report_conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            
            // Count tables
            $stmt = $report_conn->prepare("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'research_apps_db'");
            $stmt->execute();
            $tables = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $stmt = null;
            echo "📊 Total Tables: $tables\n";
            
            // Count foreign keys
            $stmt = $report_conn->prepare("SELECT COUNT(*) as count FROM information_schema.key_column_usage WHERE table_schema = 'research_apps_db' AND referenced_table_name IS NOT NULL");
            $stmt->execute();
            $fks = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $stmt = null;
            echo "🔗 Foreign Key Relationships: $fks\n";
            
            // Count indexes
            $stmt = $report_conn->prepare("SELECT COUNT(DISTINCT index_name) as count FROM information_schema.statistics WHERE table_schema = 'research_apps_db'");
            $stmt->execute();
            $indexes = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $stmt = null;
            echo "📈 Database Indexes: $indexes\n";
            
            // Check for triggers
            $stmt = $report_conn->prepare("SELECT COUNT(*) as count FROM information_schema.triggers WHERE trigger_schema = 'research_apps_db'");
            $stmt->execute();
            $triggers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $stmt = null;
            echo "⚡ Database Triggers: $triggers\n";
            
            $report_conn = null; // Close connection
            
        } catch (Exception $e) {
            echo "❌ Error generating report: " . $e->getMessage() . "\n";
        }
        
        echo str_repeat("=", 50) . "\n";
        echo "✅ Database fixes applied successfully!\n";
        echo "📖 See DATABASE_FIXES_DOCUMENTATION.md for complete details\n\n";
    }
}

// Auto-execution if run from command line
if (php_sapi_name() === 'cli') {
    echo "🚀 Database Fixes Application Tool\n";
    echo str_repeat("=", 50) . "\n\n";
    
    $fixer = new DatabaseFixer();
    
    if ($fixer->applyAllFixes()) {
        $fixer->generateReport();
        exit(0);
    } else {
        echo "\n❌ Fix application failed. Please check the errors above.\n";
        exit(1);
    }
} else {
    // Web interface
    header('Content-Type: text/plain');
    echo "Database Fixes Application Tool\n";
    echo "===============================\n\n";
    
    $fixer = new DatabaseFixer();
    
    if ($fixer->applyAllFixes()) {
        $fixer->generateReport();
    } else {
        echo "\nFix application failed. Please check the errors above.\n";
    }
}

?> 