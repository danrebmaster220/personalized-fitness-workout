<?php
require_once __DIR__ . '/../../app/services/OpenAIService.php';
require_once __DIR__ . '/../../app/services/GeminiService.php';

class AIController {
    private $openai;
    private $gemini;

    public function __construct() {
        $this->openai = new OpenAIService();
        $this->gemini = new GeminiService();
    }
    /**
     * Main chat flow implementing:
     *  - Try Gemini first (2 attempts) to produce structured JSON.
     *  - If Gemini fails to produce valid structured JSON after 2 attempts, try OpenAI (2 attempts).
     *  - A valid structured JSON is detected by attempting to parse JSON from the model text and
     *    ensuring it contains expected workout-related keys.
     *
     * Payload keys: prompt (string), provider (optional override: 'gemini'|'openai'), options (array)
     */
    public function chat($payload) {
        $prompt = $payload['prompt'] ?? '';
        $override = $payload['provider'] ?? null; // if user forces provider
        $options = $payload['options'] ?? [];

        if (!$prompt) return ["success" => false, "message" => "Missing prompt."];

        // order: gemini -> openai, unless overridden
        $providers = $override ? [$override] : ['gemini', 'openai'];

        $lastErrors = [];

        foreach ($providers as $prov) {
            // attempt up to 2 times per provider
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                if ($prov === 'gemini') {
                    $resp = $this->gemini->generate($prompt, $options);
                } else {
                    $resp = $this->openai->chat($prompt, $options);
                }

                // If the service itself failed, record and try again (unless final)
                if (!isset($resp['success']) || $resp['success'] !== true) {
                    $lastErrors[] = [
                        'provider' => $prov,
                        'attempt' => $attempt,
                        'error' => $resp
                    ];
                    // try next attempt
                    continue;
                }

                // extract textual content from the provider response
                $text = $this->extractTextFromProviderResponse($resp['response'] ?? $resp);

                // try to parse structured JSON from the text
                $decoded = $this->extractFirstJson($text);

                if ($decoded !== null && $this->isStructuredWorkout($decoded)) {
                    // success — return a standardized payload
                    return [
                        'success' => true,
                        'provider' => $prov,
                        'attempt' => $attempt,
                        'parsed' => $decoded,
                        'raw' => $resp['response'] ?? $resp
                    ];
                }

                // keep last raw response for debugging
                $lastErrors[] = [
                    'provider' => $prov,
                    'attempt' => $attempt,
                    'reason' => 'Invalid or missing JSON in model output',
                    'raw_text' => $text,
                    'raw_response' => $resp['response'] ?? $resp
                ];
                // continue attempts; after two attempts loop falls through to next provider
            }
        }

        return [
            'success' => false,
            'message' => 'Failed to obtain structured JSON from providers after retries.',
            'errors' => $lastErrors
        ];
    }

    /**
     * Try to extract a textual reply from different provider response shapes.
     */
    private function extractTextFromProviderResponse($resp) {
        if (is_string($resp)) return $resp;

        // OpenAI Chat: choices[0].message.content
        if (isset($resp['choices'][0]['message']['content'])) {
            return $resp['choices'][0]['message']['content'];
        }

        // OpenAI (older): choices[0].text
        if (isset($resp['choices'][0]['text'])) {
            return $resp['choices'][0]['text'];
        }

        // Gemini (common): candidates[0].content
        if (isset($resp['candidates'][0]['content'])) {
            return $resp['candidates'][0]['content'];
        }

        // Gemini alternative shapes
        if (isset($resp['output']) && is_array($resp['output'])) {
            // try to concatenate simple text pieces
            $out = '';
            array_walk_recursive($resp['output'], function($v) use (&$out) { if (is_string($v)) $out .= $v . "\n"; });
            if ($out !== '') return $out;
        }

        // Fall back: try to stringify the response
        return json_encode($resp);
    }

    /**
     * Extract the first JSON object found inside a piece of text.
     * Returns decoded array on success, or null on failure.
     */
    private function extractFirstJson($text) {
        if (!$text || !is_string($text)) return null;

        // If the entire text is JSON, decode directly
        $t = trim($text);
        $decoded = json_decode($t, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) return $decoded;

        // Otherwise try to find the first balanced {...} JSON object in the text using recursive regex
        $matches = [];
        if (preg_match_all('/\{(?:[^{}]|(?R))*\}/s', $text, $matches)) {
            foreach ($matches[0] as $candidate) {
                $d = json_decode($candidate, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($d)) return $d;
            }
        }

        return null;
    }

    /**
     * Basic heuristic to decide whether a decoded JSON looks like a workout payload.
     * Checks for presence of common keys like Workout_Result, workout, exercises, etc.
     */
    private function isStructuredWorkout($arr) {
        if (!is_array($arr)) return false;

        $keys = array_change_key_case(array_flip(array_keys($arr)), CASE_LOWER);

        $indicators = ['workout_result','workoutresult','workout','exercises','exercises_list','program'];

        foreach ($indicators as $k) {
            if (isset($keys[$k])) return true;
        }

        // Also accept if top-level contains keys for typical sections
        if (isset($arr['Workout_Result']) || isset($arr['Meal_Result']) || isset($arr['Body_Condition_Result'])) return true;

        return false;
    }
}
