<?php

class AdminLogs
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get paginated + filtered logs
    public function getLogs($filters)
    {
        $page   = $filters['page'];
        $limit  = $filters['limit'];
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        // Filters
        if (!empty($filters['from'])) {
            $where[] = "al.Request_Time >= :from";
            $params[':from'] = $filters['from'] . " 00:00:00";
        }
        if (!empty($filters['to'])) {
            $where[] = "al.Request_Time <= :to";
            $params[':to'] = $filters['to'] . " 23:59:59";
        }
        if (!empty($filters['search'])) {
            $where[] = "(al.API_Name LIKE :search OR al.API_Type LIKE :search OR u.Email LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }
        if (!empty($filters['method'])) {
            $where[] = "al.API_Type = :method";
            $params[':method'] = $filters['method'];
        }
        if ($filters['status'] !== "" && $filters['status'] !== null) {
            $where[] = "al.Status_Code = :status";
            $params[':status'] = (int)$filters['status'];
        }

        $whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

        // COUNT
        $countSql = "
            SELECT COUNT(*)
            FROM api_logs al
            LEFT JOIN user u ON al.User_ID = u.User_ID
            $whereSQL
        ";

        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        // DATA
        $sql = "
            SELECT al.*, u.Email AS User_Email
            FROM api_logs al
            LEFT JOIN user u ON al.User_ID = u.User_ID
            $whereSQL
            ORDER BY al.Request_Time DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            "logs" => $logs,
            "total" => $total
        ];
    }

    // Get single log
    public function getLogById($id)
    {
        $sql = "
            SELECT al.*, u.Email AS User_Email
            FROM api_logs al
            LEFT JOIN user u ON al.User_ID = u.User_ID
            WHERE al.Log_ID = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([":id" => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Delete log
    public function deleteLog($id)
    {
        $stmt = $this->db->prepare("DELETE FROM api_logs WHERE Log_ID = :id");
        return $stmt->execute([":id" => $id]);
    }
}
