<?php
require_once __DIR__ . '/../models/WorkoutModel.php';
require_once __DIR__ . '/../../config/database.php';

class WorkoutController {
    private $db;
    private $model;

    public function __construct() {
        $this->db = (new Database())->connect();
        $this->model = new WorkoutModel($this->db);
    }


      // 1 GENERATE WORKOUT (Gemini → OpenAI fallback)

    public function generate($data) {
        if (!isset($data['userId'])) {
            return ["success" => false, "message" => "User ID required"];
        }

        // Calculate metrics
        $bmi  = $this->calcBMI($data['weight'], $data['height']);
        $bmr  = $this->calcBMR($data['weight'], $data['height'], $data['age'], $data['gender']);
        $tdee = round($bmr * 1.55);

        // Build AI prompt
        $prompt = $this->buildPrompt($data, $bmi, $bmr, $tdee);

        // Try Gemini (2 attempts)
        $ai = $this->callGemini($prompt);
        if (!$this->isValidJsonStructure($ai)) $ai = $this->callGemini($prompt);

        // If still invalid, try OpenAI (2 attempts)
        if (!$this->isValidJsonStructure($ai)) {
            $ai = $this->callOpenAI($prompt);
            if (!$this->isValidJsonStructure($ai)) $ai = $this->callOpenAI($prompt);
        }

        // If still invalid → fail
        if (!$this->isValidJsonStructure($ai)) {
            return ["success" => false, "message" => "AI returned invalid response. Try again."];
        }

        // Save to DB
        $insertId = $this->model->saveGeneratedWorkout([
            "userId"       => $data['userId'],
            "goal"         => $data['goal'],
            "targetMuscle" => $data['targetMuscle'],
            "workoutPlace" => $data['workoutPlace'],
            "workoutDays"  => $data['workoutDays'],
            "duration"     => $data['duration'],
            "equipment"    => $data['equipment'],
            "healthCondition" => $data['condition'] ?? "",
            "allergies"       => $data['allergies'] ?? "",
            "bmi"  => $bmi,
            "bmr"  => $bmr,
            "tdee" => $tdee,
            "workoutResult" => $ai['workout'],
            "mealResult"    => $ai['meal'],
            "bodyResult"    => $ai['bodyCondition']
        ]);

        return [
            "success" => true,
            "message" => "Workout generated!",
            "id"      => $insertId,
            "result"  => $ai
        ];
    }


      // 2 FETCH WORKOUT HISTORY

    public function history($userId) {
        if (!$userId) return ["success" => false, "message" => "User ID required"];

        $rows = $this->model->getUserWorkouts($userId);
        return ["success" => true, "data" => $rows];
    }


      // 3 FETCH SINGLE WORKOUT (for PDF or viewing)

    public function getOne($id) {
        $row = $this->model->getWorkoutById($id);
        if (!$row) return ["success" => false, "message" => "Workout not found"];

        // Decode JSON fields before returning
        $row["Workout_Result"] = json_decode($row["Workout_Result"]);
        $row["Meal_Result"] = json_decode($row["Meal_Result"]);
        $row["Body_Condition_Result"] = json_decode($row["Body_Condition_Result"]);

        return ["success" => true, "data" => $row];
    }

      // 4 CALL GEMINI
    private function callGemini($prompt) {
        $apiKey = getenv("GEMINI_API_KEY");
        $url    = getenv("GEMINI_API_URL") ?: "https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent";
        $body   = ["contents" => [["parts" => [["text" => $prompt]]]]];

        $res = $this->curlPost("$url?key=$apiKey", json_encode($body));
        return $this->extractJson($res);
    }

      // 5 CALL OPENAI

    private function callOpenAI($prompt) {
        $key   = getenv("OPENAI_API_KEY");
        $model = getenv("OPENAI_MODEL") ?: "gpt-4o-mini";

        $body = [
            "model" => $model,
            "messages" => [
                ["role" => "system", "content" => "You output ONLY valid JSON."],
                ["role" => "user", "content" => $prompt]
            ],
            "temperature" => 0.2
        ];

        $res = $this->curlPost("https://api.openai.com/v1/chat/completions", json_encode($body), [
            "Content-Type: application/json",
            "Authorization: Bearer $key"
        ]);

        $json = json_decode($res, true);
        $text = $json["choices"][0]["message"]["content"] ?? "";
        return $this->extractJson($text);
    }


      // 6 HELPERS

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
        $first = strpos($text, '{');
        $last  = strrpos($text, '}');
        if ($first === false || $last === false) return null;
        $json  = substr($text, $first, $last - $first + 1);
        return json_decode($json, true);
    }

    private function isValidJsonStructure($ai) {
        return isset($ai["workout"], $ai["meal"], $ai["bodyCondition"]);
    }

    private function buildPrompt($data, $bmi, $bmr, $tdee) {
        $payload = [
            "age" => (int)$data['age'],
            "gender" => $data['gender'],
            "weight" => (float)$data['weight'],
            "height" => (float)$data['height'],
            "bmi" => $bmi,
            "bmr" => $bmr,
            "tdee" => $tdee,
            "goal" => $data['goal'],
            "targetMuscle" => $data['targetMuscle'],
            "workoutPlace" => $data['workoutPlace'],
            "workoutDays" => (int)$data['workoutDays'],
            "sessionMinutes" => (int)$data['duration'],
            "equipment" => $data['equipment'],
            "diet" => $data['diet'] ?? "no preference"
        ];

        return "You are a fitness coach. Return ONLY valid JSON with NO surrounding text.

Schema:
{
  \"workout\": { /* workout plan */ },
  \"meal\": { /* meal plan */ },
  \"bodyCondition\": { /* BMI/TDEE analysis & advice */ }
}

Input:
" . json_encode($payload, JSON_PRETTY_PRINT);
    }

    private function calcBMI($w,$h){ $h/=100; return round($w/($h*$h), 2); }

    private function calcBMR($w,$h,$a,$g){
        if (strtolower($g) === "male") return round(10*$w + 6.25*$h - 5*$a + 5);
        return round(10*$w + 6.25*$h - 5*$a - 161);
    }
}
?>
