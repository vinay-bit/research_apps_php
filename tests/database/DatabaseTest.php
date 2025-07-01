<?php
/**
 * Database Tests
 * Tests database schema, constraints, triggers, and data integrity
 */

require_once __DIR__ . '/../TestHelper.php';

class DatabaseTest {
    
    private $conn;
    
    public function __construct() {
        $this->conn = TestConfig::getTestDatabaseConnection();
    }
    
    /**
     * Run all database tests
     */
    public function runTests() {
        $this->testDatabaseConnection();
        $this->testTableStructure();
        $this->testForeignKeyConstraints();
        $this->testUniqueConstraints();
        $this->testTriggers();
        $this->testIndexes();
        $this->testDataIntegrity();
    }
    
    /**
     * Test database connection
     */
    public function testDatabaseConnection() {
        TestHelper::startTest('Database Connection');
        
        try {
            // Test basic connection
            TestHelper::assertNotNull($this->conn, 'Database connection should not be null');
            
            // Test connection is active
            $stmt = $this->conn->query("SELECT 1 as test");
            $result = $stmt->fetch();
            TestHelper::assertEqual(1, $result['test'], 'Database connection should be active');
            
            // Test database exists
            $stmt = $this->conn->query("SELECT DATABASE() as db_name");
            $result = $stmt->fetch();
            TestHelper::assertEqual(TestConfig::DB_NAME, $result['db_name'], 'Should be connected to correct database');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test table structure
     */
    public function testTableStructure() {
        TestHelper::startTest('Table Structure');
        
        try {
            // Get all tables
            $stmt = $this->conn->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Expected core tables
            $expectedTables = [
                'users', 'students', 'projects', 'ready_for_publication',
                'project_students', 'project_statuses', 'organizations',
                'boards', 'subjects', 'departments'
            ];
            
            foreach ($expectedTables as $expectedTable) {
                TestHelper::assertTrue(in_array($expectedTable, $tables), "Table '$expectedTable' should exist");
            }
            
            // Test key table structures
            $this->testUsersTableStructure();
            $this->testStudentsTableStructure();
            $this->testProjectsTableStructure();
            $this->testReadyForPublicationTableStructure();
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test users table structure
     */
    private function testUsersTableStructure() {
        $stmt = $this->conn->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $columnNames = array_column($columns, 'Field');
        $requiredColumns = ['id', 'username', 'full_name', 'email_id', 'password', 'user_type'];
        
        foreach ($requiredColumns as $requiredColumn) {
            TestHelper::assertTrue(in_array($requiredColumn, $columnNames), "Users table should have '$requiredColumn' column");
        }
    }
    
    /**
     * Test students table structure
     */
    private function testStudentsTableStructure() {
        $stmt = $this->conn->query("DESCRIBE students");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $columnNames = array_column($columns, 'Field');
        $requiredColumns = ['id', 'student_id', 'full_name', 'email_address', 'grade', 'board_id'];
        
        foreach ($requiredColumns as $requiredColumn) {
            TestHelper::assertTrue(in_array($requiredColumn, $columnNames), "Students table should have '$requiredColumn' column");
        }
    }
    
    /**
     * Test projects table structure
     */
    private function testProjectsTableStructure() {
        $stmt = $this->conn->query("DESCRIBE projects");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $columnNames = array_column($columns, 'Field');
        $requiredColumns = ['id', 'project_id', 'project_name', 'description', 'status_id'];
        
        foreach ($requiredColumns as $requiredColumn) {
            TestHelper::assertTrue(in_array($requiredColumn, $columnNames), "Projects table should have '$requiredColumn' column");
        }
    }
    
    /**
     * Test ready_for_publication table structure
     */
    private function testReadyForPublicationTableStructure() {
        $stmt = $this->conn->query("DESCRIBE ready_for_publication");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $columnNames = array_column($columns, 'Field');
        $requiredColumns = ['id', 'project_id', 'paper_title', 'status', 'created_at'];
        
        foreach ($requiredColumns as $requiredColumn) {
            TestHelper::assertTrue(in_array($requiredColumn, $columnNames), "ready_for_publication table should have '$requiredColumn' column");
        }
    }
    
    /**
     * Test foreign key constraints
     */
    public function testForeignKeyConstraints() {
        TestHelper::startTest('Foreign Key Constraints');
        
        try {
            // Get foreign key constraints
            $stmt = $this->conn->query("
                SELECT 
                    TABLE_NAME,
                    COLUMN_NAME,
                    CONSTRAINT_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE REFERENCED_TABLE_SCHEMA = '" . TestConfig::DB_NAME . "'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            TestHelper::assertTrue(count($foreignKeys) > 0, 'Database should have foreign key constraints');
            
            // Test specific foreign key constraints exist
            $expectedConstraints = [
                ['table' => 'students', 'column' => 'board_id', 'references' => 'boards'],
                ['table' => 'projects', 'column' => 'status_id', 'references' => 'project_statuses'],
                ['table' => 'ready_for_publication', 'column' => 'project_id', 'references' => 'projects'],
                ['table' => 'project_students', 'column' => 'project_id', 'references' => 'projects']
            ];
            
            foreach ($expectedConstraints as $expected) {
                $found = false;
                foreach ($foreignKeys as $fk) {
                    if ($fk['TABLE_NAME'] === $expected['table'] && 
                        $fk['COLUMN_NAME'] === $expected['column'] && 
                        $fk['REFERENCED_TABLE_NAME'] === $expected['references']) {
                        $found = true;
                        break;
                    }
                }
                TestHelper::assertTrue($found, "Foreign key constraint {$expected['table']}.{$expected['column']} -> {$expected['references']} should exist");
            }
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test unique constraints
     */
    public function testUniqueConstraints() {
        TestHelper::startTest('Unique Constraints');
        
        try {
            // Test user email uniqueness
            TestHelper::createTestUser(['email_id' => 'unique@example.com']);
            
            TestHelper::assertThrows(function() {
                TestHelper::createTestUser(['email_id' => 'unique@example.com']);
            }, 'PDOException', 'Duplicate email should violate unique constraint');
            
            // Test student ID uniqueness
            TestHelper::createTestStudent(['student_id' => 'STU2025TEST']);
            
            TestHelper::assertThrows(function() {
                TestHelper::createTestStudent(['student_id' => 'STU2025TEST']);
            }, 'PDOException', 'Duplicate student ID should violate unique constraint');
            
            // Test project-publication uniqueness
            TestHelper::createTestDependencies();
            $projectId = TestHelper::createTestProject();
            
            $stmt = $this->conn->prepare("INSERT INTO ready_for_publication (project_id, paper_title, status) VALUES (?, 'Test Paper', 'pending')");
            $stmt->execute([$projectId]);
            
            TestHelper::assertThrows(function() use ($projectId) {
                $stmt = $this->conn->prepare("INSERT INTO ready_for_publication (project_id, paper_title, status) VALUES (?, 'Another Paper', 'pending')");
                $stmt->execute([$projectId]);
            }, 'PDOException', 'Duplicate project in ready_for_publication should fail');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test database triggers
     */
    public function testTriggers() {
        TestHelper::startTest('Database Triggers');
        
        try {
            // Check if triggers exist
            $stmt = $this->conn->query("SHOW TRIGGERS");
            $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Look for ID generation triggers
            $triggerNames = array_column($triggers, 'Trigger');
            $expectedTriggers = ['generate_student_id', 'generate_project_id'];
            
            foreach ($expectedTriggers as $expectedTrigger) {
                $found = false;
                foreach ($triggerNames as $triggerName) {
                    if (strpos($triggerName, $expectedTrigger) !== false) {
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    TestConfig::log("Trigger '$expectedTrigger' found");
                }
            }
            
            // Test trigger functionality by creating records
            $studentBefore = new Student($this->conn);
            $studentBefore->full_name = 'Trigger Test Student';
            $studentBefore->email_address = 'trigger@example.com';
            $studentBefore->grade = '12th';
            $studentBefore->board_id = 1;
            $studentBefore->counselor_id = 1;
            $studentBefore->rbm_id = 1;
            $studentBefore->application_year = date('Y');
            $studentBefore->create();
            
            TestHelper::assertNotNull($studentBefore->student_id, 'Student ID should be generated by trigger');
            TestHelper::assertTrue(preg_match('/^STU' . date('Y') . '\d{4}$/', $studentBefore->student_id), 'Generated student ID should match pattern');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test database indexes
     */
    public function testIndexes() {
        TestHelper::startTest('Database Indexes');
        
        try {
            // Check for indexes on key columns
            $tables = ['users', 'students', 'projects', 'ready_for_publication'];
            
            foreach ($tables as $table) {
                $stmt = $this->conn->query("SHOW INDEX FROM $table");
                $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                TestHelper::assertTrue(count($indexes) > 0, "Table '$table' should have indexes");
                
                // Every table should at least have primary key index
                $hasPrimaryKey = false;
                foreach ($indexes as $index) {
                    if ($index['Key_name'] === 'PRIMARY') {
                        $hasPrimaryKey = true;
                        break;
                    }
                }
                TestHelper::assertTrue($hasPrimaryKey, "Table '$table' should have primary key index");
            }
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test data integrity
     */
    public function testDataIntegrity() {
        TestHelper::startTest('Data Integrity');
        
        try {
            TestHelper::createTestDependencies();
            
            // Create related data
            $projectId = TestHelper::createTestProject();
            $studentId = TestHelper::createTestStudent();
            
            // Test cascading deletes work properly
            $stmt = $this->conn->prepare("INSERT INTO project_students (project_id, student_id, assigned_date) VALUES (?, ?, NOW())");
            $stmt->execute([$projectId, $studentId]);
            
            // Verify relationship exists
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM project_students WHERE project_id = ? AND student_id = ?");
            $stmt->execute([$projectId, $studentId]);
            $relationshipCount = $stmt->fetchColumn();
            TestHelper::assertEqual(1, $relationshipCount, 'Project-student relationship should exist');
            
            // Test constraint validation - try to insert invalid foreign key
            TestHelper::assertThrows(function() {
                $stmt = $this->conn->prepare("INSERT INTO project_students (project_id, student_id, assigned_date) VALUES (?, ?, NOW())");
                $stmt->execute([99999, 99999]); // Non-existent IDs
            }, 'PDOException', 'Invalid foreign keys should be rejected');
            
            // Test data consistency
            $stmt = $this->conn->query("
                SELECT COUNT(*) as orphaned_publications
                FROM ready_for_publication rfp
                LEFT JOIN projects p ON rfp.project_id = p.id
                WHERE p.id IS NULL
            ");
            $orphanedCount = $stmt->fetchColumn();
            TestHelper::assertEqual(0, $orphanedCount, 'There should be no orphaned publications');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
}
?> 