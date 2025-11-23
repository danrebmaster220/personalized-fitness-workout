<?php
/**
 * Resend Email Service for FitSync
 * 
 * This uses Resend API instead of SMTP - perfect for InfinityFree hosting
 * Free tier: 3,000 emails/month, 100 emails/day
 * 
 * Setup:
 * 1. Sign up at https://resend.com
 * 2. Get your API key from dashboard
 * 3. Add RESEND_API_KEY to your .env file
 * 4. Set MAIL_DRIVER=resend in .env
 */

use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

/**
 * Send emails using Resend API
 *
 * @param string $to Recipient email
 * @param string $type Type of email: 'verification', 'password-reset', 'notification'
 * @param array $data Additional data needed for the email (token, message, etc.)
 * @return bool|array True if email sent, false otherwise (array if DEBUG_MAIL=1)
 */
function sendAppEmailResend($to, $type, $data = []) {
    $apiKey = getenv('RESEND_API_KEY');
    
    if (!$apiKey) {
        $err = "Missing Resend API key. Please set RESEND_API_KEY in backend/config/.env";
        error_log($err);
        if (getenv('DEBUG_MAIL') === '1') {
            return ['success' => false, 'error' => $err];
        }
        return false;
    }

    // Get sender email (use onboarding@resend.dev for testing, or your verified domain)
    $fromEmail = getenv('EMAIL_FROM') ?: 'onboarding@resend.dev';
    $fromName = getenv('EMAIL_FROM_NAME') ?: 'FitSync Support';

    // Determine email content based on type
    switch ($type) {
        case 'verification':
            $token = $data['token'] ?? '';
            $subject = '✅ Verify Your FitSync Account';
            $frontendUrl = getenv('FRONTEND_URL') ?: getenv('APP_URL');
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
            You received this email because you signed up for FitSync. If it wasn't you, you can ignore this.
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
            $resetLink = (getenv('FRONTEND_URL') ?: getenv('APP_URL')) . '/reset-password?token=' . $token;
            
            $htmlBody = "<!DOCTYPE html>
<html>
<body style='font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;'>
    <div style='max-width:600px;background:#fff;padding:20px;border-radius:10px;margin:auto;box-shadow:0px 0px 10px rgba(0,0,0,0.1);'>
        <h2 style='color:#2C3E50;text-align:center;'>Password Reset Request</h2>
        <p>Hello,</p>
        <p>We received a request to reset your FitSync password. Click the button below to create a new password:</p>
        <div style='text-align:center;margin:30px 0;'>
            <a href='{$resetLink}' 
               style='background:#3498DB;color:white;padding:12px 25px;text-decoration:none;border-radius:5px;font-size:16px;font-weight:bold;'>
                Reset Password
            </a>
        </div>
        <p style='font-size:14px;color:#555;'>If you didn't request this, you can safely ignore this email.</p>
        <p style='font-size:12px;color:#888;'>This link will expire in 1 hour.</p>
        <hr style='border:none;height:1px;background:#ddd;margin-top:20px;'>
        <p style='text-align:center;font-size:13px;font-weight:bold;color:#2C3E50;'>— FitSync Team</p>
    </div>
</body>
</html>";
            
            $textBody = "Password Reset Request\n\nReset your FitSync password by visiting: {$resetLink}\n\nThis link expires in 1 hour.\n\n— FitSync Team";
            break;

        case 'notification':
            $subject = $data['subject'] ?? 'FitSync Notification';
            $htmlBody = $data['message'] ?? '';
            $textBody = strip_tags($htmlBody);
            break;

        default:
            error_log("Invalid email type: $type");
            if (getenv('DEBUG_MAIL') === '1') {
                return ['success' => false, 'error' => 'Invalid email type'];
            }
            return false;
    }

    // Prepare Resend API request
    $payload = [
        'from' => "$fromName <$fromEmail>",
        'to' => [$to],
        'subject' => $subject,
        'html' => $htmlBody,
        'text' => $textBody
    ];

    // Send request to Resend API
    $ch = curl_init('https://api.resend.com/emails');
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

    // Handle response
    if ($curlError) {
        error_log("Resend cURL Error: $curlError");
        if (getenv('DEBUG_MAIL') === '1') {
            return ['success' => false, 'error' => $curlError];
        }
        return false;
    }

    $responseData = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300) {
        if (getenv('DEBUG_MAIL') === '1') {
            error_log("Resend Success: Email sent to $to - ID: " . ($responseData['id'] ?? 'unknown'));
            return [
                'success' => true, 
                'info' => 'Email sent via Resend',
                'email_id' => $responseData['id'] ?? null
            ];
        }
        return true;
    } else {
        $errorMsg = $responseData['message'] ?? 'Unknown error';
        error_log("Resend API Error ($httpCode): $errorMsg");
        if (getenv('DEBUG_MAIL') === '1') {
            return ['success' => false, 'error' => $errorMsg, 'http_code' => $httpCode];
        }
        return false;
    }
}

/**
 * Universal email sender - automatically chooses between SMTP or Resend
 * based on MAIL_DRIVER setting in .env
 */
function sendAppEmail($to, $type, $data = []) {
    $driver = getenv('MAIL_DRIVER') ?: 'smtp';
    
    if ($driver === 'resend') {
        return sendAppEmailResend($to, $type, $data);
    } else {
        // Fall back to original SMTP function
        require_once __DIR__ . '/email.php';
        return sendAppEmail($to, $type, $data);
    }
}
