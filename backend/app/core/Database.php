<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // Load credentials from environment variables or config file
        $envPath = __DIR__ . '/../../config/.env';
        if (file_exists($envPath)) {
            $env = parse_ini_file($envPath);
            $this->host = $env['DB_HOST'] ?? 'localhost';
            $this->db_name = $env['DB_NAME'] ?? 'fitness_db';
            $this->username = $env['DB_USER'] ?? 'root';
            $this->password = $env['DB_PASS'] ?? '';
        } else {
            // Fallback to default values (for local development only)
            $this->host = "localhost";
            $this->db_name = "fitness_db";
            $this->username = "root";
            $this->password = "";
        }
    }

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
            // Don't expose database details in production
            error_log("Database connection error: " . $e->getMessage());
            echo json_encode([
                "success" => false,
                "message" => "Database connection error. Please contact support."
            ]);
            exit;
        }
        return $this->conn;
    }
}
?>
