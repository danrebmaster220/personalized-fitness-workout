<?php
require_once '../app/controllers/UserController.php';
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

// Check if email is provided
if (empty($data['email'])) {
    echo json_encode(["success" => false, "message" => "Email is required."]);
    exit();
}

$controller = new UserController();
echo json_encode($controller->forgot($data['email']));