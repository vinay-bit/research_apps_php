<?php
/**
 * Database Setup Script for Research Apps
 * Run this script to automatically create the database and tables
 */

// Database configuration
$host = 'localhost';
$root_username = 'root';
$root_password = '';  // Change this to your MySQL root password
$database_name = 'research_apps_db';

echo "<h2>Research Apps - Database Setup</h2>";
echo "<p>Setting up database: <strong>$database_name</strong></p>";

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host", $root_username, $root_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to MySQL server successfully!</p>";
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS `$database_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "<p>✅ Database '$database_name' created successfully!</p>";
    
    // Select the database
    $pdo->exec("USE `$database_name`");
    
    // Create organizations table
    $sql = "CREATE TABLE IF NOT EXISTS organizations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "<p>✅ Organizations table created!</p>";
    
    // Create departments table
    $sql = "CREATE TABLE IF NOT EXISTS departments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "<p>✅ Departments table created!</p>";
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_type ENUM('admin', 'mentor', 'councillor') NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        username VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        
        -- Admin & Mentor fields
        department_id INT NULL,
        
        -- Mentor specific fields
        specialization VARCHAR(255) NULL,
        organization_id INT NULL,
        
        -- Councillor specific fields
        organization_name VARCHAR(255) NULL,
        mou_signed BOOLEAN DEFAULT FALSE,
        mou_drive_link VARCHAR(500) NULL,
        
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "<p>✅ Users table created!</p>";
    
    // Create sessions table
    $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_token VARCHAR(255) NOT NULL UNIQUE,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "<p>✅ User sessions table created!</p>";
    
    // Insert sample organizations
    $organizations = [
        'Tech Corp',
        'Innovation Hub', 
        'Digital Solutions',
        'Future Systems',
        'Smart Technologies',
        'AI Research Lab',
        'Data Science Institute',
        'Cloud Computing Solutions'
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO organizations (name) VALUES (?)");
    foreach ($organizations as $org) {
        $stmt->execute([$org]);
    }
    echo "<p>✅ Sample organizations inserted!</p>";
    
    // Insert sample departments
    $departments = [
        'Computer Science',
        'Information Technology',
        'Software Engineering', 
        'Data Science',
        'Artificial Intelligence',
        'Cybersecurity',
        'Web Development',
        'Mobile App Development'
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO departments (name) VALUES (?)");
    foreach ($departments as $dept) {
        $stmt->execute([$dept]);
    }
    echo "<p>✅ Sample departments inserted!</p>";
    
    // Create default admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT IGNORE INTO users (user_type, full_name, username, password, department_id, status) 
            VALUES ('admin', 'System Administrator', 'admin', ?, 1, 'active')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$admin_password]);
    echo "<p>✅ Default admin user created!</p>";
    
    // Create sample mentor user
    $mentor_password = password_hash('mentor123', PASSWORD_DEFAULT);
    $sql = "INSERT IGNORE INTO users (user_type, full_name, username, password, department_id, specialization, organization_id, status) 
            VALUES ('mentor', 'John Smith', 'mentor', ?, 1, 'Machine Learning', 1, 'active')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$mentor_password]);
    echo "<p>✅ Sample mentor user created!</p>";
    
    // Create sample councillor user
    $councillor_password = password_hash('councillor123', PASSWORD_DEFAULT);
    $sql = "INSERT IGNORE INTO users (user_type, full_name, username, password, organization_name, mou_signed, mou_drive_link, status) 
            VALUES ('councillor', 'Dr. Sarah Johnson', 'councillor', ?, 'Academic Support Center', TRUE, 'https://drive.google.com/sample-mou-link', 'active')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$councillor_password]);
    echo "<p>✅ Sample councillor user created!</p>";
    
    echo "<hr>";
    echo "<h3>🎉 Database Setup Complete!</h3>";
    echo "<p><strong>Default Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> username = <code>admin</code>, password = <code>admin123</code></li>";
    echo "<li><strong>Mentor:</strong> username = <code>mentor</code>, password = <code>mentor123</code></li>";
    echo "<li><strong>Councillor:</strong> username = <code>councillor</code>, password = <code>councillor123</code></li>";
    echo "</ul>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Update database credentials in <code>config/database.php</code></li>";
    echo "<li>Access the application: <a href='login.php' target='_blank'>login.php</a></li>";
    echo "<li>Test with any of the default users above</li>";
    echo "</ol>";
    
    // Display database statistics
    $stmt = $pdo->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Database Statistics:</strong></p>";
    echo "<ul>";
    foreach ($stats as $stat) {
        echo "<li>" . ucfirst($stat['user_type']) . "s: " . $stat['count'] . "</li>";
    }
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM departments");
    $dept_count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<li>Departments: " . $dept_count['count'] . "</li>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM organizations");
    $org_count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<li>Organizations: " . $org_count['count'] . "</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Common Solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure MySQL server is running</li>";
    echo "<li>Check your MySQL root password</li>";
    echo "<li>Verify MySQL service is started</li>";
    echo "<li>Check firewall settings</li>";
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
code {
    background-color: #f0f0f0;
    padding: 2px 5px;
    border-radius: 3px;
    font-family: monospace;
}
ul, ol {
    margin-left: 20px;
}
</style> 