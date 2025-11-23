<?php
/**
 * SendGrid Email Service for FitSync
 * 
 * SendGrid allows sending to ANY email without domain verification!
 * Perfect for InfinityFree free tier hosting
 * 
 * Free tier: 100 emails/day (forever free)
 * 
 * Setup:
 * 1. Sign up at https://sendgrid.com/
 * 2. Verify your email
 * 3. Create API key at https://app.sendgrid.com/settings/api_keys
 * 4. Add SENDGRID_API_KEY to your .env file
 * 5. Set MAIL_DRIVER=sendgrid in .env
 */

use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

/**
 * Send emails using SendGrid API
 * No domain verification required - works immediately!
 *
 * @param string $to Recipient email
 * @param string $type Type of email: 'verification', 'password-reset', 'notification'
 * @param array $data Additional data needed for the email (token, message, etc.)
 * @return bool|array True if email sent, false otherwise (array if DEBUG_MAIL=1)
 */
function sendAppEmailSendGrid($to, $type, $data = []) {
    $apiKey = getenv('SENDGRID_API_KEY');
    
    if (!$apiKey) {
        $err = "Missing SendGrid API key. Please set SENDGRID_API_KEY in backend/config/.env";
        error_log($err);
        if (getenv('DEBUG_MAIL') === '1') {
            return ['success' => false, 'error' => $err];
        }
        return false;
    }

    // Get sender email
    $fromEmail = getenv('EMAIL_FROM') ?: 'noreply@yourdomain.com';
    $fromName = getenv('EMAIL_FROM_NAME') ?: 'FitSync Support';

    // Build email content
    $frontendUrl = getenv('FRONTEND_URL') ?: getenv('APP_URL');
    
    switch ($type) {
        case 'verification':
            $token = $data['token'] ?? '';
            $subject = '✅ Verify Your FitSync Account';
            $verifyLink = $frontendUrl . '/verify-email?token=' . $token;
            
            $htmlBody = "<!DOCTYPE html>
<html>
<body style='font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;'>
    <div style='max-width:600px;background:#fff;padding:20px;border-radius:10px;margin:auto;box-shadow:0px 0px 10px rgba(0,0,0,0.1);'>
        <h2 style='color:#2C3E50;text-align:center;'>
            Welcome to <span style='color:#27AE60;'>FitSync</span> 💪
        </h2>
        <p>Hello,</p>
        <p>Thank you for joining FitSync! Please verify your email to activate your account.</p>
        <div style='text-align:center;margin:30px 0;'>
            <a href='{$verifyLink}' 
               style='background:#27AE60;color:white;padding:12px 25px;text-decoration:none;border-radius:5px;font-size:16px;font-weight:bold;'>
                Verify My Account
            </a>
        </div>
        <p style='font-size:14px;color:#555;'>If the button doesn't work, copy and paste this link:</p>
        <p style='word-break:break-all;font-size:13px;color:#2980B9;'>{$verifyLink}</p>
        <hr style='border:none;height:1px;background:#ddd;margin-top:20px;'>
        <p style='font-size:12px;color:#888;text-align:center;'>
            You received this email because you signed up for FitSync.
        </p>
        <p style='text-align:center;font-size:13px;font-weight:bold;color:#2C3E50;'>
            — FitSync Team
        </p>
    </div>
</body>
</html>";
            
            $textBody = "Welcome to FitSync!\n\nPlease verify your email by visiting: {$verifyLink}\n\n— FitSync Team";
            break;

        case 'password-reset':
            $token = $data['token'] ?? '';
            $subject = '🔐 Reset Your FitSync Password';
            $resetLink = $frontendUrl . '/reset-password?token=' . $token;
            
            $htmlBody = "<!DOCTYPE html>
<html>
<body style='font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;'>
    <div style='max-width:600px;background:#fff;padding:20px;border-radius:10px;margin:auto;box-shadow:0px 0px 10px rgba(0,0,0,0.1);'>
        <h2 style='color:#2C3E50;text-align:center;'>Password Reset Request</h2>
        <p>Hello,</p>
        <p>We received a request to reset your FitSync password.</p>
        <div style='text-align:center;margin:30px 0;'>
            <a href='{$resetLink}' 
               style='background:#3498DB;color:white;padding:12px 25px;text-decoration:none;border-radius:5px;font-size:16px;font-weight:bold;'>
                Reset Password
            </a>
        </div>
        <p style='font-size:14px;color:#555;'>If you didn't request this, ignore this email.</p>
        <p style='font-size:12px;color:#888;'>This link expires in 1 hour.</p>
        <hr style='border:none;height:1px;background:#ddd;margin-top:20px;'>
        <p style='text-align:center;font-size:13px;font-weight:bold;color:#2C3E50;'>— FitSync Team</p>
    </div>
</body>
</html>";
            
            $textBody = "Password Reset\n\nReset your password: {$resetLink}\n\nExpires in 1 hour.\n\n— FitSync Team";
            break;

        case 'notification':
            $subject = $data['subject'] ?? 'FitSync Notification';
            $htmlBody = $data['message'] ?? '';
            $textBody = strip_tags($htmlBody);
            break;

        default:
            error_log("Invalid email type: $type");
            return false;
    }

    // Prepare SendGrid API request
    $payload = [
        'personalizations' => [
            [
                'to' => [['email' => $to]]
            ]
        ],
        'from' => [
            'email' => $fromEmail,
            'name' => $fromName
        ],
        'subject' => $subject,
        'content' => [
            [
                'type' => 'text/plain',
                'value' => $textBody
            ],
            [
                'type' => 'text/html',
                'value' => $htmlBody
            ]
        ]
    ];

    // Send request to SendGrid API
    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("SendGrid cURL Error: $curlError");
        if (getenv('DEBUG_MAIL') === '1') {
            return ['success' => false, 'error' => $curlError];
        }
        return false;
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        if (getenv('DEBUG_MAIL') === '1') {
            error_log("✅ SendGrid: Email sent to $to");
            return [
                'success' => true, 
                'info' => 'Email sent via SendGrid API',
                'http_code' => $httpCode
            ];
        }
        return true;
    } else {
        $errorMsg = $response ?: 'Unknown error';
        error_log("❌ SendGrid API Error ($httpCode): $errorMsg");
        if (getenv('DEBUG_MAIL') === '1') {
            return ['success' => false, 'error' => $errorMsg, 'http_code' => $httpCode];
        }
        return false;
    }
}
