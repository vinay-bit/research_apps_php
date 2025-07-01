<?php
/**
 * Unit Tests for Student Class
 */

require_once __DIR__ . '/../TestHelper.php';
require_once __DIR__ . '/../../classes/Student.php';

class StudentTest {
    
    private $conn;
    private $student;
    
    public function __construct() {
        $this->conn = TestConfig::getTestDatabaseConnection();
        $this->student = new Student($this->conn);
    }
    
    /**
     * Run all student tests
     */
    public function runTests() {
        TestHelper::createTestDependencies();
        
        $this->testStudentCreation();
        $this->testStudentIDGeneration();
        $this->testStudentUpdate();
        $this->testStudentValidation();
        $this->testStudentDeletion();
        $this->testStudentSearch();
        $this->testStudentAssignments();
    }
    
    /**
     * Test student creation
     */
    public function testStudentCreation() {
        TestHelper::startTest('Student Creation');
        
        try {
            // Test valid student creation
            $this->student->full_name = 'Test Student';
            $this->student->email_address = 'student@example.com';
            $this->student->grade = '12th';
            $this->student->board_id = 1;
            $this->student->counselor_id = 1;
            $this->student->rbm_id = 1;
            $this->student->application_year = date('Y');
            
            $result = $this->student->create();
            TestHelper::assertTrue($result, 'Student creation should succeed');
            TestHelper::assertNotNull($this->student->student_id, 'Student ID should be generated');
            TestHelper::assertNotNull($this->student->id, 'Database ID should be set');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test student ID generation
     */
    public function testStudentIDGeneration() {
        TestHelper::startTest('Student ID Generation');
        
        try {
            // Create multiple students to test ID generation
            $student1 = new Student($this->conn);
            $student1->full_name = 'Student One';
            $student1->email_address = 'student1@example.com';
            $student1->grade = '12th';
            $student1->board_id = 1;
            $student1->counselor_id = 1;
            $student1->rbm_id = 1;
            $student1->application_year = date('Y');
            $student1->create();
            
            $student2 = new Student($this->conn);
            $student2->full_name = 'Student Two';
            $student2->email_address = 'student2@example.com';
            $student2->grade = '11th';
            $student2->board_id = 1;
            $student2->counselor_id = 1;
            $student2->rbm_id = 1;
            $student2->application_year = date('Y');
            $student2->create();
            
            // Verify ID format
            $pattern = '/^STU' . date('Y') . '\d{4}$/';
            TestHelper::assertTrue(preg_match($pattern, $student1->student_id), 'Student ID should match pattern STU2025XXXX');
            TestHelper::assertTrue(preg_match($pattern, $student2->student_id), 'Student ID should match pattern STU2025XXXX');
            
            // Verify IDs are unique
            TestHelper::assertNotEqual($student1->student_id, $student2->student_id, 'Student IDs should be unique');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test student update
     */
    public function testStudentUpdate() {
        TestHelper::startTest('Student Update');
        
        try {
            // Create test student
            $studentId = TestHelper::createTestStudent([
                'full_name' => 'Original Student Name'
            ]);
            
            // Update student
            $updateStudent = new Student($this->conn);
            $updateStudent->id = $studentId;
            $updateStudent->readOne();
            
            $updateStudent->full_name = 'Updated Student Name';
            $updateStudent->grade = '11th';
            
            $result = $updateStudent->update();
            TestHelper::assertTrue($result, 'Student update should succeed');
            
            // Verify update
            $verifyStudent = new Student($this->conn);
            $verifyStudent->id = $studentId;
            $verifyStudent->readOne();
            
            TestHelper::assertEqual('Updated Student Name', $verifyStudent->full_name, 'Name should be updated');
            TestHelper::assertEqual('11th', $verifyStudent->grade, 'Grade should be updated');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test student validation
     */
    public function testStudentValidation() {
        TestHelper::startTest('Student Validation');
        
        try {
            // Test missing required fields
            $invalidStudent = new Student($this->conn);
            
            TestHelper::assertThrows(function() use ($invalidStudent) {
                $invalidStudent->create();
            }, 'Exception', 'Missing full name should throw exception');
            
            // Test invalid foreign key references
            $invalidFKStudent = new Student($this->conn);
            $invalidFKStudent->full_name = 'Test Student';
            $invalidFKStudent->email_address = 'test@example.com';
            $invalidFKStudent->grade = '12th';
            $invalidFKStudent->board_id = 999; // Non-existent board
            $invalidFKStudent->counselor_id = 1;
            $invalidFKStudent->rbm_id = 1;
            $invalidFKStudent->application_year = date('Y');
            
            TestHelper::assertThrows(function() use ($invalidFKStudent) {
                $invalidFKStudent->create();
            }, 'Exception', 'Invalid board ID should throw exception');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test student deletion
     */
    public function testStudentDeletion() {
        TestHelper::startTest('Student Deletion');
        
        try {
            // Create test student
            $studentId = TestHelper::createTestStudent();
            
            // Delete student
            $deleteStudent = new Student($this->conn);
            $deleteStudent->id = $studentId;
            
            $result = $deleteStudent->delete();
            TestHelper::assertTrue($result, 'Student deletion should succeed');
            
            // Verify deletion
            $verifyStudent = new Student($this->conn);
            $verifyStudent->id = $studentId;
            $found = $verifyStudent->readOne();
            TestHelper::assertFalse($found, 'Deleted student should not be found');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test student search functionality
     */
    public function testStudentSearch() {
        TestHelper::startTest('Student Search');
        
        try {
            // Create test students
            TestHelper::createTestStudent([
                'full_name' => 'Alice Johnson',
                'student_id' => 'STU2025001'
            ]);
            TestHelper::createTestStudent([
                'full_name' => 'Bob Smith',
                'student_id' => 'STU2025002'
            ]);
            
            // Test search by name
            $searchStudent = new Student($this->conn);
            $results = $searchStudent->search('Alice')->fetchAll(PDO::FETCH_ASSOC);
            TestHelper::assertTrue(count($results) > 0, 'Search by name should return results');
            
            // Test search by student ID
            $idResults = $searchStudent->search('STU2025001')->fetchAll(PDO::FETCH_ASSOC);
            TestHelper::assertTrue(count($idResults) > 0, 'Search by student ID should return results');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test student assignments (counselor, RBM)
     */
    public function testStudentAssignments() {
        TestHelper::startTest('Student Assignments');
        
        try {
            // Create test users
            $counselorId = TestHelper::createTestUser(['user_type' => 'councillor']);
            $rbmId = TestHelper::createTestUser(['user_type' => 'rbm']);
            
            // Create student with assignments
            $student = new Student($this->conn);
            $student->full_name = 'Assignment Test Student';
            $student->email_address = 'assignment@example.com';
            $student->grade = '12th';
            $student->board_id = 1;
            $student->counselor_id = $counselorId;
            $student->rbm_id = $rbmId;
            $student->application_year = date('Y');
            
            $result = $student->create();
            TestHelper::assertTrue($result, 'Student with assignments should be created');
            
            // Verify assignments
            $verifyStudent = new Student($this->conn);
            $verifyStudent->id = $student->id;
            $verifyStudent->readOne();
            
            TestHelper::assertEqual((string)$counselorId, (string)$verifyStudent->counselor_id, 'Counselor assignment should be correct');
            TestHelper::assertEqual((string)$rbmId, (string)$verifyStudent->rbm_id, 'RBM assignment should be correct');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
}
?> 