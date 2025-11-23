<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/controllers/UserController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

if (file_exists(__DIR__ . '/../config/.env')) {
    // Use manual parser directly (Dotenv library has issues with some .env formats)
    $envFile = __DIR__ . '/../config/.env';
    if (file_exists($envFile) && is_readable($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
            list($k, $v) = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if ((substr($v,0,1) === '"' && substr($v,-1) === '"') || (substr($v,0,1) === "'" && substr($v,-1) === "'")) {
                $v = substr($v, 1, -1);
            }
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }
    }
}

$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

if (!$code) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Missing code parameter"]);
    exit;
}

if (!$state || !isset($_SESSION['oauth_state']) || $_SESSION['oauth_state'] !== $state) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Invalid OAuth state"]);
    exit;
}

$uc = new UserController();
$res = $uc->googleCallback($code);
$frontend = getenv('FRONTEND_URL') ?: 'http://localhost:5174';

if (isset($res['success']) && $res['success'] === true && isset($res['user'])) {
    $user = $res['user'];
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['User_ID'] ?? null;
    $_SESSION['user'] = [
        'User_ID' => $user['User_ID'] ?? null,
        'FirstName' => $user['FirstName'] ?? '',
        'LastName' => $user['LastName'] ?? '',
        'Email' => $user['Email'] ?? ''
    ];

    $needsProfile = empty($user['Age']) || empty($user['Height']) || empty($user['Weight']) || empty($user['Gender']) || empty($user['Fitness_Level']);
    $target = $needsProfile ? $frontend . '/register?google=1' : $frontend . '/dashboard';
    header('Location: ' . $target);
    exit;
}

header('Content-Type: application/json');
echo json_encode($res);