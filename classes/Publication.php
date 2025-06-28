<?php
require_once __DIR__ . '/../config/database.php';

class Publication {
    private $conn;
    private $table_name = "publications";
    
    // Publication properties
    public $id;
    public $publication_id;
    public $project_id;
    public $paper_title;
    public $venue_type;
    
    // Conference fields
    public $conference_acceptance_date;
    public $conference_reviewer_comments;
    public $conference_presentation_date;
    public $conference_camera_ready_submission_date;
    public $conference_copyright_submission_date;
    public $conference_doi_link;
    public $conference_publisher;
    
    // Journal fields
    public $journal_acceptance_date;
    public $journal_reviewer_comments;
    public $journal_link;
    public $journal_publishing_date;
    public $journal_doi_link;
    public $journal_publisher;
    
    public $created_at;
    public $updated_at;
    
    public function __construct($db = null) {
        if ($db) {
            $this->conn = $db;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }
    
    // Generate unique publication ID
    public function generatePublicationId() {
        $prefix = "PUB";
        $year = date('Y');
        
        // Get the next sequence number for this year
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE publication_id LIKE ? AND YEAR(created_at) = ?";
        $stmt = $this->conn->prepare($query);
        $search_pattern = $prefix . $year . "%";
        $stmt->execute([$search_pattern, $year]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $sequence = str_pad($result['count'] + 1, 4, "0", STR_PAD_LEFT);
        return $prefix . $year . $sequence;
    }
    
    // Create publication
    public function create() {
        try {
            $this->conn->beginTransaction();
            
            // Generate publication ID if not set
            if (empty($this->publication_id)) {
                $this->publication_id = $this->generatePublicationId();
            }
            
            $query = "INSERT INTO " . $this->table_name . " 
                     SET publication_id = :publication_id,
                         project_id = :project_id,
                         paper_title = :paper_title,
                         venue_type = :venue_type,
                         conference_acceptance_date = :conf_acceptance_date,
                         conference_reviewer_comments = :conf_reviewer_comments,
                         conference_presentation_date = :conf_presentation_date,
                         conference_camera_ready_submission_date = :conf_camera_ready_date,
                         conference_copyright_submission_date = :conf_copyright_date,
                         conference_doi_link = :conf_doi_link,
                         conference_publisher = :conf_publisher,
                         journal_acceptance_date = :jour_acceptance_date,
                         journal_reviewer_comments = :jour_reviewer_comments,
                         journal_link = :jour_link,
                         journal_publishing_date = :jour_publishing_date,
                         journal_doi_link = :jour_doi_link,
                         journal_publisher = :jour_publisher";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind values
            $stmt->bindParam(':publication_id', $this->publication_id);
            $stmt->bindParam(':project_id', $this->project_id);
            $stmt->bindParam(':paper_title', $this->paper_title);
            $stmt->bindParam(':venue_type', $this->venue_type);
            
            // Conference fields
            $stmt->bindParam(':conf_acceptance_date', $this->conference_acceptance_date);
            $stmt->bindParam(':conf_reviewer_comments', $this->conference_reviewer_comments);
            $stmt->bindParam(':conf_presentation_date', $this->conference_presentation_date);
            $stmt->bindParam(':conf_camera_ready_date', $this->conference_camera_ready_submission_date);
            $stmt->bindParam(':conf_copyright_date', $this->conference_copyright_submission_date);
            $stmt->bindParam(':conf_doi_link', $this->conference_doi_link);
            $stmt->bindParam(':conf_publisher', $this->conference_publisher);
            
            // Journal fields
            $stmt->bindParam(':jour_acceptance_date', $this->journal_acceptance_date);
            $stmt->bindParam(':jour_reviewer_comments', $this->journal_reviewer_comments);
            $stmt->bindParam(':jour_link', $this->journal_link);
            $stmt->bindParam(':jour_publishing_date', $this->journal_publishing_date);
            $stmt->bindParam(':jour_doi_link', $this->journal_doi_link);
            $stmt->bindParam(':jour_publisher', $this->journal_publisher);
            
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                
                // Add to status history
                $this->addStatusHistory('Draft', $_SESSION['user_id']);
                
                $this->conn->commit();
                return true;
            }
            
            $this->conn->rollback();
            return false;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Publication creation error: " . $e->getMessage());
            return false;
        }
    }
    
    // Read all publications with filters
    public function readAll($filters = []) {
        $query = "SELECT p.*, 
                         pr.project_name, pr.project_id as project_code,
                         u.full_name as lead_mentor_name,
                         GROUP_CONCAT(DISTINCT CONCAT(s.full_name, ' (', s.student_id, ')') SEPARATOR ', ') as students,
                         COUNT(DISTINCT ps.student_id) as student_count
                  FROM " . $this->table_name . " p
                  LEFT JOIN projects pr ON p.project_id = pr.id
                  LEFT JOIN users u ON pr.lead_mentor_id = u.id
                  LEFT JOIN publication_students ps ON p.id = ps.publication_id
                  LEFT JOIN students s ON ps.student_id = s.id";
        
        $where_conditions = [];
        $params = [];
        
        // Apply filters
        if (!empty($filters['venue_type'])) {
            $where_conditions[] = "p.venue_type = ?";
            $params[] = $filters['venue_type'];
        }
        
        if (!empty($filters['project_id'])) {
            $where_conditions[] = "p.project_id = ?";
            $params[] = $filters['project_id'];
        }
        
        if (!empty($filters['search'])) {
            $where_conditions[] = "(p.paper_title LIKE ? OR p.publication_id LIKE ? OR pr.project_name LIKE ?)";
            $search_term = "%" . $filters['search'] . "%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($where_conditions)) {
            $query .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        $query .= " GROUP BY p.id ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt;
    }
    
    // Read single publication
    public function readOne() {
        $query = "SELECT p.*, 
                         pr.project_name, pr.project_id as project_code,
                         u.full_name as lead_mentor_name
                  FROM " . $this->table_name . " p
                  LEFT JOIN projects pr ON p.project_id = pr.id
                  LEFT JOIN users u ON pr.lead_mentor_id = u.id
                  WHERE p.id = ?
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->publication_id = $row['publication_id'];
            $this->project_id = $row['project_id'];
            $this->paper_title = $row['paper_title'];
            $this->venue_type = $row['venue_type'];
            
            // Conference fields
            $this->conference_acceptance_date = $row['conference_acceptance_date'];
            $this->conference_reviewer_comments = $row['conference_reviewer_comments'];
            $this->conference_presentation_date = $row['conference_presentation_date'];
            $this->conference_camera_ready_submission_date = $row['conference_camera_ready_submission_date'];
            $this->conference_copyright_submission_date = $row['conference_copyright_submission_date'];
            $this->conference_doi_link = $row['conference_doi_link'];
            $this->conference_publisher = $row['conference_publisher'];
            
            // Journal fields
            $this->journal_acceptance_date = $row['journal_acceptance_date'];
            $this->journal_reviewer_comments = $row['journal_reviewer_comments'];
            $this->journal_link = $row['journal_link'];
            $this->journal_publishing_date = $row['journal_publishing_date'];
            $this->journal_doi_link = $row['journal_doi_link'];
            $this->journal_publisher = $row['journal_publisher'];
            
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return $row;
        }
        
        return false;
    }
    
    // Update publication
    public function update() {
        try {
            $this->conn->beginTransaction();
            
            // Get current values for audit log
            $current = $this->readOne();
            
            $query = "UPDATE " . $this->table_name . " 
                     SET paper_title = :paper_title,
                         venue_type = :venue_type,
                         conference_acceptance_date = :conf_acceptance_date,
                         conference_reviewer_comments = :conf_reviewer_comments,
                         conference_presentation_date = :conf_presentation_date,
                         conference_camera_ready_submission_date = :conf_camera_ready_date,
                         conference_copyright_submission_date = :conf_copyright_date,
                         conference_doi_link = :conf_doi_link,
                         conference_publisher = :conf_publisher,
                         journal_acceptance_date = :jour_acceptance_date,
                         journal_reviewer_comments = :jour_reviewer_comments,
                         journal_link = :jour_link,
                         journal_publishing_date = :jour_publishing_date,
                         journal_doi_link = :jour_doi_link,
                         journal_publisher = :jour_publisher,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind values
            $stmt->bindParam(':paper_title', $this->paper_title);
            $stmt->bindParam(':venue_type', $this->venue_type);
            $stmt->bindParam(':id', $this->id);
            
            // Conference fields
            $stmt->bindParam(':conf_acceptance_date', $this->conference_acceptance_date);
            $stmt->bindParam(':conf_reviewer_comments', $this->conference_reviewer_comments);
            $stmt->bindParam(':conf_presentation_date', $this->conference_presentation_date);
            $stmt->bindParam(':conf_camera_ready_date', $this->conference_camera_ready_submission_date);
            $stmt->bindParam(':conf_copyright_date', $this->conference_copyright_submission_date);
            $stmt->bindParam(':conf_doi_link', $this->conference_doi_link);
            $stmt->bindParam(':conf_publisher', $this->conference_publisher);
            
            // Journal fields
            $stmt->bindParam(':jour_acceptance_date', $this->journal_acceptance_date);
            $stmt->bindParam(':jour_reviewer_comments', $this->journal_reviewer_comments);
            $stmt->bindParam(':jour_link', $this->journal_link);
            $stmt->bindParam(':jour_publishing_date', $this->journal_publishing_date);
            $stmt->bindParam(':jour_doi_link', $this->journal_doi_link);
            $stmt->bindParam(':jour_publisher', $this->journal_publisher);
            
            if ($stmt->execute()) {
                // Log changes in audit log
                $this->logChanges($current, $_SESSION['user_id']);
                
                $this->conn->commit();
                return true;
            }
            
            $this->conn->rollback();
            return false;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Publication update error: " . $e->getMessage());
            return false;
        }
    }
    
    // Delete publication
    public function delete() {
        try {
            $this->conn->beginTransaction();
            
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            
            if ($stmt->execute()) {
                $this->conn->commit();
                return true;
            }
            
            $this->conn->rollback();
            return false;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Publication deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get all projects for dropdown
    public function getProjects() {
        $query = "SELECT id, project_id, project_name FROM projects ORDER BY project_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // Get students for a project
    public function getProjectStudents($project_id) {
        $query = "SELECT s.id, s.student_id, s.full_name 
                  FROM project_students ps
                  JOIN students s ON ps.student_id = s.id
                  WHERE ps.project_id = ?
                  ORDER BY s.full_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$project_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get mentors for a project
    public function getProjectMentors($project_id) {
        $query = "SELECT u.id, u.full_name 
                  FROM projects p
                  JOIN users u ON p.lead_mentor_id = u.id
                  WHERE p.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$project_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Associate students with publication
    public function associateStudents($student_ids) {
        try {
            // Clear existing associations
            $query = "DELETE FROM publication_students WHERE publication_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$this->id]);
            
            // Add new associations
            if (!empty($student_ids)) {
                $query = "INSERT INTO publication_students (publication_id, student_id) VALUES (?, ?)";
                $stmt = $this->conn->prepare($query);
                
                foreach ($student_ids as $student_id) {
                    $stmt->execute([$this->id, $student_id]);
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Student association error: " . $e->getMessage());
            return false;
        }
    }
    
    // Associate mentors with publication
    public function associateMentors($mentor_ids, $lead_mentor_id = null) {
        try {
            // Clear existing associations
            $query = "DELETE FROM publication_mentors WHERE publication_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$this->id]);
            
            // Add new associations
            if (!empty($mentor_ids)) {
                $query = "INSERT INTO publication_mentors (publication_id, mentor_id, is_lead_mentor) VALUES (?, ?, ?)";
                $stmt = $this->conn->prepare($query);
                
                foreach ($mentor_ids as $mentor_id) {
                    $is_lead = ($lead_mentor_id && $mentor_id == $lead_mentor_id) ? 1 : 0;
                    $stmt->execute([$this->id, $mentor_id, $is_lead]);
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Mentor association error: " . $e->getMessage());
            return false;
        }
    }
    
    // Add status history
    public function addStatusHistory($status, $changed_by) {
        $query = "INSERT INTO publication_status_history (publication_id, status, changed_by) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$this->id, $status, $changed_by]);
    }
    
    // Get status history
    public function getStatusHistory() {
        $query = "SELECT psh.*, u.full_name as changed_by_name 
                  FROM publication_status_history psh
                  JOIN users u ON psh.changed_by = u.id
                  WHERE psh.publication_id = ?
                  ORDER BY psh.timestamp DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Log changes for audit
    private function logChanges($old_data, $changed_by) {
        $current_data = $this->readOne();
        $fields_to_track = [
            'paper_title', 'venue_type', 'conference_acceptance_date', 'conference_reviewer_comments',
            'conference_presentation_date', 'conference_camera_ready_submission_date', 
            'conference_copyright_submission_date', 'conference_doi_link', 'conference_publisher',
            'journal_acceptance_date', 'journal_reviewer_comments', 'journal_link',
            'journal_publishing_date', 'journal_doi_link', 'journal_publisher'
        ];
        
        $query = "INSERT INTO publication_audit_log (publication_id, field_name, old_value, new_value, changed_by) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        foreach ($fields_to_track as $field) {
            if ($old_data[$field] !== $current_data[$field]) {
                $stmt->execute([$this->id, $field, $old_data[$field], $current_data[$field], $changed_by]);
            }
        }
    }
    
    // Get audit log
    public function getAuditLog() {
        $query = "SELECT pal.*, u.full_name as changed_by_name 
                  FROM publication_audit_log pal
                  JOIN users u ON pal.changed_by = u.id
                  WHERE pal.publication_id = ?
                  ORDER BY pal.timestamp DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get publication statistics
    public function getStatistics() {
        $stats = [];
        
        // Total publications
        $stmt = $this->conn->query("SELECT COUNT(*) as total FROM publications");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // By venue type
        $stmt = $this->conn->query("SELECT venue_type, COUNT(*) as count FROM publications GROUP BY venue_type");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['by_venue'][$row['venue_type']] = $row['count'];
        }
        
        // Recent publications (last 30 days)
        $stmt = $this->conn->query("SELECT COUNT(*) as recent FROM publications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['recent'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent'];
        
        return $stats;
    }
}
?> 