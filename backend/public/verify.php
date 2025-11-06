<?php
require_once '../app/controllers/UserController.php';
header("Content-Type: application/json");

$token = $_GET['token'] ?? '';

// Check if token is provided
if (empty($token)) {
    echo json_encode(["success" => false, "message" => "Verification token is missing."]);
    exit();
}

$controller = new UserController();
echo json_encode($controller->verify($token));
