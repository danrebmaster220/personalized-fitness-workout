<?php
class Database {
    private $host = "localhost";
    private $db_name = "fitness_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function connect() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->db_name}", 
                                   $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Database connection error: " . $e->getMessage()]);
            exit;
        }
        return $this->conn;
    }
}
?>
