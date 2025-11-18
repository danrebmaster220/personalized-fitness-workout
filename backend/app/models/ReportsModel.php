<?php

class ReportsModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // SYSTEM SUMMARY (Top Cards)
    public function getSystemStats() {
        $stats = [];

        // Total users
        $stats["totalUsers"] = $this->db->query("SELECT COUNT(*) FROM user")->fetchColumn();

        // Total generated workouts
        $stats["totalGeneratedWorkouts"] = $this->db->query("SELECT COUNT(*) FROM generated_workout")->fetchColumn();

        // Total API logs
        $stats["totalApiLogs"] = $this->db->query("SELECT COUNT(*) FROM api_logs")->fetchColumn();

        // Total successful API calls
        $stats["totalSuccessLogs"] = $this->db->query("SELECT COUNT(*) FROM api_logs WHERE Status_Code = 200")->fetchColumn();

        return $stats;
    }

    // USERS PER MONTH (last 12 months)
    public function getUsersPerMonth() {
        $query = "
            SELECT DATE_FORMAT(Created_At, '%Y-%m') AS month, COUNT(*) AS count
            FROM user
            WHERE Created_At >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY month
            ORDER BY month ASC
        ";

        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // GENDER DISTRIBUTION
    public function getGenderStats() {
        $query = "
            SELECT Gender, COUNT(*) AS count
            FROM user
            GROUP BY Gender
        ";

        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    // WORKOUTS PER MONTH
    public function getWorkoutsPerMonth() {
        $query = "
            SELECT DATE_FORMAT(Created_At, '%Y-%m') AS month, COUNT(*) AS count
            FROM generated_workout
            WHERE Created_At >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY month
            ORDER BY month ASC
        ";

        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    // API SUCCESS VS FAILED
    public function getApiStatusBreakdown() {
        $query = "
            SELECT 
                SUM(CASE WHEN Status_Code = 200 THEN 1 ELSE 0 END) AS success,
                SUM(CASE WHEN Status_Code != 200 THEN 1 ELSE 0 END) AS failed
            FROM api_logs
        ";

        return $this->db->query($query)->fetch(PDO::FETCH_ASSOC);
    }
}
