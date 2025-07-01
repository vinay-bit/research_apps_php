<?php
/**
 * Unit Tests for ReadyForPublication Class
 */

require_once __DIR__ . '/../TestHelper.php';
require_once __DIR__ . '/../../classes/ReadyForPublication.php';

class ReadyForPublicationTest {
    
    private $conn;
    private $publication;
    
    public function __construct() {
        $this->conn = TestConfig::getTestDatabaseConnection();
        $this->publication = new ReadyForPublication($this->conn);
    }
    
    /**
     * Run all publication tests
     */
    public function runTests() {
        TestHelper::createTestDependencies();
        
        $this->testPublicationCreation();
        $this->testCreateFromProject();
        $this->testDuplicateProjectPrevention();
        $this->testPublicationUpdate();
        $this->testStudentAssignments();
        $this->testStatusValidation();
        $this->testPublicationDeletion();
        $this->testStatistics();
    }
    
    /**
     * Test manual publication creation
     */
    public function testPublicationCreation() {
        TestHelper::startTest('Publication Creation');
        
        try {
            // Create test project first
            $projectId = TestHelper::createTestProject();
            
            // Create manual publication
            $publicationData = [
                'project_id' => $projectId,
                'paper_title' => 'Test Publication Paper',
                'mentor_affiliation' => 'Test University',
                'first_draft_link' => 'http://example.com/draft.pdf',
                'plagiarism_report_link' => 'http://example.com/plagiarism.pdf',
                'status' => 'pending',
                'notes' => 'Test publication notes',
                'students' => []
            ];
            
            $publicationId = $this->publication->createManual($publicationData);
            TestHelper::assertNotNull($publicationId, 'Publication ID should be returned');
            
            // Verify publication exists
            $createdPublication = $this->publication->getById($publicationId);
            TestHelper::assertNotNull($createdPublication, 'Publication should exist');
            TestHelper::assertEqual('Test Publication Paper', $createdPublication['paper_title'], 'Paper title should match');
            TestHelper::assertEqual('pending', $createdPublication['status'], 'Status should match');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test creating publication from project
     */
    public function testCreateFromProject() {
        TestHelper::startTest('Create From Project');
        
        try {
            // Create test project with students
            $projectId = TestHelper::createTestProject();
            $studentId = TestHelper::createTestStudent();
            
            // Assign student to project
            $this->conn->exec("INSERT INTO project_students (project_id, student_id, assigned_date) VALUES ($projectId, $studentId, NOW())");
            
            // Create publication from project
            $publicationId = $this->publication->createFromProject($projectId);
            TestHelper::assertNotNull($publicationId, 'Publication should be created from project');
            
            // Verify publication details
            $createdPublication = $this->publication->getById($publicationId);
            TestHelper::assertEqual((string)$projectId, (string)$createdPublication['project_id'], 'Project ID should match');
            
            // Verify student assignment
            $students = $this->publication->getStudentsByPublicationId($publicationId);
            TestHelper::assertTrue(count($students) > 0, 'Students should be assigned to publication');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test duplicate project prevention
     */
    public function testDuplicateProjectPrevention() {
        TestHelper::startTest('Duplicate Project Prevention');
        
        try {
            // Create test project
            $projectId = TestHelper::createTestProject();
            
            // Create first publication for project
            $publicationId1 = $this->publication->createFromProject($projectId);
            TestHelper::assertNotNull($publicationId1, 'First publication should be created');
            
            // Try to create second publication for same project (should fail)
            TestHelper::assertThrows(function() use ($projectId) {
                $this->publication->createFromProject($projectId);
            }, 'Exception', 'Duplicate project publication should throw exception');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test publication update
     */
    public function testPublicationUpdate() {
        TestHelper::startTest('Publication Update');
        
        try {
            // Create test publication
            $projectId = TestHelper::createTestProject();
            $publicationId = $this->publication->createFromProject($projectId);
            
            // Update publication
            $updateData = [
                'paper_title' => 'Updated Paper Title',
                'mentor_affiliation' => 'Updated University',
                'first_draft_link' => 'http://example.com/updated_draft.pdf',
                'plagiarism_report_link' => 'http://example.com/updated_plagiarism.pdf',
                'ai_detection_link' => 'http://example.com/ai_detection.pdf',
                'status' => 'pending',
                'notes' => 'Updated notes'
            ];
            
            $result = $this->publication->update($publicationId, $updateData);
            TestHelper::assertTrue($result, 'Publication update should succeed');
            
            // Verify update
            $updatedPublication = $this->publication->getById($publicationId);
            TestHelper::assertEqual('Updated Paper Title', $updatedPublication['paper_title'], 'Paper title should be updated');
            TestHelper::assertEqual('Updated University', $updatedPublication['mentor_affiliation'], 'Mentor affiliation should be updated');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test student assignments to publication
     */
    public function testStudentAssignments() {
        TestHelper::startTest('Student Assignments');
        
        try {
            // Create test publication
            $projectId = TestHelper::createTestProject();
            $publicationId = $this->publication->createFromProject($projectId);
            
            // Create test students
            $student1Id = TestHelper::createTestStudent(['full_name' => 'Student One']);
            $student2Id = TestHelper::createTestStudent(['full_name' => 'Student Two']);
            
            // Add students to publication manually
            $stmt = $this->conn->prepare("INSERT INTO ready_for_publication_students (ready_for_publication_id, student_id, student_affiliation, author_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$publicationId, $student1Id, 'Test University', 1]);
            $stmt->execute([$publicationId, $student2Id, 'Test University', 2]);
            
            // Get students for publication
            $students = $this->publication->getStudentsByPublicationId($publicationId);
            TestHelper::assertTrue(count($students) >= 2, 'Publication should have assigned students');
            
            // Verify author order
            $firstStudent = null;
            $secondStudent = null;
            foreach ($students as $student) {
                if ($student['author_order'] == 1) $firstStudent = $student;
                if ($student['author_order'] == 2) $secondStudent = $student;
            }
            
            TestHelper::assertNotNull($firstStudent, 'First author should exist');
            TestHelper::assertNotNull($secondStudent, 'Second author should exist');
            TestHelper::assertEqual('Student One', $firstStudent['full_name'], 'First author name should match');
            TestHelper::assertEqual('Student Two', $secondStudent['full_name'], 'Second author name should match');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test status validation
     */
    public function testStatusValidation() {
        TestHelper::startTest('Status Validation');
        
        try {
            // Create test publication
            $projectId = TestHelper::createTestProject();
            $publicationId = $this->publication->createFromProject($projectId);
            
            // Try to approve without required links (should fail)
            $invalidApprovalData = [
                'paper_title' => 'Test Paper',
                'mentor_affiliation' => 'Test University',
                'first_draft_link' => '', // Missing required link
                'plagiarism_report_link' => 'http://example.com/plagiarism.pdf',
                'ai_detection_link' => '', // Missing required link
                'status' => 'approved',
                'notes' => 'Test notes'
            ];
            
            TestHelper::assertThrows(function() use ($publicationId, $invalidApprovalData) {
                $this->publication->update($publicationId, $invalidApprovalData);
            }, 'Exception', 'Approval without required links should fail');
            
            // Valid approval with all required links
            $validApprovalData = [
                'paper_title' => 'Test Paper',
                'mentor_affiliation' => 'Test University',
                'first_draft_link' => 'http://example.com/draft.pdf',
                'plagiarism_report_link' => 'http://example.com/plagiarism.pdf',
                'ai_detection_link' => 'http://example.com/ai_detection.pdf',
                'status' => 'approved',
                'notes' => 'Approved for publication'
            ];
            
            $result = $this->publication->update($publicationId, $validApprovalData);
            TestHelper::assertTrue($result, 'Valid approval should succeed');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test publication deletion
     */
    public function testPublicationDeletion() {
        TestHelper::startTest('Publication Deletion');
        
        try {
            // Create test publication
            $projectId = TestHelper::createTestProject();
            $publicationId = $this->publication->createFromProject($projectId);
            
            // Delete publication
            $result = $this->publication->delete($publicationId);
            TestHelper::assertTrue($result, 'Publication deletion should succeed');
            
            // Verify deletion
            $deletedPublication = $this->publication->getById($publicationId);
            TestHelper::assertTrue(empty($deletedPublication), 'Deleted publication should not exist');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test statistics generation
     */
    public function testStatistics() {
        TestHelper::startTest('Statistics Generation');
        
        try {
            // Create multiple publications with different statuses
            $project1Id = TestHelper::createTestProject(['project_name' => 'Project 1']);
            $project2Id = TestHelper::createTestProject(['project_name' => 'Project 2']);
            $project3Id = TestHelper::createTestProject(['project_name' => 'Project 3']);
            
            $pub1Id = $this->publication->createFromProject($project1Id);
            $pub2Id = $this->publication->createFromProject($project2Id);
            $pub3Id = $this->publication->createFromProject($project3Id);
            
            // Update statuses
            $this->publication->update($pub1Id, [
                'paper_title' => 'Paper 1',
                'mentor_affiliation' => 'Uni 1',
                'status' => 'pending',
                'notes' => ''
            ]);
            
            $this->publication->update($pub2Id, [
                'paper_title' => 'Paper 2',
                'mentor_affiliation' => 'Uni 2',
                'first_draft_link' => 'http://example.com/draft.pdf',
                'ai_detection_link' => 'http://example.com/ai.pdf',
                'status' => 'approved',
                'notes' => ''
            ]);
            
            // Get statistics
            $stats = $this->publication->getStatistics();
            TestHelper::assertNotNull($stats, 'Statistics should be returned');
            TestHelper::assertTrue(is_array($stats), 'Statistics should be an array');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
}
?> 