<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';

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

    private function sendEmail($to, $subject, $body) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'your_email@gmail.com';
            $mail->Password   = 'your_app_password';
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

    public function register($email, $password) {
        if ($this->user->findByEmail($email)) {
            return ["success" => false, "message" => "Email already registered."];
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $token = bin2hex(random_bytes(16));

        if ($this->user->create($email, $hashedPassword, $token)) {
            $verifyLink = "http://localhost/personalized-fitness-workout/verify?token=$token";
            $this->sendEmail($email, "Verify Your FitSync Account", "
                <p>Welcome! Please click the link below to verify your account:</p>
                <a href='$verifyLink'>$verifyLink</a>
            ");
            return ["success" => true, "message" => "Registration successful! Please verify your email."];
        }

        return ["success" => false, "message" => "Registration failed."];
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
            $resetLink = "http://localhost/personalized-fitness-workout/reset?token=$token";
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
}
?>
