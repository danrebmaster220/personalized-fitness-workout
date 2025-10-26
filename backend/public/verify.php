<?php
require_once '../controllers/UserController.php';
header("Content-Type: application/json");

$token = $_GET['token'] ?? '';
$controller = new UserController();

echo json_encode($controller->verify($token));
