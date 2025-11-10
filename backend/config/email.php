<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

/**
 * Send emails for different purposes in the Fitness App.
 *
 * @param string $to Recipient email
 * @param string $type Type of email: 'verification', 'password-reset', 'notification'
 * @param array $data Additional data needed for the email (token, message, etc.)
 * @return bool True if email sent, false otherwise
 */

if (!file_exists(__DIR__ . '/.env')) {
    die("❌ ERROR: .env file is missing. Please create backend/config/.env using .env.example");
}

function sendAppEmail($to, $type, $data = []) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = getenv('EMAIL_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('EMAIL_USER');
        $mail->Password   = getenv('EMAIL_PASS');         // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = getenv('EMAIL_PORT');

        // Recipient
        $mail->setFrom('your-email@gmail.com', 'FitSync Support');
        $mail->addAddress($to);
        $mail->addReplyTo('your-email@gmail.com', 'FitSync Support');

        // Determine email content
        switch ($type) {
            case 'verification':
                $token = $data['token'] ?? '';
                $subject = '✅ Verify Your FitSync Account';
                $body = "
                <!DOCTYPE html>
                <html>
                <body style='font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;'>
                    <div style='max-width:600px;background:#fff;padding:20px;border-radius:10px;margin:auto;box-shadow:0px 0px 10px rgba(0,0,0,0.1);'>

                        <h2 style='color:#2C3E50;text-align:center;'>Welcome to <span style='color:#27AE60;'>FitSync</span> 💪</h2>
                        <p>Hello,</p>
                        <p>Thank you for joining FitSync! Please verify your email to activate your account.</p>

                        <div style='text-align:center;margin:30px 0;'>
                            <a href='http://localhost/PERSONALIZED-FITNESS-WORKOUT/backend/public/verify.php?token=$token'
                            style='background:#27AE60;color:white;padding:12px 25px;text-decoration:none;border-radius:5px;font-size:16px;font-weight:bold;'>
                            Verify My Account
                            </a>
                        </div>

                        <p style='font-size:14px;color:#555;'>If the button doesn’t work, copy and paste this link:</p>
                        <p style='word-break:break-all;font-size:13px;color:#2980B9;'>
                        http://localhost/PERSONALIZED-FITNESS-WORKOUT/backend/public/verify.php?token=$token
                        </p>

                        <hr style='border:none;height:1px;background:#ddd;margin-top:20px;'>
                        <p style='font-size:12px;color:#888;text-align:center;'>You received this email because you signed up for FitSync. If it wasn't you, you can ignore this.</p>

                        <p style='text-align:center;font-size:13px;font-weight:bold;color:#2C3E50;'>— FitSync Team</p>
                    </div>
                </body>
                </html>
                ";
                $altBody = "Verify your FitSync account: http://localhost/PERSONALIZED-FITNESS-WORKOUT/backend/public/verify.php?token=$token";
                break;

            case 'password-reset':
                $token = $data['token'] ?? '';
                $subject = 'Reset Your Password';
                $body = "
                    <h3>Password Reset Request</h3>
                    <p>Click the link below to reset your password:</p>
                    <a href='http://localhost/PERSONALIZED-FITNESS-WORKOUT/backend/public/reset_password.php?token=$token'>
                    Reset Password
                    </a>
                ";
                $altBody = "Reset your password by visiting: http://localhost/PERSONALIZED-FITNESS-WORKOUT/backend/public/reset_password.php?token=$token";
                break;

            case 'notification':
                $subject = $data['subject'] ?? 'Fitness App Notification';
                $body = $data['message'] ?? '';
                $altBody = strip_tags($body);
                break;

            default:
                throw new Exception("Invalid email type: $type");
        }

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody;

        // Send email
        $mail->send();
        $mail->addReplyTo('hassan.alshaik89@gmail.com', 'FitSync Support');
        $mail->addCustomHeader('X-Mailer', 'PHP/' . phpversion());
        $mail->addCustomHeader('Precedence', 'bulk');
        $mail->AddCustomHeader("List-Unsubscribe: <mailto:hassan.alshaik89@gmail.com>");
        return true;

    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
