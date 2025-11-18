<?php
require_once __DIR__ . '/../config/env.php';

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

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

            case 'getProfile':
                echo json_encode($controller->getUserProfile($_GET['userId'] ?? ''));
                break;

            case 'updateProfile':
                $data = json_decode(file_get_contents("php://input"), true);
                echo json_encode($controller->updateProfile(
                    $data['userId'] ?? '',
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

            case "uploadImage":
                require_once "../controllers/UserController.php";
                $controller = new UserController();
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

                default:
                    echo json_encode(["success" => false, "message" => "Invalid admin action"]);
            }
            break;

    // Workout Routes
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

    default:
        echo json_encode(["success" => false, "message" => "Invalid route"]);
}
