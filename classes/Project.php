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
        try {
            $this->conn->beginTransaction();
            
            // Validate required fields
            if (empty($this->project_name)) {
                throw new Exception('Project name is required');
            }
            
            // Validate foreign key references
            if (!empty($this->status_id) && !$this->statusExists($this->status_id)) {
                throw new Exception('Invalid status ID');
            }
            
            if (!empty($this->lead_mentor_id) && !$this->mentorExists($this->lead_mentor_id)) {
                throw new Exception('Invalid lead mentor ID');
            }
            
            if (!empty($this->subject_id) && !$this->subjectExists($this->subject_id)) {
                throw new Exception('Invalid subject ID');
            }
            
            if (!empty($this->rbm_id) && !$this->rbmExists($this->rbm_id)) {
                throw new Exception('Invalid RBM ID');
            }
            
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
                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
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
        
        // Handle sorting by deadline
        if (!empty($filters['sort_by'])) {
            switch ($filters['sort_by']) {
                case 'overdue':
                    $query .= " ORDER BY CASE 
                                    WHEN p.end_date IS NULL THEN 1 
                                    WHEN p.end_date < CURDATE() THEN 0 
                                    ELSE 1 
                                END, p.end_date ASC";
                    break;
                case 'due_soon':
                    $query .= " ORDER BY CASE 
                                    WHEN p.end_date IS NULL THEN 1 
                                    WHEN p.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 0 
                                    ELSE 1 
                                END, p.end_date ASC";
                    break;
                case 'deadline_asc':
                    $query .= " ORDER BY CASE 
                                    WHEN p.end_date IS NULL THEN 1 
                                    ELSE 0 
                                END, p.end_date ASC";
                    break;
                case 'deadline_desc':
                    $query .= " ORDER BY CASE 
                                    WHEN p.end_date IS NULL THEN 1 
                                    ELSE 0 
                                END, p.end_date DESC";
                    break;
                case 'created_desc':
                    $query .= " ORDER BY p.created_at DESC";
                    break;
                default:
                    $query .= " ORDER BY p.created_at DESC";
                    break;
            }
        } else {
            $query .= " ORDER BY p.created_at DESC";
        }
        
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
    public function addTag($tag_name, $color = '#007bff') {
        // Try with tag_color first, fall back to color if that fails
        try {
            $query = "INSERT INTO project_tags (tag_name, tag_color) VALUES (:tag_name, :tag_color)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':tag_name', $tag_name);
            $stmt->bindParam(':tag_color', $color);
            return $stmt->execute();
        } catch (PDOException $e) {
            // If tag_color column doesn't exist, try with color column
            if (strpos($e->getMessage(), 'tag_color') !== false) {
                $query = "INSERT INTO project_tags (tag_name, color) VALUES (:tag_name, :color)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':tag_name', $tag_name);
                $stmt->bindParam(':color', $color);
                return $stmt->execute();
            }
            // If it's a different error, re-throw it
            throw $e;
        }
    }
    
    // Assign students to project with better duplicate handling
    public function assignStudents($project_id, $student_ids) {
        try {
            $this->conn->beginTransaction();
            
            // Validate project exists
            if (!$this->projectExists($project_id)) {
                throw new Exception('Project does not exist');
            }
            
            // First, remove existing assignments
            $query = "DELETE FROM project_students WHERE project_id = :project_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':project_id', $project_id);
            $stmt->execute();
            
            // Add new assignments with validation
            if (!empty($student_ids) && is_array($student_ids)) {
                $query = "INSERT IGNORE INTO project_students (project_id, student_id, assigned_date, role, is_active) 
                         SELECT :project_id, :student_id, CURDATE(), 'Team Member', 1
                         FROM students WHERE id = :student_id";
                $stmt = $this->conn->prepare($query);
                
                foreach ($student_ids as $student_id) {
                    // Validate student exists before assignment
                    if ($this->studentExists($student_id)) {
                        $stmt->bindParam(':project_id', $project_id);
                        $stmt->bindParam(':student_id', $student_id);
                        $stmt->execute();
                    }
                }
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    
    // Get assigned students for a project
    public function getAssignedStudents($project_id) {
        $query = "SELECT s.id, s.student_id, s.full_name, s.grade, s.email_address, 
                         ps.assigned_date, ps.role, ps.is_active
                  FROM students s
                  JOIN project_students ps ON s.id = ps.student_id
                  WHERE ps.project_id = :project_id AND ps.is_active = 1
                  ORDER BY s.full_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Assign tags to project with better duplicate handling
    public function assignTags($project_id, $tag_ids) {
        try {
            $this->conn->beginTransaction();
            
            // Validate project exists
            if (!$this->projectExists($project_id)) {
                throw new Exception('Project does not exist');
            }
            
            // First, remove existing assignments
            $query = "DELETE FROM project_tag_assignments WHERE project_id = :project_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':project_id', $project_id);
            $stmt->execute();
            
            // Add new assignments with validation
            if (!empty($tag_ids) && is_array($tag_ids)) {
                $query = "INSERT IGNORE INTO project_tag_assignments (project_id, tag_id) 
                         SELECT :project_id, :tag_id
                         FROM project_tags WHERE id = :tag_id";
                $stmt = $this->conn->prepare($query);
                
                foreach ($tag_ids as $tag_id) {
                    // Validate tag exists before assignment
                    if ($this->tagExists($tag_id)) {
                        $stmt->bindParam(':project_id', $project_id);
                        $stmt->bindParam(':tag_id', $tag_id);
                        $stmt->execute();
                    }
                }
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    
    // Get assigned tags for a project
    public function getAssignedTags($project_id) {
        // Try with tag_color first, fall back to color if that fails
        try {
            $query = "SELECT t.id, t.tag_name, t.tag_color as color
                      FROM project_tags t
                      JOIN project_tag_assignments pta ON t.id = pta.tag_id
                      WHERE pta.project_id = :project_id
                      ORDER BY t.tag_name";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':project_id', $project_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // If tag_color column doesn't exist, try with color column
            if (strpos($e->getMessage(), 'tag_color') !== false) {
                $query = "SELECT t.id, t.tag_name, 
                                COALESCE(t.color, '#007bff') as color
                          FROM project_tags t
                          JOIN project_tag_assignments pta ON t.id = pta.tag_id
                          WHERE pta.project_id = :project_id
                          ORDER BY t.tag_name";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':project_id', $project_id);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            // If it's a different error, re-throw it
            throw $e;
        }
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
    
    // Assign mentors to project with better duplicate handling
    public function assignMentors($project_id, $mentor_ids) {
        try {
            $this->conn->beginTransaction();
            
            // Validate project exists
            if (!$this->projectExists($project_id)) {
                throw new Exception('Project does not exist');
            }
            
            // First, remove existing assignments
            $query = "DELETE FROM project_mentors WHERE project_id = :project_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':project_id', $project_id);
            $stmt->execute();
            
            // Add new assignments with validation
            if (!empty($mentor_ids) && is_array($mentor_ids)) {
                $query = "INSERT IGNORE INTO project_mentors (project_id, mentor_id, assigned_date, role, is_active) 
                         SELECT :project_id, :mentor_id, CURDATE(), 'Mentor', 1
                         FROM users WHERE id = :mentor_id AND user_type = 'mentor'";
                $stmt = $this->conn->prepare($query);
                
                foreach ($mentor_ids as $mentor_id) {
                    // Validate mentor exists and is of correct type
                    if ($this->mentorExists($mentor_id)) {
                        $stmt->bindParam(':project_id', $project_id);
                        $stmt->bindParam(':mentor_id', $mentor_id);
                        $stmt->execute();
                    }
                }
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    
    // Get assigned mentors for a project
    public function getAssignedMentors($project_id) {
        $query = "SELECT u.id, u.full_name, u.specialization, pm.assigned_date, pm.role
                  FROM users u
                  JOIN project_mentors pm ON u.id = pm.mentor_id
                  WHERE pm.project_id = :project_id AND pm.is_active = 1
                  ORDER BY u.full_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Validation helper methods
    private function projectExists($project_id) {
        $query = "SELECT id FROM projects WHERE id = :project_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function studentExists($student_id) {
        $query = "SELECT id FROM students WHERE id = :student_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function tagExists($tag_id) {
        $query = "SELECT id FROM project_tags WHERE id = :tag_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tag_id', $tag_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function mentorExists($mentor_id) {
        $query = "SELECT id FROM users WHERE id = :mentor_id AND user_type = 'mentor' AND status = 'active' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentor_id', $mentor_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function statusExists($status_id) {
        $query = "SELECT id FROM project_statuses WHERE id = :status_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status_id', $status_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function subjectExists($subject_id) {
        $query = "SELECT id FROM subjects WHERE id = :subject_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':subject_id', $subject_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function rbmExists($rbm_id) {
        $query = "SELECT id FROM users WHERE id = :rbm_id AND user_type = 'rbm' AND status = 'active' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rbm_id', $rbm_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
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
    
    // Get completed projects (Project Execution - completed only)
    public function getCompletedProjects($filters = []) {
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
                  WHERE ps.status_name = 'Project Execution - completed'";
        
        $params = [];
        
        // Add search filter
        if (isset($filters['search']) && !empty($filters['search'])) {
            $query .= " AND (p.project_name LIKE :search OR p.project_id LIKE :search OR p.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Add mentor filter
        if (isset($filters['lead_mentor_id']) && !empty($filters['lead_mentor_id'])) {
            $query .= " AND p.lead_mentor_id = :lead_mentor_id";
            $params[':lead_mentor_id'] = $filters['lead_mentor_id'];
        }
        
        // Add RBM filter
        if (isset($filters['rbm_id']) && !empty($filters['rbm_id'])) {
            $query .= " AND p.rbm_id = :rbm_id";
            $params[':rbm_id'] = $filters['rbm_id'];
        }
        
        // Add sorting
        $sort_by = $filters['sort_by'] ?? 'completion_date';
        $sort_order = ($sort_by == 'completion_date') ? 'DESC' : 'ASC';
        $query .= " ORDER BY " . $sort_by . " " . $sort_order;
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Move completed project back to active (in progress)
    public function moveBackToActive($project_id) {
        // Get the status ID for "Project Execution - in progress"
        $status_query = "SELECT id FROM project_statuses WHERE status_name = 'Project Execution - in progress' LIMIT 1";
        $status_stmt = $this->conn->prepare($status_query);
        $status_stmt->execute();
        $status_result = $status_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$status_result) {
            return false;
        }
        
        $in_progress_status_id = $status_result['id'];
        
        // Update the project status and clear completion date
        $query = "UPDATE " . $this->table_name . " 
                  SET status_id = :status_id, 
                      completion_date = NULL,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :project_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status_id', $in_progress_status_id);
        $stmt->bindParam(':project_id', $project_id);
        
        return $stmt->execute();
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