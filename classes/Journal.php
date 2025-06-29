<?php
require_once __DIR__ . '/../config/database.php';

class Journal {
    private $conn;
    private $table_name = "journals";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Get all journals
    public function getAll($filters = []) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE 1 = 1";
        $params = [];
        
        // Apply filters
        if (!empty($filters['publisher'])) {
            $query .= " AND publisher = :publisher";
            $params[':publisher'] = $filters['publisher'];
        }
        
        if (!empty($filters['acceptance'])) {
            $query .= " AND acceptance_frequency = :acceptance";
            $params[':acceptance'] = $filters['acceptance'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND journal_name LIKE :search";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $query .= " ORDER BY journal_name ASC";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get journal by ID
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Create new journal
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (journal_name, publisher, journal_link, acceptance_frequency, created_by) 
                  VALUES (:name, :publisher, :link, :acceptance, :created_by)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $data['journal_name']);
        $stmt->bindParam(':publisher', $data['publisher']);
        $stmt->bindParam(':link', $data['journal_link']);
        $stmt->bindParam(':acceptance', $data['acceptance_frequency']);
        $stmt->bindParam(':created_by', $data['created_by']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    // Update journal
    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET journal_name = :name,
                      publisher = :publisher,
                      journal_link = :link,
                      acceptance_frequency = :acceptance,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $data['journal_name']);
        $stmt->bindParam(':publisher', $data['publisher']);
        $stmt->bindParam(':link', $data['journal_link']);
        $stmt->bindParam(':acceptance', $data['acceptance_frequency']);
        
        return $stmt->execute();
    }
    
    // Delete journal
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    // Get available publishers
    public function getPublishers() {
        $query = "SELECT DISTINCT publisher FROM " . $this->table_name . " ORDER BY publisher";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Get journal statistics
    public function getStatistics() {
        $stats = [
            'total' => 0,
            'by_publisher' => [],
            'by_acceptance' => []
        ];
        
        // Total journals
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // By publisher
        $query = "SELECT publisher, COUNT(*) as count FROM " . $this->table_name . " GROUP BY publisher ORDER BY count DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['by_publisher'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // By acceptance frequency
        $query = "SELECT acceptance_frequency, COUNT(*) as count FROM " . $this->table_name . " GROUP BY acceptance_frequency ORDER BY count DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['by_acceptance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    // Get journals for dropdown (sorted by name)
    public function getForDropdown() {
        $query = "SELECT id, journal_name, publisher, acceptance_frequency
                  FROM " . $this->table_name . " 
                  ORDER BY journal_name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?> 