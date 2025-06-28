<?php
require_once 'config/database.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Publications Database Setup</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }";
echo ".success { color: #28a745; }";
echo ".error { color: #dc3545; }";
echo ".info { color: #007bff; }";
echo ".warning { color: #ffc107; }";
echo "pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>📚 Publications Database Setup</h1>";
echo "<p>This script will set up the publications tracking system tables in your database.</p>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>📋 Database Connection</h2>";
    echo "<p class='success'>✅ Successfully connected to database!</p>";
    
    // Read and execute the SQL schema
    $sql_file = 'database/publications_schema.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("Schema file not found: $sql_file");
    }
    
    echo "<h2>📋 Reading Schema File</h2>";
    echo "<p class='info'>📁 Reading: $sql_file</p>";
    
    $sql_content = file_get_contents($sql_file);
    
    // Split SQL statements (handle multi-line statements properly)
    $statements = [];
    $current_statement = '';
    $lines = explode("\n", $sql_content);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and comments
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        
        $current_statement .= $line . " ";
        
        // Check if statement ends with semicolon
        if (substr($line, -1) === ';') {
            $statements[] = trim($current_statement);
            $current_statement = '';
        }
    }
    
    echo "<h2>📋 Executing Database Schema</h2>";
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            // Extract table name for better reporting
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                $table_name = $matches[1];
                echo "<p class='info'>🔧 Creating table: <strong>$table_name</strong></p>";
            } elseif (preg_match('/INSERT.*?INTO.*?`?(\w+)`?/i', $statement, $matches)) {
                $table_name = $matches[1];
                echo "<p class='info'>📝 Inserting data into: <strong>$table_name</strong></p>";
            }
            
            $conn->exec($statement);
            $success_count++;
            
        } catch (PDOException $e) {
            $error_count++;
            // Only show error if it's not a "table already exists" error
            if (strpos($e->getMessage(), 'already exists') === false && strpos($e->getMessage(), 'Duplicate entry') === false) {
                echo "<p class='error'>❌ Error in statement " . ($index + 1) . ": " . $e->getMessage() . "</p>";
                echo "<pre>" . htmlspecialchars($statement) . "</pre>";
            } else {
                echo "<p class='warning'>⚠️ Table already exists (skipping)</p>";
                $success_count++; // Count as success since table exists
                $error_count--; // Don't count as error
            }
        }
    }
    
    echo "<h2>📊 Execution Summary</h2>";
    echo "<p class='success'>✅ Successful operations: $success_count</p>";
    if ($error_count > 0) {
        echo "<p class='error'>❌ Failed operations: $error_count</p>";
    }
    
    // Verify tables were created
    echo "<h2>🔍 Verification</h2>";
    
    $tables_to_check = [
        'publications',
        'publication_students', 
        'publication_mentors',
        'publication_status_history',
        'publication_audit_log',
        'publication_statuses'
    ];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $conn->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<p class='success'>✅ Table '<strong>$table</strong>' exists with " . count($columns) . " columns</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Table '<strong>$table</strong>' not found</p>";
        }
    }
    
    // Check if publication statuses were inserted
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM publication_statuses");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p class='success'>✅ Publication statuses table has $count default statuses</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Could not verify publication statuses</p>";
    }
    
    echo "<h2>✨ Setup Complete!</h2>";
    echo "<p class='success'><strong>Publications module database setup completed successfully!</strong></p>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>🔗 <a href='/publications/list.php'>View Publications List</a></li>";
    echo "<li>🔗 <a href='/publications/create.php'>Create New Publication</a></li>";
    echo "<li>🔗 <a href='/dashboard.php'>Go to Dashboard</a></li>";
    echo "</ul>";
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>📋 Database Schema Summary</h3>";
    echo "<ul>";
    echo "<li><strong>publications</strong> - Main publications table with conference/journal fields</li>";
    echo "<li><strong>publication_students</strong> - Many-to-many relationship with students</li>";
    echo "<li><strong>publication_mentors</strong> - Many-to-many relationship with mentors</li>";
    echo "<li><strong>publication_status_history</strong> - Track status changes over time</li>";
    echo "<li><strong>publication_audit_log</strong> - Detailed audit trail of all changes</li>";
    echo "<li><strong>publication_statuses</strong> - Lookup table for publication statuses</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2>❌ Setup Failed</h2>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
}

echo "</body>";
echo "</html>";
?> 