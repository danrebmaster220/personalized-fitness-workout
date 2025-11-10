<?php
class User {
    private $conn;
    private $table = "user";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Updated create to include personal info
    public function create($firstName, $lastName, $email, $password, $age, $height, $weight, $gender, $fitnessLevel, $token) {
        $query = "INSERT INTO {$this->table} (FirstName, LastName, Email, Password, Age, Height, Weight, Gender, Fitness_Level, Verification_Token) VALUES (:firstName, :lastName, :email, :password, :age, :height, :weight, :gender, :fitnessLevel, :token)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":firstName", $firstName);
        $stmt->bindParam(":lastName", $lastName);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $password);
        $stmt->bindParam(":age", $age);
        $stmt->bindParam(":height", $height);
        $stmt->bindParam(":weight", $weight);
        $stmt->bindParam(":gender", $gender);
        $stmt->bindParam(":fitnessLevel", $fitnessLevel);
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

    // New: Find by ID
    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE ID = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
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

    // New: Update password by ID
    public function updatePasswordById($id, $password) {
        $query = "UPDATE {$this->table} SET Password = :password WHERE ID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password", $password);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    // New: Update profile
    public function updateProfile($id, $data) {
        $query = "UPDATE {$this->table} SET FirstName = :firstName, LastName = :lastName, Age = :age, Height = :height, Weight = :weight, Gender = :gender, Fitness_Level = :fitnessLevel WHERE ID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":firstName", $data['firstName']);
        $stmt->bindParam(":lastName", $data['lastName']);
        $stmt->bindParam(":age", $data['age']);
        $stmt->bindParam(":height", $data['height']);
        $stmt->bindParam(":weight", $data['weight']);
        $stmt->bindParam(":gender", $data['gender']);
        $stmt->bindParam(":fitnessLevel", $data['fitnessLevel']);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    // New: Update verification token
    public function updateVerificationToken($id, $token) {
        $query = "UPDATE {$this->table} SET Verification_Token = :token WHERE ID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}
?>