<?php
/**
 * Production Database Configuration
 * 
 * IMPORTANT: Update these credentials with your production database details
 * before deploying to the server.
 */

class Database {
    // Update these with your production database credentials
    private $host = "localhost";                    // Your database host (usually localhost)
    private $db_name = "your_production_db_name";   // Your production database name
    private $username = "your_db_username";         // Your database username
    private $password = "your_secure_password";     // Your database password (use a strong password)
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

/** 
 * DEPLOYMENT INSTRUCTIONS:
 * 
 * 1. Rename this file to 'database.php' after updating credentials
 * 2. Update the following variables with your production values:
 *    - $host: Your database server host
 *    - $db_name: Your production database name
 *    - $username: Your database username
 *    - $password: Your database password
 * 
 * 3. Ensure your database user has the following privileges:
 *    - SELECT, INSERT, UPDATE, DELETE
 *    - CREATE, ALTER, DROP (for initial setup only)
 * 
 * 4. Test the connection by running setup_database.php once
 * 
 * 5. Remove setup_database.php after successful deployment
 */
?>