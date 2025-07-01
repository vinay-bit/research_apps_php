<?php
require_once __DIR__ . '/../config/database.php';

class Student {
    private $conn;
    private $table_name = "students";

    public $id;
    public $student_id;
    public $full_name;
    public $affiliation;
    public $grade;
    public $counselor_id;
    public $rbm_id;
    public $board_id;
    public $contact_no;
    public $email_address;
    public $application_year;
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

    // Generate unique student ID with better duplicate prevention
    private function generateStudentId() {
        $year = date('Y');
        $prefix = 'STU' . $year;
        $max_attempts = 100;
        $attempt = 0;
        
        while ($attempt < $max_attempts) {
            // Get the last student ID for this year with proper locking
            $query = "SELECT student_id FROM " . $this->table_name . " 
                     WHERE student_id LIKE ? 
                     ORDER BY CAST(SUBSTRING(student_id, 8) AS UNSIGNED) DESC 
                     LIMIT 1 FOR UPDATE";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$prefix . '%']);
            
            if ($stmt->rowCount() > 0) {
                $last_id = $stmt->fetch(PDO::FETCH_ASSOC)['student_id'];
                $number = intval(substr($last_id, -4)) + 1;
            } else {
                $number = 1;
            }
            
            $new_id = $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
            
            // Double-check this ID doesn't exist
            if (!$this->studentIdExists($new_id)) {
                return $new_id;
            }
            
            $attempt++;
        }
        
        throw new Exception('Unable to generate unique student ID after ' . $max_attempts . ' attempts');
    }

    // Check if student ID exists (improved with parameter)
    public function studentIdExists($student_id = null) {
        $check_id = $student_id ?? $this->student_id;
        
        $query = "SELECT id FROM " . $this->table_name . " WHERE student_id = :student_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $check_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Create student with improved error handling
    public function create() {
        try {
            // Start transaction
            $this->conn->beginTransaction();
            
            // Generate student ID if not provided
            if (empty($this->student_id)) {
                $this->student_id = $this->generateStudentId();
            } else {
                // If student_id is provided, check if it already exists
                if ($this->studentIdExists()) {
                    throw new Exception('Student ID already exists: ' . $this->student_id);
                }
            }
            
            // Validate required fields
            if (empty($this->full_name)) {
                throw new Exception('Full name is required');
            }
            
            // Validate foreign key references
            if (!empty($this->counselor_id) && !$this->userExists($this->counselor_id)) {
                throw new Exception('Invalid counselor ID');
            }
            
            if (!empty($this->rbm_id) && !$this->userExists($this->rbm_id)) {
                throw new Exception('Invalid RBM ID');
            }
            
            if (!empty($this->board_id) && !$this->boardExists($this->board_id)) {
                throw new Exception('Invalid board ID');
            }
            
            $query = "INSERT INTO " . $this->table_name . " 
                    SET student_id=:student_id, full_name=:full_name, affiliation=:affiliation, 
                        grade=:grade, counselor_id=:counselor_id, rbm_id=:rbm_id, board_id=:board_id,
                        contact_no=:contact_no, email_address=:email_address, application_year=:application_year";

            $stmt = $this->conn->prepare($query);

            // Clean data
            $this->full_name = htmlspecialchars(strip_tags($this->full_name));
            $this->affiliation = htmlspecialchars(strip_tags($this->affiliation));
            $this->grade = htmlspecialchars(strip_tags($this->grade));
            $this->contact_no = htmlspecialchars(strip_tags($this->contact_no));
            $this->email_address = htmlspecialchars(strip_tags($this->email_address));

            // Bind data
            $stmt->bindParam(":student_id", $this->student_id);
            $stmt->bindParam(":full_name", $this->full_name);
            $stmt->bindParam(":affiliation", $this->affiliation);
            $stmt->bindParam(":grade", $this->grade);
            $stmt->bindParam(":counselor_id", $this->counselor_id);
            $stmt->bindParam(":rbm_id", $this->rbm_id);
            $stmt->bindParam(":board_id", $this->board_id);
            $stmt->bindParam(":contact_no", $this->contact_no);
            $stmt->bindParam(":email_address", $this->email_address);
            $stmt->bindParam(":application_year", $this->application_year);

            if($stmt->execute()) {
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

    // Validate user exists
    private function userExists($user_id) {
        $query = "SELECT id FROM users WHERE id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Validate board exists
    private function boardExists($board_id) {
        $query = "SELECT id FROM boards WHERE id = :board_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':board_id', $board_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Read all students with improved joins
    public function read() {
        $query = "SELECT s.*, 
                         rbm.full_name as rbm_name, rbm.branch as rbm_branch,
                         c.full_name as counselor_name, c.user_type as counselor_type,
                         b.name as board_name
                FROM " . $this->table_name . " s
                LEFT JOIN users rbm ON s.rbm_id = rbm.id AND rbm.user_type = 'rbm'
                LEFT JOIN users c ON s.counselor_id = c.id AND c.user_type IN ('councillor', 'admin')
                LEFT JOIN boards b ON s.board_id = b.id
                ORDER BY s.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Read single student with validation
    public function readOne() {
        $query = "SELECT s.*, 
                         rbm.full_name as rbm_name, rbm.branch as rbm_branch,
                         c.full_name as counselor_name, c.user_type as counselor_type,
                         b.name as board_name
                FROM " . $this->table_name . " s
                LEFT JOIN users rbm ON s.rbm_id = rbm.id
                LEFT JOIN users c ON s.counselor_id = c.id
                LEFT JOIN boards b ON s.board_id = b.id
                WHERE s.id = ? LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->student_id = $row['student_id'];
            $this->full_name = $row['full_name'];
            $this->affiliation = $row['affiliation'];
            $this->grade = $row['grade'];
            $this->counselor_id = $row['counselor_id'];
            $this->rbm_id = $row['rbm_id'];
            $this->board_id = $row['board_id'];
            $this->contact_no = $row['contact_no'];
            $this->email_address = $row['email_address'];
            $this->application_year = $row['application_year'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Update student with validation
    public function update() {
        try {
            // Start transaction
            $this->conn->beginTransaction();
            
            // Validate foreign key references
            if (!empty($this->counselor_id) && !$this->userExists($this->counselor_id)) {
                throw new Exception('Invalid counselor ID');
            }
            
            if (!empty($this->rbm_id) && !$this->userExists($this->rbm_id)) {
                throw new Exception('Invalid RBM ID');
            }
            
            if (!empty($this->board_id) && !$this->boardExists($this->board_id)) {
                throw new Exception('Invalid board ID');
            }
            
            $query = "UPDATE " . $this->table_name . " 
                    SET full_name=:full_name, affiliation=:affiliation, grade=:grade,
                        counselor_id=:counselor_id, rbm_id=:rbm_id, board_id=:board_id,
                        contact_no=:contact_no, email_address=:email_address, application_year=:application_year
                    WHERE id=:id";

            $stmt = $this->conn->prepare($query);

            $this->full_name = htmlspecialchars(strip_tags($this->full_name));
            $this->affiliation = htmlspecialchars(strip_tags($this->affiliation));
            $this->grade = htmlspecialchars(strip_tags($this->grade));
            $this->contact_no = htmlspecialchars(strip_tags($this->contact_no));
            $this->email_address = htmlspecialchars(strip_tags($this->email_address));

            $stmt->bindParam(':full_name', $this->full_name);
            $stmt->bindParam(':affiliation', $this->affiliation);
            $stmt->bindParam(':grade', $this->grade);
            $stmt->bindParam(':counselor_id', $this->counselor_id);
            $stmt->bindParam(':rbm_id', $this->rbm_id);
            $stmt->bindParam(':board_id', $this->board_id);
            $stmt->bindParam(':contact_no', $this->contact_no);
            $stmt->bindParam(':email_address', $this->email_address);
            $stmt->bindParam(':application_year', $this->application_year);
            $stmt->bindParam(':id', $this->id);

            if($stmt->execute()) {
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

    // Delete student with cascade handling
    public function delete() {
        try {
            $this->conn->beginTransaction();
            
            // Check for dependencies first
            $dependencies = $this->checkDependencies();
            if (!empty($dependencies)) {
                throw new Exception('Cannot delete student. Dependencies exist: ' . implode(', ', $dependencies));
            }
            
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);

            if($stmt->execute()) {
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

    // Check for dependencies before deletion
    private function checkDependencies() {
        $dependencies = [];
        
        // Check project assignments
        $query = "SELECT COUNT(*) as count FROM project_students WHERE student_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->id]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
            $dependencies[] = 'Project assignments';
        }
        
        // Check publication assignments
        $query = "SELECT COUNT(*) as count FROM ready_for_publication_students WHERE student_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->id]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
            $dependencies[] = 'Publication assignments';
        }
        
        return $dependencies;
    }

    // Search students with improved filtering
    public function search($search_term, $filters = []) {
        $query = "SELECT s.*, 
                         rbm.full_name as rbm_name, rbm.branch as rbm_branch,
                         c.full_name as counselor_name, c.user_type as counselor_type,
                         b.name as board_name
                FROM " . $this->table_name . " s
                LEFT JOIN users rbm ON s.rbm_id = rbm.id
                LEFT JOIN users c ON s.counselor_id = c.id
                LEFT JOIN boards b ON s.board_id = b.id
                WHERE (s.full_name LIKE :search OR s.student_id LIKE :search OR s.email_address LIKE :search)";
        
        $params = [':search' => '%' . $search_term . '%'];
        
        // Add filters
        if (!empty($filters['board_id'])) {
            $query .= " AND s.board_id = :board_id";
            $params[':board_id'] = $filters['board_id'];
        }
        
        if (!empty($filters['counselor_id'])) {
            $query .= " AND s.counselor_id = :counselor_id";
            $params[':counselor_id'] = $filters['counselor_id'];
        }
        
        if (!empty($filters['rbm_id'])) {
            $query .= " AND s.rbm_id = :rbm_id";
            $params[':rbm_id'] = $filters['rbm_id'];
        }
        
        if (!empty($filters['application_year'])) {
            $query .= " AND s.application_year = :application_year";
            $params[':application_year'] = $filters['application_year'];
        }
        
        $query .= " ORDER BY s.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt;
    }

    // Get students by RBM
    public function getByRBM($rbm_id) {
        $query = "SELECT s.*, 
                         rbm.full_name as rbm_name, rbm.branch as rbm_branch,
                         c.full_name as counselor_name, c.user_type as counselor_type,
                         b.name as board_name
                FROM " . $this->table_name . " s
                LEFT JOIN users rbm ON s.rbm_id = rbm.id
                LEFT JOIN users c ON s.counselor_id = c.id
                LEFT JOIN boards b ON s.board_id = b.id
                WHERE s.rbm_id = ?
                ORDER BY s.full_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $rbm_id);
        $stmt->execute();
        return $stmt;
    }

    // Get students by counselor
    public function getByCounselor($counselor_id) {
        $query = "SELECT s.*, 
                         rbm.full_name as rbm_name, rbm.branch as rbm_branch,
                         c.full_name as counselor_name, c.user_type as counselor_type,
                         b.name as board_name
                FROM " . $this->table_name . " s
                LEFT JOIN users rbm ON s.rbm_id = rbm.id
                LEFT JOIN users c ON s.counselor_id = c.id
                LEFT JOIN boards b ON s.board_id = b.id
                WHERE s.counselor_id = ?
                ORDER BY s.full_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $counselor_id);
        $stmt->execute();
        return $stmt;
    }

    // Get boards for dropdown
    public function getBoards() {
        $query = "SELECT * FROM boards ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add board safely
    public function addBoard($board_name) {
        $query = "INSERT IGNORE INTO boards (name) VALUES (?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([htmlspecialchars(strip_tags($board_name))]);
    }

    // Get counselors for dropdown
    public function getCounselors() {
        $query = "SELECT id, full_name, organization_name FROM users WHERE user_type = 'councillor' AND status = 'active' ORDER BY full_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all students for general use
    public function getAll() {
        $query = "SELECT s.*, 
                         rbm.full_name as rbm_name, rbm.branch as rbm_branch,
                         c.full_name as counselor_name, c.user_type as counselor_type,
                         b.name as board_name
                FROM " . $this->table_name . " s
                LEFT JOIN users rbm ON s.rbm_id = rbm.id
                LEFT JOIN users c ON s.counselor_id = c.id
                LEFT JOIN boards b ON s.board_id = b.id
                ORDER BY s.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get students by application year
    public function getByApplicationYear($application_year) {
        $query = "SELECT s.*, 
                         rbm.full_name as rbm_name, rbm.branch as rbm_branch,
                         c.full_name as counselor_name, c.user_type as counselor_type,
                         b.name as board_name
                FROM " . $this->table_name . " s
                LEFT JOIN users rbm ON s.rbm_id = rbm.id
                LEFT JOIN users c ON s.counselor_id = c.id
                LEFT JOIN boards b ON s.board_id = b.id
                WHERE s.application_year = ?
                ORDER BY s.full_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $application_year);
        $stmt->execute();
        return $stmt;
    }

    // Get available application years
    public function getApplicationYears() {
        $query = "SELECT DISTINCT application_year FROM " . $this->table_name . " 
                  WHERE application_year IS NOT NULL 
                  ORDER BY application_year DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>