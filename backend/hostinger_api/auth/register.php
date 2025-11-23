<?php
header('Content-Type: application/json');

// Simple registration endpoint for Hostinger deployment
// Expects JSON body with: FirstName, LastName, Email, Password

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$firstName = $input['FirstName'] ?? null;
$lastName = $input['LastName'] ?? null;
$email = strtolower(trim($input['Email'] ?? ''));
$password = $input['Password'] ?? null;

if (!$firstName || !$lastName || !$email || !$password) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// DB
$pdo = require __DIR__ . '/../config/db.php';

// Check existing
$stmt = $pdo->prepare('SELECT * FROM `user` WHERE Email = :e LIMIT 1');
$stmt->execute([':e' => $email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Email already registered']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(16));

$insert = $pdo->prepare('INSERT INTO `user` (FirstName, LastName, Email, Password, Is_Verified, Verification_Token, Created_At) VALUES (:fn,:ln,:email,:p,0,:t,NOW())');
$ok = $insert->execute([
    ':fn' => $firstName,
    ':ln' => $lastName,
    ':email' => $email,
    ':p' => $hash,
    ':t' => $token
]);

if (!$ok) {
    echo json_encode(['success' => false, 'error' => 'DB insert failed']);
    exit;
}

// Send verification email
require_once __DIR__ . '/../config/mail.php';
$mail = makeMailer();
if ($mail === null) {
    echo json_encode(['success' => false, 'error' => 'Failed to initialize mailer']);
    exit;
}

$cfg = require __DIR__ . '/../config/config.php';
$verifyUrl = rtrim($cfg['frontend_url'], '/') . '/verify?token=' . urlencode($token);

try {
    $mail->addAddress($email);
    $mail->Subject = 'Verify your FitSync account';
    $mail->Body = "<p>Hi {$firstName},</p><p>Click to verify: <a href='{$verifyUrl}'>{$verifyUrl}</a></p>";
    $mail->AltBody = "Verify your FitSync account: {$verifyUrl}";
    $mail->send();
} catch (Exception $e) {
    error_log('Send mail error: ' . $e->getMessage());
    // Non-fatal: registration succeeded; tell user to contact support
}

echo json_encode(['success' => true, 'message' => 'Registered. Check your email for verification.']);
