<?php
class User {
    private $conn;
    private $table = "user";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($email, $password, $token) {
        $query = "INSERT INTO {$this->table} (Email, Password, Verification_Token) VALUES (:email, :password, :token)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $password);
        $stmt->bindParam(":token", $token);
        return $stmt->execute();
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM {$this->table} WHERE Email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function verifyUser($token) {
        $query = "UPDATE {$this->table} SET Is_Verified = 1, Verification_Token = NULL WHERE Verification_Token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        return $stmt->execute();
    }

    public function setResetToken($email, $token, $expires) {
        $query = "UPDATE {$this->table} SET Reset_Token = :token, Reset_Expires = :expires WHERE Email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":expires", $expires);
        return $stmt->execute();
    }

    public function findByResetToken($token) {
        $query = "SELECT * FROM {$this->table} WHERE Reset_Token = :token AND Reset_Expires > NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePassword($token, $password) {
        $query = "UPDATE {$this->table} 
                  SET Password = :password, Reset_Token = NULL, Reset_Expires = NULL 
                  WHERE Reset_Token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password", $password);
        $stmt->bindParam(":token", $token);
        return $stmt->execute();
    }
}
?>
