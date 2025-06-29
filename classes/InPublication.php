<?php
require_once __DIR__ . '/../config/database.php';

class InPublication {
    private $conn;
    private $table_name = "in_publication";
    private $students_table = "in_publication_students";
    private $conf_applications_table = "publication_conference_applications";
    private $journal_applications_table = "publication_journal_applications";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Get all in-publication entries
    public function getAll($filters = []) {
        $query = "SELECT ip.*, 
                         p.project_name,
                         p.project_id as project_code,
                         mentor.full_name as mentor_name,
                         mentor.specialization as mentor_specialization,
                         ps.status_name as project_status,
                         COALESCE(conf_apps.conference_count, 0) as conference_applications,
                         COALESCE(journal_apps.journal_count, 0) as journal_applications,
                         COALESCE(conf_apps.accepted_conferences, 0) as accepted_conferences,
                         COALESCE(journal_apps.accepted_journals, 0) as accepted_journals
                  FROM " . $this->table_name . " ip
                  INNER JOIN projects p ON ip.project_id = p.id
                  LEFT JOIN users mentor ON p.lead_mentor_id = mentor.id
                  LEFT JOIN project_statuses ps ON p.status_id = ps.id
                  LEFT JOIN (
                      SELECT in_publication_id, 
                             COUNT(*) as conference_count,
                             SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_conferences
                      FROM publication_conference_applications 
                      GROUP BY in_publication_id
                  ) conf_apps ON ip.id = conf_apps.in_publication_id
                  LEFT JOIN (
                      SELECT in_publication_id, 
                             COUNT(*) as journal_count,
                             SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_journals
                      FROM publication_journal_applications 
                      GROUP BY in_publication_id
                  ) journal_apps ON ip.id = journal_apps.in_publication_id
                  WHERE 1 = 1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['search'])) {
            $query .= " AND (ip.paper_title LIKE :search OR p.project_name LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $query .= " ORDER BY ip.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get in-publication entry by ID
    public function getById($id) {
        $query = "SELECT ip.*, 
                         p.project_name,
                         p.project_id as project_code,
                         mentor.full_name as mentor_name,
                         mentor.specialization as mentor_specialization,
                         ps.status_name as project_status
                  FROM " . $this->table_name . " ip
                  INNER JOIN projects p ON ip.project_id = p.id
                  LEFT JOIN users mentor ON p.lead_mentor_id = mentor.id
                  LEFT JOIN project_statuses ps ON p.status_id = ps.id
                  WHERE ip.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get students for an in-publication entry
    public function getStudentsByPublicationId($publication_id) {
        $query = "SELECT ips.*, 
                         s.student_id,
                         s.full_name,
                         s.email_address,
                         s.grade,
                         s.affiliation as original_affiliation
                  FROM " . $this->students_table . " ips
                  INNER JOIN students s ON ips.student_id = s.id
                  WHERE ips.in_publication_id = :publication_id
                  ORDER BY ips.author_order, s.full_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':publication_id', $publication_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Move approved publication from ready_for_publication to in_publication
    public function moveFromReadyForPublication($ready_publication_id) {
        try {
            $this->conn->beginTransaction();
            
            // Get the ready for publication entry
            $ready_query = "SELECT * FROM ready_for_publication WHERE id = :id AND status = 'approved'";
            $ready_stmt = $this->conn->prepare($ready_query);
            $ready_stmt->bindParam(':id', $ready_publication_id);
            $ready_stmt->execute();
            $ready_pub = $ready_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ready_pub) {
                throw new Exception("Publication not found or not approved");
            }
            
            // Validate required links
            if (empty($ready_pub['first_draft_link']) || empty($ready_pub['ai_detection_link'])) {
                throw new Exception("Both paper link and AI detection link are required to move to in-publication");
            }
            
            // Check if already moved
            $check_query = "SELECT id FROM " . $this->table_name . " WHERE ready_publication_id = :ready_id";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(':ready_id', $ready_publication_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                throw new Exception("This publication has already been moved to in-publication");
            }
            
            // Create in_publication entry
            $insert_query = "INSERT INTO " . $this->table_name . " 
                            (ready_publication_id, project_id, paper_title, mentor_affiliation, 
                             first_draft_link, plagiarism_report_link, ai_detection_link, notes) 
                            VALUES (:ready_id, :project_id, :paper_title, :mentor_affiliation, 
                                    :first_draft_link, :plagiarism_report_link, :ai_detection_link, :notes)";
            $insert_stmt = $this->conn->prepare($insert_query);
            
            $insert_stmt->bindParam(':ready_id', $ready_publication_id);
            $insert_stmt->bindParam(':project_id', $ready_pub['project_id']);
            $insert_stmt->bindParam(':paper_title', $ready_pub['paper_title']);
            $insert_stmt->bindParam(':mentor_affiliation', $ready_pub['mentor_affiliation']);
            $insert_stmt->bindParam(':first_draft_link', $ready_pub['first_draft_link']);
            $insert_stmt->bindParam(':plagiarism_report_link', $ready_pub['plagiarism_report_link']);
            $insert_stmt->bindParam(':ai_detection_link', $ready_pub['ai_detection_link']);
            $insert_stmt->bindParam(':notes', $ready_pub['notes']);
            $insert_stmt->execute();
            
            $in_publication_id = $this->conn->lastInsertId();
            
            // Copy students from ready_for_publication_students to in_publication_students
            $students_query = "INSERT INTO " . $this->students_table . " 
                              (in_publication_id, student_id, student_affiliation, student_address, author_order)
                              SELECT :in_pub_id, student_id, student_affiliation, student_address, author_order
                              FROM ready_for_publication_students 
                              WHERE ready_for_publication_id = :ready_id";
            $students_stmt = $this->conn->prepare($students_query);
            $students_stmt->bindParam(':in_pub_id', $in_publication_id);
            $students_stmt->bindParam(':ready_id', $ready_publication_id);
            $students_stmt->execute();
            
            // Update ready_for_publication workflow status
            $update_query = "UPDATE ready_for_publication 
                            SET workflow_status = 'moved_to_publication' 
                            WHERE id = :id";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(':id', $ready_publication_id);
            $update_stmt->execute();
            
            $this->conn->commit();
            return $in_publication_id;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    
    // Update in-publication entry
    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET paper_title = :paper_title,
                      mentor_affiliation = :mentor_affiliation,
                      first_draft_link = :first_draft_link,
                      plagiarism_report_link = :plagiarism_report_link,
                      final_paper_link = :final_paper_link,
                      ai_detection_link = :ai_detection_link,
                      notes = :notes,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':paper_title', $data['paper_title']);
        $stmt->bindParam(':mentor_affiliation', $data['mentor_affiliation']);
        $stmt->bindValue(':first_draft_link', $data['first_draft_link'] ?? null);
        $stmt->bindValue(':plagiarism_report_link', $data['plagiarism_report_link'] ?? null);
        $stmt->bindValue(':final_paper_link', $data['final_paper_link'] ?? null);
        $stmt->bindValue(':ai_detection_link', $data['ai_detection_link'] ?? null);
        $stmt->bindParam(':notes', $data['notes']);
        
        return $stmt->execute();
    }
    
    // Get all conferences for application
    public function getAllConferences() {
        $query = "SELECT id, conference_name, conference_shortform, conference_date, affiliation, conference_type
                  FROM conferences 
                  ORDER BY 
                    CASE 
                        WHEN conference_date >= CURDATE() THEN 0 
                        ELSE 1 
                    END,
                    conference_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get all journals for application
    public function getAllJournals() {
        $query = "SELECT id, journal_name, publisher, acceptance_frequency
                  FROM journals 
                  ORDER BY journal_name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Apply to conference
    public function applyToConference($in_publication_id, $data) {
        $query = "INSERT INTO " . $this->conf_applications_table . " 
                  (in_publication_id, conference_id, application_date, submission_deadline, 
                   submission_link, notes) 
                  VALUES (:in_pub_id, :conf_id, :app_date, :deadline, :submission_link, :notes)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':in_pub_id', $in_publication_id);
        $stmt->bindParam(':conf_id', $data['conference_id']);
        $stmt->bindParam(':app_date', $data['application_date']);
        $stmt->bindValue(':deadline', $data['submission_deadline'] ?? null);
        $stmt->bindValue(':submission_link', $data['submission_link'] ?? null);
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        
        return $stmt->execute();
    }
    
    // Apply to journal
    public function applyToJournal($in_publication_id, $data) {
        $query = "INSERT INTO " . $this->journal_applications_table . " 
                  (in_publication_id, journal_id, application_date, submission_deadline, 
                   submission_link, manuscript_id, notes) 
                  VALUES (:in_pub_id, :journal_id, :app_date, :deadline, :submission_link, :manuscript_id, :notes)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':in_pub_id', $in_publication_id);
        $stmt->bindParam(':journal_id', $data['journal_id']);
        $stmt->bindParam(':app_date', $data['application_date']);
        $stmt->bindValue(':deadline', $data['submission_deadline'] ?? null);
        $stmt->bindValue(':submission_link', $data['submission_link'] ?? null);
        $stmt->bindValue(':manuscript_id', $data['manuscript_id'] ?? null);
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        
        return $stmt->execute();
    }
    
    // Get conference applications for a publication
    public function getConferenceApplications($in_publication_id) {
        $query = "SELECT pca.*, c.conference_name, c.conference_shortform, c.conference_date, 
                         c.affiliation, c.conference_type
                  FROM " . $this->conf_applications_table . " pca
                  INNER JOIN conferences c ON pca.conference_id = c.id
                  WHERE pca.in_publication_id = :in_pub_id
                  ORDER BY pca.application_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':in_pub_id', $in_publication_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get journal applications for a publication
    public function getJournalApplications($in_publication_id) {
        $query = "SELECT pja.*, j.journal_name, j.publisher, j.acceptance_frequency
                  FROM " . $this->journal_applications_table . " pja
                  INNER JOIN journals j ON pja.journal_id = j.id
                  WHERE pja.in_publication_id = :in_pub_id
                  ORDER BY pja.application_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':in_pub_id', $in_publication_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Update conference application status
    public function updateConferenceApplication($application_id, $status, $feedback = null, $response_date = null) {
        $query = "UPDATE " . $this->conf_applications_table . " 
                  SET status = :status, feedback = :feedback, response_date = :response_date,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $application_id);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':feedback', $feedback);
        $stmt->bindParam(':response_date', $response_date);
        
        return $stmt->execute();
    }
    
    // Update journal application status
    public function updateJournalApplication($application_id, $status, $feedback = null, $response_date = null) {
        $query = "UPDATE " . $this->journal_applications_table . " 
                  SET status = :status, feedback = :feedback, response_date = :response_date,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $application_id);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':feedback', $feedback);
        $stmt->bindParam(':response_date', $response_date);
        
        return $stmt->execute();
    }
    
    // Get statistics
    public function getStatistics() {
        $stats = [];
        
        // Total in publication
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Conference applications stats
        $query = "SELECT status, COUNT(*) as count 
                  FROM " . $this->conf_applications_table . " 
                  GROUP BY status";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['conference_applications'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Journal applications stats
        $query = "SELECT status, COUNT(*) as count 
                  FROM " . $this->journal_applications_table . " 
                  GROUP BY status";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['journal_applications'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
}
?> 