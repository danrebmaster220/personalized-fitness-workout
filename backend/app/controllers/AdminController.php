<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Users.php';

class AdminController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        $this->user = new User($this->db);
    }

    // Get All Users
    public function getAllUsers() {
        $users = $this->user->getAllUsers();
        return [
            "success" => true,
            "users" => $users
        ];
    }

    // Delete User
    public function deleteUser($id) {
        if (!$id) {
            return ["success" => false, "message" => "Missing user ID."];
        }

        if ($this->user->deleteUser($id)) {
            return ["success" => true, "message" => "User deleted successfully."];
        }

        return ["success" => false, "message" => "Failed to delete user."];
    }

    // Dashboard Queries
    public function getDashboardStats() {
        $stats = [];

        // Total Users
        $stats['totalUsers'] = $this->db->query("SELECT COUNT(*) FROM user WHERE Role='user'")->fetchColumn();

        // Verified Users
        $stats['verifiedUsers'] = $this->db->query("SELECT COUNT(*) FROM user WHERE Role='user' AND Is_Verified = 1")->fetchColumn();

        // Unverified users (computed)
        $stats['unverifiedUsers'] = $stats['totalUsers'] - $stats['verifiedUsers'];

        // All Generated Workouts
        $stats['totalWorkouts'] = $this->db->query("SELECT COUNT(*) FROM generated_workout")->fetchColumn();

        // API Logs
        $stats['totalApiLogs'] = $this->db->query("SELECT COUNT(*) FROM api_logs")->fetchColumn();

        return ["success" => true, "stats" => $stats];
    }
    // Monthly user growth (last 12 months)
    public function getMonthlyUserGrowth($monthsBack = 12) {
        $base = $this->getMonthRange($monthsBack);

        $sql = "SELECT DATE_FORMAT(Created_At, '%Y-%m') AS m, COUNT(*) AS c
                FROM user
                WHERE Created_At >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY m";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["months" => $monthsBack]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $base[$row['m']] = (int)$row['c'];
        }

        // Convert to Recharts-friendly array
        $output = [];
        foreach ($base as $month => $count) {
            $output[] = ["month" => $month, "count" => $count];
        }

        return ["success" => true, "data" => $output];
    }

    // Verification breakdown
    public function getVerificationBreakdown() {
        $sql = "SELECT Is_Verified AS verified, COUNT(*) AS count FROM user GROUP BY Is_Verified";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ["success" => true, "data" => $rows];
    }

    // Monthly generated workouts (last 12 months)
    public function getMonthlyWorkouts($monthsBack = 12) {
        $base = $this->getMonthRange($monthsBack);

        $sql = "SELECT DATE_FORMAT(Created_At, '%Y-%m') AS m, COUNT(*) AS c
                FROM generated_workout
                WHERE Created_At >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY m";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["months" => $monthsBack]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $base[$row['m']] = (int)$row['c'];
        }

        $output = [];
        foreach ($base as $month => $count) {
            $output[] = ["month" => $month, "count" => $count];
        }

        return ["success" => true, "data" => $output];
    }

    // Recent users (latest 6)
    public function getRecentUsers() {
        $sql = "SELECT User_ID, FirstName, LastName, Email, Is_Verified, Created_At, Role FROM user ORDER BY Created_At DESC LIMIT 6";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ["success" => true, "data" => $rows];
    }

    // Optional: get top workouts (if you want later)
    public function getTopWorkouts() {
        // Placeholder: e.g. top by frequency if you store references
        return ["success" => true, "data" => []];
    }

    // Generate continuous months array
    private function getMonthRange($monthsBack = 12) {
        $range = [];
        for ($i = $monthsBack - 1; $i >= 0; $i--) {
            $key = date("Y-m", strtotime("-$i months"));
            $range[$key] = 0; 
        }
        return $range;
    }

    // Generated Workouts Queries
    public function getGeneratedWorkouts() {
        $query = "
            SELECT gw.*, u.FirstName, u.LastName, u.Email 
            FROM generated_workout gw
            LEFT JOIN user u ON gw.User_ID = u.User_ID
            ORDER BY gw.Created_At DESC
        ";
        $stmt = $this->db->query($query);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // If workout result columns are JSON strings, ensure they are returned as strings
        return [
            "success" => true,
            "workouts" => $rows
        ];
    }

    // Get single generated workout detail by id
    public function getGeneratedWorkoutById($id) {
        $query = "
            SELECT gw.*, u.FirstName, u.LastName, u.Email 
            FROM generated_workout gw
            LEFT JOIN user u ON gw.User_ID = u.User_ID
            WHERE gw.Generate_ID = :id
            LIMIT 1
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([":id" => $id]);
        $workout = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$workout) {
            return ["success" => false, "message" => "Workout not found."];
        }

        // optionally decode json fields to arrays for clarity (frontend can also parse)
        $workout['Workout_Result_decoded'] = json_decode($workout['Workout_Result'], true);
        $workout['Meal_Result_decoded'] = json_decode($workout['Meal_Result'], true);
        $workout['Body_Condition_Result_decoded'] = json_decode($workout['Body_Condition_Result'], true);

        return ["success" => true, "workout" => $workout];
    }

    // Optional: export a single workout as simple downloadable JSON (or implement PDF creation)
    public function exportWorkoutJson($id) {
        $res = $this->getGeneratedWorkoutById($id);
        if (!$res['success']) return $res;

        $payload = $res['workout'];
        // return JSON (frontend will handle download)
        return ["success" => true, "payload" => $payload];
    }

    public function deleteGeneratedWorkout($id) {
        if (!$id) return ["success"=>false,"message"=>"Invalid id"];
        $stmt = $this->db->prepare("DELETE FROM generated_workout WHERE Generate_ID = ?");
        if ($stmt->execute([$id])) return ["success"=>true,"message"=>"Deleted"];
        return ["success"=>false,"message"=>"Delete failed"];
    }

   
}
?>
