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

    // Find user by Google ID
    public function findByGoogleId($googleId) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE Google_ID = :gid LIMIT 1");
        $stmt->execute([":gid" => $googleId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Create a user from Google profile
    public function createGoogleUser($firstName, $lastName, $email, $googleId) {
        $query = "INSERT INTO {$this->table} (FirstName, LastName, Email, Password, Is_Verified, Google_ID, Login_Method) VALUES (:firstName, :lastName, :email, NULL, 1, :googleId, 'google')";
        $stmt = $this->conn->prepare($query);
        $ok = $stmt->execute([
            ":firstName" => $firstName,
            ":lastName" => $lastName,
            ":email" => $email,
            ":googleId" => $googleId
        ]);

        if (!$ok) return false;
        return $this->conn->lastInsertId();
    }

    // Link an existing account (by email) to a Google ID
    public function linkGoogleToEmail($email, $googleId) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET Google_ID = :gid, Is_Verified = 1, Login_Method = 'google' WHERE Email = :email");
        return $stmt->execute([":gid" => $googleId, ":email" => $email]);
    }

    // DYNAMIC SAFE PROFILE UPDATE
    public function updateProfile($id, $data) {

        $allowed = [
            "FirstName", "LastName", "Age", "Height", "Weight",
            "Gender", "Fitness_Level", "Activity_Level"
        ];

        $setParts = [];
        $params = [":id" => $id];

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $setParts[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($setParts)) return false;

        $sql = "UPDATE {$this->table} SET " . implode(", ", $setParts) . " WHERE User_ID = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    // Update Profile Image
    public function updateProfileImage($id, $imageUrl) {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table}
            SET Profile_Image = :img
            WHERE User_ID = :id
        ");

        return $stmt->execute([
            ":img" => $imageUrl,
            ":id"  => $id
        ]);
    }

    // Update Password by ID
    public function updatePasswordById($id, $password) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET Password = :p WHERE User_ID = :id");
        return $stmt->execute([":p" => $password, ":id" => $id]);
    }

    public function updateVerificationToken($id, $token) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET Verification_Token = :t WHERE User_ID = :id");
        return $stmt->execute([":t" => $token, ":id" => $id]);
    }

    // Update Email and reset verification token (used when user changes email)
    public function updateEmailAndToken($id, $email, $token) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET Email = :email, Is_Verified = 0, Verification_Token = :t WHERE User_ID = :id");
        $ok = $stmt->execute([
            ":email" => $email,
            ":t" => $token,
            ":id" => $id
        ]);

        if (!$ok) {
            $err = $stmt->errorInfo();
            return ["success" => false, "error" => $err[2] ?? "Unknown DB error"];
        }

        return ["success" => true];
    }

    public function verifyUser($token) {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table} 
            SET Is_Verified = 1, Verification_Token = NULL 
            WHERE Verification_Token = :t
        ");
        return $stmt->execute([":t" => $token]);
    }

    public function setResetToken($email, $token, $expires) {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table} 
            SET Reset_Token = :t, Reset_Expires = :e 
            WHERE Email = :email
        ");
        return $stmt->execute([
            ":t" => $token,
            ":e" => $expires,
            ":email" => $email
        ]);
    }

    public function findByResetToken($token) {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table} 
            WHERE Reset_Token = :t AND Reset_Expires > NOW()
        ");
        $stmt->execute([":t" => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePassword($token, $password) {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table} 
            SET Password = :p, Reset_Token = NULL, Reset_Expires = NULL 
            WHERE Reset_Token = :t
        ");
        return $stmt->execute([
            ":p" => $password,
            ":t" => $token
        ]);
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

    public function uploadProfileImage($id, $path) {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table} 
            SET Profile_Image = :img
            WHERE User_ID = :id
        ");

        return $stmt->execute([
            ":img" => $path,
            ":id"  => $id
        ]);
    }
    
    public function updateLoginMethod($id, $method) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET Login_Method = :method WHERE User_ID = :id");
        return $stmt->execute([":method" => $method, ":id" => $id]);
    }
    
}
?>
