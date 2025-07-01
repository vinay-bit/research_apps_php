<?php
/**
 * Test Database Fixes
 * This script tests all the database fixes to ensure they're working correctly
 */

require_once 'config/database.php';
require_once 'classes/Student.php';
require_once 'classes/Project.php';

class DatabaseFixTester {
    private $conn;
    private $test_results = [];
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        // Enable buffered queries and set proper PDO attributes
        $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    public function runAllTests() {
        echo "🧪 Starting Database Fix Testing...\n\n";
        
        try {
            $this->testForeignKeyConstraints();
            $this->testIDGeneration();
            $this->testTriggers();
            $this->testProcedures();
            $this->testIndexes();
            $this->testClassMethods();
            
            $this->displayResults();
            
        } catch (Exception $e) {
            echo "❌ Critical error during testing: " . $e->getMessage() . "\n";
            return false;
        }
        
        return true;
    }
    
    private function testForeignKeyConstraints() {
        echo "🔗 Testing Foreign Key Constraints...\n";
        
        try {
            // Create fresh connection for FK testing
            $database = new Database();
            $fk_conn = $database->getConnection();
            $fk_conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            
            // Test 1: Count foreign keys
            $stmt = $fk_conn->prepare("
                SELECT COUNT(*) as fk_count 
                FROM information_schema.key_column_usage 
                WHERE table_schema = 'research_apps_db' 
                AND referenced_table_name IS NOT NULL
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = null;
            
            $fk_count = $result['fk_count'];
            $this->test_results[] = [
                'test' => 'Foreign Key Count',
                'expected' => '> 20',
                'actual' => $fk_count,
                'status' => $fk_count > 20 ? 'PASS' : 'FAIL',
                'description' => "Found $fk_count foreign key constraints"
            ];
            
            // Test 2: Check specific critical foreign keys
            $critical_fks = [
                ['table' => 'projects', 'column' => 'lead_mentor_id', 'ref_table' => 'users'],
                ['table' => 'projects', 'column' => 'status_id', 'ref_table' => 'project_statuses'],
                ['table' => 'students', 'column' => 'board_id', 'ref_table' => 'boards']
            ];
            
            foreach ($critical_fks as $fk) {
                $stmt = $fk_conn->prepare("
                    SELECT COUNT(*) as exists_count
                    FROM information_schema.key_column_usage
                    WHERE table_schema = 'research_apps_db'
                    AND table_name = ?
                    AND column_name = ?
                    AND referenced_table_name = ?
                ");
                $stmt->execute([$fk['table'], $fk['column'], $fk['ref_table']]);
                $exists = $stmt->fetch(PDO::FETCH_ASSOC)['exists_count'] > 0;
                $stmt = null;
                
                $this->test_results[] = [
                    'test' => "FK: {$fk['table']}.{$fk['column']}",
                    'expected' => 'EXISTS',
                    'actual' => $exists ? 'EXISTS' : 'MISSING',
                    'status' => $exists ? 'PASS' : 'FAIL',
                    'description' => "Foreign key constraint for {$fk['table']}.{$fk['column']} -> {$fk['ref_table']}"
                ];
            }
            
            $fk_conn = null; // Close connection
            echo "   ✓ Foreign key constraint tests completed\n";
            
        } catch (Exception $e) {
            echo "   ❌ Foreign key test failed: " . $e->getMessage() . "\n";
            $this->test_results[] = [
                'test' => 'Foreign Keys',
                'expected' => 'SUCCESS',
                'actual' => 'ERROR',
                'status' => 'FAIL',
                'description' => $e->getMessage()
            ];
        }
    }
    
    private function testIDGeneration() {
        echo "🆔 Testing ID Generation...\n";
        
        try {
            // Create fresh connection for ID testing
            $database = new Database();
            $id_conn = $database->getConnection();
            $id_conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            
            // Test student ID generation
            $student = new Student($id_conn);
            $student->full_name = 'Test Student ID Generation';
            $student->email_address = 'test.id@example.com';
            $student->board_id = 1;
            $student->counselor_id = 1;
            $student->grade = '12th';
            $student->application_year = 2025;
            
            $create_result = $student->create();
            
            if ($create_result && !empty($student->student_id)) {
                $generated_id = $student->student_id;
                $pattern_match = preg_match('/^STU2025\d{4}$/', $generated_id);
                
                $this->test_results[] = [
                    'test' => 'Student ID Generation',
                    'expected' => 'STU2025XXXX format',
                    'actual' => $generated_id,
                    'status' => $pattern_match ? 'PASS' : 'FAIL',
                    'description' => "Generated student ID: $generated_id"
                ];
                
                // Clean up test student
                $stmt = $id_conn->prepare("DELETE FROM students WHERE student_id = ?");
                $stmt->execute([$generated_id]);
                $stmt = null;
                
            } else {
                $this->test_results[] = [
                    'test' => 'Student ID Generation',
                    'expected' => 'SUCCESS',
                    'actual' => 'FAILED',
                    'status' => 'FAIL',
                    'description' => 'Could not generate student ID'
                ];
            }
            
            $id_conn = null; // Close connection
            echo "   ✓ ID generation tests completed\n";
            
        } catch (Exception $e) {
            echo "   ❌ ID generation test failed: " . $e->getMessage() . "\n";
            $this->test_results[] = [
                'test' => 'ID Generation',
                'expected' => 'SUCCESS',
                'actual' => 'ERROR',
                'status' => 'FAIL',
                'description' => $e->getMessage()
            ];
        }
    }
    
    private function testTriggers() {
        echo "⚡ Testing Database Triggers...\n";
        
        try {
            // Create fresh connection for trigger testing
            $database = new Database();
            $trigger_conn = $database->getConnection();
            $trigger_conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            
            // Count triggers
            $stmt = $trigger_conn->prepare("
                SELECT COUNT(*) as trigger_count 
                FROM information_schema.triggers 
                WHERE trigger_schema = 'research_apps_db'
            ");
            $stmt->execute();
            $trigger_count = $stmt->fetch(PDO::FETCH_ASSOC)['trigger_count'];
            $stmt = null;
            
            $this->test_results[] = [
                'test' => 'Database Triggers',
                'expected' => '> 0',
                'actual' => $trigger_count,
                'status' => $trigger_count > 0 ? 'PASS' : 'FAIL',
                'description' => "Found $trigger_count database triggers"
            ];
            
            // Check for specific triggers
            $expected_triggers = [
                'generate_student_id',
                'generate_project_id'
            ];
            
            foreach ($expected_triggers as $trigger_name) {
                $stmt = $trigger_conn->prepare("
                    SELECT COUNT(*) as exists_count
                    FROM information_schema.triggers
                    WHERE trigger_schema = 'research_apps_db'
                    AND trigger_name = ?
                ");
                $stmt->execute([$trigger_name]);
                $exists = $stmt->fetch(PDO::FETCH_ASSOC)['exists_count'] > 0;
                $stmt = null;
                
                $this->test_results[] = [
                    'test' => "Trigger: $trigger_name",
                    'expected' => 'EXISTS',
                    'actual' => $exists ? 'EXISTS' : 'MISSING',
                    'status' => $exists ? 'PASS' : 'FAIL',
                    'description' => "Trigger $trigger_name"
                ];
            }
            
            $trigger_conn = null; // Close connection
            echo "   ✓ Trigger tests completed\n";
            
        } catch (Exception $e) {
            echo "   ❌ Trigger test failed: " . $e->getMessage() . "\n";
            $this->test_results[] = [
                'test' => 'Triggers',
                'expected' => 'SUCCESS',
                'actual' => 'ERROR',
                'status' => 'FAIL',
                'description' => $e->getMessage()
            ];
        }
    }
    
    private function testProcedures() {
        echo "🔧 Testing Stored Procedures...\n";
        
        try {
            // Create fresh connection for procedure testing
            $database = new Database();
            $proc_conn = $database->getConnection();
            $proc_conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            
            // Test CheckDataIntegrity procedure
            try {
                $stmt = $proc_conn->prepare("CALL CheckDataIntegrity()");
                $stmt->execute();
                
                $procedure_works = true;
                // Process all result sets
                do {
                    try {
                        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        // Just consume the results
                    } catch (PDOException $e) {
                        break;
                    }
                } while ($stmt->nextRowset());
                
                $stmt = null;
                
                $this->test_results[] = [
                    'test' => 'CheckDataIntegrity Procedure',
                    'expected' => 'CALLABLE',
                    'actual' => 'CALLABLE',
                    'status' => 'PASS',
                    'description' => 'Data integrity check procedure executed successfully'
                ];
                
            } catch (PDOException $e) {
                $this->test_results[] = [
                    'test' => 'CheckDataIntegrity Procedure',
                    'expected' => 'CALLABLE',
                    'actual' => 'ERROR',
                    'status' => 'FAIL',
                    'description' => 'Procedure not found or failed: ' . $e->getMessage()
                ];
            }
            
            $proc_conn = null; // Close connection
            echo "   ✓ Procedure tests completed\n";
            
        } catch (Exception $e) {
            echo "   ❌ Procedure test failed: " . $e->getMessage() . "\n";
            $this->test_results[] = [
                'test' => 'Procedures',
                'expected' => 'SUCCESS',
                'actual' => 'ERROR',
                'status' => 'FAIL',
                'description' => $e->getMessage()
            ];
        }
    }
    
    private function testIndexes() {
        echo "📈 Testing Database Indexes...\n";
        
        try {
            // Create fresh connection for index testing
            $database = new Database();
            $index_conn = $database->getConnection();
            $index_conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            
            // Count total indexes
            $stmt = $index_conn->prepare("
                SELECT COUNT(DISTINCT index_name) as index_count 
                FROM information_schema.statistics 
                WHERE table_schema = 'research_apps_db'
            ");
            $stmt->execute();
            $index_count = $stmt->fetch(PDO::FETCH_ASSOC)['index_count'];
            $stmt = null;
            
            $this->test_results[] = [
                'test' => 'Database Indexes',
                'expected' => '> 15',
                'actual' => $index_count,
                'status' => $index_count > 15 ? 'PASS' : 'FAIL',
                'description' => "Found $index_count database indexes"
            ];
            
            // Check for specific performance indexes
            $critical_indexes = [
                ['table' => 'projects', 'column' => 'lead_mentor_id'],
                ['table' => 'projects', 'column' => 'status_id'],
                ['table' => 'students', 'column' => 'board_id']
            ];
            
            foreach ($critical_indexes as $idx) {
                $stmt = $index_conn->prepare("
                    SELECT COUNT(*) as exists_count
                    FROM information_schema.statistics
                    WHERE table_schema = 'research_apps_db'
                    AND table_name = ?
                    AND column_name = ?
                ");
                $stmt->execute([$idx['table'], $idx['column']]);
                $exists = $stmt->fetch(PDO::FETCH_ASSOC)['exists_count'] > 0;
                $stmt = null;
                
                $this->test_results[] = [
                    'test' => "Index: {$idx['table']}.{$idx['column']}",
                    'expected' => 'EXISTS',
                    'actual' => $exists ? 'EXISTS' : 'MISSING',
                    'status' => $exists ? 'PASS' : 'FAIL',
                    'description' => "Performance index for {$idx['table']}.{$idx['column']}"
                ];
            }
            
            $index_conn = null; // Close connection
            echo "   ✓ Index tests completed\n";
            
        } catch (Exception $e) {
            echo "   ❌ Index test failed: " . $e->getMessage() . "\n";
            $this->test_results[] = [
                'test' => 'Indexes',
                'expected' => 'SUCCESS',
                'actual' => 'ERROR',
                'status' => 'FAIL',
                'description' => $e->getMessage()
            ];
        }
    }
    
    private function testClassMethods() {
        echo "🎯 Testing Enhanced Class Methods...\n";
        
        try {
            // Create fresh connection for class testing
            $database = new Database();
            $class_conn = $database->getConnection();
            $class_conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            
            // Test Student class instantiation
            $student = new Student($class_conn);
            $this->test_results[] = [
                'test' => 'Student Class Loading',
                'expected' => 'SUCCESS',
                'actual' => 'SUCCESS',
                'status' => 'PASS',
                'description' => 'Student class loaded and instantiated successfully'
            ];
            
            // Test Project class instantiation
            $project = new Project();
            $this->test_results[] = [
                'test' => 'Project Class Loading',
                'expected' => 'SUCCESS',
                'actual' => 'SUCCESS',
                'status' => 'PASS',
                'description' => 'Project class loaded and instantiated successfully'
            ];
            
            // Test basic database connectivity
            $stmt = $class_conn->prepare("SELECT COUNT(*) as count FROM users LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = null;
            
            $this->test_results[] = [
                'test' => 'Database Connectivity',
                'expected' => 'SUCCESS',
                'actual' => 'SUCCESS',
                'status' => 'PASS',
                'description' => "Database connection working, found {$result['count']} users"
            ];
            
            $class_conn = null; // Close connection
            echo "   ✓ Class method tests completed\n";
            
        } catch (Exception $e) {
            echo "   ❌ Class method test failed: " . $e->getMessage() . "\n";
            $this->test_results[] = [
                'test' => 'Class Methods',
                'expected' => 'SUCCESS',
                'actual' => 'ERROR',
                'status' => 'FAIL',
                'description' => $e->getMessage()
            ];
        }
    }
    
    private function displayResults() {
        echo "\n📊 Test Results Summary\n";
        echo str_repeat("=", 80) . "\n";
        
        $total_tests = count($this->test_results);
        $passed_tests = 0;
        $failed_tests = 0;
        
        foreach ($this->test_results as $result) {
            $status_icon = $result['status'] === 'PASS' ? '✅' : '❌';
            $status_color = $result['status'] === 'PASS' ? '' : '';
            
            printf("%-50s %s %s\n", 
                $result['test'], 
                $status_icon, 
                $result['status']
            );
            
            if ($result['status'] === 'PASS') {
                $passed_tests++;
            } else {
                $failed_tests++;
                echo "   Error: " . $result['description'] . "\n";
            }
        }
        
        echo str_repeat("-", 80) . "\n";
        echo sprintf("Total Tests: %d | Passed: %d | Failed: %d\n", 
            $total_tests, $passed_tests, $failed_tests);
        
        $success_rate = ($passed_tests / $total_tests) * 100;
        echo sprintf("Success Rate: %.1f%%\n", $success_rate);
        
        if ($failed_tests === 0) {
            echo "\n🎉 All tests passed! Database fixes are working correctly.\n";
        } else {
            echo "\n⚠️  Some tests failed. Please check the errors above.\n";
        }
        
        echo str_repeat("=", 80) . "\n";
    }
}

// Auto-execution if run from command line
if (php_sapi_name() === 'cli') {
    echo "🚀 Database Fix Testing Tool\n";
    echo str_repeat("=", 50) . "\n\n";
    
    $tester = new DatabaseFixTester();
    
    if ($tester->runAllTests()) {
        exit(0);
    } else {
        echo "\n❌ Testing failed. Please check the errors above.\n";
        exit(1);
    }
} else {
    // Web interface
    header('Content-Type: text/plain');
    echo "Database Fix Testing Tool\n";
    echo "=========================\n\n";
    
    $tester = new DatabaseFixTester();
    $tester->runAllTests();
}

?> 