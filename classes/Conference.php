<?php
require_once __DIR__ . '/../config/database.php';

class Conference {
    private $conn;
    private $table_name = "conferences";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Get all conferences
    public function getAll($filters = []) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE 1 = 1";
        $params = [];
        
        // Apply filters
        if (!empty($filters['affiliation'])) {
            $query .= " AND affiliation = :affiliation";
            $params[':affiliation'] = $filters['affiliation'];
        }
        
        if (!empty($filters['type'])) {
            $query .= " AND conference_type = :type";
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (conference_name LIKE :search OR conference_shortform LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Show upcoming conferences first, then by date
        $query .= " ORDER BY 
                    CASE 
                        WHEN conference_date >= CURDATE() THEN 0 
                        ELSE 1 
                    END,
                    conference_date ASC";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get conference by ID
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get upcoming conferences
    public function getUpcoming() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE conference_date >= CURDATE() 
                  ORDER BY conference_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Create new conference
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (conference_name, conference_shortform, conference_link, affiliation, conference_type, conference_date, submission_due_date, created_by) 
                  VALUES (:name, :shortform, :link, :affiliation, :type, :date, :submission_due_date, :created_by)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $data['conference_name']);
        $stmt->bindParam(':shortform', $data['conference_shortform']);
        $stmt->bindParam(':link', $data['conference_link']);
        $stmt->bindParam(':affiliation', $data['affiliation']);
        $stmt->bindParam(':type', $data['conference_type']);
        $stmt->bindParam(':date', $data['conference_date']);
        $stmt->bindValue(':submission_due_date', $data['submission_due_date'] ?? null);
        $stmt->bindParam(':created_by', $data['created_by']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    // Update conference
    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET conference_name = :name,
                      conference_shortform = :shortform,
                      conference_link = :link,
                      affiliation = :affiliation,
                      conference_type = :type,
                      conference_date = :date,
                      submission_due_date = :submission_due_date,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $data['conference_name']);
        $stmt->bindParam(':shortform', $data['conference_shortform']);
        $stmt->bindParam(':link', $data['conference_link']);
        $stmt->bindParam(':affiliation', $data['affiliation']);
        $stmt->bindParam(':type', $data['conference_type']);
        $stmt->bindParam(':date', $data['conference_date']);
        $stmt->bindValue(':submission_due_date', $data['submission_due_date'] ?? null);
        
        return $stmt->execute();
    }
    
    // Delete conference
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    // Get available conference affiliations
    public function getAffiliations() {
        $query = "SELECT DISTINCT affiliation FROM " . $this->table_name . " ORDER BY affiliation";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Get conference statistics
    public function getStatistics() {
        $stats = [
            'total' => 0,
            'upcoming' => 0,
            'by_affiliation' => [],
            'by_type' => []
        ];
        
        // Total conferences
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Upcoming conferences
        $query = "SELECT COUNT(*) as upcoming FROM " . $this->table_name . " WHERE conference_date >= CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['upcoming'] = $stmt->fetch(PDO::FETCH_ASSOC)['upcoming'];
        
        // By affiliation
        $query = "SELECT affiliation, COUNT(*) as count FROM " . $this->table_name . " GROUP BY affiliation ORDER BY count DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['by_affiliation'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // By type
        $query = "SELECT conference_type, COUNT(*) as count FROM " . $this->table_name . " GROUP BY conference_type ORDER BY count DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
}
?> 