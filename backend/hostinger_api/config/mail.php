<?php
// PHPMailer factory for Hostinger SMTP
// Place PHPMailer in api/vendor via Composer, or upload vendor/ alongside this folder.

$cfg = require __DIR__ . '/config.php';

require_once __DIR__ . '/../vendor/autoload.php'; // when deployed under public_html/api/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function makeMailer() {
    global $cfg;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $cfg['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $cfg['smtp_user'];
        $mail->Password = $cfg['smtp_pass'];
        if (!empty($cfg['smtp_secure']) && strtolower($cfg['smtp_secure']) === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Port = (int) $cfg['smtp_port'];
        $mail->setFrom($cfg['mail_from'], $cfg['mail_from_name'] ?? 'FitSync');
        $mail->isHTML(true);
        return $mail;
    } catch (Exception $e) {
        error_log('Mailer init error: ' . $e->getMessage());
        return null;
    }
}
