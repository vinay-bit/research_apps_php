<?php
/**
 * Database Connection Test Script
 * Use this to test your database credentials before running the main setup
 */

// Test different database configurations
echo "<h2>Research Apps - Database Connection Test</h2>";
echo "<hr>";

// Configuration 1: Your current credentials
echo "<h3>Testing Current Configuration:</h3>";
$host1 = "localhost";
$db_name1 = "u527896677_researchApps";
$username1 = "u527896677_vinay";
$password1 = ""; // Enter your password here

echo "<p><strong>Host:</strong> $host1</p>";
echo "<p><strong>Database:</strong> $db_name1</p>";
echo "<p><strong>Username:</strong> $username1</p>";
echo "<p><strong>Password:</strong> " . (empty($password1) ? "[EMPTY - PLEASE SET]" : "[SET]") . "</p>";

if (empty($password1)) {
    echo "<p style='color: red;'><strong>❌ Password is empty! Please set the password in this file first.</strong></p>";
} else {
    test_connection($host1, $db_name1, $username1, $password1, "Current Configuration");
}

echo "<hr>";

// Configuration 2: Alternative localhost formats
echo "<h3>Testing Alternative Host Formats:</h3>";
$alternative_hosts = [
    "localhost",
    "127.0.0.1",
    "localhost:3306",
    "127.0.0.1:3306"
];

foreach ($alternative_hosts as $host) {
    if (!empty($password1)) {
        test_connection($host, $db_name1, $username1, $password1, "Host: $host");
    }
}

echo "<hr>";

// Configuration 3: Test without database name (just connection)
echo "<h3>Testing Connection Without Database:</h3>";
if (!empty($password1)) {
    test_connection($host1, "", $username1, $password1, "Connection Only (No Database)");
}

echo "<hr>";

// Instructions
echo "<h3>📋 Troubleshooting Steps:</h3>";
echo "<ol>";
echo "<li><strong>Check cPanel Database Section:</strong> Verify database name and username are correct</li>";
echo "<li><strong>Verify Password:</strong> Make sure you're using the correct database user password</li>";
echo "<li><strong>Check User Privileges:</strong> Ensure the user has ALL privileges on the database</li>";
echo "<li><strong>Remote MySQL:</strong> Some hosts use different hostnames (check hosting documentation)</li>";
echo "<li><strong>Contact Support:</strong> If none work, contact your hosting provider</li>";
echo "</ol>";

echo "<h3>🔧 Common Hosting Configurations:</h3>";
echo "<ul>";
echo "<li><strong>Shared Hosting:</strong> Usually localhost or 127.0.0.1</li>";
echo "<li><strong>Some Hosts:</strong> mysql.yourdomain.com or similar</li>";
echo "<li><strong>Port Issues:</strong> Some require :3306 explicitly</li>";
echo "</ul>";

function test_connection($host, $db_name, $username, $password, $label) {
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo "<h4>$label</h4>";
    
    try {
        $dsn = "mysql:host=$host";
        if (!empty($db_name)) {
            $dsn .= ";dbname=$db_name";
        }
        
        $pdo = new PDO(
            $dsn,
            $username,
            $password,
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5
            )
        );
        
        echo "<p style='color: green;'><strong>✅ SUCCESS!</strong> Connection established successfully.</p>";
        
        // Test database selection if database name provided
        if (!empty($db_name)) {
            $stmt = $pdo->query("SELECT DATABASE() as current_db");
            $result = $stmt->fetch();
            echo "<p><strong>Current Database:</strong> " . ($result['current_db'] ?? 'None') . "</p>";
        }
        
        // Show MySQL version
        $stmt = $pdo->query("SELECT VERSION() as version");
        $result = $stmt->fetch();
        echo "<p><strong>MySQL Version:</strong> " . $result['version'] . "</p>";
        
        // Show available databases (if user has privileges)
        try {
            $stmt = $pdo->query("SHOW DATABASES");
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<p><strong>Available Databases:</strong> " . implode(', ', $databases) . "</p>";
        } catch (Exception $e) {
            echo "<p><strong>Available Databases:</strong> Cannot list (limited privileges)</p>";
        }
        
        $pdo = null;
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'><strong>❌ FAILED!</strong></p>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>";
        
        // Provide specific guidance based on error
        $error_msg = $e->getMessage();
        if (strpos($error_msg, 'Access denied') !== false) {
            echo "<p style='color: orange;'><strong>💡 Suggestion:</strong> Check username and password in cPanel</p>";
        } elseif (strpos($error_msg, 'Unknown database') !== false) {
            echo "<p style='color: orange;'><strong>💡 Suggestion:</strong> Database name might be incorrect</p>";
        } elseif (strpos($error_msg, 'Connection refused') !== false) {
            echo "<p style='color: orange;'><strong>💡 Suggestion:</strong> Try different host or contact hosting support</p>";
        }
    }
    
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>⚠️ IMPORTANT:</strong> Delete this file after testing for security!</p>";
echo "<p><strong>Next Step:</strong> Once connection works, update config/database.php with working credentials</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
h3 { color: #666; }
h4 { color: #888; margin: 0; }
div { margin-bottom: 10px; }
hr { margin: 20px 0; }
</style> 