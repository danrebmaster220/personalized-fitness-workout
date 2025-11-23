<?php

class ApiLogModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function log($data) {
        $sql = "INSERT INTO api_logs (
                    User_ID, API_Name, API_Type, Status_Code, Response_Status,
                    Request_Body, Response_Body, Error_Message, Request_Time
                ) VALUES (
                    :userId, :apiName, :apiType, :statusCode, :responseStatus,
                    :requestBody, :responseBody, :errorMessage, NOW()
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ":userId" => $data["userId"],
            ":apiName" => $data["apiName"],
            ":apiType" => $data["apiType"],
            ":statusCode" => $data["statusCode"],
            ":responseStatus" => $data["responseStatus"],
            ":requestBody" => $data["requestBody"],
            ":responseBody" => $data["responseBody"],
            ":errorMessage" => $data["errorMessage"]
        ]);
    }

    public function getLogs($filters = []) {
        $page = $filters['page'] ?? 1;
        $limit = $filters['limit'] ?? 10;
        $offset = ($page - 1) * $limit;

        // Build WHERE clause
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(al.API_Name LIKE :search OR al.API_Type LIKE :search OR u.Email LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['method'])) {
            $where[] = "al.API_Type = :method";
            $params[':method'] = $filters['method'];
        }

        if (!empty($filters['status'])) {
            $where[] = "al.Status_Code = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['from'])) {
            $where[] = "DATE(al.Request_Time) >= :from";
            $params[':from'] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $where[] = "DATE(al.Request_Time) <= :to";
            $params[':to'] = $filters['to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count total records
        $countSql = "SELECT COUNT(*) as total FROM api_logs al 
                     LEFT JOIN users u ON al.User_ID = u.User_ID 
                     $whereClause";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get paginated results
        $sql = "SELECT al.Log_ID, al.API_Name as Endpoint, al.API_Type as Method, 
                       al.Status_Code as Status, al.Request_Body, al.Response_Body,
                       al.Error_Message, al.Request_Time as Created_At,
                       u.Email as User_Email
                FROM api_logs al
                LEFT JOIN users u ON al.User_ID = u.User_ID
                $whereClause
                ORDER BY al.Request_Time DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'logs' => $logs,
            'total' => $total
        ];
    }

    public function getLogById($id) {
        $sql = "SELECT al.Log_ID, al.API_Name as Endpoint, al.API_Type as Method, 
                       al.Status_Code as Status, al.Request_Body, al.Response_Body,
                       al.Error_Message, al.Request_Time as Created_At,
                       u.Email as User_Email
                FROM api_logs al
                LEFT JOIN users u ON al.User_ID = u.User_ID
                WHERE al.Log_ID = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteLog($id) {
        $sql = "DELETE FROM api_logs WHERE Log_ID = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
?>
