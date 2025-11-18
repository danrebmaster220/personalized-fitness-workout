<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/models/Users.php';
require_once __DIR__ . '/../../config/email.php'; // email sender

class UserController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        $this->user = new User($this->db);
    }

    // LOGIN
    public function login($email, $password) {
        $user = $this->user->findByEmail($email);

        if (!$user) {
            return ["success" => false, "message" => "User not found."];
        }

        // Allow login even if unverified 
        if (!$user['Is_Verified']) {
            return [
                "success" => true,
                "message" => "Your account is not verified yet.",
                "user" => $user
            ];
        }

        if (!password_verify($password, $user['Password'])) {
            return ["success" => false, "message" => "Incorrect password."];
        }

        unset($user['Password'], $user['Verification_Token'], $user['Reset_Token'], $user['Reset_Expires']);

        return [
            "success" => true,
            "message" => "Login successful.",
            "user" => $user
        ];
    }

    // REGISTER
    public function register($data) {

        if ($this->user->findByEmail($data['email'])) {
            return ["success" => false, "message" => "Email already registered."];
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $token = bin2hex(random_bytes(16));

        $success = $this->user->create(
            $data['firstName'],
            $data['lastName'],
            $data['email'],
            $hashedPassword,
            $data['age'],
            $data['height'],
            $data['weight'],
            $data['gender'],
            $data['fitnessLevel'],
            $data['activityLevel'],
            $token
        );

        if ($success) {
            sendAppEmail($data['email'], 'verification', ['token' => $token]);

            return [
                "success" => true,
                "message" => "Registration successful! Please check your email to verify your account."
            ];
        }

        return ["success" => false, "message" => "Registration failed."];
    }

    // VERIFY EMAIL
    public function verify($token) {
        if ($this->user->verifyUser($token)) {
            return ["success" => true, "message" => "Account verified successfully!"];
        }
        return ["success" => false, "message" => "Invalid or expired verification token."];
    }

    // FORGOT PASSWORD
    public function forgot($email) {
        $user = $this->user->findByEmail($email);

        if (!$user)
            return ["success" => false, "message" => "Email not found."];

        $token = bin2hex(random_bytes(16));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        if (!$this->user->setResetToken($email, $token, $expires)) {
            return ["success" => false, "message" => "Failed to create reset token."];
        }

        sendAppEmail($email, "password-reset", ["token" => $token]);

        return ["success" => true, "message" => "Reset link sent to your email."];
    }

    // RESET PASSWORD
    public function reset($token, $newPassword) {
        $user = $this->user->findByResetToken($token);

        if (!$user)
            return ["success" => false, "message" => "Invalid or expired token."];

        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);

        if ($this->user->updatePassword($token, $hashed)) {
            return ["success" => true, "message" => "Password reset successful."];
        }

        return ["success" => false, "message" => "Failed to reset password."];
    }

    // GET PROFILE
    public function getUserProfile($userId) {
        $user = $this->user->findById($userId);

        if ($user) {
            unset($user['Password'], $user['Verification_Token'], $user['Reset_Token'], $user['Reset_Expires']);
            return ["success" => true, "profile" => $user];
        }

        return ["success" => false, "message" => "User not found."];
    }

    // NEW: UPDATE PROFILE (Fields only, JSON body)
    public function updateProfile($userId, $data) {
        $allowed = [
            "FirstName", "LastName", "Gender",
            "Height", "Weight", "Age",
            "Fitness_Level", "Activity_Level"
        ];

        $cleanData = [];
        foreach ($allowed as $key) {
            if (isset($data[$key])) {
                $cleanData[$key] = $data[$key];
            }
        }

        if (empty($cleanData)) {
            return ["success" => false, "message" => "No valid fields to update."];
        }

        if ($this->user->updateProfile($userId, $cleanData)) {
            $user = $this->user->findById($userId);
            unset($user['Password']);
            return ["success" => true, "user" => $user];
        }

        return ["success" => false, "message" => "Failed to update profile."];
    }

    // NEW: UPLOAD PROFILE IMAGE
    public function uploadImage($data, $files) {
        if (!isset($data['userId']) || !isset($files['image'])) {
            return ["success" => false, "message" => "Invalid request"];
        }

        $userId = $data['userId'];
        $image = $files['image'];

        // Validate
        $allowed = ["image/jpeg", "image/png", "image/jpg"];
        if (!in_array($image["type"], $allowed)) {
            return ["success" => false, "message" => "Invalid file type."];
        }

        if ($image["size"] > 3 * 1024 * 1024) { // 3MB limit
            return ["success" => false, "message" => "File too large."];
        }

        // Create folder if not exists
        $uploadDir = __DIR__ . "/../../uploads/profiles/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Unique file name
        $ext = pathinfo($image["name"], PATHINFO_EXTENSION);
        $fileName = "user_" . $userId . "_" . time() . "." . $ext;

        $filePath = $uploadDir . $fileName;
        $dbPath = "/uploads/profiles/" . $fileName;

        if (!move_uploaded_file($image["tmp_name"], $filePath)) {
            return ["success" => false, "message" => "Upload failed."];
        }

        // Save to DB
        if ($this->user->uploadProfileImage($userId, $dbPath)) {
            return [
                "success" => true,
                "message" => "Profile image updated.",
                "image"   => $dbPath
            ];
        }

        return ["success" => false, "message" => "Database update failed."];
    }

    //  NEW: CHANGE PASSWORD (JSON Body)
    public function changePassword($userId, $old, $new) {
        $user = $this->user->findById($userId);

        if (!$user) return ["success" => false, "message" => "User not found"];
        if (!password_verify($old, $user['Password'])) {
            return ["success" => false, "message" => "Incorrect old password"];
        }

        $hash = password_hash($new, PASSWORD_BCRYPT);
        $this->user->updatePasswordById($userId, $hash);

        return ["success" => true, "message" => "Password updated"];
    }

    // RESEND VERIFICATION EMAIL
    public function resendVerification($userId) {
        $user = $this->user->findById($userId);

        if (!$user)
            return ["success" => false, "message" => "User not found."];

        $token = bin2hex(random_bytes(16));

        if (!$this->user->updateVerificationToken($userId, $token)) {
            return ["success" => false, "message" => "Failed to generate verification token."];
        }

        sendAppEmail($user['Email'], "verification", ["token" => $token]);

        return ["success" => true, "message" => "Verification email resent."];
    }
}
?>
