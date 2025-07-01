<?php
/**
 * Database Inspector - Command Line Interface
 * Usage: php database_inspector_cli.php [format]
 * Formats: summary, tables, relationships, json
 */

require_once 'config/database.php';

class DatabaseInspectorCLI {
    private $conn;
    private $database_name;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->database_name = 'research_apps_db';
    }
    
    public function printHeader($title) {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "  " . strtoupper($title) . "\n";
        echo str_repeat("=", 60) . "\n";
    }
    
    public function printSubHeader($title) {
        echo "\n" . str_repeat("-", 40) . "\n";
        echo $title . "\n";
        echo str_repeat("-", 40) . "\n";
    }
    
    public function getAllTables() {
        $query = "SELECT TABLE_NAME, TABLE_ROWS, ENGINE FROM INFORMATION_SCHEMA.TABLES 
                  WHERE TABLE_SCHEMA = :database_name ORDER BY TABLE_NAME";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':database_name', $this->database_name);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAllForeignKeys() {
        $query = "SELECT 
                    kcu.TABLE_NAME as source_table,
                    kcu.COLUMN_NAME as source_column,
                    kcu.REFERENCED_TABLE_NAME as target_table,
                    kcu.REFERENCED_COLUMN_NAME as target_column,
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
    
    public function getTableColumns($table_name) {
        $query = "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT, EXTRA
                  FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_SCHEMA = :database_name AND TABLE_NAME = :table_name
                  ORDER BY ORDINAL_POSITION";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':database_name', $this->database_name);
        $stmt->bindParam(':table_name', $table_name);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function showSummary() {
        $this->printHeader("Database Summary");
        
        $tables = $this->getAllTables();
        $foreign_keys = $this->getAllForeignKeys();
        
        echo "Database: {$this->database_name}\n";
        echo "Total Tables: " . count($tables) . "\n";
        echo "Total Foreign Key Relationships: " . count($foreign_keys) . "\n\n";
        
        echo "Tables:\n";
        foreach ($tables as $table) {
            $rows = $table['TABLE_ROWS'] ?: '0';
            $engine = $table['ENGINE'] ?: 'N/A';
            printf("  %-30s Rows: %-8s Engine: %s\n", 
                   $table['TABLE_NAME'], $rows, $engine);
        }
        echo "\n";
    }
    
    public function showTables() {
        $this->printHeader("All Tables with Columns");
        
        $tables = $this->getAllTables();
        $foreign_keys = $this->getAllForeignKeys();
        
        // Create a map of foreign keys for quick lookup
        $fk_map = [];
        foreach ($foreign_keys as $fk) {
            $fk_map[$fk['source_table']][$fk['source_column']] = [
                'target' => $fk['target_table'] . '.' . $fk['target_column'],
                'update_rule' => $fk['UPDATE_RULE'],
                'delete_rule' => $fk['DELETE_RULE']
            ];
        }
        
        foreach ($tables as $table) {
            $table_name = $table['TABLE_NAME'];
            $this->printSubHeader("Table: " . $table_name);
            
            $columns = $this->getTableColumns($table_name);
            
            printf("%-25s %-15s %-8s %-8s %-15s %-10s %s\n", 
                   "COLUMN", "TYPE", "NULL", "KEY", "DEFAULT", "EXTRA", "FK_REFERENCE");
            echo str_repeat("-", 100) . "\n";
            
            foreach ($columns as $column) {
                $fk_ref = '';
                if (isset($fk_map[$table_name][$column['COLUMN_NAME']])) {
                    $fk_info = $fk_map[$table_name][$column['COLUMN_NAME']];
                    $fk_ref = "→ " . $fk_info['target'];
                }
                
                printf("%-25s %-15s %-8s %-8s %-15s %-10s %s\n",
                       $column['COLUMN_NAME'],
                       substr($column['COLUMN_TYPE'], 0, 15),
                       $column['IS_NULLABLE'],
                       $column['COLUMN_KEY'],
                       substr($column['COLUMN_DEFAULT'] ?: 'NULL', 0, 15),
                       $column['EXTRA'],
                       $fk_ref
                );
            }
            echo "\n";
        }
    }
    
    public function showRelationships() {
        $this->printHeader("Foreign Key Relationships");
        
        $foreign_keys = $this->getAllForeignKeys();
        
        if (empty($foreign_keys)) {
            echo "No foreign key relationships found.\n";
            return;
        }
        
        printf("%-25s %-20s %-25s %-20s %-10s %-10s\n",
               "SOURCE TABLE", "COLUMN", "TARGET TABLE", "COLUMN", "UPDATE", "DELETE");
        echo str_repeat("-", 115) . "\n";
        
        foreach ($foreign_keys as $fk) {
            printf("%-25s %-20s %-25s %-20s %-10s %-10s\n",
                   $fk['source_table'],
                   $fk['source_column'],
                   $fk['target_table'],
                   $fk['target_column'],
                   $fk['UPDATE_RULE'],
                   $fk['DELETE_RULE']
            );
        }
        echo "\n";
    }
    
    public function exportJSON() {
        // Reuse the web version's logic but output to stdout
        require_once 'database_inspector.php';
        $inspector = new DatabaseInspector();
        echo $inspector->exportSchemaJSON();
    }
}

// Command line execution
if (php_sapi_name() === 'cli') {
    $format = $argv[1] ?? 'summary';
    
    try {
        $cli = new DatabaseInspectorCLI();
        
        switch ($format) {
            case 'summary':
                $cli->showSummary();
                break;
                
            case 'tables':
                $cli->showTables();
                break;
                
            case 'relationships':
                $cli->showRelationships();
                break;
                
            case 'json':
                $cli->exportJSON();
                break;
                
            default:
                echo "Usage: php database_inspector_cli.php [format]\n";
                echo "Formats:\n";
                echo "  summary       - Database overview (default)\n";
                echo "  tables        - All tables with columns\n";
                echo "  relationships - Foreign key relationships\n";
                echo "  json          - Full schema as JSON\n";
                break;
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "This script is designed to run from command line.\n";
    echo "Use: php database_inspector_cli.php [format]\n";
}
?> 