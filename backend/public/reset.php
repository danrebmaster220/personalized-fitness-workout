<?php
require_once '../app/controllers/UserController.php';
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

// Check if token and password are provided
if (!isset($data['token']) || !isset($data['password'])) {
    echo json_encode(["success" => false, "message" => "Token and password are required."]);
    exit();
}

$controller = new UserController();
echo json_encode($controller->reset($data['token'], $data['password']));
