<?php

class WorkoutModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Save generated workout
    public function saveGeneratedWorkout($data) {
        $sql = "INSERT INTO generated_workout (
                    User_ID, Goal, Target_Muscle, Workout_Place, Workout_Days,
                    Duration_Min, Equipment, Health_Condition, Allergies,
                    BMI, BMR, TDEE,
                    Workout_Result, Meal_Result, Body_Condition_Result,
                    Created_At
                )
                VALUES (
                    :userId, :goal, :targetMuscle, :workoutPlace, :workoutDays,
                    :duration, :equipment, :healthCondition, :allergies,
                    :bmi, :bmr, :tdee,
                    :workoutResult, :mealResult, :bodyResult,
                    NOW()
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ":userId"         => $data["userId"],
            ":goal"           => $data["goal"],
            ":targetMuscle"   => $data["targetMuscle"],
            ":workoutPlace"   => $data["workoutPlace"],
            ":workoutDays"    => $data["workoutDays"],
            ":duration"       => $data["duration"],
            ":equipment"      => $data["equipment"],
            ":healthCondition"=> $data["healthCondition"],
            ":allergies"      => $data["allergies"],
            ":bmi"            => $data["bmi"],
            ":bmr"            => $data["bmr"],
            ":tdee"           => $data["tdee"],
            ":workoutResult"  => json_encode($data["workoutResult"]),
            ":mealResult"     => json_encode($data["mealResult"]),
            ":bodyResult"     => json_encode($data["bodyResult"])
        ]);

        return $this->db->lastInsertId();
    }

    // User history
    public function getUserWorkouts($userId, $page = 1, $limit = 10, $search = "")
    {
        $offset = ($page - 1) * $limit;

        // SEARCH FILTER
        $searchSql = "";
        $params = [":uid" => $userId];

        if ($search !== "") {
            $searchSql = " AND (Goal LIKE :search 
                            OR Target_Muscle LIKE :search
                            OR Workout_Result LIKE :search
                            OR Meal_Result LIKE :search)";
            $params[":search"] = "%$search%";
        }

        // COUNT total rows
        $countQuery = "
            SELECT COUNT(*) AS total
            FROM generated_workout
            WHERE User_ID = :uid
            $searchSql
        ";

        $stmt = $this->db->prepare($countQuery);
        $stmt->execute($params);
        $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)["total"];

        // FETCH paginated data
        $query = "
            SELECT *
            FROM generated_workout
            WHERE User_ID = :uid
            $searchSql
            ORDER BY Created_At DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($query);

        // Bind normal params
        foreach ($params as $key => $value) {
            if ($key === ":uid") {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }

        // Bind pagination params (must be bound as integers)
        $stmt->bindValue(":limit", (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", (int)$offset, PDO::PARAM_INT);

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            "rows" => $rows,
            "total" => $total,
            "totalPages" => max(1, ceil($total / $limit))
        ];
    }

    // Single workout
    public function getWorkoutById($id) {
        $sql = "SELECT * FROM generated_workout WHERE Generate_ID = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
