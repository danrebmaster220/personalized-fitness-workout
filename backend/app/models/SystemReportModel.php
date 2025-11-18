<?php

class SystemReportModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /** 1. API usage summary */
    public function getApiUsageSummary() {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(API_Name LIKE '%Gemini%') AS gemini_calls,
                SUM(API_Name LIKE '%OpenAI%') AS openai_calls,
                SUM(API_Type = 'GET') AS get_count,
                SUM(API_Type = 'POST') AS post_count,
                SUM(API_Type = 'PUT') AS put_count,
                SUM(API_Type = 'DELETE') AS delete_count
            FROM api_logs
        ";
        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    /** 2. Requests per day (last 30 days) */
    public function getRequestsPerDay() {
        $sql = "
            SELECT DATE(Request_Time) AS day, COUNT(*) AS count
            FROM api_logs
            WHERE Request_Time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(Request_Time);
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** 3. AI success vs failures */
    public function getAiSuccessFail() {
        $sql = "
            SELECT 
                SUM(API_Name LIKE '%Gemini%' AND Status_Code = 200) AS gemini_success,
                SUM(API_Name LIKE '%Gemini%' AND Status_Code != 200) AS gemini_fail,
                SUM(API_Name LIKE '%OpenAI%' AND Status_Code = 200) AS openai_success,
                SUM(API_Name LIKE '%OpenAI%' AND Status_Code != 200) AS openai_fail
            FROM api_logs
        ";
        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    /** 4. API Usage by user */
    public function getUserApiUsage() {
        $sql = "
            SELECT 
                u.Email AS user,
                COUNT(al.Log_ID) AS total_calls
            FROM api_logs al
            LEFT JOIN user u ON u.User_ID = al.User_ID
            GROUP BY al.User_ID
            ORDER BY total_calls DESC
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** 5. Workout statistics */
    public function getWorkoutStats() {
        $sql = "
            SELECT 
                COUNT(*) AS total_workouts,
                COUNT(DISTINCT User_ID) AS users_involved,
                ROUND(COUNT(*) / COUNT(DISTINCT User_ID), 2) AS avg_per_user
            FROM generated_workout
        ";
        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    /** 6. Workouts per day (past 30 days) */
    public function getWorkoutsPerDay() {
        $sql = "
            SELECT DATE(Created_At) AS day, COUNT(*) AS count
            FROM generated_workout
            WHERE Created_At >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(Created_At)
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** 7. Most common API errors */
    public function getCommonErrors() {
        $sql = "
            SELECT 
                API_Name,
                Status_Code,
                COUNT(*) AS count
            FROM api_logs
            WHERE Status_Code != 200
            GROUP BY API_Name, Status_Code
            ORDER BY count DESC
            LIMIT 10
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
