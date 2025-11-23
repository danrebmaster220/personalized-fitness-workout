<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Users.php';
require_once __DIR__ . '/../models/AdminLogs.php';
require_once __DIR__ . '/../models/GeneratedWorkoutModel.php';

class AdminController {
    private $db;
    private $user;
    private $logs;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        $this->user = new User($this->db);
        $this->logs = new AdminLogs($this->db);
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

        // Admins (count) - used to ensure admin accounts are not included in total users
        $stats['totalAdmins'] = $this->db->query("SELECT COUNT(*) FROM user WHERE Role='admin'")->fetchColumn();

        // Total Users (exclude admin accounts)
        $stats['totalUsers'] = $this->db->query("SELECT COUNT(*) FROM user WHERE COALESCE(Role, '') != 'admin'")->fetchColumn();

        // Verified Users (exclude admin accounts)
        $stats['verifiedUsers'] = $this->db->query("SELECT COUNT(*) FROM user WHERE COALESCE(Role, '') != 'admin' AND Is_Verified = 1")->fetchColumn();

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
        // Exclude admin accounts from the verification breakdown so dashboard reflects regular users only
        $sql = "SELECT Is_Verified AS verified, COUNT(*) AS count FROM user WHERE COALESCE(Role, '') != 'admin' GROUP BY Is_Verified";
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

        require_once __DIR__ . "/../models/GeneratedWorkoutModel.php";

        $model = new GeneratedWorkoutModel($this->db);

        $filters = [
            "page"   => isset($_GET["page"]) ? (int)$_GET["page"] : 1,
            "limit"  => isset($_GET["limit"]) ? (int)$_GET["limit"] : 10,
            "search" => $_GET["search"] ?? null,
            "goal"   => $_GET["goal"] ?? null,
            "from"   => $_GET["from"] ?? null,
            "to"     => $_GET["to"] ?? null,
        ];

        $result = $model->getGeneratedWorkouts($filters);

        return [
            "success" => true,
            "workouts" => $result["workouts"],
            "pagination" => [
                "page" => $result["page"],
                "limit" => $result["limit"],
                "total" => $result["total"],
                "totalPages" => $result["totalPages"]
            ]
        ];
    }


    // API Logs Queries
    public function getApiLogs()
    {
        $filters = [
            "page"   => isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1,
            "limit"  => isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10,
            "search" => $_GET['search'] ?? null,
            "method" => $_GET['method'] ?? null,
            "status" => $_GET['status'] ?? null,
            "from"   => $_GET['from'] ?? null,
            "to"     => $_GET['to'] ?? null,
        ];

        $result = $this->logs->getLogs($filters);

        return [
            "success" => true,
            "logs" => $result['logs'],
            "pagination" => [
                "page" => $filters['page'],
                "limit" => $filters['limit'],
                "total" => $result['total'],
                "totalPages" => $result['total'] ? ceil($result['total'] / $filters['limit']) : 1
            ]
        ];
    }

    public function getApiLogById($id)
    {
        if (!$id) return ["success" => false, "message" => "ID required"];

        $log = $this->logs->getLogById($id);
        if ($log) return ["success" => true, "log" => $log];

        return ["success" => false, "message" => "Log not found"];
    }

    public function deleteApiLog($id)
    {
        if (!$id) return ["success" => false, "message" => "ID required"];

        if ($this->logs->deleteLog($id)) {
            return ["success" => true, "message" => "Log deleted"];
        }

        return ["success" => false, "message" => "Delete failed"];
    }
   
}
?>
