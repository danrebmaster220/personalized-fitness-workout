<?php
require_once __DIR__ . '/../config/env.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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
    // USER ROUTES
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
                echo json_encode($controller->login($data['email'], $data['password']));
                break;
            case 'forgot':
                $data = json_decode(file_get_contents("php://input"), true);
                echo json_encode($controller->forgot($data['email']));
                break;
            case 'reset':
                $data = json_decode(file_get_contents("php://input"), true);
                echo json_encode($controller->reset($data['token'], $data['password']));
                break;
            case 'verify':
                echo json_encode($controller->verify($_GET['token'] ?? ''));
                break;
            case 'getProfile':
                echo json_encode($controller->getUserProfile($_GET['userId']));
                break;
            case 'updateProfile':
                $data = json_decode(file_get_contents("php://input"), true);
                echo json_encode($controller->updateProfile($data['userId'], $data));
                break;
            case 'changePassword':
                $data = json_decode(file_get_contents("php://input"), true);
                echo json_encode($controller->changePassword($data['userId'], $data['oldPassword'], $data['newPassword']));
                break;
            case 'resendVerification':
                echo json_encode($controller->resendVerification($_GET['userId']));
                break;
            default:
                echo json_encode(["success"=>false,"message"=>"Invalid user action"]);
        }
        break;


    // WORKOUT + AI ROUTES
    case 'workout':
        require_once '../app/controllers/WorkoutController.php';
        $c = new WorkoutController();

        switch ($action) {
            case 'generate':
                $data = json_decode(file_get_contents("php://input"), true);
                echo json_encode($c->generate($data));
                break;

            case 'history':
                $uid = $_GET['userId'] ?? null;
                echo json_encode($c->history($uid));
                break;

            case 'getOne':
                $id = $_GET['id'] ?? null;
                echo json_encode($c->getOne($id));
                break;

            default:
                echo json_encode(["success"=>false,"message"=>"Invalid workout action"]);
        }
        break;


    default:
        echo json_encode(["success"=>false,"message"=>"Invalid route"]);
}
