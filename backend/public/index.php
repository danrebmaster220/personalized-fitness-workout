<?php
header("Content-Type: application/json");

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Handle preflight request (optional, but safe to keep)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$route = $_GET['route'] ?? '';
$action = $_GET['action'] ?? '';

if (empty($route)) {
    echo json_encode(["success" => false, "message" => "Route is required."]);
    exit();
}

if (empty($action)) {
    echo json_encode(["success" => false, "message" => "Action is required for route '$route'."]);
    exit();
}

switch ($route) {
    case 'user':
        require_once '../app/controllers/UserController.php';
        $controller = new UserController();

        switch ($action) {
            case 'register':
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data || !isset($data['email'], $data['password'])) {
                    echo json_encode(["success" => false, "message" => "Email and password are required."]);
                    exit();
                }
                echo json_encode($controller->register($data)); // ✅ Send full array instead
                break;

            case 'login':
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data || !isset($data['email'], $data['password'])) {
                    echo json_encode(["success" => false, "message" => "Email and password are required."]);
                    exit();
                }
                echo json_encode($controller->login($data['email'], $data['password']));
                break;

            case 'forgot':
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data || !isset($data['email'])) {
                    echo json_encode(["success" => false, "message" => "Email is required."]);
                    exit();
                }
                echo json_encode($controller->forgot($data['email']));
                break;

            case 'reset':
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data || !isset($data['token'], $data['password'])) {
                    echo json_encode(["success" => false, "message" => "Token and password are required."]);
                    exit();
                }
                echo json_encode($controller->reset($data['token'], $data['password']));
                break;

            case 'verify':
                $token = $_GET['token'] ?? '';
                if (empty($token)) {
                    echo json_encode(["success" => false, "message" => "Verification token missing."]);
                    exit();
                }
                echo json_encode($controller->verify($token));
                break;

            case 'getProfile':
                $userId = $_GET['userId'] ?? null;
                if (!$userId) {
                    echo json_encode(["success" => false, "message" => "User ID required."]);
                    exit();
                }
                echo json_encode($controller->getUserProfile($userId));
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
                $userId = $_GET['userId'] ?? null;
                echo json_encode($controller->resendVerification($userId));
                break;
            case 'generateWorkout':
                $data = json_decode(file_get_contents("php://input"), true);
                echo json_encode($controller->generateWorkout($data['userId'], $data));
                break;

            default:
                echo json_encode(["success" => false, "message" => "Invalid user action."]);
        }
        break;

    default:
        echo json_encode(["success" => false, "message" => "Invalid route."]);
}
