<?php
// Simple configuration file for Hostinger deployment.
// Copy this file to public_html/api/config/config.php on Hostinger and fill values.

return [
    // Database
    'db_host' => 'mysql_host_here', // e.g. mysql123.hostinger.com
    'db_name' => 'fitness_db',
    'db_user' => 'fitness_user',
    'db_pass' => 'your_db_password',

    // App URLs
    'frontend_url' => 'https://yourdomain.com',
    'app_url' => 'https://yourdomain.com',

    // SMTP (Hostinger)
    'smtp_host' => 'smtp.hostinger.com',
    'smtp_port' => 465, // or 587
    'smtp_user' => 'no-reply@yourdomain.com',
    'smtp_pass' => 'your_email_password',
    'smtp_secure' => 'ssl', // 'ssl' for 465, 'tls' for 587

    // From
    'mail_from' => 'no-reply@yourdomain.com',
    'mail_from_name' => 'FitSync',
];
