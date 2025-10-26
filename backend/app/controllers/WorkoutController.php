<?php
require_once "../core/Database.php";
require_once "../core/Response.php";
require_once "../models/Workout.php";

$db = new Database();
$workout = new Workout($db->conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = json_decode(file_get_contents("php://input"), true);

  // Example: call external API (replace YOUR_API_KEY)
  $apiUrl = "https://exercisedb.p.rapidapi.com/exercises/bodyPart/chest";
  $headers = [
    "X-RapidAPI-Key: YOUR_API_KEY",
    "X-RapidAPI-Host: exercisedb.p.rapidapi.com"
  ];

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $apiUrl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $response = curl_exec($ch);
  curl_close($ch);

  $apiData = json_decode($response, true);

  // Example: save to DB
  $save = $workout->saveGeneratedWorkout($data['user_id'], "Chest Day", "Generated chest exercises", 45);

  Response::json(["success" => true, "apiData" => $apiData, "saveResult" => $save]);
}
