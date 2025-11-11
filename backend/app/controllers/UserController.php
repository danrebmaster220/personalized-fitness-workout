<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/models/Users.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UserController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        $this->user = new User($this->db);
    }

    public function login($email, $password) {
        $user = $this->user->findByEmail($email);

        if (!$user) {
            return ["success" => false, "message" => "User not found."];
        }

        if (isset($user['Is_Verified']) && !$user['Is_Verified']) {
            $warning = "Your account isn’t verified yet. You can verify it later in your profile settings.";
            return [
                "success" => true,
                "message" => $warning,
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

    // Updated for two-step: Accepts full data (personal + email/pass)
    public function register($data) {
        if ($this->user->findByEmail($data['email'])) {
            return ["success" => false, "message" => "Email already registered."];
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $token = bin2hex(random_bytes(16));

        if ($this->user->create($data['firstName'], $data['lastName'], $data['email'], $hashedPassword, $data['age'], $data['height'], $data['weight'], $data['gender'], $data['fitnessLevel'], $data['activityLevel'], $token)) {
            $verifyLink = "http://localhost/personalized-fitness-workout/backend/public/verify.php?token=$token";
            $this->sendEmail($data['email'], "Verify Your FitSync Account", "
                <p>Welcome! Please click the link below to verify your account:</p>
                <a href='$verifyLink'>$verifyLink</a>
            ");
            return ["success" => true, "message" => "Registration successful! Please verify your email."];
        }

        return ["success" => false, "message" => "Registration failed."];
    }

    private function sendEmail($to, $subject, $body) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'your_email@gmail.com'; // Replace with your email
            $mail->Password   = 'your_app_password';     // Replace with app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('your_email@gmail.com', 'FitSync');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mail Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    public function verify($token) {
        if ($this->user->verifyUser($token)) {
            return ["success" => true, "message" => "Account verified successfully!"];
        }
        return ["success" => false, "message" => "Invalid or expired token."];
    }

    public function forgot($email) {
        $user = $this->user->findByEmail($email);
        if (!$user) return ["success" => false, "message" => "Email not found."];

        $token = bin2hex(random_bytes(16));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        if ($this->user->setResetToken($email, $token, $expires)) {
            $resetLink = "http://localhost:5173/reset-password?token=$token";
            $this->sendEmail($email, "Password Reset Request", "
                <p>Click the link below to reset your password:</p>
                <a href='$resetLink'>$resetLink</a>
            ");
            return ["success" => true, "message" => "Reset link sent to your email."];
        }

        return ["success" => false, "message" => "Failed to create reset token."];
    }

    public function reset($token, $newPassword) {
        $user = $this->user->findByResetToken($token);
        if (!$user) return ["success" => false, "message" => "Invalid or expired reset token."];

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        if ($this->user->updatePassword($token, $hashedPassword)) {
            return ["success" => true, "message" => "Password reset successful."];
        }
        return ["success" => false, "message" => "Failed to reset password."];
    }

    // New: Get user profile
    public function getUserProfile($userId) {
        $user = $this->user->findById($userId);
        if ($user) {
            unset($user['Password'], $user['Verification_Token'], $user['Reset_Token'], $user['Reset_Expires']);
            return ["success" => true, "profile" => $user];
        }
        return ["success" => false, "message" => "User not found."];
    }

    // New: Update profile
    public function updateProfile($userId, $data) {
        if ($this->user->updateProfile($userId, $data)) {
            return ["success" => true, "message" => "Profile updated successfully."];
        }
        return ["success" => false, "message" => "Failed to update profile."];
    }

    // New: Change password
    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->user->findById($userId);
        if (!$user || !password_verify($oldPassword, $user['Password'])) {
            return ["success" => false, "message" => "Incorrect old password."];
        }

        $hashedNewPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        if ($this->user->updatePasswordById($userId, $hashedNewPassword)) {
            return ["success" => true, "message" => "Password changed successfully."];
        }
        return ["success" => false, "message" => "Failed to change password."];
    }

    // New: Resend verification
    public function resendVerification($userId) {
        $user = $this->user->findById($userId);
        if (!$user) return ["success" => false, "message" => "User not found."];

        $token = bin2hex(random_bytes(16));
        if ($this->user->updateVerificationToken($userId, $token)) {
            $verifyLink = "http://localhost/personalized-fitness-workout/backend/public/verify.php?token=$token";
            $this->sendEmail($user['Email'], "Verify Your FitSync Account", "
                <p>Please click the link below to verify your account:</p>
                <a href='$verifyLink'>$verifyLink</a>
            ");
            return ["success" => true, "message" => "Verification email resent."];
        }
        return ["success" => false, "message" => "Failed to resend verification."];
    }

    
    private function callAPI($type, $data) {
        // Placeholder: Return mock data
        return ['description' => 'Sample workout', 'exercises' => [['name' => 'Push-up', 'muscle' => 'Chest', 'equipment' => 'None', 'reps' => 10, 'sets' => 3]]];
    }

    private function logAPI($apiName, $userId) {
        $query = "INSERT INTO api_logs (API_Name, User_ID) VALUES (?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$apiName, $userId]);
    }
}
?>