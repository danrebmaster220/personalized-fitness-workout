<?php
require_once '../controllers/UserController.php';
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$controller = new UserController();

echo json_encode($controller->forgot($data['email']));
