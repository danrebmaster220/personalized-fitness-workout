<?php
require_once __DIR__ . '/../models/SystemReportModel.php';
require_once __DIR__ . '/../../config/database.php';

class SystemReportsController {
    private $db;
    private $model;

    public function __construct() {
        $this->db = (new Database())->connect();
        $this->model = new SystemReportModel($this->db);
    }

    public function getReport() {
        return [
            "success" => true,
            "apiSummary"      => $this->model->getApiUsageSummary(),
            "dailyRequests"   => $this->model->getRequestsPerDay(),
            "aiSuccessFail"   => $this->model->getAiSuccessFail(),
            "userApiUsage"    => $this->model->getUserApiUsage(),
            "workoutStats"    => $this->model->getWorkoutStats(),
            "workoutsDaily"   => $this->model->getWorkoutsPerDay(),
            "commonErrors"    => $this->model->getCommonErrors()
        ];
    }
}
?>
