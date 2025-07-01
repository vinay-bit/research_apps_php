<?php
require_once __DIR__ . '/../config/database.php';

class ReadyForPublication {
    private $conn;
    private $table_name = "ready_for_publication";
    private $students_table = "ready_for_publication_students";
    
    public function __construct($db = null) {
        if ($db) {
            $this->conn = $db;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }
    
    // Get all ready for publication entries
    public function getAll($filters = []) {
        $query = "SELECT rfp.*, 
                         p.project_name,
                         p.project_id as project_code,
                         mentor.full_name as mentor_name,
                         mentor.specialization as mentor_specialization,
                         ps.status_name as project_status
                  FROM " . $this->table_name . " rfp
                  INNER JOIN projects p ON rfp.project_id = p.id
                  LEFT JOIN users mentor ON p.lead_mentor_id = mentor.id
                  LEFT JOIN project_statuses ps ON p.status_id = ps.id
                  WHERE rfp.workflow_status = 'active'";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $query .= " AND rfp.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (rfp.paper_title LIKE :search OR p.project_name LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $query .= " ORDER BY rfp.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get ready for publication entry by ID
    public function getById($id) {
        $query = "SELECT rfp.*, 
                         p.project_name,
                         p.project_id as project_code,
                         mentor.full_name as mentor_name,
                         mentor.specialization as mentor_specialization,
                         ps.status_name as project_status
                  FROM " . $this->table_name . " rfp
                  INNER JOIN projects p ON rfp.project_id = p.id
                  LEFT JOIN users mentor ON p.lead_mentor_id = mentor.id
                  LEFT JOIN project_statuses ps ON p.status_id = ps.id
                  WHERE rfp.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get students for a ready for publication entry
    public function getStudentsByPublicationId($publication_id) {
        $query = "SELECT rfps.*, 
                         s.student_id,
                         s.full_name,
                         s.email_address,
                         s.grade,
                         s.affiliation as original_affiliation
                  FROM " . $this->students_table . " rfps
                  INNER JOIN students s ON rfps.student_id = s.id
                  WHERE rfps.ready_for_publication_id = :publication_id
                  ORDER BY rfps.author_order, s.full_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':publication_id', $publication_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Create ready for publication entry from project
    public function createFromProject($project_id) {
        // Check if project already exists in ready for publication (due to unique constraint)
        $check_query = "SELECT id, status, workflow_status FROM " . $this->table_name . " WHERE project_id = :project_id";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':project_id', $project_id);
        $check_stmt->execute();
        $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            throw new Exception("Project already has a Ready for Publication entry (ID: {$existing['id']}, Status: {$existing['status']}, Workflow: {$existing['workflow_status']}). Please edit the existing entry instead of creating a new one.");
        }
        
        try {
            $this->conn->beginTransaction();
            
            // Get project details
            $project_query = "SELECT p.*, mentor.full_name as mentor_name, mentor.specialization as mentor_specialization
                             FROM projects p
                             LEFT JOIN users mentor ON p.lead_mentor_id = mentor.id
                             WHERE p.id = :project_id";
            $project_stmt = $this->conn->prepare($project_query);
            $project_stmt->bindParam(':project_id', $project_id);
            $project_stmt->execute();
            $project = $project_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$project) {
                throw new Exception("Project not found");
            }
            
            // Create ready for publication entry
            $insert_query = "INSERT INTO " . $this->table_name . " 
                            (project_id, paper_title, mentor_affiliation, first_draft_link, plagiarism_report_link) 
                            VALUES (:project_id, :paper_title, :mentor_affiliation, :first_draft_link, :plagiarism_report_link)";
            $insert_stmt = $this->conn->prepare($insert_query);
            
            $paper_title = $project['project_name']; // Default to project name
            $mentor_affiliation = $project['mentor_specialization'] ?? '';
            
            $first_draft_link = null;
            $plagiarism_report_link = null;
            
            $insert_stmt->bindParam(':project_id', $project_id);
            $insert_stmt->bindParam(':paper_title', $paper_title);
            $insert_stmt->bindParam(':mentor_affiliation', $mentor_affiliation);
            $insert_stmt->bindParam(':first_draft_link', $first_draft_link);
            $insert_stmt->bindParam(':plagiarism_report_link', $plagiarism_report_link);
            $insert_stmt->execute();
            
            $publication_id = $this->conn->lastInsertId();
            
            // Get assigned students with their affiliation and add them
            $students_query = "SELECT ps.student_id, s.affiliation
                              FROM project_students ps
                              LEFT JOIN students s ON ps.student_id = s.id
                              WHERE ps.project_id = :project_id";
            $students_stmt = $this->conn->prepare($students_query);
            $students_stmt->bindParam(':project_id', $project_id);
            $students_stmt->execute();
            $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add students to ready for publication with auto-fetched affiliation
            $student_insert_query = "INSERT INTO " . $this->students_table . " 
                                    (ready_for_publication_id, student_id, student_affiliation, author_order) 
                                    VALUES (:publication_id, :student_id, :student_affiliation, :author_order)";
            $student_insert_stmt = $this->conn->prepare($student_insert_query);
            
            $author_order = 1;
            foreach ($students as $student) {
                $student_insert_stmt->bindParam(':publication_id', $publication_id);
                $student_insert_stmt->bindParam(':student_id', $student['student_id']);
                $student_insert_stmt->bindParam(':student_affiliation', $student['affiliation']);
                $student_insert_stmt->bindParam(':author_order', $author_order);
                $student_insert_stmt->execute();
                $author_order++;
            }
            
            $this->conn->commit();
            return $publication_id;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    
    // Update ready for publication entry
    public function update($id, $data) {
        // Validate approved status requirements
        if ($data['status'] == 'approved') {
            if (empty($data['first_draft_link']) || empty($data['ai_detection_link'])) {
                throw new Exception("Both paper link and AI detection link are required to set status as approved");
            }
        }
        
        $query = "UPDATE " . $this->table_name . " 
                  SET paper_title = :paper_title,
                      mentor_affiliation = :mentor_affiliation,
                      first_draft_link = :first_draft_link,
                      plagiarism_report_link = :plagiarism_report_link,
                      ai_detection_link = :ai_detection_link,
                      status = :status,
                      notes = :notes,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':paper_title', $data['paper_title']);
        $stmt->bindParam(':mentor_affiliation', $data['mentor_affiliation']);
        $stmt->bindValue(':first_draft_link', $data['first_draft_link'] ?? null);
        $stmt->bindValue(':plagiarism_report_link', $data['plagiarism_report_link'] ?? null);
        $stmt->bindValue(':ai_detection_link', $data['ai_detection_link'] ?? null);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':notes', $data['notes']);
        
        return $stmt->execute();
    }
    
    // Update student details
    public function updateStudentDetails($publication_student_id, $affiliation, $address) {
        $query = "UPDATE " . $this->students_table . " 
                  SET student_affiliation = :affiliation,
                      student_address = :address
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $publication_student_id);
        $stmt->bindParam(':affiliation', $affiliation);
        $stmt->bindParam(':address', $address);
        
        return $stmt->execute();
    }
    
    // Delete ready for publication entry
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    // Create a new publication entry directly (manual add)
    public function createManual($data) {
        try {
            $this->conn->beginTransaction();
            
            // Validate approved status requirements
            if ($data['status'] == 'approved') {
                if (empty($data['first_draft_link']) || empty($data['ai_detection_link'])) {
                    throw new Exception("Both paper link and AI detection link are required to set status as approved");
                }
            }
            
            // Create ready for publication entry
            $insert_query = "INSERT INTO " . $this->table_name . " 
                            (project_id, paper_title, mentor_affiliation, first_draft_link, plagiarism_report_link, ai_detection_link, status, notes) 
                            VALUES (:project_id, :paper_title, :mentor_affiliation, :first_draft_link, :plagiarism_report_link, :ai_detection_link, :status, :notes)";
            $insert_stmt = $this->conn->prepare($insert_query);
            
            $insert_stmt->bindParam(':project_id', $data['project_id']);
            $insert_stmt->bindParam(':paper_title', $data['paper_title']);
            $insert_stmt->bindParam(':mentor_affiliation', $data['mentor_affiliation']);
            $insert_stmt->bindValue(':first_draft_link', $data['first_draft_link']);
            $insert_stmt->bindValue(':plagiarism_report_link', $data['plagiarism_report_link']);
            $insert_stmt->bindValue(':ai_detection_link', $data['ai_detection_link'] ?? null);
            $insert_stmt->bindParam(':status', $data['status']);
            $insert_stmt->bindParam(':notes', $data['notes']);
            $insert_stmt->execute();
            
            $publication_id = $this->conn->lastInsertId();
            
            // Add students if provided
            if (!empty($data['students'])) {
                $student_insert_query = "INSERT INTO " . $this->students_table . " 
                                        (ready_for_publication_id, student_id, student_affiliation, student_address, author_order) 
                                        VALUES (:publication_id, :student_id, :student_affiliation, :student_address, :author_order)";
                $student_insert_stmt = $this->conn->prepare($student_insert_query);
                
                foreach ($data['students'] as $index => $student) {
                    $author_order_value = $student['author_order'] ?? ($index + 1);
                    
                    $student_insert_stmt->bindParam(':publication_id', $publication_id);
                    $student_insert_stmt->bindParam(':student_id', $student['student_id']);
                    $student_insert_stmt->bindParam(':student_affiliation', $student['affiliation']);
                    $student_insert_stmt->bindParam(':student_address', $student['address']);
                    $student_insert_stmt->bindParam(':author_order', $author_order_value);
                    $student_insert_stmt->execute();
                }
            }
            
            $this->conn->commit();
            return $publication_id;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    
    // Get all projects (for manual add)
    public function getAllProjects() {
        $query = "SELECT p.*, ps.status_name,
                         mentor.full_name as mentor_name,
                         mentor.specialization as mentor_specialization
                  FROM projects p
                  LEFT JOIN project_statuses ps ON p.status_id = ps.id
                  LEFT JOIN users mentor ON p.lead_mentor_id = mentor.id
                  WHERE p.id NOT IN (SELECT project_id FROM " . $this->table_name . " WHERE workflow_status = 'active')
                  ORDER BY p.updated_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get projects that are ready for publication (by status)
    public function getReadyProjects() {
        $query = "SELECT p.*, ps.status_name,
                         mentor.full_name as mentor_name
                  FROM projects p
                  LEFT JOIN project_statuses ps ON p.status_id = ps.id
                  LEFT JOIN users mentor ON p.lead_mentor_id = mentor.id
                  WHERE ps.status_name LIKE '%ready for publication%'
                  AND p.id NOT IN (SELECT project_id FROM " . $this->table_name . " WHERE workflow_status = 'active')
                  ORDER BY p.updated_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get statistics
    public function getStatistics() {
        $stats = [];
        
        // Total ready for publication
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // By status
        $query = "SELECT status, COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  GROUP BY status 
                  ORDER BY count DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    

}
?> 