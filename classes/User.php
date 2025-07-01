<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $user_type;
    public $full_name;
    public $username;
    public $password;
    public $department_id;
    public $specialization;
    public $organization_id;
    public $organization_name;
    public $mou_signed;
    public $mou_drive_link;
    public $contact_no;
    public $email_id;
    public $address;
    public $primary_contact_id;
    public $councillor_rbm_id;
    public $branch;
    public $status;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create user
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                SET user_type=:user_type, full_name=:full_name, username=:username, 
                    password=:password, department_id=:department_id, specialization=:specialization,
                    organization_id=:organization_id, organization_name=:organization_name,
                    mou_signed=:mou_signed, mou_drive_link=:mou_drive_link, 
                    contact_no=:contact_no, email_id=:email_id, address=:address,
                    primary_contact_id=:primary_contact_id, councillor_rbm_id=:councillor_rbm_id,
                    branch=:branch, status=:status";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->user_type = htmlspecialchars(strip_tags($this->user_type));
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind data
        $stmt->bindParam(":user_type", $this->user_type);
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":department_id", $this->department_id);
        $stmt->bindParam(":specialization", $this->specialization);
        $stmt->bindParam(":organization_id", $this->organization_id);
        $stmt->bindParam(":organization_name", $this->organization_name);
        $stmt->bindParam(":mou_signed", $this->mou_signed);
        $stmt->bindParam(":mou_drive_link", $this->mou_drive_link);
        $stmt->bindParam(":contact_no", $this->contact_no);
        $stmt->bindParam(":email_id", $this->email_id);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":primary_contact_id", $this->primary_contact_id);
        $stmt->bindParam(":councillor_rbm_id", $this->councillor_rbm_id);
        $stmt->bindParam(":branch", $this->branch);
        $stmt->bindParam(":status", $this->status);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Read all users
    public function read() {
        $query = "SELECT u.*, d.name as department_name, o.name as organization_name_ref
                FROM " . $this->table_name . " u
                LEFT JOIN departments d ON u.department_id = d.id
                LEFT JOIN organizations o ON u.organization_id = o.id
                ORDER BY u.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Read single user
    public function readOne() {
        $query = "SELECT u.*, d.name as department_name, o.name as organization_name_ref
                FROM " . $this->table_name . " u
                LEFT JOIN departments d ON u.department_id = d.id
                LEFT JOIN organizations o ON u.organization_id = o.id
                WHERE u.id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->full_name = $row['full_name'];
            $this->username = $row['username'];
            $this->user_type = $row['user_type'];
            $this->department_id = $row['department_id'];
            $this->specialization = $row['specialization'];
            $this->organization_id = $row['organization_id'];
            $this->organization_name = $row['organization_name'];
            $this->mou_signed = $row['mou_signed'];
            $this->mou_drive_link = $row['mou_drive_link'];
            $this->contact_no = $row['contact_no'];
            $this->email_id = $row['email_id'];
            $this->address = $row['address'];
            $this->primary_contact_id = $row['primary_contact_id'];
            $this->councillor_rbm_id = $row['councillor_rbm_id'];
            $this->branch = $row['branch'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    // Update user
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                SET full_name=:full_name, username=:username, user_type=:user_type, department_id=:department_id,
                    specialization=:specialization, organization_id=:organization_id,
                    organization_name=:organization_name, mou_signed=:mou_signed,
                    mou_drive_link=:mou_drive_link, contact_no=:contact_no, email_id=:email_id,
                    address=:address, primary_contact_id=:primary_contact_id, 
                    councillor_rbm_id=:councillor_rbm_id, branch=:branch, status=:status
                WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->user_type = htmlspecialchars(strip_tags($this->user_type));

        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':user_type', $this->user_type);
        $stmt->bindParam(':department_id', $this->department_id);
        $stmt->bindParam(':specialization', $this->specialization);
        $stmt->bindParam(':organization_id', $this->organization_id);
        $stmt->bindParam(':organization_name', $this->organization_name);
        $stmt->bindParam(':mou_signed', $this->mou_signed);
        $stmt->bindParam(':mou_drive_link', $this->mou_drive_link);
        $stmt->bindParam(':contact_no', $this->contact_no);
        $stmt->bindParam(':email_id', $this->email_id);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':primary_contact_id', $this->primary_contact_id);
        $stmt->bindParam(':councillor_rbm_id', $this->councillor_rbm_id);
        $stmt->bindParam(':branch', $this->branch);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete user
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Login user
    public function login($username, $password) {
        $query = "SELECT id, username, password, user_type, full_name, status 
                FROM " . $this->table_name . " 
                WHERE username = :username AND status = 'active' LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row && password_verify($password, $row['password'])) {
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->user_type = $row['user_type'];
            $this->full_name = $row['full_name'];
            return true;
        }
        return false;
    }

    // Check if username exists
    public function usernameExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $this->username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }

    // Get all departments
    public function getDepartments() {
        $query = "SELECT * FROM departments ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get all organizations
    public function getOrganizations() {
        $query = "SELECT * FROM organizations ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // Get all RBM users
    public function getRBMUsers() {
        $query = "SELECT id, full_name, branch FROM users WHERE user_type = 'rbm' AND status = 'active' ORDER BY full_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // Get all users for primary contact dropdown
    public function getAllUsersForContact() {
        $query = "SELECT id, full_name, user_type FROM users WHERE status = 'active' ORDER BY user_type, full_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // Get users by type
    public function getByType($user_type) {
        $query = "SELECT id, full_name, username, specialization, branch FROM users WHERE user_type = :user_type AND status = 'active' ORDER BY full_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_type', $user_type);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Search users by name or username
    public function search($searchTerm) {
        $query = "SELECT id, full_name, username, user_type, email_id FROM " . $this->table_name . " 
                  WHERE (full_name LIKE :search OR username LIKE :search) AND status = 'active' 
                  ORDER BY full_name";
        $stmt = $this->conn->prepare($query);
        $searchTerm = '%' . $searchTerm . '%';
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>