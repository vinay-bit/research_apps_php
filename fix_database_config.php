<?php
/**
 * Database Configuration Fix Script
 * This will help you update your database.php file with correct credentials
 */

echo "<h2>Research Apps - Database Configuration Fix</h2>";
echo "<hr>";

// Current detected credentials from your error
$detected_host = "localhost";
$detected_db = "u527896677_researchApps";
$detected_user = "u527896677_vinay";

echo "<h3>🔍 Detected Credentials from Error:</h3>";
echo "<p><strong>Host:</strong> $detected_host</p>";
echo "<p><strong>Database:</strong> $detected_db</p>";
echo "<p><strong>Username:</strong> $detected_user</p>";
echo "<p><strong>Issue:</strong> Access denied - likely password problem</p>";

echo "<hr>";

echo "<h3>📋 Step-by-Step Fix Instructions:</h3>";
echo "<ol>";
echo "<li><strong>Go to your cPanel</strong> → MySQL Databases section</li>";
echo "<li><strong>Find your database:</strong> u527896677_researchApps</li>";
echo "<li><strong>Find your database user:</strong> u527896677_vinay</li>";
echo "<li><strong>Check if user is assigned to database</strong> (should show in 'Current Users' section)</li>";
echo "<li><strong>If not assigned:</strong> Add user to database with ALL PRIVILEGES</li>";
echo "<li><strong>If password forgotten:</strong> Change user password in cPanel</li>";
echo "</ol>";

echo "<hr>";

echo "<h3>🛠️ Manual Configuration Update:</h3>";
echo "<p>After fixing in cPanel, update your <code>config/database.php</code> file:</p>";

$config_content = '<?php
class Database {
    private $host = "localhost";                    // Usually localhost for shared hosting
    private $db_name = "u527896677_researchApps";   // Your database name
    private $username = "u527896677_vinay";         // Your database username  
    private $password = "YOUR_ACTUAL_PASSWORD";     // Replace with your actual password
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
        
        return $this->conn;
    }
}
?>';

echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow-x: auto;'>";
echo htmlspecialchars($config_content);
echo "</pre>";

echo "<hr>";

echo "<h3>🔧 Alternative Host Names to Try:</h3>";
echo "<p>If 'localhost' doesn't work, try these common alternatives:</p>";
echo "<ul>";
echo "<li><code>127.0.0.1</code></li>";
echo "<li><code>localhost:3306</code></li>";
echo "<li><code>mysql.yourdomain.com</code> (replace with your domain)</li>";
echo "<li><code>server_ip_address</code> (check cPanel for server IP)</li>";
echo "</ul>";

echo "<hr>";

echo "<h3>🎯 Quick Test Process:</h3>";
echo "<ol>";
echo "<li><strong>First:</strong> Run the database connection test script</li>";
echo "<li><strong>Edit:</strong> <code>test_db_connection.php</code> and add your password on line 15</li>";
echo "<li><strong>Visit:</strong> yourdomain.com/research_apps/test_db_connection.php</li>";
echo "<li><strong>Find working configuration</strong> from test results</li>";
echo "<li><strong>Update:</strong> <code>config/database.php</code> with working credentials</li>";
echo "<li><strong>Run:</strong> setup_database.php again</li>";
echo "<li><strong>Delete:</strong> test files after success</li>";
echo "</ol>";

echo "<hr>";

echo "<h3>⚠️ Common Shared Hosting Issues:</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px;'>";
echo "<h4>Issue 1: User Not Assigned to Database</h4>";
echo "<p><strong>Solution:</strong> In cPanel → MySQL Databases → Add User to Database</p>";
echo "<h4>Issue 2: Wrong Password</h4>";
echo "<p><strong>Solution:</strong> Reset password in cPanel → MySQL Databases → Change Password</p>";
echo "<h4>Issue 3: Insufficient Privileges</h4>";
echo "<p><strong>Solution:</strong> When adding user to database, select 'ALL PRIVILEGES'</p>";
echo "<h4>Issue 4: Database Doesn't Exist</h4>";
echo "<p><strong>Solution:</strong> Create database first in cPanel → MySQL Databases → Create Database</p>";
echo "</div>";

echo "<hr>";

echo "<h3>📞 If Nothing Works:</h3>";
echo "<p>Contact your hosting provider support with this information:</p>";
echo "<ul>";
echo "<li>Database name: u527896677_researchApps</li>";
echo "<li>Username: u527896677_vinay</li>";
echo "<li>Error: Access denied for user (using password: YES)</li>";
echo "<li>Request: Please check if user has proper privileges on the database</li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>🗑️ Remember:</strong> Delete this file and test_db_connection.php after fixing!</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
h3 { color: #666; margin-top: 25px; }
h4 { color: #888; margin: 10px 0 5px 0; }
code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
pre { font-size: 14px; line-height: 1.4; }
ol, ul { margin: 10px 0; padding-left: 25px; }
li { margin: 5px 0; }
hr { margin: 25px 0; border: none; border-top: 1px solid #ddd; }
</style> 