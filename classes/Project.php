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
                      end_date = :end_date,
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
        $stmt->bindParam(':end_date', $this->end_date);
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
        $query = "SELECT DISTINCT p.*, 
                         ps.status_name,
                         s.subject_name,
                         mentor.full_name as mentor_name,
                         rbm.full_name as rbm_name,
                         rbm.branch as rbm_branch
                  FROM " . $this->table_name . " p
                  LEFT JOIN project_statuses ps ON p.status_id = ps.id
                  LEFT JOIN subjects s ON p.subject_id = s.id
                  LEFT JOIN users mentor ON p.lead_mentor_id = mentor.id
                  LEFT JOIN users rbm ON p.rbm_id = rbm.id";
        
        // Add tag join if filtering by tag
        if (!empty($filters['tag_id'])) {
            $query .= " INNER JOIN project_tag_assignments pta ON p.id = pta.project_id";
        }
        
        $query .= " WHERE 1 = 1";
        
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
        if (!empty($filters['tag_id'])) {
            $query .= " AND pta.tag_id = :tag_id";
            $params[':tag_id'] = $filters['tag_id'];
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
                  WHERE p.id = :id";
        
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
                      end_date = :end_date,
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
        $stmt->bindParam(':end_date', $this->end_date);
        $stmt->bindParam(':assigned_date', $this->assigned_date);
        $stmt->bindParam(':completion_date', $this->completion_date);
        $stmt->bindParam(':drive_link', $this->drive_link);
        $stmt->bindParam(':rbm_id', $this->rbm_id);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':notes', $this->notes);
        
        return $stmt->execute();
    }
    
    // Delete project
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    // Get all project statuses
    public function getStatuses() {
        $query = "SELECT * FROM project_statuses ORDER BY id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Add new status
    public function addStatus($status_name) {
        $query = "INSERT INTO project_statuses (status_name) VALUES (:status_name)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status_name', $status_name);
        return $stmt->execute();
    }
    
    // Get all subjects
    public function getSubjects() {
        $query = "SELECT * FROM subjects ORDER BY subject_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Add new subject
    public function addSubject($subject_name) {
        $query = "INSERT INTO subjects (subject_name) VALUES (:subject_name)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':subject_name', $subject_name);
        return $stmt->execute();
    }
    
    // Get all mentors
    public function getMentors() {
        $query = "SELECT id, full_name, specialization 
                  FROM users 
                  WHERE user_type = 'mentor' AND status = 'active' 
                  ORDER BY full_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get all RBMs
    public function getRBMs() {
        $query = "SELECT id, full_name, branch 
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
        $query = "SELECT * FROM project_tags ORDER BY tag_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Add new tag
    public function addTag($tag_name, $color = 'primary') {
        $query = "INSERT INTO project_tags (tag_name, color) VALUES (:tag_name, :color)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tag_name', $tag_name);
        $stmt->bindParam(':color', $color);
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
        $query = "SELECT s.id, s.student_id, s.full_name, s.grade, s.email_address, ps.assigned_at
                  FROM students s
                  JOIN project_students ps ON s.id = ps.student_id
                  WHERE ps.project_id = :project_id
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
        $query = "SELECT t.id, t.tag_name, t.color
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
        $query = "SELECT COUNT(*) as total FROM projects";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Projects by status
        $query = "SELECT ps.status_name, COUNT(*) as count
                  FROM projects p
                  JOIN project_statuses ps ON p.status_id = ps.id
                  GROUP BY ps.status_name
                  ORDER BY ps.id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Projects with prototypes
        $query = "SELECT COUNT(*) as total FROM projects WHERE has_prototype = 'Yes'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['with_prototypes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Projects by subject
        $query = "SELECT s.subject_name, COUNT(*) as count
                  FROM projects p
                  JOIN subjects s ON p.subject_id = s.id
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
    
    // Assign mentors to project
    public function assignMentors($project_id, $mentor_ids) {
        // First, remove existing assignments
        $query = "DELETE FROM project_mentors WHERE project_id = :project_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        
        // Add new assignments
        if (!empty($mentor_ids)) {
            $query = "INSERT INTO project_mentors (project_id, mentor_id) VALUES (:project_id, :mentor_id)";
            $stmt = $this->conn->prepare($query);
            
            foreach ($mentor_ids as $mentor_id) {
                $stmt->bindParam(':project_id', $project_id);
                $stmt->bindParam(':mentor_id', $mentor_id);
                $stmt->execute();
            }
        }
        return true;
    }
    
    // Get assigned mentors for a project
    public function getAssignedMentors($project_id) {
        $query = "SELECT u.id, u.full_name, u.specialization, pm.assigned_date
                  FROM users u
                  JOIN project_mentors pm ON u.id = pm.mentor_id
                  WHERE pm.project_id = :project_id
                  ORDER BY u.full_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Alias methods for edit.php compatibility
    public function getAllStatuses() {
        return $this->getStatuses();
    }
    
    public function getAllSubjects() {
        return $this->getSubjects();
    }
    
    public function getAllTags() {
        return $this->getTags();
    }
    
    // Update assignment methods for edit.php compatibility
    public function updateStudentAssignments($project_id, $student_ids) {
        return $this->assignStudents($project_id, $student_ids);
    }
    
    public function updateMentorAssignments($project_id, $mentor_ids) {
        return $this->assignMentors($project_id, $mentor_ids);
    }
    
    public function updateTagAssignments($project_id, $tag_ids) {
        return $this->assignTags($project_id, $tag_ids);
    }
    
    // Update project method that accepts project_id and data array
    public function updateProject($project_id, $data) {
        // Get current project data first
        $current_project = $this->getById($project_id);
        if (!$current_project) {
            return false;
        }
        
        // Set the properties from the data array, using current values as defaults
        $this->id = $project_id;
        $this->project_name = $data['project_name'] ?? $current_project['project_name'];
        $this->description = $data['description'] ?? $current_project['description'];
        $this->status_id = $data['status_id'] ?? $current_project['status_id'];
        $this->lead_mentor_id = $data['lead_mentor_id'] ?? $current_project['lead_mentor_id'];
        $this->subject_id = $data['subject_id'] ?? $current_project['subject_id'];
        $this->has_prototype = $data['has_prototype'] ?? $current_project['has_prototype'];
        $this->start_date = $data['start_date'] ?? $current_project['start_date'];
        $this->end_date = $data['end_date'] ?? $current_project['end_date'];
        $this->assigned_date = $data['assigned_date'] ?? $current_project['assigned_date'];
        $this->completion_date = $data['completion_date'] ?? $current_project['completion_date'];
        $this->drive_link = $data['drive_link'] ?? $current_project['drive_link'];
        $this->rbm_id = $data['rbm_id'] ?? $current_project['rbm_id'];
        $this->notes = $data['notes'] ?? $current_project['notes'];
        
        // Call the existing update method
        return $this->update();
    }
}
?>