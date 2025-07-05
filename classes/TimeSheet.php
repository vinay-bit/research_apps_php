<?php
require_once __DIR__ . '/../config/database.php';

class TimeSheet {
    private $conn;
    private $table_name = "timesheet_entries";
    
    // TimeSheet properties
    public $id;
    public $project_id;
    public $mentor_id;
    public $entry_date;
    public $start_time;
    public $end_time;
    public $activity_id;
    public $task_description;
    public $hours_worked;
    public $notes;
    public $is_approved;
    public $approved_by;
    public $approved_at;
    public $created_at;
    public $updated_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Create new time sheet entry
    public function create() {
        try {
            $this->conn->beginTransaction();
            
            // Validate required fields
            if (empty($this->project_id) || empty($this->mentor_id) || empty($this->entry_date)) {
                throw new Exception('Project ID, Mentor ID, and Entry Date are required');
            }
            
            if (empty($this->start_time) || empty($this->end_time)) {
                throw new Exception('Start time and end time are required');
            }
            
            if (empty($this->activity_id)) {
                throw new Exception('Activity is required');
            }
            
            if (empty($this->task_description)) {
                throw new Exception('Task description is required');
            }
            
            // Validate time logic
            if ($this->start_time >= $this->end_time) {
                throw new Exception('End time must be after start time');
            }
            
            // Check for overlapping entries
            if ($this->hasOverlappingEntries()) {
                throw new Exception('Time entry overlaps with existing entries for this date');
            }
            
            // Validate maximum hours per day
            if (!$this->validateMaxHoursPerDay()) {
                throw new Exception('Total hours for this day exceed the maximum allowed');
            }
            
            $query = "INSERT INTO " . $this->table_name . " 
                      SET project_id = :project_id,
                          mentor_id = :mentor_id,
                          entry_date = :entry_date,
                          start_time = :start_time,
                          end_time = :end_time,
                          activity_id = :activity_id,
                          task_description = :task_description,
                          notes = :notes";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitize input
            $this->task_description = htmlspecialchars(strip_tags($this->task_description));
            $this->notes = htmlspecialchars(strip_tags($this->notes));
            
            // Bind parameters
            $stmt->bindParam(':project_id', $this->project_id);
            $stmt->bindParam(':mentor_id', $this->mentor_id);
            $stmt->bindParam(':entry_date', $this->entry_date);
            $stmt->bindParam(':start_time', $this->start_time);
            $stmt->bindParam(':end_time', $this->end_time);
            $stmt->bindParam(':activity_id', $this->activity_id);
            $stmt->bindParam(':task_description', $this->task_description);
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
    
    // Get time sheet entry by ID
    public function getById($id) {
        $query = "SELECT te.*, 
                         ta.activity_name, ta.color,
                         p.project_name, p.project_id as project_code,
                         m.full_name as mentor_name,
                         a.full_name as approver_name
                  FROM " . $this->table_name . " te
                  LEFT JOIN timesheet_activities ta ON te.activity_id = ta.id
                  LEFT JOIN projects p ON te.project_id = p.id
                  LEFT JOIN users m ON te.mentor_id = m.id
                  LEFT JOIN users a ON te.approved_by = a.id
                  WHERE te.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Update time sheet entry
    public function update() {
        try {
            $this->conn->beginTransaction();
            
            // Validate required fields
            if (empty($this->id)) {
                throw new Exception('Entry ID is required for update');
            }
            
            // Check if entry exists and is not approved
            $existing = $this->getById($this->id);
            if (!$existing) {
                throw new Exception('Time sheet entry not found');
            }
            
            if ($existing['is_approved']) {
                throw new Exception('Cannot update approved time sheet entry');
            }
            
            // Validate time logic
            if ($this->start_time >= $this->end_time) {
                throw new Exception('End time must be after start time');
            }
            
            // Check for overlapping entries (excluding current entry)
            if ($this->hasOverlappingEntries($this->id)) {
                throw new Exception('Time entry overlaps with existing entries for this date');
            }
            
            $query = "UPDATE " . $this->table_name . " 
                      SET entry_date = :entry_date,
                          start_time = :start_time,
                          end_time = :end_time,
                          activity_id = :activity_id,
                          task_description = :task_description,
                          notes = :notes
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitize input
            $this->task_description = htmlspecialchars(strip_tags($this->task_description));
            $this->notes = htmlspecialchars(strip_tags($this->notes));
            
            // Bind parameters
            $stmt->bindParam(':entry_date', $this->entry_date);
            $stmt->bindParam(':start_time', $this->start_time);
            $stmt->bindParam(':end_time', $this->end_time);
            $stmt->bindParam(':activity_id', $this->activity_id);
            $stmt->bindParam(':task_description', $this->task_description);
            $stmt->bindParam(':notes', $this->notes);
            $stmt->bindParam(':id', $this->id);
            
            if ($stmt->execute()) {
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
    
    // Delete time sheet entry
    public function delete($id) {
        try {
            $this->conn->beginTransaction();
            
            // Check if entry exists and is not approved
            $existing = $this->getById($id);
            if (!$existing) {
                throw new Exception('Time sheet entry not found');
            }
            
            if ($existing['is_approved']) {
                throw new Exception('Cannot delete approved time sheet entry');
            }
            
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
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
    
    // Get calendar data for a specific month
    public function getCalendarData($mentor_id, $year, $month, $project_id = null) {
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $query = "SELECT te.*, 
                         ta.activity_name, ta.color,
                         p.project_name, p.project_id as project_code,
                         m.full_name as mentor_name
                  FROM " . $this->table_name . " te
                  LEFT JOIN timesheet_activities ta ON te.activity_id = ta.id
                  LEFT JOIN projects p ON te.project_id = p.id
                  LEFT JOIN users m ON te.mentor_id = m.id
                  WHERE te.entry_date BETWEEN :start_date AND :end_date";
        
        $params = [':start_date' => $start_date, ':end_date' => $end_date];
        
        if ($mentor_id) {
            $query .= " AND te.mentor_id = :mentor_id";
            $params[':mentor_id'] = $mentor_id;
        }
        
        if ($project_id) {
            $query .= " AND te.project_id = :project_id";
            $params[':project_id'] = $project_id;
        }
        
        $query .= " ORDER BY te.entry_date, te.start_time";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get entries for a specific date
    public function getEntriesByDate($mentor_id, $date, $project_id = null) {
        $query = "SELECT te.*, 
                         ta.activity_name, ta.color,
                         p.project_name, p.project_id as project_code,
                         m.full_name as mentor_name
                  FROM " . $this->table_name . " te
                  LEFT JOIN timesheet_activities ta ON te.activity_id = ta.id
                  LEFT JOIN projects p ON te.project_id = p.id
                  LEFT JOIN users m ON te.mentor_id = m.id
                  WHERE te.entry_date = :date";
        
        $params = [':date' => $date];
        
        if ($mentor_id) {
            $query .= " AND te.mentor_id = :mentor_id";
            $params[':mentor_id'] = $mentor_id;
        }
        
        if ($project_id) {
            $query .= " AND te.project_id = :project_id";
            $params[':project_id'] = $project_id;
        }
        
        $query .= " ORDER BY te.start_time";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get all activities
    public function getActivities() {
        $query = "SELECT * FROM timesheet_activities WHERE is_active = 1 ORDER BY activity_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get projects assigned to mentor
    public function getMentorProjects($mentor_id) {
        $query = "SELECT DISTINCT p.* 
                  FROM projects p
                  WHERE (p.lead_mentor_id = :mentor_id)
                     OR (p.id IN (
                         SELECT pm.project_id 
                         FROM project_mentors pm 
                         WHERE pm.mentor_id = :mentor_id2 AND pm.is_active = 1
                     ))
                  ORDER BY p.project_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentor_id', $mentor_id);
        $stmt->bindParam(':mentor_id2', $mentor_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get all mentors for admin dropdown
    public function getAllMentors() {
        $query = "SELECT DISTINCT u.id, u.full_name 
                  FROM users u
                  WHERE u.user_type = 'mentor' AND u.status = 'active'
                  ORDER BY u.full_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get projects by mentor for admin
    public function getProjectsByMentor($mentor_id) {
        return $this->getMentorProjects($mentor_id);
    }
    
    // Get total hours for a date
    public function getTotalHoursForDate($mentor_id, $date, $project_id = null) {
        $query = "SELECT SUM(hours_worked) as total_hours 
                  FROM " . $this->table_name . " 
                  WHERE mentor_id = :mentor_id AND entry_date = :date";
        
        if ($project_id) {
            $query .= " AND project_id = :project_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentor_id', $mentor_id);
        $stmt->bindParam(':date', $date);
        
        if ($project_id) {
            $stmt->bindParam(':project_id', $project_id);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_hours'] ?? 0;
    }
    
    // Get settings
    public function getSettings() {
        $query = "SELECT setting_key, setting_value FROM timesheet_settings";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
    
    // Check for overlapping entries
    private function hasOverlappingEntries($exclude_id = null) {
        $query = "SELECT COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  WHERE mentor_id = :mentor_id 
                    AND entry_date = :entry_date
                    AND (
                        (start_time < :end_time AND end_time > :start_time)
                        OR (start_time = :start_time AND end_time = :end_time)
                    )";
        
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentor_id', $this->mentor_id);
        $stmt->bindParam(':entry_date', $this->entry_date);
        $stmt->bindParam(':start_time', $this->start_time);
        $stmt->bindParam(':end_time', $this->end_time);
        
        if ($exclude_id) {
            $stmt->bindParam(':exclude_id', $exclude_id);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    
    // Validate maximum hours per day
    private function validateMaxHoursPerDay() {
        $settings = $this->getSettings();
        $max_hours = floatval($settings['max_hours_per_day'] ?? 10);
        
        $current_hours = $this->getTotalHoursForDate($this->mentor_id, $this->entry_date, $this->project_id);
        $new_hours = (strtotime($this->end_time) - strtotime($this->start_time)) / 3600;
        
        return ($current_hours + $new_hours) <= $max_hours;
    }
    
    // Get reports data
    public function getReports($filters = []) {
        $query = "SELECT te.*, 
                         ta.activity_name, ta.color,
                         p.project_name, p.project_id as project_code,
                         m.full_name as mentor_name,
                         a.full_name as approver_name
                  FROM " . $this->table_name . " te
                  LEFT JOIN timesheet_activities ta ON te.activity_id = ta.id
                  LEFT JOIN projects p ON te.project_id = p.id
                  LEFT JOIN users m ON te.mentor_id = m.id
                  LEFT JOIN users a ON te.approved_by = a.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['mentor_id'])) {
            $query .= " AND te.mentor_id = :mentor_id";
            $params[':mentor_id'] = $filters['mentor_id'];
        }
        
        if (!empty($filters['project_id'])) {
            $query .= " AND te.project_id = :project_id";
            $params[':project_id'] = $filters['project_id'];
        }
        
        if (!empty($filters['activity_id'])) {
            $query .= " AND te.activity_id = :activity_id";
            $params[':activity_id'] = $filters['activity_id'];
        }
        
        if (!empty($filters['start_date'])) {
            $query .= " AND te.entry_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $query .= " AND te.entry_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        
        if (isset($filters['is_approved'])) {
            $query .= " AND te.is_approved = :is_approved";
            $params[':is_approved'] = $filters['is_approved'];
        }
        
        $query .= " ORDER BY te.entry_date DESC, te.start_time DESC";
        
        if (!empty($filters['limit'])) {
            $query .= " LIMIT :limit";
            $params[':limit'] = $filters['limit'];
        }
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get summary statistics
    public function getSummaryStats($filters = []) {
        $query = "SELECT 
                    COUNT(*) as total_entries,
                    SUM(hours_worked) as total_hours,
                    AVG(hours_worked) as avg_hours_per_entry,
                    COUNT(DISTINCT mentor_id) as unique_mentors,
                    COUNT(DISTINCT project_id) as unique_projects,
                    COUNT(DISTINCT entry_date) as unique_days
                  FROM " . $this->table_name . " 
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['mentor_id'])) {
            $query .= " AND mentor_id = :mentor_id";
            $params[':mentor_id'] = $filters['mentor_id'];
        }
        
        if (!empty($filters['project_id'])) {
            $query .= " AND project_id = :project_id";
            $params[':project_id'] = $filters['project_id'];
        }
        
        if (!empty($filters['start_date'])) {
            $query .= " AND entry_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $query .= " AND entry_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get activity breakdown
    public function getActivityBreakdown($filters = []) {
        $query = "SELECT 
                    ta.activity_name,
                    ta.color,
                    COUNT(*) as entry_count,
                    SUM(te.hours_worked) as total_hours,
                    AVG(te.hours_worked) as avg_hours
                  FROM " . $this->table_name . " te
                  LEFT JOIN timesheet_activities ta ON te.activity_id = ta.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['mentor_id'])) {
            $query .= " AND te.mentor_id = :mentor_id";
            $params[':mentor_id'] = $filters['mentor_id'];
        }
        
        if (!empty($filters['project_id'])) {
            $query .= " AND te.project_id = :project_id";
            $params[':project_id'] = $filters['project_id'];
        }
        
        if (!empty($filters['start_date'])) {
            $query .= " AND te.entry_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $query .= " AND te.entry_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        
        $query .= " GROUP BY ta.id, ta.activity_name, ta.color
                    ORDER BY total_hours DESC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Approve/reject time sheet entry
    public function approveEntry($entry_id, $approver_id, $action, $comments = null) {
        try {
            $this->conn->beginTransaction();
            
            $is_approved = ($action === 'approve') ? 1 : 0;
            $approved_at = ($action === 'approve') ? date('Y-m-d H:i:s') : null;
            
            // Update the entry
            $query = "UPDATE " . $this->table_name . " 
                      SET is_approved = :is_approved,
                          approved_by = :approved_by,
                          approved_at = :approved_at
                      WHERE id = :entry_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':is_approved', $is_approved);
            $stmt->bindParam(':approved_by', $approver_id);
            $stmt->bindParam(':approved_at', $approved_at);
            $stmt->bindParam(':entry_id', $entry_id);
            
            if ($stmt->execute()) {
                // Log the approval action
                $query2 = "INSERT INTO timesheet_approvals (entry_id, approver_id, action, comments)
                           VALUES (:entry_id, :approver_id, :action, :comments)";
                
                $stmt2 = $this->conn->prepare($query2);
                $stmt2->bindParam(':entry_id', $entry_id);
                $stmt2->bindParam(':approver_id', $approver_id);
                $stmt2->bindParam(':action', $action);
                $stmt2->bindParam(':comments', $comments);
                
                if ($stmt2->execute()) {
                    $this->conn->commit();
                    return true;
                } else {
                    $this->conn->rollback();
                    return false;
                }
            } else {
                $this->conn->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
} 