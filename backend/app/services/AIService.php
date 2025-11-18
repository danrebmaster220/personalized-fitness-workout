<?php
require_once __DIR__ . '/../models/ApiLogModel.php';
require_once __DIR__ . '/../../config/database.php';

class AIService {
    private $logModel;

    public function __construct() {
        $db = (new Database())->connect();
        $this->logModel = new ApiLogModel($db);
    }

    public function generateWorkout($prompt) {
        // Try Gemini twice
        $ai = $this->callGemini($prompt);
        if (!$this->isValid($ai)) $ai = $this->callGemini($prompt);

        // Fallback OpenAI twice
        if (!$this->isValid($ai)) {
            $ai = $this->callOpenAI($prompt);
            if (!$this->isValid($ai)) $ai = $this->callOpenAI($prompt);
        }

        return $this->isValid($ai) ? $ai : null;
    }

    // --------------------------
    // GEMINI
    // --------------------------
    private function callGemini($prompt) {
        $apiKey = getenv("GEMINI_API_KEY");
        $url = getenv("GEMINI_API_URL");

        $request = ["contents" => [["parts" => [["text" => $prompt]]]]];
        $response = $this->curlPost("$url?key=$apiKey", json_encode($request));

        $decoded = $this->extractJson($response);

        $this->logModel->log([
            "userId" => 0,
            "apiName" => "Gemini Generate",
            "apiType" => "POST",
            "statusCode" => $decoded ? 200 : 500,
            "responseStatus" => $decoded ? "success" : "fail",
            "requestBody" => json_encode($request),
            "responseBody" => $response,
            "errorMessage" => $decoded ? "" : "Gemini returned invalid JSON"
        ]);

        return $decoded;
    }

    // --------------------------
    // OPENAI
    // --------------------------
    private function callOpenAI($prompt) {
        $key = getenv("OPENAI_API_KEY");
        $model = getenv("OPENAI_MODEL");

        $body = [
            "model" => $model,
            "messages" => [
                ["role" => "system", "content" => "Return ONLY valid JSON."],
                ["role" => "user", "content" => $prompt]
            ],
            "temperature" => 0.2
        ];

        $response = $this->curlPost(
            "https://api.openai.com/v1/chat/completions",
            json_encode($body),
            [
                "Content-Type: application/json",
                "Authorization: Bearer $key"
            ]
        );

        $json = json_decode($response, true);
        $text = $json["choices"][0]["message"]["content"] ?? "";
        $decoded = $this->extractJson($text);

        $this->logModel->log([
            "userId" => 0,
            "apiName" => "OpenAI Generate",
            "apiType" => "POST",
            "statusCode" => $decoded ? 200 : 500,
            "responseStatus" => $decoded ? "success" : "fail",
            "requestBody" => json_encode($body),
            "responseBody" => $response,
            "errorMessage" => $decoded ? "" : "OpenAI returned invalid JSON"
        ]);

        return $decoded;
    }

    // --------------------------
    // HELPERS
    // --------------------------
    private function curlPost($url, $body, $headers = ["Content-Type: application/json"]) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    private function extractJson($text) {
        $start = strpos($text, "{");
        $end   = strrpos($text, "}");
        if ($start === false || $end === false) return null;
        return json_decode(substr($text, $start, $end - $start + 1), true);
    }

    private function isValid($ai) {
        return isset($ai["workout"], $ai["meal"], $ai["bodyCondition"]);
    }
}
