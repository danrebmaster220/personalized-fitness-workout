<?php
require_once __DIR__ . '/../config/env.php';

// Load DOTENV if present so FRONTEND_URL can be read for CORS
if (file_exists(__DIR__ . '/../config/.env')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../config');
        $dotenv->load();
    } catch (Throwable $e) {
        // ignore
    }
    // simple parser fallback
    if (getenv('FRONTEND_URL') === false) {
        $envFile = __DIR__ . '/../config/.env';
        if (file_exists($envFile) && is_readable($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                list($k, $v) = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v);
                if ((substr($v,0,1) === '"' && substr($v,-1) === '"') || (substr($v,0,1) === "'" && substr($v,-1) === "'")) {
                    $v = substr($v, 1, -1);
                }
                if (getenv($k) === false) {
                    putenv("$k=$v");
                    $_ENV[$k] = $v;
                }
            }
        }
    }

}

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

// Load Security Middleware
require_once __DIR__ . '/../app/core/SecurityMiddleware.php';

// Set security headers
SecurityMiddleware::setSecurityHeaders();

// Basic CORS headers so the React dev server or deployed frontend can call this API
$allowedOrigin = getenv('FRONTEND_URL') ?: '*';
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
header('Access-Control-Allow-Credentials: true');
header("Content-Type: application/json");

// Start session so endpoints can read session-based authentication (me/logout)
if (session_status() === PHP_SESSION_NONE) {
    // Match cookie params used elsewhere (dev: secure=false)
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

// Handle CORS Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$route  = $_GET['route']  ?? '';
$action = $_GET['action'] ?? '';

if (!$route)  { echo json_encode(["success"=>false,"message"=>"Route is required."]); exit(); }
if (!$action) { echo json_encode(["success"=>false,"message"=>"Action is required."]); exit(); }

switch ($route) {

    // User Routes
    case 'user':
        require_once '../app/controllers/UserController.php';
        $controller = new UserController();

        switch ($action) {

            case 'register':
                $data = json_decode(file_get_contents("php://input"), true);
                echo json_encode($controller->register($data));
                break;

            case 'login':
                // Rate limiting for login attempts
                if (!SecurityMiddleware::checkRateLimit('login', 5, 300)) {
                    http_response_code(429);
                    echo json_encode(["success" => false, "message" => "Too many login attempts. Please try again later."]);
                    break;
                }
                
                $data = json_decode(file_get_contents("php://input"), true);
                echo json_encode($controller->login(
                    $data['email'] ?? '',
                    $data['password'] ?? ''
                ));
                break;

            case 'forgot':
                $data = json_decode(file_get_contents("php://input"), true);
                echo json_encode($controller->forgot($data['email'] ?? ''));
                break;

            case 'reset':
                $data = json_decode(file_get_contents("php://input"), true);
                echo json_encode($controller->reset(
                    $data['token'] ?? '',
                    $data['password'] ?? ''
                ));
                break;

            case 'verify':
                echo json_encode($controller->verify($_GET['token'] ?? ''));
                break;
            
            // Profile Management
            case 'getProfile':
                echo json_encode($controller->getUserProfile($_GET['userId'] ?? ''));
                break;

            case 'me':
                // Return the currently authenticated user (from session)
                echo json_encode($controller->me());
                break;

            case 'logout':
                echo json_encode($controller->logout());
                break;

            case 'updateProfile':
                $data = json_decode(file_get_contents("php://input"), true);
                // prefer explicit userId in payload, otherwise use session user
                $userId = $data['userId'] ?? ($_SESSION['user_id'] ?? '');
                echo json_encode($controller->updateProfile(
                    $userId,
                    $data
                ));
                break;

            case 'changePassword':
                $data = json_decode(file_get_contents("php://input"), true);
                echo json_encode($controller->changePassword(
                    $data['userId'] ?? '',
                    $data['oldPassword'] ?? '',
                    $data['newPassword'] ?? ''
                ));
                break;

            case 'resendVerification':
                echo json_encode($controller->resendVerification($_GET['userId'] ?? ''));
                break;

            case 'downloadWorkout':
                // Stream the generated workout file (PDF/TXT) after auth/ownership check.
                $id = $_GET['id'] ?? '';
                // Controller will handle headers and exit on success/failure
                $controller->downloadWorkoutStream($id);
                // downloadWorkoutStream will exit after sending file or JSON error
                break;

            case 'changeEmail':
                $data = json_decode(file_get_contents("php://input"), true);
                echo json_encode($controller->changeEmail($data['userId'] ?? '', $data['email'] ?? ''));
                break;

            case "uploadImage":
                // controller already required/instantiated above for user routes
                echo json_encode($controller->uploadImage($_POST, $_FILES));
                break;

            default:
                echo json_encode(["success" => false, "message" => "Invalid user action"]);
        }
        break;
        
        // Admin Actions
        case 'admin':
            require_once '../app/controllers/AdminController.php';
            $admin = new AdminController();

            switch ($action) {
                // Dashboard
                case 'getDashboardStats':
                    echo json_encode($admin->getDashboardStats());
                    break;

                case 'getMonthlyUserGrowth':
                    $months = $_GET['months'] ?? 12;
                    echo json_encode($admin->getMonthlyUserGrowth($months));
                    break;

                case 'getVerificationBreakdown':
                    echo json_encode($admin->getVerificationBreakdown());
                    break;

                case 'getMonthlyWorkouts':
                    $months = $_GET['months'] ?? 12;
                    echo json_encode($admin->getMonthlyWorkouts($months));
                    break;

                case 'getRecentUsers':
                    echo json_encode($admin->getRecentUsers());
                    break;
                
                case 'getUsers':
                    echo json_encode($admin->getRecentUsers());
                    break;
                
                // User Management
                case 'getAllUsers':
                    echo json_encode($admin->getAllUsers());
                    break;
                    
                case 'deleteUser':
                    $id = $_GET['id'] ?? null;
                    echo json_encode($admin->deleteUser($id));
                    break;
                
                // Generated Workouts
                case 'getGeneratedWorkouts':
                    echo json_encode($admin->getGeneratedWorkouts());
                    break;

                case 'getGeneratedWorkoutById':
                    $id = $_GET['id'] ?? null;
                    echo json_encode($admin->getGeneratedWorkoutById($id));
                    break;

                case 'exportGeneratedWorkout':
                    $id = $_GET['id'] ?? null;
                    echo json_encode($admin->exportWorkoutJson($id));
                    break;

                case 'deleteGeneratedWorkout':
                    $id = $_GET['id'] ?? null;
                    echo json_encode($admin->deleteGeneratedWorkout($id));
                    break;

                // System Reports
                case "getSystemStats":
                    $ctrl = new ReportsController();
                    echo json_encode($ctrl->systemStats());
                    break;

                case "getUserStats":
                    $ctrl = new ReportsController();
                    echo json_encode($ctrl->userStats());
                    break;

                case "getWorkoutStats":
                    $ctrl = new ReportsController();
                    echo json_encode($ctrl->workoutStats());
                    break;

                case "getApiStats":
                    $ctrl = new ReportsController();
                    echo json_encode($ctrl->apiStats());
                    break;
                
                // API Logs
                case 'getApiLogs':
                    echo json_encode($admin->getApiLogs());
                    break;

                case 'getApiLogById':
                    $id = $_GET['id'] ?? null;
                    echo json_encode($admin->getApiLogById($id));
                    break;

                case 'deleteApiLog':
                    $id = $_GET['id'] ?? null;
                    echo json_encode($admin->deleteApiLog($id));
                    break;
                
                // Admin Settings (protected)
                case 'getSettings':
                    require_once __DIR__ . '/../app/core/Database.php';
                    require_once __DIR__ . '/../app/models/Users.php';
                    require_once __DIR__ . '/../app/models/Settings.php';
                    $database = new Database();
                    $db = $database->connect();
                    $userModel = new User($db);
                    $uid = $_SESSION['user_id'] ?? null;
                    if (!$uid) { echo json_encode(["success"=>false,"message"=>"Not authenticated (admin)"]); exit(); }
                    $u = $userModel->findById($uid);
                    if (!$u || (($u['Role'] ?? '') !== 'admin')) { echo json_encode(["success"=>false,"message"=>"Admin access required"]); exit(); }
                    $settingsModel = new Settings($db);
                    echo json_encode(["success"=>true, "settings" => $settingsModel->getAll(true)]);
                    break;

                case 'saveSettings':
                    require_once __DIR__ . '/../app/core/Database.php';
                    require_once __DIR__ . '/../app/models/Users.php';
                    require_once __DIR__ . '/../app/models/Settings.php';
                    $database = new Database();
                    $db = $database->connect();
                    $userModel = new User($db);
                    $uid = $_SESSION['user_id'] ?? null;
                    if (!$uid) { echo json_encode(["success"=>false,"message"=>"Not authenticated (admin)"]); exit(); }
                    $u = $userModel->findById($uid);
                    if (!$u || (($u['Role'] ?? '') !== 'admin')) { echo json_encode(["success"=>false,"message"=>"Admin access required"]); exit(); }
                    $payload = json_decode(file_get_contents("php://input"), true);
                    $updates = $payload['updates'] ?? [];
                    $reason = $payload['reason'] ?? null;
                    $settingsModel = new Settings($db);
                    $results = [];
                    foreach ($updates as $urow) {
                        $k = $urow['k'] ?? null;
                        $v = $urow['v'] ?? null;
                        $type = $urow['type'] ?? 'string';
                        if (!$k) continue;
                        $ok = $settingsModel->set($k, $v, $type, $uid, $reason);
                        $results[$k] = $ok;
                    }
                    echo json_encode(["success"=>true, "results" => $results]);
                    break;

                case 'getSettingsHistory':
                    require_once __DIR__ . '/../app/core/Database.php';
                    require_once __DIR__ . '/../app/models/Users.php';
                    require_once __DIR__ . '/../app/models/Settings.php';
                    $database = new Database();
                    $db = $database->connect();
                    $userModel = new User($db);
                    $uid = $_SESSION['user_id'] ?? null;
                    if (!$uid) { echo json_encode(["success"=>false,"message"=>"Not authenticated (admin)"]); exit(); }
                    $u = $userModel->findById($uid);
                    if (!$u || (($u['Role'] ?? '') !== 'admin')) { echo json_encode(["success"=>false,"message"=>"Admin access required"]); exit(); }
                    $settingsModel = new Settings($db);
                    $key = $_GET['key'] ?? null;
                    $limit = intval($_GET['limit'] ?? 100);
                    $hist = $settingsModel->getHistory($key, $limit);
                    echo json_encode(["success"=>true, "history" => $hist]);
                    break;

                default:
                    echo json_encode(["success" => false, "message" => "Invalid admin action"]);
            }
            break;

    // Workout Routes
    case 'app':
        // Public app endpoints (non-sensitive settings for frontend)
        switch ($action) {
            case 'getPublicSettings':
                require_once __DIR__ . '/../app/core/Database.php';
                require_once __DIR__ . '/../app/models/Settings.php';
                $database = new Database();
                $db = $database->connect();
                $settings = new Settings($db);
                $out = $settings->getAutoloadPublic();
                echo json_encode(["success"=>true, "settings" => $out]);
                break;
            default:
                echo json_encode(["success"=>false, "message"=>"Invalid app action"]);
        }
        break;

    case 'workout':
        require_once '../app/controllers/WorkoutController.php';
        $c = new WorkoutController();

        switch ($action) {
            case 'generate':
                $data = json_decode(file_get_contents("php://input"), true);
                echo json_encode($c->generate($data));
                break;

            case 'history':
                echo json_encode($c->history($_GET['userId'] ?? null));
                break;

            case 'getOne':
                echo json_encode($c->getOne($_GET['id'] ?? null));
                break;

            default:
                echo json_encode(["success" => false, "message" => "Invalid workout action"]);
        }
        break;

    case 'ai':
        // Simple AI proxy endpoint - POST JSON { provider: 'openai'|'gemini', prompt: '...', options: {...} }
        require_once __DIR__ . '/../app/controllers/AIController.php';
        $ctrl = new AIController();

        switch ($action) {
            case 'chat':
                $payload = json_decode(file_get_contents("php://input"), true) ?? [];
                echo json_encode($ctrl->chat($payload));
                break;

            default:
                echo json_encode(["success" => false, "message" => "Invalid ai action"]);
        }
        break;

    default:
        echo json_encode(["success" => false, "message" => "Invalid route"]);
}
