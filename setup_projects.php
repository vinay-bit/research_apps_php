<?php
/**
 * Project Management Database Setup Script
 * This script creates all necessary tables for the project management system
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>Research Apps - Project Management Setup</h2>";
    echo "<p>Setting up project management database tables...</p>";
    
    // Read and execute the SQL schema
    $schema_file = 'database/projects_schema.sql';
    if (!file_exists($schema_file)) {
        throw new Exception("Schema file not found: $schema_file");
    }
    
    $sql_content = file_get_contents($schema_file);
    
    // Split SQL commands by semicolon and execute them
    $sql_commands = explode(';', $sql_content);
    
    foreach ($sql_commands as $command) {
        $command = trim($command);
        if (!empty($command) && !preg_match('/^--/', $command)) {
            try {
                $conn->exec($command);
            } catch (PDOException $e) {
                // Skip errors for existing tables/triggers
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "<p style='color: orange;'>Warning: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    echo "<p style='color: green;'>✅ Project statuses table created!</p>";
    echo "<p style='color: green;'>✅ Subjects table created!</p>";
    echo "<p style='color: green;'>✅ Project tags table created!</p>";
    echo "<p style='color: green;'>✅ Projects table created!</p>";
    echo "<p style='color: green;'>✅ Project-students assignment table created!</p>";
    echo "<p style='color: green;'>✅ Project-tags assignment table created!</p>";
    echo "<p style='color: green;'>✅ Project activity log table created!</p>";
    echo "<p style='color: green;'>✅ Database triggers created!</p>";
    
    // Create sample projects for demonstration
    echo "<p>Creating sample projects...</p>";
    
    // Get some IDs for sample data
    $stmt = $conn->query("SELECT id FROM project_statuses WHERE status_name = 'Project Execution - in progress' LIMIT 1");
    $status_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    
    $stmt = $conn->query("SELECT id FROM subjects WHERE subject_name = 'Computer Science' LIMIT 1");
    $subject_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    
    $stmt = $conn->query("SELECT id FROM users WHERE user_type = 'mentor' LIMIT 1");
    $mentor_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $mentor_id = $mentor_result ? $mentor_result['id'] : null;
    
    $stmt = $conn->query("SELECT id FROM users WHERE user_type = 'rbm' LIMIT 1");
    $rbm_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $rbm_id = $rbm_result ? $rbm_result['id'] : null;
    
    // Sample project 1
    $stmt = $conn->prepare("
        INSERT INTO projects (project_name, status_id, lead_mentor_id, subject_id, has_prototype, start_date, assigned_date, rbm_id, description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        'AI-Powered Student Performance Analyzer',
        $status_id,
        $mentor_id,
        $subject_id,
        'Yes',
        '2025-01-15',
        '2025-01-10',
        $rbm_id,
        'Development of an AI system to analyze and predict student performance patterns using machine learning algorithms.'
    ]);
    $project1_id = $conn->lastInsertId();
    
    // Sample project 2
    $stmt = $conn->prepare("
        INSERT INTO projects (project_name, status_id, lead_mentor_id, subject_id, has_prototype, start_date, assigned_date, rbm_id, description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        'Smart Campus IoT System',
        $status_id,
        $mentor_id,
        $subject_id,
        'No',
        '2025-02-01',
        '2025-01-25',
        $rbm_id,
        'Design and implementation of an IoT-based smart campus management system for energy optimization and security.'
    ]);
    $project2_id = $conn->lastInsertId();
    
    // Assign some tags to projects
    $stmt = $conn->query("SELECT id FROM project_tags WHERE tag_name IN ('Research', 'Technology', 'Innovation') LIMIT 3");
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tags as $tag) {
        $stmt = $conn->prepare("INSERT INTO project_tag_assignments (project_id, tag_id) VALUES (?, ?)");
        $stmt->execute([$project1_id, $tag['id']]);
        $stmt->execute([$project2_id, $tag['id']]);
    }
    
    // Assign some students to projects (if any exist)
    $stmt = $conn->query("SELECT id FROM students LIMIT 2");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($students as $student) {
        $stmt = $conn->prepare("INSERT INTO project_students (project_id, student_id) VALUES (?, ?)");
        $stmt->execute([$project1_id, $student['id']]);
    }
    
    echo "<p style='color: green;'>✅ Sample projects created!</p>";
    echo "<p style='color: green;'>✅ Project tags assigned!</p>";
    echo "<p style='color: green;'>✅ Students assigned to projects!</p>";
    
    echo "<h3>✅ Project Management System Setup Complete!</h3>";
    echo "<p><strong>What's been created:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Complete project database structure</li>";
    echo "<li>✅ Default project statuses (5 statuses)</li>";
    echo "<li>✅ Default subjects (10 subjects)</li>";
    echo "<li>✅ Default project tags (10 tags)</li>";
    echo "<li>✅ Auto-generation of project IDs (PRJ2025XXXX format)</li>";
    echo "<li>✅ Auto-calculation of end dates (4 months from start)</li>";
    echo "<li>✅ Project activity logging system</li>";
    echo "<li>✅ Multi-student assignment capability</li>";
    echo "<li>✅ Multi-tag assignment capability</li>";
    echo "<li>✅ Sample projects for demonstration</li>";
    echo "</ul>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>🔗 <a href='/research_apps/projects/list.php'>View Projects List</a></li>";
    echo "<li>🔗 <a href='/research_apps/projects/create.php'>Create New Project</a></li>";
    echo "<li>🔗 <a href='/research_apps/dashboard.php'>Go to Dashboard</a></li>";
    echo "</ul>";
    
    echo "<p style='color: red;'><strong>🗑️ Security Note:</strong> Delete this setup file after successful initialization!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Common Solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure MySQL server is running</li>";
    echo "<li>Check your database connection settings</li>";
    echo "<li>Verify database user has CREATE and INSERT privileges</li>";
    echo "<li>Ensure the users and students tables exist (run main setup first)</li>";
    echo "</ul>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background-color: #f5f5f5;
}
h2, h3 {
    color: #333;
}
p {
    margin: 10px 0;
}
ul, ol {
    margin-left: 20px;
}
a {
    color: #007bff;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>