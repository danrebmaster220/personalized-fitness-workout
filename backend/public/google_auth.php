<?php
// Start Google OAuth flow by redirecting to Google's OAuth 2.0 consent screen.
// This endpoint reads client ID / redirect URI from backend config env and constructs
// the consent URL so the frontend does not need to embed the client secret.
require_once __DIR__ . '/../vendor/autoload.php';

// Start session so we can persist and validate the OAuth `state` value
if (session_status() === PHP_SESSION_NONE) {
    // In dev we use non-secure cookie; in production set 'secure' => true and proper domain
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Load .env from backend/config - use manual parser directly
if (file_exists(__DIR__ . '/../config/.env')) {
    $envFile = __DIR__ . '/../config/.env';
    if (is_readable($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') === false) continue;
            list($k, $v) = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            // strip surrounding quotes
            if ((substr($v,0,1) === '"' && substr($v,-1) === '"') || (substr($v,0,1) === "'" && substr($v,-1) === "'")) {
                $v = substr($v, 1, -1);
            }
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }
    }
}

header('Content-Type: application/json');

$clientId = getenv('GOOGLE_CLIENT_ID');
$redirect = getenv('GOOGLE_REDIRECT_URI');

if (!$clientId || !$redirect) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'GOOGLE_CLIENT_ID and GOOGLE_REDIRECT_URI must be configured in backend/config/.env']);
    exit;
}

$scope = urlencode('openid email profile');
$state = bin2hex(random_bytes(16));
// persist state in session so callback can validate it (mitigates CSRF)
$_SESSION['oauth_state'] = $state;

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth' .
    '?client_id=' . urlencode($clientId) .
    '&redirect_uri=' . urlencode($redirect) .
    '&response_type=code' .
    '&scope=' . $scope .
    '&access_type=offline' .
    '&prompt=consent' .
    '&state=' . $state;

// For server-side redirect, send Location header.
header('Location: ' . $authUrl);
exit;
