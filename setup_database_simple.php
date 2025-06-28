<?php
// Simple Database Setup Script
// This script will create the complete database structure

require_once 'config/database.php';

try {
    // Read the SQL file
    $sql_file = 'setup_complete_database.sql';
    if (!file_exists($sql_file)) {
        die("❌ Error: SQL file not found: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    if ($sql_content === false) {
        die("❌ Error: Could not read SQL file");
    }
    
    echo "📁 Reading SQL file: $sql_file\n<br>";
    
    // Create database connection (without selecting database first)
    $host = DB_HOST;
    $username = DB_USER;
    $password = DB_PASS;
    
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔗 Connected to MySQL server\n<br>";
    
    // Split SQL into individual statements
    $statements = explode(';', $sql_content);
    
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip empty statements and comments
        if (empty($statement) || substr($statement, 0, 2) === '--') {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
            
            // Show progress for important statements
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?(\w+)/i', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✅ Created table: {$matches[1]}\n<br>";
                }
            } elseif (stripos($statement, 'CREATE DATABASE') !== false) {
                echo "✅ Created database: research_apps_db\n<br>";
            } elseif (stripos($statement, 'INSERT INTO users') !== false && stripos($statement, 'admin') !== false) {
                echo "✅ Created admin user (username: admin, password: admin123)\n<br>";
            }
            
        } catch (PDOException $e) {
            $errors++;
            // Only show non-duplicate errors
            if (stripos($e->getMessage(), 'already exists') === false && 
                stripos($e->getMessage(), 'Duplicate entry') === false) {
                echo "⚠️ Warning: " . $e->getMessage() . "\n<br>";
            }
        }
    }
    
    echo "\n<br>📊 <strong>Database Setup Summary:</strong>\n<br>";
    echo "✅ Statements executed: $executed\n<br>";
    if ($errors > 0) {
        echo "⚠️ Warnings/Errors: $errors (mostly duplicates - this is normal)\n<br>";
    }
    
    // Verify the setup
    echo "\n<br>🔍 <strong>Verification:</strong>\n<br>";
    
    // Switch to the research_apps_db
    $pdo->exec("USE research_apps_db");
    
    // Check tables
    $result = $pdo->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "📋 Tables created: " . count($tables) . "\n<br>";
    foreach ($tables as $table) {
        echo "&nbsp;&nbsp;• $table\n<br>";
    }
    
    // Check admin user
    $stmt = $pdo->query("SELECT username, full_name, user_type, status FROM users WHERE user_type = 'admin'");
    $admin_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n<br>👤 <strong>Admin Users:</strong>\n<br>";
    foreach ($admin_users as $user) {
        echo "&nbsp;&nbsp;• Username: {$user['username']}, Name: {$user['full_name']}, Status: {$user['status']}\n<br>";
    }
    
    echo "\n<br>🎉 <strong>Database setup completed successfully!</strong>\n<br>";
    echo "🔑 You can now login with: <strong>admin</strong> / <strong>admin123</strong>\n<br>";
    echo "🌐 Go to: <a href='login.php'>login.php</a>\n<br>";
    
} catch (PDOException $e) {
    echo "❌ <strong>Database Error:</strong> " . $e->getMessage() . "\n<br>";
    echo "🔧 Please check your database configuration in config/database.php\n<br>";
} catch (Exception $e) {
    echo "❌ <strong>Error:</strong> " . $e->getMessage() . "\n<br>";
}
?> 