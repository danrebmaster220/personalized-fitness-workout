<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$path = $_GET['route'] ?? '';

switch ($path) {
  case 'user':
    require_once "../controllers/UserController.php";
    break;
  case 'workout':
    require_once "../controllers/WorkoutController.php";
    break;
  default:
    echo json_encode(["success" => false, "message" => "Invalid route."]);
}
