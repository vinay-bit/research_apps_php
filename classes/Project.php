<?php
require_once __DIR__ . '/../config/database.php';

class Project {
    private $conn;
    private $table_name = "projects";
    
    // Project properties
    public $id;
    public $project_id;
    public $project_name;
    public $status_id;
    public $lead_mentor_id;
    public $subject_id;
    public $has_prototype;
    public $start_date;
    public $end_date;
    public $assigned_date;
    public $completion_date;
    public $drive_link;
    public $rbm_id;
    public $description;
    public $notes;
    public $is_active;
    public $created_at;
    public $updated_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Create new project
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET project_name = :project_name,
                      status_id = :status_id,
                      lead_mentor_id = :lead_mentor_id,
                      subject_id = :subject_id,
                      has_prototype = :has_prototype,
                      start_date = :start_date,
                      assigned_date = :assigned_date,
                      completion_date = :completion_date,
                      drive_link = :drive_link,
                      rbm_id = :rbm_id,
                      description = :description,
                      notes = :notes";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->project_name = htmlspecialchars(strip_tags($this->project_name));
        $this->drive_link = htmlspecialchars(strip_tags($this->drive_link));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->notes = htmlspecialchars(strip_tags($this->notes));
        
        // Bind parameters
        $stmt->bindParam(':project_name', $this->project_name);
        $stmt->bindParam(':status_id', $this->status_id);
        $stmt->bindParam(':lead_mentor_id', $this->lead_mentor_id);
        $stmt->bindParam(':subject_id', $this->subject_id);
        $stmt->bindParam(':has_prototype', $this->has_prototype);
        $stmt->bindParam(':start_date', $this->start_date);
        $stmt->bindParam(':assigned_date', $this->assigned_date);
        $stmt->bindParam(':completion_date', $this->completion_date);
        $stmt->bindParam(':drive_link', $this->drive_link);
        $stmt->bindParam(':rbm_id', $this->rbm_id);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':notes', $this->notes);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
    
    // Get all projects with related data
    public function getAll($filters = []) {
        $query = "SELECT p.*, 
                         ps.status_name,
                         s.subject_name,
                         mentor.full_name as mentor_name,
                         rbm.full_name as rbm_name,
                         rbm.branch as rbm_branch
                  FROM " . $this->table_name . " p
                  LEFT JOIN project_statuses ps ON p.status_id = ps.id
                  LEFT JOIN subjects s ON p.subject_id = s.id
                  LEFT JOIN users mentor ON p.lead_mentor_id = mentor.id
                  LEFT JOIN users rbm ON p.rbm_id = rbm.id
                  WHERE p.is_active = 1";
        
        // Apply filters
        $params = [];
        if (!empty($filters['status_id'])) {
            $query .= " AND p.status_id = :status_id";
            $params[':status_id'] = $filters['status_id'];
        }
        if (!empty($filters['lead_mentor_id'])) {
            $query .= " AND p.lead_mentor_id = :lead_mentor_id";
            $params[':lead_mentor_id'] = $filters['lead_mentor_id'];
        }
        if (!empty($filters['rbm_id'])) {
            $query .= " AND p.rbm_id = :rbm_id";
            $params[':rbm_id'] = $filters['rbm_id'];
        }
        if (!empty($filters['subject_id'])) {
            $query .= " AND p.subject_id = :subject_id";
            $params[':subject_id'] = $filters['subject_id'];
        }
        if (!empty($filters['search'])) {
            $query .= " AND (p.project_name LIKE :search OR p.project_id LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $query .= " ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get project by ID
    public function getById($id) {
        $query = "SELECT p.*, 
                         ps.status_name,
                         s.subject_name,
                         mentor.full_name as mentor_name,
                         rbm.full_name as rbm_name,
                         rbm.branch as rbm_branch
                  FROM " . $this->table_name . " p
                  LEFT JOIN project_statuses ps ON p.status_id = ps.id
                  LEFT JOIN subjects s ON p.subject_id = s.id
                  LEFT JOIN users mentor ON p.lead_mentor_id = mentor.id
                  LEFT JOIN users rbm ON p.rbm_id = rbm.id
                  WHERE p.id = :id AND p.is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Update project
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET project_name = :project_name,
                      status_id = :status_id,
                      lead_mentor_id = :lead_mentor_id,
                      subject_id = :subject_id,
                      has_prototype = :has_prototype,
                      start_date = :start_date,
                      assigned_date = :assigned_date,
                      completion_date = :completion_date,
                      drive_link = :drive_link,
                      rbm_id = :rbm_id,
                      description = :description,
                      notes = :notes,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->project_name = htmlspecialchars(strip_tags($this->project_name));
        $this->drive_link = htmlspecialchars(strip_tags($this->drive_link));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->notes = htmlspecialchars(strip_tags($this->notes));
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':project_name', $this->project_name);
        $stmt->bindParam(':status_id', $this->status_id);
        $stmt->bindParam(':lead_mentor_id', $this->lead_mentor_id);
        $stmt->bindParam(':subject_id', $this->subject_id);
        $stmt->bindParam(':has_prototype', $this->has_prototype);
        $stmt->bindParam(':start_date', $this->start_date);
        $stmt->bindParam(':assigned_date', $this->assigned_date);
        $stmt->bindParam(':completion_date', $this->completion_date);
        $stmt->bindParam(':drive_link', $this->drive_link);
        $stmt->bindParam(':rbm_id', $this->rbm_id);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':notes', $this->notes);
        
        return $stmt->execute();
    }
    
    // Delete project (soft delete)
    public function delete($id) {
        $query = "UPDATE " . $this->table_name . " SET is_active = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    // Get all project statuses
    public function getStatuses() {
        $query = "SELECT * FROM project_statuses WHERE is_active = 1 ORDER BY status_order";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Add new status
    public function addStatus($status_name) {
        $query = "INSERT INTO project_statuses (status_name, status_order) 
                  VALUES (:status_name, (SELECT COALESCE(MAX(status_order), 0) + 1 FROM project_statuses ps))";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status_name', $status_name);
        return $stmt->execute();
    }
    
    // Get all subjects
    public function getSubjects() {
        $query = "SELECT * FROM subjects WHERE is_active = 1 ORDER BY subject_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Add new subject
    public function addSubject($subject_name, $subject_code = '') {
        $query = "INSERT INTO subjects (subject_name, subject_code) VALUES (:subject_name, :subject_code)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':subject_name', $subject_name);
        $stmt->bindParam(':subject_code', $subject_code);
        return $stmt->execute();
    }
    
    // Get all mentors
    public function getMentors() {
        $query = "SELECT id, full_name, email, specialization 
                  FROM users 
                  WHERE user_type = 'mentor' AND status = 'active' 
                  ORDER BY full_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get all RBMs
    public function getRBMs() {
        $query = "SELECT id, full_name, email, branch 
                  FROM users 
                  WHERE user_type = 'rbm' AND status = 'active' 
                  ORDER BY full_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get all students
    public function getStudents() {
        $query = "SELECT id, student_id, full_name, grade, email_address, affiliation 
                  FROM students 
                  WHERE 1 = 1 
                  ORDER BY full_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get all tags
    public function getTags() {
        $query = "SELECT * FROM project_tags WHERE is_active = 1 ORDER BY tag_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Add new tag
    public function addTag($tag_name, $tag_color = '#007bff') {
        $query = "INSERT INTO project_tags (tag_name, tag_color) VALUES (:tag_name, :tag_color)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tag_name', $tag_name);
        $stmt->bindParam(':tag_color', $tag_color);
        return $stmt->execute();
    }
    
    // Assign students to project
    public function assignStudents($project_id, $student_ids) {
        // First, remove existing assignments
        $query = "DELETE FROM project_students WHERE project_id = :project_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        
        // Add new assignments
        if (!empty($student_ids)) {
            $query = "INSERT INTO project_students (project_id, student_id) VALUES (:project_id, :student_id)";
            $stmt = $this->conn->prepare($query);
            
            foreach ($student_ids as $student_id) {
                $stmt->bindParam(':project_id', $project_id);
                $stmt->bindParam(':student_id', $student_id);
                $stmt->execute();
            }
        }
        return true;
    }
    
    // Get assigned students for a project
    public function getAssignedStudents($project_id) {
        $query = "SELECT s.id, s.student_id, s.full_name, s.grade, s.email_address, ps.assigned_date
                  FROM students s
                  JOIN project_students ps ON s.id = ps.student_id
                  WHERE ps.project_id = :project_id AND ps.is_active = 1
                  ORDER BY s.full_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Assign tags to project
    public function assignTags($project_id, $tag_ids) {
        // First, remove existing assignments
        $query = "DELETE FROM project_tag_assignments WHERE project_id = :project_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        
        // Add new assignments
        if (!empty($tag_ids)) {
            $query = "INSERT INTO project_tag_assignments (project_id, tag_id) VALUES (:project_id, :tag_id)";
            $stmt = $this->conn->prepare($query);
            
            foreach ($tag_ids as $tag_id) {
                $stmt->bindParam(':project_id', $project_id);
                $stmt->bindParam(':tag_id', $tag_id);
                $stmt->execute();
            }
        }
        return true;
    }
    
    // Get assigned tags for a project
    public function getAssignedTags($project_id) {
        $query = "SELECT t.id, t.tag_name, t.tag_color
                  FROM project_tags t
                  JOIN project_tag_assignments pta ON t.id = pta.tag_id
                  WHERE pta.project_id = :project_id
                  ORDER BY t.tag_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get project statistics
    public function getStatistics() {
        $stats = [];
        
        // Total projects
        $query = "SELECT COUNT(*) as total FROM projects WHERE is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Projects by status
        $query = "SELECT ps.status_name, COUNT(*) as count
                  FROM projects p
                  JOIN project_statuses ps ON p.status_id = ps.id
                  WHERE p.is_active = 1
                  GROUP BY ps.status_name
                  ORDER BY ps.status_order";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Projects with prototypes
        $query = "SELECT COUNT(*) as total FROM projects WHERE has_prototype = 'Yes' AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['with_prototypes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Projects by subject
        $query = "SELECT s.subject_name, COUNT(*) as count
                  FROM projects p
                  JOIN subjects s ON p.subject_id = s.id
                  WHERE p.is_active = 1
                  GROUP BY s.subject_name
                  ORDER BY count DESC
                  LIMIT 5";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['by_subject'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    // Get project activity log
    public function getActivityLog($project_id, $limit = 10) {
        $query = "SELECT pal.*, u.full_name as user_name
                  FROM project_activity_log pal
                  LEFT JOIN users u ON pal.user_id = u.id
                  WHERE pal.project_id = :project_id
                  ORDER BY pal.created_at DESC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?> 