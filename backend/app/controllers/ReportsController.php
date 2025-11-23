<?php
require_once __DIR__ . '/../models/ReportsModel.php';
require_once __DIR__ . '/../core/Database.php';

class ReportsController {

    private $db;
    private $model;

    public function __construct() {
        $this->db = (new Database())->connect();
        $this->model = new ReportsModel($this->db);
    }

    public function systemStats() {
        return [
            "success" => true,
            "data" => $this->model->getSystemStats()
        ];
    }

    public function userStats() {
        return [
            "success" => true,
            "usersPerMonth" => $this->model->getUsersPerMonth(),
            "genderStats" => $this->model->getGenderStats()
        ];
    }

    public function workoutStats() {
        return [
            "success" => true,
            "workoutsPerMonth" => $this->model->getWorkoutsPerMonth()
        ];
    }

    public function apiStats() {
        return [
            "success" => true,
            "apiStatus" => $this->model->getApiStatusBreakdown()
        ];
    }
}
