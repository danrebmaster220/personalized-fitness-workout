<?php
require_once __DIR__ . '/../models/WorkoutModel.php';
require_once __DIR__ . '/../services/AIService.php';
require_once __DIR__ . '/../core/Database.php';

class WorkoutController {
    private $db;
    private $model;
    private $aiService;

    public function __construct() {
        $this->db = (new Database())->connect();
        $this->model = new WorkoutModel($this->db);
        $this->aiService = new AIService();
    }

    // ------------------------------
    // 1. GENERATE WORKOUT
    // ------------------------------
    public function generate($data) {

        if (!isset($data['userId'])) {
            return ["success" => false, "message" => "User ID required"];
        }

        // Enforce that the session user matches the supplied userId to prevent
        // generating workouts on behalf of another user.
        if (session_status() === PHP_SESSION_NONE) session_start();
        $sessionUid = $_SESSION['user_id'] ?? null;
        if (!$sessionUid) {
            return ["success" => false, "message" => "Not authenticated"];
        }
        if ($sessionUid != $data['userId']) {
            return ["success" => false, "message" => "Not authorized to generate for this user"];
        }

        // Calculate metrics
        $bmi  = $this->calcBMI($data['weight'], $data['height']);
        $bmr  = $this->calcBMR($data['weight'], $data['height'], $data['age'], $data['gender']);
        $tdee = round($bmr * 1.55);

        // Build AI prompt
        $prompt = $this->buildPrompt($data, $bmi, $bmr, $tdee);

        // CALL AI SERVICE (Gemini → fallback → OpenAI)
        $ai = $this->aiService->generateWorkout($prompt, $data['userId']);

        if (!$ai) {
            error_log("WorkoutController: AI returned null/invalid. Prompt length: " . strlen($prompt));
            error_log("WorkoutController: AI response structure: " . json_encode($ai));
            return ["success" => false, "message" => "AI returned invalid response. Try again."];
        }

        error_log("WorkoutController: AI generation SUCCESS");

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
            // include the ai payload plus helpful metadata for the frontend
            "result"  => array_merge($ai, [
                'id' => $insertId,
                'bmi' => $bmi,
                'bmr' => $bmr,
                'tdee' => $tdee,
                'createdAt' => date("c")
            ])
        ];
    }

    // ------------------------------
    // 2. FETCH USER WORKOUT HISTORY
    // ------------------------------
    public function history($userId)
    {
        if (!$userId) {
            return ["success" => false, "message" => "Missing userId"];
        }

        $page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
        $limit = isset($_GET["limit"]) ? max(1, intval($_GET["limit"])) : 10;
        $search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

        try {
            $result = $this->model->getUserWorkouts($userId, $page, $limit, $search);

            return [
                "success" => true,
                "data" => $result["rows"],
                "pagination" => [
                    "page" => $page,
                    "limit" => $limit,
                    "total" => $result["total"],
                    "totalPages" => $result["totalPages"]
                ]
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    // ------------------------------
    // 3. FETCH SINGLE WORKOUT
    // ------------------------------
    public function getOne($id) {
        $row = $this->model->getWorkoutById($id);
        if (!$row) return ["success" => false, "message" => "Workout not found"];

        $row["Workout_Result"] = json_decode($row["Workout_Result"]);
        $row["Meal_Result"] = json_decode($row["Meal_Result"]);
        $row["Body_Condition_Result"] = json_decode($row["Body_Condition_Result"]);

        return ["success" => true, "data" => $row];
    }

    // ------------------------------
    // HELPERS
    // ------------------------------

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
            "diet" => $data['diet'] ?? "no preference",
            "bodyCondition" => $data['bodyCondition'] ?? "none"
        ];

        return "You are an expert fitness coach and nutritionist. Generate a personalized workout and meal plan based on the user's data.

IMPORTANT: Respond with ONLY valid JSON. No markdown, no explanations, just the JSON object.

Required JSON structure:
{
  \"workout\": {
    \"weeklyPlan\": [
      {
        \"day\": \"Day 1\",
        \"focus\": \"Upper Body\",
        \"exercises\": [
          {\"name\": \"Push-ups\", \"sets\": 3, \"reps\": \"10-12\", \"rest\": \"60s\"}
        ]
      }
    ],
    \"notes\": \"Workout guidelines and tips\"
  },
  \"meal\": {
    \"dailyCalories\": " . $tdee . ",
    \"macros\": {\"protein\": \"150g\", \"carbs\": \"200g\", \"fats\": \"60g\"},
    \"meals\": [
      {\"meal\": \"Breakfast\", \"foods\": [\"Oatmeal\", \"Eggs\"], \"calories\": 400}
    ]
  },
  \"bodyCondition\": {
    \"bmi\": " . $bmi . ",
    \"category\": \"normal/underweight/overweight/obese\",
    \"assessment\": \"Health assessment text\",
    \"recommendations\": [\"Recommendation 1\", \"Recommendation 2\"]
  }
}

User Data:
" . json_encode($payload, JSON_PRETTY_PRINT) . "

Generate the complete JSON response now:";
    }

    private function calcBMI($w, $h) {
        $h /= 100;
        return round($w / ($h * $h), 2);
    }

    private function calcBMR($w, $h, $a, $g) {
        if (strtolower($g) === "male") {
            return round(10*$w + 6.25*$h - 5*$a + 5);
        }
        return round(10*$w + 6.25*$h - 5*$a - 161);
    }
}
?>
