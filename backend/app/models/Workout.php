<?php
class Workout {
  private $conn;
  private $table = 'workout_plan';

  public function __construct($db) {
    $this->conn = $db;
  }

  public function saveGeneratedWorkout($user_id, $plan_name, $description, $duration) {
    $stmt = $this->conn->prepare("INSERT INTO $this->table (User_ID, Plan_Name, Description, Duration) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $user_id, $plan_name, $description, $duration);
    return $stmt->execute()
      ? ["success" => true, "message" => "Workout saved successfully."]
      : ["success" => false, "message" => "Failed to save workout."];
  }
}
