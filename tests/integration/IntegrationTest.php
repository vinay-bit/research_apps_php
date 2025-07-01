<?php
/**
 * Integration Tests
 * Tests the interaction between different modules and complete workflows
 */

require_once __DIR__ . '/../TestHelper.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Student.php';
require_once __DIR__ . '/../../classes/Project.php';
require_once __DIR__ . '/../../classes/ReadyForPublication.php';

class IntegrationTest {
    
    private $conn;
    
    public function __construct() {
        $this->conn = TestConfig::getTestDatabaseConnection();
    }
    
    /**
     * Run all integration tests
     */
    public function runTests() {
        TestHelper::createTestDependencies();
        
        $this->testCompleteProjectWorkflow();
        $this->testPublicationWorkflow();
        $this->testUserRolePermissions();
        $this->testStudentProjectAssignment();
        $this->testMentorStudentRelationship();
        $this->testDataConsistencyAcrossModules();
        $this->testCascadingOperations();
    }
    
    /**
     * Test complete project workflow from creation to publication
     */
    public function testCompleteProjectWorkflow() {
        TestHelper::startTest('Complete Project Workflow');
        
        try {
            // 1. Create users (mentor, RBM, counselor)
            $mentorId = TestHelper::createTestUser([
                'user_type' => 'mentor',
                'full_name' => 'Test Mentor',
                'username' => 'mentor_' . uniqid()
            ]);
            
            $rbmId = TestHelper::createTestUser([
                'user_type' => 'rbm',
                'full_name' => 'Test RBM',
                'username' => 'rbm_' . uniqid()
            ]);
            
            $counselorId = TestHelper::createTestUser([
                'user_type' => 'councillor',
                'full_name' => 'Test Counselor',
                'username' => 'counselor_' . uniqid()
            ]);
            
            // 2. Create students
            $student1Id = TestHelper::createTestStudent([
                'full_name' => 'Student One',
                'counselor_id' => $counselorId,
                'rbm_id' => $rbmId
            ]);
            
            $student2Id = TestHelper::createTestStudent([
                'full_name' => 'Student Two',
                'counselor_id' => $counselorId,
                'rbm_id' => $rbmId
            ]);
            
            // 3. Create project with mentor
            $projectId = TestHelper::createTestProject([
                'project_name' => 'Complete Workflow Test Project',
                'lead_mentor_id' => $mentorId,
                'rbm_id' => $rbmId
            ]);
            
            // 4. Assign students to project
            $stmt = $this->conn->prepare("INSERT INTO project_students (project_id, student_id, assigned_date) VALUES (?, ?, NOW())");
            $stmt->execute([$projectId, $student1Id]);
            $stmt->execute([$projectId, $student2Id]);
            
            // 5. Verify project has all components
            $stmt = $this->conn->prepare("
                SELECT 
                    p.project_name,
                    p.lead_mentor_id,
                    m.full_name as mentor_name,
                    COUNT(ps.student_id) as student_count
                FROM projects p
                JOIN users m ON p.lead_mentor_id = m.id
                LEFT JOIN project_students ps ON p.id = ps.project_id
                WHERE p.id = ?
                GROUP BY p.id
            ");
            $stmt->execute([$projectId]);
            $projectData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            TestHelper::assertEqual('Complete Workflow Test Project', $projectData['project_name'], 'Project name should match');
            TestHelper::assertEqual('Test Mentor', $projectData['mentor_name'], 'Mentor should be assigned');
            TestHelper::assertEqual(2, $projectData['student_count'], 'Two students should be assigned');
            
            // 6. Create publication from project
            $publication = new ReadyForPublication();
            $publicationId = $publication->createFromProject($projectId);
            TestHelper::assertNotNull($publicationId, 'Publication should be created from project');
            
            // 7. Verify publication has students
            $students = $publication->getStudentsByPublicationId($publicationId);
            TestHelper::assertEqual(2, count($students), 'Publication should have 2 students');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test complete publication workflow
     */
    public function testPublicationWorkflow() {
        TestHelper::startTest('Publication Workflow');
        
        try {
            // Create project and publication
            $projectId = TestHelper::createTestProject();
            $publication = new ReadyForPublication();
            $publicationId = $publication->createFromProject($projectId);
            
            // Initial status should be pending
            $publicationData = $publication->getById($publicationId);
            TestHelper::assertEqual('pending', $publicationData['status'], 'Initial status should be pending');
            
            // Update with minimal required data
            $updateResult = $publication->update($publicationId, [
                'paper_title' => 'Test Publication Title',
                'mentor_affiliation' => 'Test University',
                'status' => 'pending',
                'notes' => 'Initial submission'
            ]);
            TestHelper::assertTrue($updateResult, 'Basic update should succeed');
            
            // Try to approve without required documents (should fail)
            TestHelper::assertThrows(function() use ($publication, $publicationId) {
                $publication->update($publicationId, [
                    'paper_title' => 'Test Publication Title',
                    'mentor_affiliation' => 'Test University',
                    'status' => 'approved',
                    'notes' => 'Trying to approve without documents'
                ]);
            }, 'Exception', 'Approval without required documents should fail');
            
            // Update with all required documents
            $approvalResult = $publication->update($publicationId, [
                'paper_title' => 'Test Publication Title',
                'mentor_affiliation' => 'Test University',
                'first_draft_link' => 'http://example.com/draft.pdf',
                'plagiarism_report_link' => 'http://example.com/plagiarism.pdf',
                'ai_detection_link' => 'http://example.com/ai_detection.pdf',
                'status' => 'approved',
                'notes' => 'All requirements met'
            ]);
            TestHelper::assertTrue($approvalResult, 'Approval with all documents should succeed');
            
            // Verify final status
            $finalData = $publication->getById($publicationId);
            TestHelper::assertEqual('approved', $finalData['status'], 'Final status should be approved');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test user role permissions and access control
     */
    public function testUserRolePermissions() {
        TestHelper::startTest('User Role Permissions');
        
        try {
            // Create users with different roles
            $adminId = TestHelper::createTestUser(['user_type' => 'admin']);
            $mentorId = TestHelper::createTestUser(['user_type' => 'mentor']);
            $rbmId = TestHelper::createTestUser(['user_type' => 'rbm']);
            $counselorId = TestHelper::createTestUser(['user_type' => 'councillor']);
            
            // Verify user types are correctly assigned
            $stmt = $this->conn->prepare("SELECT user_type FROM users WHERE id = ?");
            
            $stmt->execute([$adminId]);
            $adminType = $stmt->fetchColumn();
            TestHelper::assertEqual('admin', $adminType, 'Admin user type should be correct');
            
            $stmt->execute([$mentorId]);
            $mentorType = $stmt->fetchColumn();
            TestHelper::assertEqual('mentor', $mentorType, 'Mentor user type should be correct');
            
            $stmt->execute([$rbmId]);
            $rbmType = $stmt->fetchColumn();
            TestHelper::assertEqual('rbm', $rbmType, 'RBM user type should be correct');
            
            $stmt->execute([$counselorId]);
            $counselorType = $stmt->fetchColumn();
            TestHelper::assertEqual('councillor', $counselorType, 'Counselor user type should be correct');
            
            // Test role-based project access
            $projectId = TestHelper::createTestProject([
                'lead_mentor_id' => $mentorId,
                'rbm_id' => $rbmId
            ]);
            
            // Verify mentor and RBM are associated with project
            $stmt = $this->conn->prepare("SELECT lead_mentor_id, rbm_id FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            $projectRoles = $stmt->fetch(PDO::FETCH_ASSOC);
            
            TestHelper::assertEqual((string)$mentorId, (string)$projectRoles['lead_mentor_id'], 'Mentor should be assigned to project');
            TestHelper::assertEqual((string)$rbmId, (string)$projectRoles['rbm_id'], 'RBM should be assigned to project');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test student-project assignment process
     */
    public function testStudentProjectAssignment() {
        TestHelper::startTest('Student Project Assignment');
        
        try {
            // Create multiple students and projects
            $student1Id = TestHelper::createTestStudent(['full_name' => 'Student Alpha']);
            $student2Id = TestHelper::createTestStudent(['full_name' => 'Student Beta']);
            $student3Id = TestHelper::createTestStudent(['full_name' => 'Student Gamma']);
            
            $project1Id = TestHelper::createTestProject(['project_name' => 'Project X']);
            $project2Id = TestHelper::createTestProject(['project_name' => 'Project Y']);
            
            // Assign students to projects
            $stmt = $this->conn->prepare("INSERT INTO project_students (project_id, student_id, assigned_date) VALUES (?, ?, NOW())");
            $stmt->execute([$project1Id, $student1Id]);
            $stmt->execute([$project1Id, $student2Id]);
            $stmt->execute([$project2Id, $student3Id]);
            
            // Verify assignments
            $stmt = $this->conn->prepare("
                SELECT 
                    p.project_name,
                    s.full_name as student_name
                FROM project_students ps
                JOIN projects p ON ps.project_id = p.id
                JOIN students s ON ps.student_id = s.id
                ORDER BY p.project_name, s.full_name
            ");
            $stmt->execute();
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            TestHelper::assertTrue(count($assignments) >= 3, 'Should have at least 3 assignments');
            
            // Check Project X has 2 students
            $projectXStudents = array_filter($assignments, function($a) {
                return $a['project_name'] === 'Project X';
            });
            TestHelper::assertEqual(2, count($projectXStudents), 'Project X should have 2 students');
            
            // Check Project Y has 1 student
            $projectYStudents = array_filter($assignments, function($a) {
                return $a['project_name'] === 'Project Y';
            });
            TestHelper::assertEqual(1, count($projectYStudents), 'Project Y should have 1 student');
            
            // Test constraint: student can't be assigned to same project twice
            TestHelper::assertThrows(function() use ($project1Id, $student1Id) {
                $stmt = $this->conn->prepare("INSERT INTO project_students (project_id, student_id, assigned_date) VALUES (?, ?, NOW())");
                $stmt->execute([$project1Id, $student1Id]);
            }, 'PDOException', 'Duplicate assignment should fail');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test mentor-student relationship workflow
     */
    public function testMentorStudentRelationship() {
        TestHelper::startTest('Mentor Student Relationship');
        
        try {
            // Create mentor and counselor
            $mentorId = TestHelper::createTestUser(['user_type' => 'mentor', 'full_name' => 'Dr. Mentor']);
            $counselorId = TestHelper::createTestUser(['user_type' => 'councillor', 'full_name' => 'Counselor Smith']);
            $rbmId = TestHelper::createTestUser(['user_type' => 'rbm', 'full_name' => 'RBM Johnson']);
            
            // Create students with counselor and RBM
            $student1Id = TestHelper::createTestStudent([
                'full_name' => 'Student Under Mentor',
                'counselor_id' => $counselorId,
                'rbm_id' => $rbmId
            ]);
            
            // Create project with mentor
            $projectId = TestHelper::createTestProject([
                'project_name' => 'Mentor-Student Project',
                'lead_mentor_id' => $mentorId,
                'rbm_id' => $rbmId
            ]);
            
            // Assign student to project
            $stmt = $this->conn->prepare("INSERT INTO project_students (project_id, student_id, assigned_date) VALUES (?, ?, NOW())");
            $stmt->execute([$projectId, $student1Id]);
            
            // Verify complete relationship chain
            $stmt = $this->conn->prepare("
                SELECT 
                    s.full_name as student_name,
                    c.full_name as counselor_name,
                    r.full_name as rbm_name,
                    p.project_name,
                    m.full_name as mentor_name
                FROM students s
                JOIN users c ON s.counselor_id = c.id
                JOIN users r ON s.rbm_id = r.id
                JOIN project_students ps ON s.id = ps.student_id
                JOIN projects p ON ps.project_id = p.id
                JOIN users m ON p.lead_mentor_id = m.id
                WHERE s.id = ?
            ");
            $stmt->execute([$student1Id]);
            $relationship = $stmt->fetch(PDO::FETCH_ASSOC);
            
            TestHelper::assertNotNull($relationship, 'Relationship chain should exist');
            TestHelper::assertEqual('Student Under Mentor', $relationship['student_name'], 'Student name should match');
            TestHelper::assertEqual('Counselor Smith', $relationship['counselor_name'], 'Counselor should be assigned');
            TestHelper::assertEqual('RBM Johnson', $relationship['rbm_name'], 'RBM should be assigned');
            TestHelper::assertEqual('Dr. Mentor', $relationship['mentor_name'], 'Mentor should be assigned');
            TestHelper::assertEqual('Mentor-Student Project', $relationship['project_name'], 'Project should be assigned');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test data consistency across modules
     */
    public function testDataConsistencyAcrossModules() {
        TestHelper::startTest('Data Consistency Across Modules');
        
        try {
            // Create complete dataset
            $mentorId = TestHelper::createTestUser(['user_type' => 'mentor']);
            $studentId = TestHelper::createTestStudent();
            $projectId = TestHelper::createTestProject(['lead_mentor_id' => $mentorId]);
            
            // Assign student to project
            $stmt = $this->conn->prepare("INSERT INTO project_students (project_id, student_id, assigned_date) VALUES (?, ?, NOW())");
            $stmt->execute([$projectId, $studentId]);
            
            // Create publication
            $publication = new ReadyForPublication();
            $publicationId = $publication->createFromProject($projectId);
            
            // Verify all data is consistent
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(DISTINCT p.id) as project_count,
                    COUNT(DISTINCT s.id) as student_count,
                    COUNT(DISTINCT u.id) as mentor_count,
                    COUNT(DISTINCT rfp.id) as publication_count
                FROM projects p
                JOIN project_students ps ON p.id = ps.project_id
                JOIN students s ON ps.student_id = s.id
                JOIN users u ON p.lead_mentor_id = u.id
                JOIN ready_for_publication rfp ON p.id = rfp.project_id
                WHERE p.id = ?
            ");
            $stmt->execute([$projectId]);
            $consistency = $stmt->fetch(PDO::FETCH_ASSOC);
            
            TestHelper::assertEqual(1, $consistency['project_count'], 'Should have 1 project');
            TestHelper::assertEqual(1, $consistency['student_count'], 'Should have 1 student');
            TestHelper::assertEqual(1, $consistency['mentor_count'], 'Should have 1 mentor');
            TestHelper::assertEqual(1, $consistency['publication_count'], 'Should have 1 publication');
            
            // Test referential integrity
            $stmt = $this->conn->query("
                SELECT 
                    (SELECT COUNT(*) FROM project_students ps 
                     LEFT JOIN projects p ON ps.project_id = p.id 
                     WHERE p.id IS NULL) as orphaned_project_students,
                    (SELECT COUNT(*) FROM ready_for_publication rfp 
                     LEFT JOIN projects p ON rfp.project_id = p.id 
                     WHERE p.id IS NULL) as orphaned_publications
            ");
            $integrity = $stmt->fetch(PDO::FETCH_ASSOC);
            
            TestHelper::assertEqual(0, $integrity['orphaned_project_students'], 'No orphaned project students should exist');
            TestHelper::assertEqual(0, $integrity['orphaned_publications'], 'No orphaned publications should exist');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test cascading operations and cleanup
     */
    public function testCascadingOperations() {
        TestHelper::startTest('Cascading Operations');
        
        try {
            // Create complete workflow
            $mentorId = TestHelper::createTestUser(['user_type' => 'mentor']);
            $studentId = TestHelper::createTestStudent();
            $projectId = TestHelper::createTestProject(['lead_mentor_id' => $mentorId]);
            
            // Create relationships
            $stmt = $this->conn->prepare("INSERT INTO project_students (project_id, student_id, assigned_date) VALUES (?, ?, NOW())");
            $stmt->execute([$projectId, $studentId]);
            
            $publication = new ReadyForPublication();
            $publicationId = $publication->createFromProject($projectId);
            
            // Verify relationships exist
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM project_students WHERE project_id = ?");
            $stmt->execute([$projectId]);
            $projectStudentCount = $stmt->fetchColumn();
            TestHelper::assertEqual(1, $projectStudentCount, 'Project-student relationship should exist');
            
            $publicationData = $publication->getById($publicationId);
            TestHelper::assertNotNull($publicationData, 'Publication should exist');
            
            // Test deletion cascade (or prevention based on constraints)
            try {
                // Try to delete project that has dependencies
                $stmt = $this->conn->prepare("DELETE FROM projects WHERE id = ?");
                $stmt->execute([$projectId]);
                
                // If successful, verify cascading delete worked
                $stmt = $this->conn->prepare("SELECT COUNT(*) FROM project_students WHERE project_id = ?");
                $stmt->execute([$projectId]);
                $remainingAssignments = $stmt->fetchColumn();
                TestHelper::assertEqual(0, $remainingAssignments, 'Project-student assignments should be cleaned up');
                
            } catch (PDOException $e) {
                // If deletion was prevented by foreign key constraint, that's also correct behavior
                TestConfig::log("Project deletion prevented by foreign key constraint (expected behavior)");
                
                // Verify data is still intact
                $stmt = $this->conn->prepare("SELECT COUNT(*) FROM projects WHERE id = ?");
                $stmt->execute([$projectId]);
                $projectExists = $stmt->fetchColumn();
                TestHelper::assertEqual(1, $projectExists, 'Project should still exist if deletion was prevented');
            }
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
}
?> 