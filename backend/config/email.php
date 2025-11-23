<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (!file_exists(__DIR__ . '/.env')) {
    die("❌ ERROR: .env file is missing. Please create backend/config/.env using .env.example");
}

/**
 * Build email content based on type
 * Returns array: [subject, htmlBody, textBody]
 * 
 * Available email types:
 * - verification: Email verification for new accounts
 * - resend-verification: Resend verification link
 * - password-reset: Password reset request
 * - password-changed: Confirmation after password change
 * - email-changed: Confirmation after email change
 * - welcome: Welcome email after verification
 * - workout-generated: Notification when workout is generated
 * - notification: Generic notification
 */
function buildEmailContent($type, $data = []) {
    $frontendUrl = getenv('FRONTEND_URL') ?: getenv('APP_URL');
    $userName = $data['name'] ?? 'there';
    
    switch ($type) {
        case 'verification':
        case 'resend-verification':
            $token = $data['token'] ?? '';
            $isResend = ($type === 'resend-verification');
            $subject = $isResend ? '📧 Resend: Verify Your FitSync Account' : '✅ Verify Your FitSync Account';
            $verifyLink = $frontendUrl . '/verify-email?token=' . $token;
            
            $htmlBody = "<!DOCTYPE html>
<html>
<body style='font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;margin:0;'>
    <div style='max-width:600px;background:#fff;padding:30px;border-radius:10px;margin:auto;box-shadow:0px 0px 15px rgba(0,0,0,0.1);'>
        <div style='text-align:center;margin-bottom:20px;'>
            <h1 style='color:#27AE60;margin:0;font-size:32px;'>FitSync</h1>
            <p style='color:#7f8c8d;margin:5px 0;'>Your Personal Fitness Companion</p>
        </div>
        
        <h2 style='color:#2C3E50;text-align:center;margin:20px 0;'>
            " . ($isResend ? 'Verification Link Resent' : 'Welcome to <span style=\"color:#27AE60;\">FitSync</span>') . " 💪
        </h2>
        
        <p style='color:#34495e;line-height:1.6;'>Hello" . ($userName !== 'there' ? " $userName" : '') . ",</p>
        
        <p style='color:#34495e;line-height:1.6;'>
            " . ($isResend ? 
                "As requested, here's a fresh verification link for your FitSync account." : 
                "Thank you for joining FitSync! We're excited to have you on board. Please verify your email address to activate your account and start your fitness journey."
            ) . "
        </p>
        
        <div style='text-align:center;margin:35px 0;'>
            <a href='{$verifyLink}' 
               style='display:inline-block;background:#27AE60;color:white;padding:15px 35px;text-decoration:none;border-radius:5px;font-size:16px;font-weight:bold;box-shadow:0 2px 5px rgba(39,174,96,0.3);'>
                ✓ Verify My Account
            </a>
        </div>
        
        <p style='font-size:14px;color:#7f8c8d;line-height:1.6;'>
            If the button doesn't work, copy and paste this link into your browser:
        </p>
        <p style='word-break:break-all;font-size:13px;color:#2980B9;background:#ecf0f1;padding:10px;border-radius:5px;'>
            {$verifyLink}
        </p>
        
        <hr style='border:none;height:1px;background:#ddd;margin:30px 0;'>
        
        <p style='font-size:12px;color:#95a5a6;text-align:center;line-height:1.5;'>
            You received this email because you " . ($isResend ? 'requested a new verification link' : 'signed up') . " for FitSync.<br>
            If you didn't " . ($isResend ? 'request this' : 'create an account') . ", you can safely ignore this email.
        </p>
        
        <p style='text-align:center;font-size:13px;font-weight:bold;color:#2C3E50;margin-top:20px;'>
            — The FitSync Team
        </p>
    </div>
</body>
</html>";
            
            $textBody = ($isResend ? "Verification Link Resent\n\n" : "Welcome to FitSync!\n\n") . 
                        "Please verify your email by visiting: {$verifyLink}\n\n" .
                        "This link will verify your account and activate all features.\n\n" .
                        "— FitSync Team";
            break;

        case 'password-reset':
            $token = $data['token'] ?? '';
            $subject = '🔐 Reset Your FitSync Password';
            $resetLink = $frontendUrl . '/reset-password?token=' . $token;
            
            $htmlBody = "<!DOCTYPE html>
<html>
<body style='font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;margin:0;'>
    <div style='max-width:600px;background:#fff;padding:30px;border-radius:10px;margin:auto;box-shadow:0px 0px 15px rgba(0,0,0,0.1);'>
        <div style='text-align:center;margin-bottom:20px;'>
            <h1 style='color:#27AE60;margin:0;font-size:32px;'>FitSync</h1>
            <p style='color:#7f8c8d;margin:5px 0;'>Your Personal Fitness Companion</p>
        </div>
        
        <h2 style='color:#2C3E50;text-align:center;margin:20px 0;'>Password Reset Request 🔒</h2>
        
        <p style='color:#34495e;line-height:1.6;'>Hello" . ($userName !== 'there' ? " $userName" : '') . ",</p>
        
        <p style='color:#34495e;line-height:1.6;'>
            We received a request to reset your FitSync password. Click the button below to create a new password:
        </p>
        
        <div style='text-align:center;margin:35px 0;'>
            <a href='{$resetLink}' 
               style='display:inline-block;background:#3498DB;color:white;padding:15px 35px;text-decoration:none;border-radius:5px;font-size:16px;font-weight:bold;box-shadow:0 2px 5px rgba(52,152,219,0.3);'>
                🔑 Reset Password
            </a>
        </div>
        
        <p style='font-size:14px;color:#7f8c8d;line-height:1.6;'>
            If the button doesn't work, copy and paste this link:
        </p>
        <p style='word-break:break-all;font-size:13px;color:#2980B9;background:#ecf0f1;padding:10px;border-radius:5px;'>
            {$resetLink}
        </p>
        
        <div style='background:#fff3cd;border-left:4px solid:#ffc107;padding:15px;margin:20px 0;border-radius:3px;'>
            <p style='margin:0;color:#856404;font-size:14px;'>
                <strong>⏱️ Important:</strong> This link will expire in <strong>1 hour</strong> for security reasons.
            </p>
        </div>
        
        <p style='font-size:14px;color:#e74c3c;line-height:1.6;'>
            <strong>Didn't request this?</strong> If you didn't ask to reset your password, you can safely ignore this email. Your password will remain unchanged.
        </p>
        
        <hr style='border:none;height:1px;background:#ddd;margin:30px 0;'>
        
        <p style='font-size:12px;color:#95a5a6;text-align:center;line-height:1.5;'>
            This is an automated security email from FitSync.<br>
            For your protection, never share your password with anyone.
        </p>
        
        <p style='text-align:center;font-size:13px;font-weight:bold;color:#2C3E50;margin-top:20px;'>
            — The FitSync Team
        </p>
    </div>
</body>
</html>";
            
            $textBody = "Password Reset Request\n\n" .
                        "Reset your FitSync password: {$resetLink}\n\n" .
                        "This link expires in 1 hour.\n\n" .
                        "If you didn't request this, ignore this email.\n\n" .
                        "— FitSync Team";
            break;

        case 'password-changed':
            $subject = '✅ Your FitSync Password Was Changed';
            $changeTime = $data['time'] ?? date('F j, Y g:i A');
            
            $htmlBody = "<!DOCTYPE html>
<html>
<body style='font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;margin:0;'>
    <div style='max-width:600px;background:#fff;padding:30px;border-radius:10px;margin:auto;box-shadow:0px 0px 15px rgba(0,0,0,0.1);'>
        <div style='text-align:center;margin-bottom:20px;'>
            <h1 style='color:#27AE60;margin:0;font-size:32px;'>FitSync</h1>
        </div>
        
        <h2 style='color:#27AE60;text-align:center;margin:20px 0;'>✅ Password Successfully Changed</h2>
        
        <p style='color:#34495e;line-height:1.6;'>Hello" . ($userName !== 'there' ? " $userName" : '') . ",</p>
        
        <p style='color:#34495e;line-height:1.6;'>
            This is a confirmation that your FitSync account password was successfully changed.
        </p>
        
        <div style='background:#d4edda;border-left:4px solid:#28a745;padding:15px;margin:20px 0;border-radius:3px;'>
            <p style='margin:0;color:#155724;font-size:14px;'>
                <strong>📅 Changed on:</strong> {$changeTime}
            </p>
        </div>
        
        <p style='font-size:14px;color:#34495e;line-height:1.6;'>
            You can now use your new password to log in to your FitSync account.
        </p>
        
        <div style='background:#f8d7da;border-left:4px solid:#dc3545;padding:15px;margin:20px 0;border-radius:3px;'>
            <p style='margin:0;color:#721c24;font-size:14px;'>
                <strong>⚠️ Didn't make this change?</strong><br>
                If you didn't change your password, your account may be compromised. Please contact support immediately and secure your account.
            </p>
        </div>
        
        <div style='text-align:center;margin:30px 0;'>
            <a href='{$frontendUrl}/login' 
               style='display:inline-block;background:#27AE60;color:white;padding:12px 30px;text-decoration:none;border-radius:5px;font-size:14px;font-weight:bold;'>
                Login to FitSync
            </a>
        </div>
        
        <hr style='border:none;height:1px;background:#ddd;margin:30px 0;'>
        
        <p style='font-size:12px;color:#95a5a6;text-align:center;'>
            This is an automated security notification from FitSync.
        </p>
        
        <p style='text-align:center;font-size:13px;font-weight:bold;color:#2C3E50;margin-top:20px;'>
            — The FitSync Team
        </p>
    </div>
</body>
</html>";
            
            $textBody = "Password Successfully Changed\n\n" .
                        "Your FitSync password was changed on: {$changeTime}\n\n" .
                        "If you didn't make this change, please contact support immediately.\n\n" .
                        "— FitSync Team";
            break;

        case 'email-changed':
            $newEmail = $data['newEmail'] ?? '';
            $subject = '📧 Your FitSync Email Was Updated';
            
            $htmlBody = "<!DOCTYPE html>
<html>
<body style='font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;margin:0;'>
    <div style='max-width:600px;background:#fff;padding:30px;border-radius:10px;margin:auto;box-shadow:0px 0px 15px rgba(0,0,0,0.1);'>
        <div style='text-align:center;margin-bottom:20px;'>
            <h1 style='color:#27AE60;margin:0;font-size:32px;'>FitSync</h1>
        </div>
        
        <h2 style='color:#3498DB;text-align:center;margin:20px 0;'>📧 Email Address Updated</h2>
        
        <p style='color:#34495e;line-height:1.6;'>Hello" . ($userName !== 'there' ? " $userName" : '') . ",</p>
        
        <p style='color:#34495e;line-height:1.6;'>
            This is a confirmation that your email address was successfully updated.
        </p>
        
        <div style='background:#d1ecf1;border-left:4px solid:#17a2b8;padding:15px;margin:20px 0;border-radius:3px;'>
            <p style='margin:0;color:#0c5460;font-size:14px;'>
                <strong>New Email:</strong> {$newEmail}
            </p>
        </div>
        
        <p style='font-size:14px;color:#34495e;line-height:1.6;'>
            A verification email has been sent to your new address. Please verify it to complete the change.
        </p>
        
        <p style='font-size:14px;color:#e74c3c;line-height:1.6;'>
            <strong>Didn't make this change?</strong> Please contact support immediately.
        </p>
        
        <hr style='border:none;height:1px;background:#ddd;margin:30px 0;'>
        
        <p style='text-align:center;font-size:13px;font-weight:bold;color:#2C3E50;margin-top:20px;'>
            — The FitSync Team
        </p>
    </div>
</body>
</html>";
            
            $textBody = "Email Address Updated\n\n" .
                        "Your email was changed to: {$newEmail}\n\n" .
                        "Please verify your new email address.\n\n" .
                        "— FitSync Team";
            break;

        case 'welcome':
            $subject = '🎉 Welcome to FitSync - Let\'s Get Started!';
            
            $htmlBody = "<!DOCTYPE html>
<html>
<body style='font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;margin:0;'>
    <div style='max-width:600px;background:#fff;padding:30px;border-radius:10px;margin:auto;box-shadow:0px 0px 15px rgba(0,0,0,0.1);'>
        <div style='text-align:center;margin-bottom:20px;'>
            <h1 style='color:#27AE60;margin:0;font-size:36px;'>FitSync</h1>
            <p style='color:#7f8c8d;margin:5px 0;font-size:16px;'>Your Personal Fitness Companion 💪</p>
        </div>
        
        <h2 style='color:#27AE60;text-align:center;margin:20px 0;'>🎉 Welcome Aboard!</h2>
        
        <p style='color:#34495e;line-height:1.6;font-size:16px;'>Hello" . ($userName !== 'there' ? " <strong>$userName</strong>" : '') . ",</p>
        
        <p style='color:#34495e;line-height:1.6;'>
            Congratulations! Your email is verified and your FitSync account is fully activated. You're all set to begin your personalized fitness journey!
        </p>
        
        <div style='background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);padding:20px;border-radius:8px;margin:25px 0;'>
            <h3 style='color:white;margin:0 0 15px 0;'>🚀 Get Started in 3 Easy Steps:</h3>
            <ol style='color:white;line-height:2;margin:0;padding-left:20px;'>
                <li>Complete your profile with your fitness goals</li>
                <li>Generate your first AI-powered workout plan</li>
                <li>Start tracking your progress!</li>
            </ol>
        </div>
        
        <div style='text-align:center;margin:30px 0;'>
            <a href='{$frontendUrl}/user/dashboard' 
               style='display:inline-block;background:#27AE60;color:white;padding:15px 40px;text-decoration:none;border-radius:5px;font-size:16px;font-weight:bold;box-shadow:0 4px 6px rgba(39,174,96,0.3);'>
                Go to Dashboard →
            </a>
        </div>
        
        <div style='background:#f8f9fa;padding:20px;border-radius:8px;margin:20px 0;'>
            <h4 style='color:#2C3E50;margin:0 0 10px 0;'>✨ What You Can Do:</h4>
            <ul style='color:#34495e;line-height:1.8;margin:5px 0;padding-left:20px;'>
                <li>Get AI-generated workout plans tailored to your goals</li>
                <li>Track your fitness progress</li>
                <li>Access personalized nutrition recommendations</li>
                <li>View your workout history</li>
            </ul>
        </div>
        
        <p style='color:#34495e;line-height:1.6;'>
            Need help? We're here for you! Feel free to explore the app and reach out if you have any questions.
        </p>
        
        <hr style='border:none;height:1px;background:#ddd;margin:30px 0;'>
        
        <p style='font-size:12px;color:#95a5a6;text-align:center;'>
            Thank you for choosing FitSync for your fitness journey!
        </p>
        
        <p style='text-align:center;font-size:13px;font-weight:bold;color:#2C3E50;margin-top:20px;'>
            — The FitSync Team
        </p>
    </div>
</body>
</html>";
            
            $textBody = "🎉 Welcome to FitSync!\n\n" .
                        "Your account is verified and ready to go!\n\n" .
                        "Get Started:\n" .
                        "1. Complete your profile\n" .
                        "2. Generate your first workout\n" .
                        "3. Start tracking your progress\n\n" .
                        "Visit your dashboard: {$frontendUrl}/user/dashboard\n\n" .
                        "— FitSync Team";
            break;

        case 'workout-generated':
            $subject = '💪 Your FitSync Workout Plan is Ready!';
            $workoutType = $data['workoutType'] ?? 'workout plan';
            
            $htmlBody = "<!DOCTYPE html>
<html>
<body style='font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;margin:0;'>
    <div style='max-width:600px;background:#fff;padding:30px;border-radius:10px;margin:auto;box-shadow:0px 0px 15px rgba(0,0,0,0.1);'>
        <div style='text-align:center;margin-bottom:20px;'>
            <h1 style='color:#27AE60;margin:0;font-size:32px;'>FitSync</h1>
        </div>
        
        <h2 style='color:#27AE60;text-align:center;margin:20px 0;'>💪 Your Workout is Ready!</h2>
        
        <p style='color:#34495e;line-height:1.6;'>Hello" . ($userName !== 'there' ? " $userName" : '') . ",</p>
        
        <p style='color:#34495e;line-height:1.6;'>
            Great news! Your personalized <strong>{$workoutType}</strong> has been generated and is waiting for you.
        </p>
        
        <div style='background:#27AE60;padding:20px;border-radius:8px;margin:25px 0;text-align:center;'>
            <p style='color:white;font-size:18px;margin:0;font-weight:bold;'>
                🎯 Time to crush your fitness goals!
            </p>
        </div>
        
        <div style='text-align:center;margin:30px 0;'>
            <a href='{$frontendUrl}/user/workout-history' 
               style='display:inline-block;background:#27AE60;color:white;padding:15px 35px;text-decoration:none;border-radius:5px;font-size:16px;font-weight:bold;box-shadow:0 2px 5px rgba(39,174,96,0.3);'>
                View My Workout →
            </a>
        </div>
        
        <p style='color:#34495e;line-height:1.6;'>
            Your workout has been tailored specifically for your goals and fitness level. Remember to warm up before starting and cool down after!
        </p>
        
        <div style='background:#fff3cd;border-left:4px solid:#ffc107;padding:15px;margin:20px 0;border-radius:3px;'>
            <p style='margin:0;color:#856404;font-size:14px;'>
                <strong>💡 Tip:</strong> Stay consistent, track your progress, and adjust as needed!
            </p>
        </div>
        
        <hr style='border:none;height:1px;background:#ddd;margin:30px 0;'>
        
        <p style='text-align:center;font-size:13px;font-weight:bold;color:#2C3E50;margin-top:20px;'>
            — The FitSync Team
        </p>
    </div>
</body>
</html>";
            
            $textBody = "💪 Your Workout is Ready!\n\n" .
                        "Your personalized {$workoutType} has been generated.\n\n" .
                        "View it now: {$frontendUrl}/user/workout-history\n\n" .
                        "Stay consistent and crush your goals!\n\n" .
                        "— FitSync Team";
            break;

        case 'notification':
            $subject = $data['subject'] ?? 'FitSync Notification';
            $message = $data['message'] ?? '';
            $htmlBody = $data['html'] ?? "<div style='padding:20px;'>{$message}</div>";
            $textBody = strip_tags($htmlBody);
            break;

        default:
            error_log("Invalid email type: $type");
            return [null, null, null];
    }
    
    return [$subject, $htmlBody, $textBody];
}

/**
 * Send emails for different purposes in the Fitness App.
 * 
 * Automatically routes to the correct email service based on MAIL_DRIVER setting:
 * - 'smtp': Uses PHPMailer with SMTP (Gmail, etc.) - requires SMTP ports
 * - 'brevo': Uses Brevo API - works on InfinityFree, accepts Gmail, NO domain needed!
 * - 'sendgrid': Uses SendGrid API - requires business email for signup
 * - 'resend': Uses Resend API - requires domain verification for production
 *
 * @param string $to Recipient email
 * @param string $type Type of email: 'verification', 'password-reset', 'notification'
 * @param array $data Additional data needed for the email (token, message, etc.)
 * @return bool True if email sent, false otherwise
 */
function sendAppEmail($to, $type, $data = []) {
    $driver = getenv('MAIL_DRIVER') ?: 'smtp';
    
    // Route to Brevo API if configured (RECOMMENDED for InfinityFree!)
    if ($driver === 'brevo') {
        require_once __DIR__ . '/email_brevo.php';
        return sendAppEmailBrevo($to, $type, $data);
    }
    
    // Route to SendGrid API if configured
    if ($driver === 'sendgrid') {
        require_once __DIR__ . '/email_sendgrid.php';
        return sendAppEmailSendGrid($to, $type, $data);
    }
    
    // Route to Resend API if configured
    if ($driver === 'resend') {
        return sendAppEmailResend($to, $type, $data);
    }
    
    // Otherwise use SMTP (default)
    return sendAppEmailSMTP($to, $type, $data);
}

/**
 * Send email using Resend API
 * Perfect for free hosting (InfinityFree) where SMTP ports are blocked
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

    $fromEmail = getenv('EMAIL_FROM') ?: 'onboarding@resend.dev';
    $fromName = getenv('EMAIL_FROM_NAME') ?: 'FitSync Support';

    // Build email content
    list($subject, $htmlBody, $textBody) = buildEmailContent($type, $data);
    
    if (!$subject) {
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
            error_log("✅ Resend: Email sent to $to - ID: " . ($responseData['id'] ?? 'unknown'));
            return [
                'success' => true, 
                'info' => 'Email sent via Resend API',
                'email_id' => $responseData['id'] ?? null
            ];
        }
        return true;
    } else {
        $errorMsg = $responseData['message'] ?? 'Unknown error';
        error_log("❌ Resend API Error ($httpCode): $errorMsg");
        if (getenv('DEBUG_MAIL') === '1') {
            return ['success' => false, 'error' => $errorMsg, 'http_code' => $httpCode];
        }
        return false;
    }
}

/**
 * Send email using SMTP (original method)
 */
function sendAppEmailSMTP($to, $type, $data = []) {
    // Default to SMTP-only flow (Gmail or other SMTP provider)

    // Read and validate SMTP env first so we provide a helpful error if missing
    $host = getenv('EMAIL_HOST');
    $user = getenv('EMAIL_USER');
    $pass = getenv('EMAIL_PASS');
    $port = (int) (getenv('EMAIL_PORT') ?: 587);

    if (!$host || !$user || !$pass) {
        $err = "Missing SMTP configuration. Please set EMAIL_HOST, EMAIL_USER, and EMAIL_PASS in backend/config/.env";
        error_log($err);
        if (getenv('DEBUG_MAIL') === '1') {
            return ['success' => false, 'error' => $err];
        }
        return false;
    }

    // Otherwise use SMTP via PHPMailer
    $mail = new PHPMailer(true);

    try {
        $forceIpv4 = getenv('FORCE_IPV4') === '1';

        // Resolve IPv4 when requested
        if ($forceIpv4) {
            $resolved = gethostbyname($host);
            if ($resolved && $resolved !== $host) {
                error_log("SMTP host resolved: {$host} -> {$resolved}");
                $host = $resolved;
            }
        }

        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        // Use STARTTLS and ensure port is an integer (default to 587)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $port;

        if (getenv('DEBUG_MAIL') === '1') {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer debug [level $level]: $str");
            };
        }

        $fromEmail = getenv('EMAIL_USER') ?: 'no-reply@example.com';
        $mail->setFrom($fromEmail, 'FitSync Support');
        $mail->addAddress($to);
        $mail->addReplyTo($fromEmail, 'FitSync Support');

        // Determine email content
        switch ($type) {
            case 'verification':
                $token = $data['token'] ?? '';
                $subject = '✅ Verify Your FitSync Account';
                $frontendUrl = getenv('FRONTEND_URL') ?: getenv('APP_URL');
                $verifyLink = $frontendUrl . '/verify-email?token=' . $token;
                $body = "<!DOCTYPE html><html><body style='font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;'><div style='max-width:600px;background:#fff;padding:20px;border-radius:10px;margin:auto;box-shadow:0px 0px 10px rgba(0,0,0,0.1);'><h2 style='color:#2C3E50;text-align:center;'>Welcome to <span style='color:#27AE60;'>FitSync</span> 💪</h2><p>Hello,</p><p>Thank you for joining FitSync! Please verify your email to activate your account.</p><div style='text-align:center;margin:30px 0;'><a href='{$verifyLink}' style='background:#27AE60;color:white;padding:12px 25px;text-decoration:none;border-radius:5px;font-size:16px;font-weight:bold;'>Verify My Account</a></div><p style='font-size:14px;color:#555;'>If the button doesn’t work, copy and paste this link:</p><p style='word-break:break-all;font-size:13px;color:#2980B9;'>{$verifyLink}</p><hr style='border:none;height:1px;background:#ddd;margin-top:20px;'><p style='font-size:12px;color:#888;text-align:center;'>You received this email because you signed up for FitSync. If it wasn't you, you can ignore this.</p><p style='text-align:center;font-size:13px;font-weight:bold;color:#2C3E50;'>— FitSync Team</p></div></body></html>";
                $altBody = "Verify your FitSync account: " . $verifyLink;
                break;
            case 'password-reset':
                $token = $data['token'] ?? '';
                $subject = 'Reset Your Password';
                $body = "<h3>Password Reset Request</h3><p>Click the link below to reset your password:</p><a href='http://localhost/PERSONALIZED-FITNESS-WORKOUT/backend/public/reset_password.php?token=$token'>Reset Password</a>";
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

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $altBody;

        $mail->send();

        if (getenv('DEBUG_MAIL') === '1') {
            return ['success' => true, 'info' => 'Message sent via SMTP'];
        }

        return true;

    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");

        if (getenv('DEBUG_MAIL') === '1') {
            return ['success' => false, 'error' => $mail->ErrorInfo];
        }

        return false;
    }
}
