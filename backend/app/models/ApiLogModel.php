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
}
?>
