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
            // Add charset=utf8mb4 in DSN
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4", 
                $this->username, 
                $this->password
            );

            // Optional: set default attributes
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Make sure connection uses utf8mb4
            $this->conn->exec("SET NAMES utf8mb4");
        } catch (PDOException $e) {
            echo json_encode([
                "success" => false,
                "message" => "Database connection error: " . $e->getMessage()
            ]);
            exit;
        }
        return $this->conn;
    }
}
?>
