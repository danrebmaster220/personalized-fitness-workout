<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/models/Users.php';
require_once __DIR__ . '/../../config/email.php'; // ⬅ central email sender

class UserController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        $this->user = new User($this->db);
    }

    // Login 
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

    // Register 
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
            // Send verification email
            sendAppEmail($data['email'], 'verification', [
                'token' => $token
            ]);

            return [
                "success" => true,
                "message" => "Registration successful! Please check your email to verify your account."
            ];
        }

        return ["success" => false, "message" => "Registration failed."];
    }

    // Verify Email 
    public function verify($token) {
        if ($this->user->verifyUser($token)) {
            return ["success" => true, "message" => "Account verified successfully!"];
        }
        return ["success" => false, "message" => "Invalid or expired verification token."];
    }

    // Forgot Password
    public function forgot($email) {
        $user = $this->user->findByEmail($email);

        if (!$user)
            return ["success" => false, "message" => "Email not found."];

        $token = bin2hex(random_bytes(16));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        if (!$this->user->setResetToken($email, $token, $expires)) {
            return ["success" => false, "message" => "Failed to create reset token."];
        }

        // Send reset email
        sendAppEmail($email, "password-reset", ["token" => $token]);

        return ["success" => true, "message" => "Reset link sent to your email."];
    }

    // Reset Password
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

    // Get Profile
    public function getUserProfile($userId) {
        $user = $this->user->findById($userId);

        if ($user) {
            unset($user['Password'], $user['Verification_Token'], $user['Reset_Token'], $user['Reset_Expires']);
            return ["success" => true, "profile" => $user];
        }

        return ["success" => false, "message" => "User not found."];
    }

    // Update Profile
    public function updateProfile($userId, $data) {
        if ($this->user->updateProfile($userId, $data)) {
            return ["success" => true, "message" => "Profile updated successfully."];
        }
        return ["success" => false, "message" => "Failed to update profile."];
    }

    // Change Password
    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->user->findById($userId);

        if (!$user || !password_verify($oldPassword, $user['Password'])) {
            return ["success" => false, "message" => "Incorrect old password."];
        }

        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);

        if ($this->user->updatePasswordById($userId, $hashed)) {
            return ["success" => true, "message" => "Password changed successfully."];
        }

        return ["success" => false, "message" => "Failed to change password."];
    }

    // Resend Email Verification
    public function resendVerification($userId) {
        $user = $this->user->findById($userId);

        if (!$user)
            return ["success" => false, "message" => "User not found."];

        $token = bin2hex(random_bytes(16));

        if (!$this->user->updateVerificationToken($userId, $token)) {
            return ["success" => false, "message" => "Failed to generate verification token."];
        }

        // Send verification email
        sendAppEmail($user['Email'], "verification", ["token" => $token]);

        return ["success" => true, "message" => "Verification email resent."];
    }
}
?>
