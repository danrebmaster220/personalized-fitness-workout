<?php

class GeneratedWorkoutModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Fetch generated workouts with filters + pagination
    public function getGeneratedWorkouts($filters) {

        $page   = $filters["page"];
        $limit  = $filters["limit"];
        $offset = ($page - 1) * $limit;

        $search = $filters["search"] ?? null;
        $goal   = $filters["goal"] ?? null;
        $from   = $filters["from"] ?? null;
        $to     = $filters["to"] ?? null;

        $params = [];
        $where = [];

        // Date range
        if ($from) {
            $where[] = "gw.Created_At >= :from";
            $params[":from"] = $from . " 00:00:00";
        }

        if ($to) {
            $where[] = "gw.Created_At <= :to";
            $params[":to"] = $to . " 23:59:59";
        }

        // Search (user or goal)
        if (!empty($search)) {
            $where[] = "(u.FirstName LIKE :search OR u.LastName LIKE :search OR gw.Goal LIKE :search)";
            $params[":search"] = "%{$search}%";
        }

        // Goal filter
        if (!empty($goal)) {
            $where[] = "gw.Goal = :goal";
            $params[":goal"] = $goal;
        }

        $whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

        // 1) COUNT total rows
        $countQuery = "
            SELECT COUNT(*)
            FROM generated_workout gw
            LEFT JOIN user u ON u.User_ID = gw.User_ID
            $whereSQL
        ";

        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // 2) Fetch rows (paginated)
        $query = "
            SELECT gw.*, u.FirstName, u.LastName, u.Email
            FROM generated_workout gw
            LEFT JOIN user u ON u.User_ID = gw.User_ID
            $whereSQL
            ORDER BY gw.Created_At DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            "workouts" => $rows,
            "total" => $total,
            "totalPages" => ceil($total / $limit),
            "page" => $page,
            "limit" => $limit
        ];
    }
}
