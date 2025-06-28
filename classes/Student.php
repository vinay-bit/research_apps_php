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

    public function __construct($db) {
        $this->conn = $db;
    }

    // Generate unique student ID
    private function generateStudentId() {
        $year = date('Y');
        $prefix = 'STU' . $year;
        
        // Get the last student ID for this year
        $query = "SELECT student_id FROM " . $this->table_name . " 
                 WHERE student_id LIKE ? 
                 ORDER BY student_id DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$prefix . '%']);
        
        if ($stmt->rowCount() > 0) {
            $last_id = $stmt->fetch(PDO::FETCH_ASSOC)['student_id'];
            $number = intval(substr($last_id, -4)) + 1;
        } else {
            $number = 1;
        }
        
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    // Create student
    public function create() {
        // Generate student ID if not provided
        if (empty($this->student_id)) {
            $this->student_id = $this->generateStudentId();
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
            return true;
        }
        return false;
    }

    // Read all students
    public function read() {
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
        return $stmt;
    }

    // Read single student
    public function readOne() {
        $query = "SELECT s.*, 
                         rbm.full_name as rbm_name, rbm.branch as rbm_branch,
                         c.full_name as counselor_name, c.user_type as counselor_type,
                         b.name as board_name
                FROM " . $this->table_name . " s
                LEFT JOIN users rbm ON s.rbm_id = rbm.id
                LEFT JOIN users c ON s.counselor_id = c.id
                LEFT JOIN boards b ON s.board_id = b.id
                WHERE s.id = ? LIMIT 0,1";

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

    // Update student
    public function update() {
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
            return true;
        }
        return false;
    }

    // Delete student
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Check if student ID exists
    public function studentIdExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE student_id = :student_id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $this->student_id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }

    // Search students
    public function search($search_term) {
        $query = "SELECT s.*, 
                         rbm.full_name as rbm_name, rbm.branch as rbm_branch,
                         c.full_name as counselor_name, c.user_type as counselor_type,
                         b.name as board_name
                FROM " . $this->table_name . " s
                LEFT JOIN users rbm ON s.rbm_id = rbm.id
                LEFT JOIN users c ON s.counselor_id = c.id
                LEFT JOIN boards b ON s.board_id = b.id
                WHERE s.full_name LIKE :search_term 
                   OR s.student_id LIKE :search_term 
                   OR s.email_address LIKE :search_term
                   OR s.affiliation LIKE :search_term
                   OR s.contact_no LIKE :search_term
                ORDER BY s.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $search_term = "%{$search_term}%";
        $stmt->bindParam(':search_term', $search_term);
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
                WHERE s.rbm_id = :rbm_id
                ORDER BY s.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rbm_id', $rbm_id);
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
                WHERE s.counselor_id = :counselor_id
                ORDER BY s.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':counselor_id', $counselor_id);
        $stmt->execute();
        return $stmt;
    }
    
    // Get all boards
    public function getBoards() {
        $query = "SELECT * FROM boards ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // Add new board
    public function addBoard($board_name) {
        $query = "INSERT INTO boards (name) VALUES (:name)";
        $stmt = $this->conn->prepare($query);
        $board_name = htmlspecialchars(strip_tags($board_name));
        $stmt->bindParam(':name', $board_name);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    // Get counselor users (councillor type)
    public function getCounselors() {
        $query = "SELECT id, full_name, organization_name FROM users WHERE user_type = 'councillor' AND status = 'active' ORDER BY full_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // Get all students (returns array instead of PDO statement)
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
                WHERE s.application_year = :application_year
                ORDER BY s.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':application_year', $application_year);
        $stmt->execute();
        return $stmt;
    }
    
    // Get distinct application years
    public function getApplicationYears() {
        $query = "SELECT DISTINCT application_year 
                FROM " . $this->table_name . " 
                WHERE application_year IS NOT NULL 
                ORDER BY application_year DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>