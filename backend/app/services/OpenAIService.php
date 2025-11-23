<?php
class OpenAIService {
    private $apiKey;
    private $model;

    public function __construct() {
        $this->apiKey = getenv('OPENAI_API_KEY');
        $this->model = getenv('OPENAI_MODEL') ?: 'gpt-4o-mini';
    }

    /**
     * Create a chat completion using OpenAI Chat Completions API
     * Returns associative array with the provider response or error
     */
    public function chat($prompt, $options = []) {
        if (!$this->apiKey) {
            return ["success" => false, "message" => "OpenAI API key not configured on server."];
        }

        $url = 'https://api.openai.com/v1/chat/completions';

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $options['temperature'] ?? 0.7,
        ];

        if (isset($options['max_tokens'])) $payload['max_tokens'] = intval($options['max_tokens']);

        $ch = curl_init($url);
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
            return ["success" => false, "message" => "OpenAI returned HTTP $code", 'raw' => $data];
        }

        return ["success" => true, "provider" => 'openai', "response" => $data];
    }
}
