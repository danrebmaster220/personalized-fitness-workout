<?php
require_once '../app/controllers/UserController.php';
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

// Validate email and password
if (empty($data['email']) || empty($data['password'])) {
    echo json_encode(["success" => false, "message" => "Email and password are required."]);
    exit();
}

$controller = new UserController();
echo json_encode($controller->register($data['email'], $data['password']));
