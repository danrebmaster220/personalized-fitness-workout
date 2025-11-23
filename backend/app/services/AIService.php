<?php
require_once __DIR__ . '/../models/ApiLogModel.php';
require_once __DIR__ . '/../core/Database.php';

class AIService {
    private $logModel;

    // Default behavior settings
    private $attemptsPerProvider = 2;
    private $defaultMaxTokens = 2000;  // Increased from 300 to allow full workout plans
    private $defaultTemperature = 0.7;

    public function __construct() {
        $db = (new Database())->connect();
        $this->logModel = new ApiLogModel($db);
    }

    public function generateWorkout($prompt, $userId = null) {
        $ai = null;

        // TEMPORARILY SKIP GEMINI - Go straight to OpenAI
        // Try Gemini first (configured as Gemini 2.0 Flash in env) with up to $attemptsPerProvider attempts
        // for ($i = 0; $i < $this->attemptsPerProvider; $i++) {
        //     $ai = $this->callGemini($prompt, $userId);
        //     if ($this->isValid($ai)) break;
        // }

        // If still invalid after Gemini attempts, try OpenAI GPT-4o mini with up to $attemptsPerProvider attempts
        if (!$this->isValid($ai)) {
            for ($i = 0; $i < $this->attemptsPerProvider; $i++) {
                $ai = $this->callOpenAI($prompt, $userId);
                if ($this->isValid($ai)) break;
            }
        }

        return $this->isValid($ai) ? $ai : null;
    }

    // --------------------------
    // GEMINI
    // --------------------------
    private function callGemini($prompt, $userId = null) {
        $apiKey = getenv("GEMINI_API_KEY");
        $url = getenv("GEMINI_API_URL");

        // If URL or key not configured, skip Gemini call to avoid noisy failures and let fallback run.
        if (!$url || !$apiKey) {
            return null;
        }

        // Build a typical Gemini 2.0 Flash request body. Many Google GenAI endpoints expect the model
        // to be part of the URL (e.g., https://gemini.googleapis.com/v1/models/gemini-2.0-flash:generate)
        // and accept a JSON body with `prompt` or `contents`. Adjust `GEMINI_API_URL` in env.php to
        // point to the correct generation endpoint for your project.
        $request = [
            // Some Gemini endpoints accept a simple `prompt` or `input`, others accept contents with parts.
            // We include both shapes conservatively; the service should accept at least one.
            "prompt" => ["text" => $prompt],
            "contents" => [["parts" => [["text" => $prompt]]]]
        ];

        // Add simple params to limit output length and randomness
        $requestOptions = [
            'maxTokens' => $this->defaultMaxTokens,
            'temperature' => $this->defaultTemperature
        ];
        $request['options'] = $requestOptions;

        $response = $this->curlPost("$url?key=$apiKey", json_encode($request));

        $decoded = $this->extractJson($response);

        error_log("AIService (Gemini): Raw response length: " . strlen($response));
        error_log("AIService (Gemini): Decoded keys: " . json_encode(array_keys($decoded ?: [])));

        try {
            $this->logModel->log([
                "userId" => $userId,
                "apiName" => "Gemini Generate",
                "apiType" => "POST",
                "statusCode" => $decoded ? 200 : 500,
                "responseStatus" => $decoded ? "success" : "fail",
                "requestBody" => json_encode($request),
                "responseBody" => $response,
                "errorMessage" => $decoded ? "" : "Gemini returned invalid JSON"
            ]);
        } catch (Exception $e) {
            // Log failed, continue anyway
        }

        return $decoded;
    }

    // --------------------------
    // OPENAI
    // --------------------------
    private function callOpenAI($prompt, $userId = null) {
        $key = getenv("OPENAI_API_KEY");
        $model = getenv("OPENAI_MODEL");

        // Use configured model (we recommend gpt-4o-mini for cost and speed). Use conservative defaults
        $body = [
            "model" => $model ?: 'gpt-4o-mini',
            "messages" => [
                ["role" => "system", "content" => "Return ONLY valid JSON. Respond with the JSON object only when possible."],
                ["role" => "user", "content" => $prompt]
            ],
            "temperature" => $this->defaultTemperature,
            "max_tokens" => $this->defaultMaxTokens
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
        
        error_log("AIService (OpenAI): Raw response length: " . strlen($response));
        error_log("AIService (OpenAI): Extracted text length: " . strlen($text));
        error_log("AIService (OpenAI): Text preview: " . substr($text, 0, 200));
        
        $decoded = $this->extractJson($text);
        
        error_log("AIService (OpenAI): Decoded result: " . ($decoded === null ? "NULL" : gettype($decoded)));
        error_log("AIService (OpenAI): Decoded keys: " . json_encode(array_keys($decoded ?: [])));
        if ($decoded) {
            error_log("AIService (OpenAI): JSON structure: " . json_encode(array_map(function($v) { return gettype($v); }, $decoded)));
        }

        try {
            $this->logModel->log([
                "userId" => $userId,
                "apiName" => "OpenAI Generate",
                "apiType" => "POST",
                "statusCode" => $decoded ? 200 : 500,
                "responseStatus" => $decoded ? "success" : "fail",
                "requestBody" => json_encode($body),
                "responseBody" => $response,
                "errorMessage" => $decoded ? "" : "OpenAI returned invalid JSON"
            ]);
        } catch (Exception $e) {
            // Log failed, continue anyway
        }

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
        $isValid = isset($ai["workout"], $ai["meal"], $ai["bodyCondition"]);
        if (!$isValid) {
            error_log("AIService: Validation failed. Keys present: " . json_encode(array_keys($ai ?: [])));
        }
        return $isValid;
    }
}
