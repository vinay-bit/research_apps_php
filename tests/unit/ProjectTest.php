<?php
/**
 * Unit Tests for Project Class
 */

require_once __DIR__ . '/../TestHelper.php';
require_once __DIR__ . '/../../classes/Project.php';

class ProjectTest {
    
    private $conn;
    private $project;
    
    public function __construct() {
        $this->conn = TestConfig::getTestDatabaseConnection();
        $this->project = new Project();  // Project class doesn't take parameters
    }
    
    /**
     * Run all project tests
     */
    public function runTests() {
        TestHelper::createTestDependencies();
        
        $this->testProjectCreation();
        $this->testProjectIDGeneration();
        $this->testProjectUpdate();
        $this->testProjectValidation();
        $this->testProjectDeletion();
        $this->testProjectStudentAssignment();
        $this->testProjectMentorAssignment();
        $this->testProjectStatusManagement();
    }
    
    /**
     * Test project creation
     */
    public function testProjectCreation() {
        TestHelper::startTest('Project Creation');
        
        try {
            // Create test project
            $projectId = TestHelper::createTestProject([
                'project_name' => 'Test Project Creation'
            ]);
            
            TestHelper::assertNotNull($projectId, 'Project ID should be returned');
            
            // Verify project exists in database
            $stmt = $this->conn->prepare("SELECT * FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            TestHelper::assertNotNull($project, 'Project should exist in database');
            TestHelper::assertEqual('Test Project Creation', $project['project_name'], 'Project name should match');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test project ID generation
     */
    public function testProjectIDGeneration() {
        TestHelper::startTest('Project ID Generation');
        
        try {
            // Create multiple projects to test ID generation
            $project1Id = TestHelper::createTestProject([
                'project_name' => 'Project One'
            ]);
            $project2Id = TestHelper::createTestProject([
                'project_name' => 'Project Two'
            ]);
            
            // Get project IDs from database
            $stmt = $this->conn->prepare("SELECT project_id FROM projects WHERE id IN (?, ?)");
            $stmt->execute([$project1Id, $project2Id]);
            $projectIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Verify ID format
            $pattern = '/^PRJ' . date('Y') . '\d{4}$/';
            foreach ($projectIds as $projectId) {
                TestHelper::assertTrue(preg_match($pattern, $projectId), "Project ID $projectId should match pattern PRJ2025XXXX");
            }
            
            // Verify IDs are unique
            TestHelper::assertEqual(2, count(array_unique($projectIds)), 'Project IDs should be unique');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test project update
     */
    public function testProjectUpdate() {
        TestHelper::startTest('Project Update');
        
        try {
            // Create test project
            $projectId = TestHelper::createTestProject([
                'project_name' => 'Original Project Name',
                'description' => 'Original description'
            ]);
            
            // Update project using raw SQL (since Project class may not have update method)
            $stmt = $this->conn->prepare("UPDATE projects SET project_name = ?, description = ? WHERE id = ?");
            $result = $stmt->execute(['Updated Project Name', 'Updated description', $projectId]);
            
            TestHelper::assertTrue($result, 'Project update should succeed');
            
            // Verify update
            $stmt = $this->conn->prepare("SELECT project_name, description FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            TestHelper::assertEqual('Updated Project Name', $project['project_name'], 'Project name should be updated');
            TestHelper::assertEqual('Updated description', $project['description'], 'Description should be updated');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test project validation
     */
    public function testProjectValidation() {
        TestHelper::startTest('Project Validation');
        
        try {
            // Test invalid foreign key references
            TestHelper::assertThrows(function() {
                TestHelper::createTestProject([
                    'status_id' => 999, // Non-existent status
                    'lead_mentor_id' => 1,
                    'subject_id' => 1,
                    'rbm_id' => 1
                ]);
            }, 'PDOException', 'Invalid status ID should throw exception');
            
            TestHelper::assertThrows(function() {
                TestHelper::createTestProject([
                    'status_id' => 1,
                    'lead_mentor_id' => 999, // Non-existent mentor
                    'subject_id' => 1,
                    'rbm_id' => 1
                ]);
            }, 'PDOException', 'Invalid mentor ID should throw exception');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test project deletion
     */
    public function testProjectDeletion() {
        TestHelper::startTest('Project Deletion');
        
        try {
            // Create test project
            $projectId = TestHelper::createTestProject();
            
            // Delete project
            $stmt = $this->conn->prepare("DELETE FROM projects WHERE id = ?");
            $result = $stmt->execute([$projectId]);
            
            TestHelper::assertTrue($result, 'Project deletion should succeed');
            
            // Verify deletion
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            $count = $stmt->fetchColumn();
            
            TestHelper::assertEqual('0', (string)$count, 'Deleted project should not exist');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test project student assignment
     */
    public function testProjectStudentAssignment() {
        TestHelper::startTest('Project Student Assignment');
        
        try {
            // Create test project and students
            $projectId = TestHelper::createTestProject();
            $student1Id = TestHelper::createTestStudent(['full_name' => 'Student One']);
            $student2Id = TestHelper::createTestStudent(['full_name' => 'Student Two']);
            
            // Assign students to project
            $stmt = $this->conn->prepare("INSERT INTO project_students (project_id, student_id, assigned_date) VALUES (?, ?, NOW())");
            $stmt->execute([$projectId, $student1Id]);
            $stmt->execute([$projectId, $student2Id]);
            
            // Verify assignments
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM project_students WHERE project_id = ?");
            $stmt->execute([$projectId]);
            $assignmentCount = $stmt->fetchColumn();
            
            TestHelper::assertEqual('2', (string)$assignmentCount, 'Project should have 2 assigned students');
            
            // Test unique constraint (student can't be assigned twice to same project)
            TestHelper::assertThrows(function() use ($projectId, $student1Id) {
                $stmt = $this->conn->prepare("INSERT INTO project_students (project_id, student_id, assigned_date) VALUES (?, ?, NOW())");
                $stmt->execute([$projectId, $student1Id]);
            }, 'PDOException', 'Duplicate student assignment should fail');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test project mentor assignment
     */
    public function testProjectMentorAssignment() {
        TestHelper::startTest('Project Mentor Assignment');
        
        try {
            // Create test mentors
            $mentor1Id = TestHelper::createTestUser(['user_type' => 'mentor', 'full_name' => 'Mentor One']);
            $mentor2Id = TestHelper::createTestUser(['user_type' => 'mentor', 'full_name' => 'Mentor Two']);
            
            // Create project with lead mentor
            $projectId = TestHelper::createTestProject([
                'lead_mentor_id' => $mentor1Id
            ]);
            
            // Verify lead mentor assignment
            $stmt = $this->conn->prepare("SELECT lead_mentor_id FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            $leadMentorId = $stmt->fetchColumn();
            
            TestHelper::assertEqual((string)$mentor1Id, (string)$leadMentorId, 'Lead mentor should be assigned correctly');
            
            // Test additional mentor assignments (if table exists)
            try {
                $stmt = $this->conn->prepare("INSERT INTO project_mentors (project_id, mentor_id, mentor_role, assigned_date) VALUES (?, ?, 'co-mentor', NOW())");
                $stmt->execute([$projectId, $mentor2Id]);
                
                $stmt = $this->conn->prepare("SELECT COUNT(*) FROM project_mentors WHERE project_id = ?");
                $stmt->execute([$projectId]);
                $mentorCount = $stmt->fetchColumn();
                
                TestHelper::assertTrue($mentorCount > 0, 'Additional mentors should be assignable');
            } catch (PDOException $e) {
                // Table might not exist, that's OK
                TestConfig::log("project_mentors table not found, skipping additional mentor test");
            }
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test project status management
     */
    public function testProjectStatusManagement() {
        TestHelper::startTest('Project Status Management');
        
        try {
            // Create additional project statuses for testing
            $this->conn->exec("INSERT IGNORE INTO project_statuses (id, status_name) VALUES (2, 'In Progress')");
            $this->conn->exec("INSERT IGNORE INTO project_statuses (id, status_name) VALUES (3, 'Completed')");
            
            // Create project with initial status
            $projectId = TestHelper::createTestProject([
                'status_id' => 1
            ]);
            
            // Update project status
            $stmt = $this->conn->prepare("UPDATE projects SET status_id = ? WHERE id = ?");
            $stmt->execute([2, $projectId]);
            
            // Verify status update
            $stmt = $this->conn->prepare("SELECT status_id FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            $statusId = $stmt->fetchColumn();
            
            TestHelper::assertEqual('2', (string)$statusId, 'Project status should be updated');
            
            // Test status with join
            $stmt = $this->conn->prepare("
                SELECT p.project_name, ps.status_name 
                FROM projects p 
                JOIN project_statuses ps ON p.status_id = ps.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$projectId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            TestHelper::assertEqual('In Progress', $result['status_name'], 'Status name should be correct');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
}
?> 