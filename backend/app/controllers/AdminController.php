<?php
require_once '../core/Database.php';

class AdminController {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Dashboard stats
    public function getStats() {
        $conn = $this->db->connect();
        $stats = [
            'totalUsers' => $conn->query("SELECT COUNT(*) FROM users WHERE Is_Admin = 0")->fetchColumn(),
            'totalWorkouts' => $conn->query("SELECT COUNT(*) FROM workout_plan")->fetchColumn(),
            'recentActivities' => $conn->query("SELECT * FROM workout_plan ORDER BY Created_Date DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC)
        ];
        return $stats;
    }

    // Manage users
    public function getUsers() {
        $conn = $this->db->connect();
        return $conn->query("SELECT ID, FirstName, LastName, Email, Is_Verified FROM users WHERE Is_Admin = 0")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteUser($userId) {
        $conn = $this->db->connect();
        $stmt = $conn->prepare("DELETE FROM users WHERE ID = ? AND Is_Admin = 0");
        return $stmt->execute([$userId]);
    }

    // Manage workouts
    public function getWorkouts() {
        $conn = $this->db->connect();
        return $conn->query("SELECT * FROM workout_plan")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteWorkout($workoutId) {
        $conn = $this->db->connect();
        $stmt = $conn->prepare("DELETE FROM workout_plan WHERE Workout_ID = ?");
        return $stmt->execute([$workoutId]);
    }

    // API logs
    public function getAPILogs() {
        $conn = $this->db->connect();
        return $conn->query("SELECT * FROM api_logs ORDER BY Request_Time DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
}