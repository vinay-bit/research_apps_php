<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'research_apps_db';
    private $username = 'root';
    private $password = '';
    public $conn;

    // private $host = 'localhost';
    // private $db_name = 'u527896677_research_apps';
    // private $username = 'u527896677_vinay';
    // private $password = 'Blockpass@909';
    // public $conn;

    public function getConnection(){
        $this->conn = null;
        try{
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>