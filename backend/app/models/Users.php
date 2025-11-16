<?php
class User {
    private $conn;
    private $table = "user";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create New User
    public function create($firstName, $lastName, $email, $password, $age, $height, $weight, $gender, $fitnessLevel, $activityLevel, $token) {
        $query = "INSERT INTO {$this->table} 
        (FirstName, LastName, Email, Password, Age, Height, Weight, Gender, Fitness_Level, Activity_Level, Verification_Token) 
        VALUES (:firstName, :lastName, :email, :password, :age, :height, :weight, :gender, :fitnessLevel, :activityLevel, :token)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":firstName"     => $firstName,
            ":lastName"      => $lastName,
            ":email"         => $email,
            ":password"      => $password,
            ":age"           => $age,
            ":height"        => $height,
            ":weight"        => $weight,
            ":gender"        => $gender,
            ":fitnessLevel"  => $fitnessLevel,
            ":activityLevel" => $activityLevel,
            ":token"         => $token
        ]);
    }

    // Find User by Email
    public function findByEmail($email) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE Email = :email LIMIT 1");
        $stmt->execute([":email" => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Find User by ID
    public function findById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE User_ID = :id LIMIT 1");
        $stmt->execute([":id" => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update Profile
    public function updateProfile($id, $data) {
        $stmt = $this->conn->prepare("
        UPDATE {$this->table} 
        SET 
            FirstName      = :f, 
            LastName       = :l, 
            Age            = :a, 
            Height         = :h, 
            Weight         = :w, 
            Gender         = :g, 
            Fitness_Level  = :fl,
            Activity_Level = :al
        WHERE User_ID = :id");

        return $stmt->execute([
            ":f"  => $data['firstName'],
            ":l"  => $data['lastName'],
            ":a"  => $data['age'],
            ":h"  => $data['height'],
            ":w"  => $data['weight'],
            ":g"  => $data['gender'],
            ":fl" => $data['fitnessLevel'],
            ":al" => $data['activityLevel'],
            ":id" => $id
        ]);
    }

    // Update Password by ID
    public function updatePasswordById($id, $password) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET Password = :p WHERE User_ID = :id");
        return $stmt->execute([":p" => $password, ":id" => $id]);
    }

    // Update Verification Token
    public function updateVerificationToken($id, $token) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} 
                                      SET Verification_Token = :t 
                                      WHERE User_ID = :id");
        return $stmt->execute([":t" => $token, ":id" => $id]);
    }

    // Verify Email
    public function verifyUser($token) {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table} 
            SET Is_Verified = 1, Verification_Token = NULL 
            WHERE Verification_Token = :t
        ");
        return $stmt->execute([":t" => $token]);
    }

    // Set Reset Token
    public function setResetToken($email, $token, $expires) {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table} 
            SET Reset_Token = :t, Reset_Expires = :e 
            WHERE Email = :email
        ");

        return $stmt->execute([
            ":t"     => $token,
            ":e"     => $expires,
            ":email" => $email
        ]);
    }

    // Find User by Reset Token
    public function findByResetToken($token) {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table} 
            WHERE Reset_Token = :t AND Reset_Expires > NOW()
        ");
        $stmt->execute([":t" => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update Password (reset)
    public function updatePassword($token, $password) {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table} 
            SET Password = :p, Reset_Token = NULL, Reset_Expires = NULL 
            WHERE Reset_Token = :t
        ");
        return $stmt->execute([":p" => $password, ":t" => $token]);
    }

    // Admin Actions
    public function getAllUsers() {
        $stmt = $this->conn->query("SELECT * FROM {$this->table} ORDER BY Created_At DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteUser($id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE User_ID=:id");
        return $stmt->execute([":id" => $id]);
    }
}
