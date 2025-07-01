<?php
/**
 * Database Inspector - Complete Database Schema Analysis Tool
 * This script retrieves all database details including tables, columns, and associations
 */

require_once 'config/database.php';

class DatabaseInspector {
    private $conn;
    private $database_name;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->database_name = 'research_apps_db'; // From your config
    }
    
    /**
     * Get all tables in the database
     */
    public function getAllTables() {
        $query = "SELECT TABLE_NAME, TABLE_TYPE, ENGINE, TABLE_ROWS, 
                         DATA_LENGTH, INDEX_LENGTH, TABLE_COMMENT
                  FROM INFORMATION_SCHEMA.TABLES 
                  WHERE TABLE_SCHEMA = :database_name
                  ORDER BY TABLE_NAME";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':database_name', $this->database_name);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all columns for a specific table
     */
    public function getTableColumns($table_name) {
        $query = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT,
                         COLUMN_TYPE, EXTRA, COLUMN_KEY, COLUMN_COMMENT,
                         CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE
                  FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_SCHEMA = :database_name 
                  AND TABLE_NAME = :table_name
                  ORDER BY ORDINAL_POSITION";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':database_name', $this->database_name);
        $stmt->bindParam(':table_name', $table_name);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all foreign key relationships
     */
    public function getAllForeignKeys() {
        $query = "SELECT 
                    kcu.TABLE_NAME as source_table,
                    kcu.COLUMN_NAME as source_column,
                    kcu.REFERENCED_TABLE_NAME as target_table,
                    kcu.REFERENCED_COLUMN_NAME as target_column,
                    rc.CONSTRAINT_NAME,
                    rc.UPDATE_RULE,
                    rc.DELETE_RULE
                  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                  JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc 
                    ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                  WHERE kcu.TABLE_SCHEMA = :database_name
                  AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
                  ORDER BY kcu.TABLE_NAME, kcu.COLUMN_NAME";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':database_name', $this->database_name);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all indexes for a specific table
     */
    public function getTableIndexes($table_name) {
        $query = "SELECT INDEX_NAME, COLUMN_NAME, NON_UNIQUE, INDEX_TYPE, SEQ_IN_INDEX
                  FROM INFORMATION_SCHEMA.STATISTICS 
                  WHERE TABLE_SCHEMA = :database_name 
                  AND TABLE_NAME = :table_name
                  ORDER BY INDEX_NAME, SEQ_IN_INDEX";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':database_name', $this->database_name);
        $stmt->bindParam(':table_name', $table_name);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get table relationships (both incoming and outgoing foreign keys)
     */
    public function getTableRelationships($table_name) {
        // Outgoing relationships (this table references others)
        $outgoing_query = "SELECT 
                            'REFERENCES' as relationship_type,
                            kcu.COLUMN_NAME as local_column,
                            kcu.REFERENCED_TABLE_NAME as related_table,
                            kcu.REFERENCED_COLUMN_NAME as related_column,
                            rc.CONSTRAINT_NAME,
                            rc.UPDATE_RULE,
                            rc.DELETE_RULE
                          FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                          JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc 
                            ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                          WHERE kcu.TABLE_SCHEMA = :database_name
                          AND kcu.TABLE_NAME = :table_name
                          AND kcu.REFERENCED_TABLE_NAME IS NOT NULL";
        
        // Incoming relationships (other tables reference this table)
        $incoming_query = "SELECT 
                            'REFERENCED_BY' as relationship_type,
                            kcu.REFERENCED_COLUMN_NAME as local_column,
                            kcu.TABLE_NAME as related_table,
                            kcu.COLUMN_NAME as related_column,
                            rc.CONSTRAINT_NAME,
                            rc.UPDATE_RULE,
                            rc.DELETE_RULE
                          FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                          JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc 
                            ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                          WHERE kcu.TABLE_SCHEMA = :database_name
                          AND kcu.REFERENCED_TABLE_NAME = :table_name";
        
        $stmt1 = $this->conn->prepare($outgoing_query);
        $stmt1->bindParam(':database_name', $this->database_name);
        $stmt1->bindParam(':table_name', $table_name);
        $stmt1->execute();
        $outgoing = $stmt1->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt2 = $this->conn->prepare($incoming_query);
        $stmt2->bindParam(':database_name', $this->database_name);
        $stmt2->bindParam(':table_name', $table_name);
        $stmt2->execute();
        $incoming = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'outgoing' => $outgoing,
            'incoming' => $incoming
        ];
    }
    
    /**
     * Get complete database schema
     */
    public function getCompleteSchema() {
        $schema = [
            'database_name' => $this->database_name,
            'tables' => [],
            'foreign_keys' => $this->getAllForeignKeys()
        ];
        
        $tables = $this->getAllTables();
        
        foreach ($tables as $table) {
            $table_name = $table['TABLE_NAME'];
            
            $schema['tables'][$table_name] = [
                'info' => $table,
                'columns' => $this->getTableColumns($table_name),
                'indexes' => $this->getTableIndexes($table_name),
                'relationships' => $this->getTableRelationships($table_name)
            ];
        }
        
        return $schema;
    }
    
    /**
     * Display schema in HTML format
     */
    public function displaySchemaHTML() {
        $schema = $this->getCompleteSchema();
        
        echo "<!DOCTYPE html>";
        echo "<html lang='en'>";
        echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        echo "<title>Database Schema Inspector</title>";
        echo "<style>";
        echo "body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }";
        echo ".container { max-width: 1200px; margin: 0 auto; }";
        echo ".table-section { margin-bottom: 40px; border: 1px solid #ddd; padding: 20px; border-radius: 8px; }";
        echo ".table-name { color: #2c5aa0; font-size: 24px; margin-bottom: 15px; border-bottom: 2px solid #2c5aa0; padding-bottom: 5px; }";
        echo ".section-title { color: #333; font-size: 18px; margin: 20px 0 10px 0; border-bottom: 1px solid #ccc; padding-bottom: 3px; }";
        echo "table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }";
        echo "th, td { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }";
        echo "th { background-color: #f5f5f5; font-weight: bold; }";
        echo "tr:nth-child(even) { background-color: #f9f9f9; }";
        echo ".primary-key { background-color: #fff3cd; font-weight: bold; }";
        echo ".foreign-key { background-color: #d1ecf1; }";
        echo ".relationship { padding: 10px; margin: 5px 0; border-radius: 4px; }";
        echo ".references { background-color: #d4edda; border-left: 4px solid #28a745; }";
        echo ".referenced-by { background-color: #f8d7da; border-left: 4px solid #dc3545; }";
        echo ".summary { background-color: #e3f2fd; padding: 20px; border-radius: 8px; margin-bottom: 30px; }";
        echo "</style>";
        echo "</head>";
        echo "<body>";
        
        echo "<div class='container'>";
        echo "<h1>Database Schema Inspector: " . $schema['database_name'] . "</h1>";
        
        // Summary
        echo "<div class='summary'>";
        echo "<h2>Database Summary</h2>";
        echo "<p><strong>Database:</strong> " . $schema['database_name'] . "</p>";
        echo "<p><strong>Total Tables:</strong> " . count($schema['tables']) . "</p>";
        echo "<p><strong>Total Foreign Key Relationships:</strong> " . count($schema['foreign_keys']) . "</p>";
        echo "</div>";
        
        // Tables
        foreach ($schema['tables'] as $table_name => $table_data) {
            echo "<div class='table-section'>";
            echo "<h2 class='table-name'>📋 " . $table_name . "</h2>";
            
            // Table Info
            echo "<div class='section-title'>Table Information</div>";
            echo "<table>";
            echo "<tr><th>Property</th><th>Value</th></tr>";
            echo "<tr><td>Engine</td><td>" . ($table_data['info']['ENGINE'] ?? 'N/A') . "</td></tr>";
            echo "<tr><td>Rows</td><td>" . ($table_data['info']['TABLE_ROWS'] ?? 'N/A') . "</td></tr>";
            echo "<tr><td>Data Length</td><td>" . ($table_data['info']['DATA_LENGTH'] ?? 'N/A') . " bytes</td></tr>";
            echo "<tr><td>Comment</td><td>" . ($table_data['info']['TABLE_COMMENT'] ?? 'N/A') . "</td></tr>";
            echo "</table>";
            
            // Columns
            echo "<div class='section-title'>Columns</div>";
            echo "<table>";
            echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th><th>Comment</th></tr>";
            
            foreach ($table_data['columns'] as $column) {
                $row_class = '';
                if ($column['COLUMN_KEY'] === 'PRI') {
                    $row_class = 'primary-key';
                } elseif ($column['COLUMN_KEY'] === 'MUL') {
                    // Check if it's a foreign key
                    $is_fk = false;
                    foreach ($schema['foreign_keys'] as $fk) {
                        if ($fk['source_table'] === $table_name && $fk['source_column'] === $column['COLUMN_NAME']) {
                            $is_fk = true;
                            break;
                        }
                    }
                    if ($is_fk) $row_class = 'foreign-key';
                }
                
                echo "<tr class='$row_class'>";
                echo "<td><strong>" . $column['COLUMN_NAME'] . "</strong></td>";
                echo "<td>" . $column['COLUMN_TYPE'] . "</td>";
                echo "<td>" . $column['IS_NULLABLE'] . "</td>";
                echo "<td>" . $column['COLUMN_KEY'] . "</td>";
                echo "<td>" . ($column['COLUMN_DEFAULT'] ?? 'NULL') . "</td>";
                echo "<td>" . $column['EXTRA'] . "</td>";
                echo "<td>" . $column['COLUMN_COMMENT'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Relationships
            $relationships = $table_data['relationships'];
            if (!empty($relationships['outgoing']) || !empty($relationships['incoming'])) {
                echo "<div class='section-title'>Relationships</div>";
                
                // Outgoing (References)
                if (!empty($relationships['outgoing'])) {
                    echo "<h4>🔗 References (Foreign Keys)</h4>";
                    foreach ($relationships['outgoing'] as $rel) {
                        echo "<div class='relationship references'>";
                        echo "<strong>" . $rel['local_column'] . "</strong> → ";
                        echo "<strong>" . $rel['related_table'] . "." . $rel['related_column'] . "</strong>";
                        echo " (ON UPDATE: " . $rel['UPDATE_RULE'] . ", ON DELETE: " . $rel['DELETE_RULE'] . ")";
                        echo "</div>";
                    }
                }
                
                // Incoming (Referenced by)
                if (!empty($relationships['incoming'])) {
                    echo "<h4>🔙 Referenced By</h4>";
                    foreach ($relationships['incoming'] as $rel) {
                        echo "<div class='relationship referenced-by'>";
                        echo "<strong>" . $rel['local_column'] . "</strong> ← ";
                        echo "<strong>" . $rel['related_table'] . "." . $rel['related_column'] . "</strong>";
                        echo " (ON UPDATE: " . $rel['UPDATE_RULE'] . ", ON DELETE: " . $rel['DELETE_RULE'] . ")";
                        echo "</div>";
                    }
                }
            }
            
            // Indexes
            if (!empty($table_data['indexes'])) {
                echo "<div class='section-title'>Indexes</div>";
                echo "<table>";
                echo "<tr><th>Index Name</th><th>Column</th><th>Unique</th><th>Type</th></tr>";
                
                foreach ($table_data['indexes'] as $index) {
                    echo "<tr>";
                    echo "<td>" . $index['INDEX_NAME'] . "</td>";
                    echo "<td>" . $index['COLUMN_NAME'] . "</td>";
                    echo "<td>" . ($index['NON_UNIQUE'] == 0 ? 'Yes' : 'No') . "</td>";
                    echo "<td>" . $index['INDEX_TYPE'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            echo "</div>";
        }
        
        // All Foreign Key Relationships Summary
        if (!empty($schema['foreign_keys'])) {
            echo "<div class='table-section'>";
            echo "<h2 class='table-name'>🔗 All Foreign Key Relationships</h2>";
            echo "<table>";
            echo "<tr><th>Source Table</th><th>Source Column</th><th>Target Table</th><th>Target Column</th><th>Update Rule</th><th>Delete Rule</th></tr>";
            
            foreach ($schema['foreign_keys'] as $fk) {
                echo "<tr>";
                echo "<td><strong>" . $fk['source_table'] . "</strong></td>";
                echo "<td>" . $fk['source_column'] . "</td>";
                echo "<td><strong>" . $fk['target_table'] . "</strong></td>";
                echo "<td>" . $fk['target_column'] . "</td>";
                echo "<td>" . $fk['UPDATE_RULE'] . "</td>";
                echo "<td>" . $fk['DELETE_RULE'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
        }
        
        echo "</div></body></html>";
    }
    
    /**
     * Export schema as JSON
     */
    public function exportSchemaJSON() {
        $schema = $this->getCompleteSchema();
        return json_encode($schema, JSON_PRETTY_PRINT);
    }
    
    /**
     * Export schema as array (for programmatic use)
     */
    public function getSchemaArray() {
        return $this->getCompleteSchema();
    }
}

// Usage Examples
try {
    $inspector = new DatabaseInspector();
    
    // Check if output format is specified
    $output_format = $_GET['format'] ?? 'html';
    
    switch ($output_format) {
        case 'json':
            header('Content-Type: application/json');
            echo $inspector->exportSchemaJSON();
            break;
            
        case 'array':
            // For debugging - outputs PHP array structure
            header('Content-Type: text/plain');
            print_r($inspector->getSchemaArray());
            break;
            
        default:
            // HTML output (default)
            $inspector->displaySchemaHTML();
            break;
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; border: 1px solid red; margin: 20px;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?> 