<?php
class GeminiService {
    private $apiKey;
    private $apiUrl;

    public function __construct() {
        $this->apiKey = getenv('GEMINI_API_KEY');
        $this->apiUrl = getenv('GEMINI_API_URL');
    }

    /**
     * Generate text with Gemini.
     * Note: The GEMINI_API_URL environment variable should contain the full endpoint URL
     * (for example: https://gemini.googleapis.com/v1/models/text-bison-001:generate)
     * The implementation sends a JSON payload with `prompt` and `options` keys. Adjust if your endpoint requires
     * a different format.
     */
    public function generate($prompt, $options = []) {
        if (!$this->apiKey || !$this->apiUrl) {
            return ["success" => false, "message" => "Gemini API URL or key not configured on server."];
        }

        $payload = [
            'prompt' => $prompt,
            'options' => $options
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout'] ?? 30);

        $result = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result === false) {
            return ["success" => false, "message" => "HTTP request failed: $err"];
        }

        $data = json_decode($result, true);
        if ($code >= 400) {
            return ["success" => false, "message" => "Gemini returned HTTP $code", 'raw' => $data];
        }

        return ["success" => true, "provider" => 'gemini', "response" => $data];
    }
}
