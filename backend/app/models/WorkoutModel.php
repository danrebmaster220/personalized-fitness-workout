<?php
class WorkoutModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function saveGeneratedWorkout($data) {
        $sql = "INSERT INTO generated_workout
        (User_ID, Goal, Target_Muscle, Workout_Place, Workout_Days, Duration_Min,
        Equipment, Health_Condition, Allergies, BMI, BMR, TDEE,
        Workout_Result, Meal_Result, Body_Condition_Result, Raw_AI_Response)
        VALUES
        (:User_ID, :Goal, :Target_Muscle, :Workout_Place, :Workout_Days, :Duration_Min,
        :Equipment, :Health_Condition, :Allergies, :BMI, :BMR, :TDEE,
        :Workout_Result, :Meal_Result, :Body_Condition_Result, :Raw_AI_Response)";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ":User_ID"             => $data['User_ID'],
            ":Goal"                => $data['Goal'],
            ":Target_Muscle"       => $data['Target_Muscle'],
            ":Workout_Place"       => $data['Workout_Place'],
            ":Workout_Days"        => $data['Workout_Days'],
            ":Duration_Min"        => $data['Duration_Min'],
            ":Equipment"           => $data['Equipment'],
            ":Health_Condition"    => $data['Health_Condition'],
            ":Allergies"           => $data['Allergies'],
            ":BMI"                 => $data['BMI'],
            ":BMR"                 => $data['BMR'],
            ":TDEE"                => $data['TDEE'],
            ":Workout_Result"      => $data['Workout_Result'],
            ":Meal_Result"         => $data['Meal_Result'],
            ":Body_Condition_Result" => $data['Body_Condition_Result'],
            ":Raw_AI_Response"     => $data['Raw_AI_Response'],
        ]);

        return $this->db->lastInsertId();
    }

    public function getUserWorkouts($userId) {
        $stmt = $this->db->prepare("SELECT * FROM generated_workout WHERE User_ID = ? ORDER BY Created_At DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWorkoutById($id) {
        $stmt = $this->db->prepare("SELECT * FROM generated_workout WHERE Generate_ID = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
