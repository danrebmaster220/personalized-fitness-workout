<?php
class WorkoutPDF {
    public static function generate($data) {
        $dir = __DIR__ . "/../../public/pdfs";
        if (!file_exists($dir)) mkdir($dir, 0777, true);

        $filename = "workout_" . $data['Generate_ID'] . ".txt";
        $path = "$dir/$filename";

        $content = "=== PERSONALIZED WORKOUT PLAN ===\n";
        $content .= "Generated: " . $data['Created_At'] . "\n\n";
        $content .= "BMI: {$data['BMI']}\n";
        $content .= "BMR: {$data['BMR']}\n";
        $content .= "TDEE: {$data['TDEE']}\n\n";

        $content .= "--- Workout Result ---\n";
        $content .= $data['Workout_Result'] . "\n\n";

        file_put_contents($path, $content);

        // This is a placeholder TXT, replace later with real PDF generator
        return "/pdfs/$filename";
    }
}
