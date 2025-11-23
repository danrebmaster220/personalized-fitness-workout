<?php
use Dompdf\Dompdf;

class WorkoutPDF {
    public static function generate($data) {
        $dir = __DIR__ . "/../../public/pdfs";
        if (!file_exists($dir)) mkdir($dir, 0777, true);

        $id = $data['Generate_ID'] ?? time();
        $pdfFilename = "workout_" . $id . ".pdf";
        $pdfPath = "$dir/$pdfFilename";

        // Prepare workout result content (accept JSON string or array)
        $workoutRaw = $data['Workout_Result'] ?? '';
        if (is_string($workoutRaw)) {
            $decoded = json_decode($workoutRaw, true);
            $workout = $decoded === null ? $workoutRaw : $decoded;
        } else {
            $workout = $workoutRaw;
        }

        // Prepare meal result content
        $mealRaw = $data['Meal_Result'] ?? '';
        if (is_string($mealRaw)) {
            $decoded = json_decode($mealRaw, true);
            $meal = $decoded === null ? $mealRaw : $decoded;
        } else {
            $meal = $mealRaw;
        }

        // Prepare body condition result content
        $bodyConditionRaw = $data['Body_Condition_Result'] ?? '';
        if (is_string($bodyConditionRaw)) {
            $decoded = json_decode($bodyConditionRaw, true);
            $bodyCondition = $decoded === null ? $bodyConditionRaw : $decoded;
        } else {
            $bodyCondition = $bodyConditionRaw;
        }

        // Build HTML for PDF with header/footer and branding
        $title = "FitSync - Personalized Workout Plan";
        $created = $data['Created_At'] ?? date("c");
        $bmi = $data['BMI'] ?? '-';
        $bmr = $data['BMR'] ?? '-';
        $tdee = $data['TDEE'] ?? '-';

        // No logo needed - using text branding
        $html = "<html><head><meta charset=\"utf-8\">";
        $html .= "<style>";
        $html .= "@page { margin: 100px 50px 80px 50px; }";
        $html .= "body{font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#222} ";
        $html .= ".pdf-header{position:fixed;left:0;right:0;top:-80px;height:80px;border-bottom:1px solid #e6e6e6;padding:10px 20px;}";
        $html .= ".pdf-header .logo{float:left;width:60px;height:60px;margin-right:12px;}";
        $html .= ".pdf-header .meta{float:right;text-align:right;font-size:11px;color:#555;}";
        $html .= ".pdf-title{margin:0;padding-top:6px;font-size:16px;}";
        $html .= ".pdf-footer{position:fixed;left:0;right:0;bottom:-50px;height:40px;border-top:1px solid #e6e6e6;text-align:center;font-size:11px;color:#666;padding-top:8px;}";
        $html .= "h1{font-size:16px;margin:0 0 6px 0} h2{font-size:14px;margin-top:12px} h3{font-size:12px;margin:8px 0 4px 0} pre{background:#f7f7f7;padding:8px;border-radius:4px} ul{margin:6px 0;padding-left:16px} li{margin-bottom:4px}";
        $html .= ".health-banner{background:#667eea;color:#fff;padding:12px 16px;border-radius:8px;margin-bottom:14px;}";
        $html .= ".health-grid{display:table;width:100%;margin-top:8px;}";
        $html .= ".health-item{display:table-cell;padding-right:20px;vertical-align:top;}";
        $html .= ".health-label{font-size:10px;opacity:0.9;margin-bottom:2px;}";
        $html .= ".health-value{font-size:18px;font-weight:bold;}";
        $html .= ".health-assess{margin-top:8px;font-size:11px;line-height:1.4;border-top:1px solid rgba(255,255,255,0.3);padding-top:8px;}";
        $html .= "</style></head><body>";

        // Header
        $html .= "<div class=\"pdf-header\">";
        // FitSync branding as text instead of logo
        $html .= "<div style=\"float:left;margin-right:12px;\">";
        $html .= "<div style=\"font-size:24px;font-weight:bold;color:#667eea;\">FitSync</div>";
        $html .= "</div>";
        $html .= "<div class=\"pdf-title\"><h1>" . htmlspecialchars($title) . "</h1></div>";
        $html .= "<div class=\"meta\"><div><strong>Generated:</strong> " . htmlspecialchars($created) . "</div>";
        $html .= "</div><div style=\"clear:both\"></div></div>";

        // Footer placeholder (page numbers added via canvas)
        $html .= "<div class=\"pdf-footer\">" . htmlspecialchars('FitSync - Your Personalized Fitness Companion') . "</div>";

        // Main content
        $html .= "<div class=\"pdf-body\">";
        
        // Health Assessment Banner
        $html .= "<div class=\"health-banner\">";
        $html .= "<div style=\"font-size:14px;font-weight:bold;margin-bottom:8px;\">📊 Your Health Assessment</div>";
        $html .= "<div class=\"health-grid\">";
        $html .= "<div class=\"health-item\"><div class=\"health-label\">BMI</div><div class=\"health-value\">$bmi</div></div>";
        $html .= "<div class=\"health-item\"><div class=\"health-label\">BMR</div><div class=\"health-value\">$bmr <span style=\"font-size:11px;\">kcal</span></div></div>";
        $html .= "<div class=\"health-item\"><div class=\"health-label\">TDEE</div><div class=\"health-value\">$tdee <span style=\"font-size:11px;\">kcal</span></div></div>";
        
        // Add category and assessment if available
        if (is_array($bodyCondition)) {
            if (!empty($bodyCondition['category'])) {
                $html .= "<div class=\"health-item\"><div class=\"health-label\">Category</div><div class=\"health-value\" style=\"font-size:14px;\">" . htmlspecialchars($bodyCondition['category']) . "</div></div>";
            }
        }
        
        $html .= "</div>"; // end health-grid
        
        // Add assessment text if available
        if (is_array($bodyCondition) && !empty($bodyCondition['assessment'])) {
            $html .= "<div class=\"health-assess\">" . htmlspecialchars($bodyCondition['assessment']) . "</div>";
        }
        
        $html .= "</div>"; // end health-banner

        $html .= "<h2>Workout Result</h2>";

        if (is_array($workout)) {
            // Render structured workout as HTML
            foreach ($workout as $section => $content) {
                $html .= "<h3>" . htmlspecialchars(ucfirst($section)) . "</h3>";
                if (is_array($content)) {
                    $html .= "<ul>";
                    foreach ($content as $item) {
                        if (is_array($item)) {
                            $name = $item['name'] ?? ($item['day'] ?? json_encode($item));
                            $html .= "<li><strong>" . htmlspecialchars($name) . "</strong>";
                            
                            // Show focus if available
                            if (!empty($item['focus'])) {
                                $html .= " <em style=\"color:#667eea;\">(" . htmlspecialchars($item['focus']) . ")</em>";
                            }
                            
                            if (!empty($item['exercises']) && is_array($item['exercises'])) {
                                $html .= "<ul>";
                                foreach ($item['exercises'] as $ex) {
                                    $html .= "<li>" . htmlspecialchars(($ex['name'] ?? json_encode($ex))) . " ";
                                    if (!empty($ex['sets']) || !empty($ex['reps'])) {
                                        $html .= "<em>" . htmlspecialchars(trim((($ex['sets'] ?? '') . ' sets × ' . ($ex['reps'] ?? '')))) . "</em>";
                                    }
                                    if (!empty($ex['rest'])) {
                                        $html .= " <span style=\"color:#666;font-size:10px;\">Rest: " . htmlspecialchars($ex['rest']) . "</span>";
                                    }
                                    $html .= "</li>";
                                }
                                $html .= "</ul>";
                            }
                            
                            // Show notes if available
                            if (!empty($item['notes'])) {
                                $html .= "<p style=\"color:#666;font-size:11px;margin:4px 0 8px 0;\">" . htmlspecialchars($item['notes']) . "</p>";
                            }
                            
                            $html .= "</li>";
                        } else {
                            $html .= "<li>" . htmlspecialchars($item) . "</li>";
                        }
                    }
                    $html .= "</ul>";
                } else if ($section === 'notes' && is_string($content)) {
                    // Display notes as a highlighted section
                    $html .= "<div style=\"background:#fffbeb;padding:10px;border-left:3px solid #f59e0b;\">";
                    $html .= htmlspecialchars($content);
                    $html .= "</div>";
                } else {
                    $html .= "<pre>" . htmlspecialchars(print_r($content, true)) . "</pre>";
                }
            }
        } else {
            $html .= "<pre>" . htmlspecialchars($workout) . "</pre>";
        }

        // ========== MEAL PLAN SECTION ==========
        $html .= "<h2 style=\"page-break-before:auto;margin-top:20px;\">Meal Plan</h2>";
        
        if (is_array($meal)) {
            // Show daily calories if available
            if (isset($meal['dailyCalories'])) {
                $html .= "<div style=\"background:#f0fdf4;padding:8px;border-radius:4px;margin-bottom:10px;\">";
                $html .= "<strong>Daily Target:</strong> " . htmlspecialchars($meal['dailyCalories']) . " kcal";
                $html .= "</div>";
            }
            
            // Show macros if available
            if (isset($meal['macros']) && is_array($meal['macros'])) {
                $html .= "<div style=\"margin-bottom:10px;\"><strong>Macros:</strong> ";
                $macrosList = [];
                foreach ($meal['macros'] as $key => $value) {
                    $macrosList[] = htmlspecialchars(ucfirst($key) . ': ' . $value);
                }
                $html .= implode(' &nbsp;|&nbsp; ', $macrosList);
                $html .= "</div>";
            }
            
            // Render meals
            if (isset($meal['meals']) && is_array($meal['meals'])) {
                $html .= "<ul>";
                foreach ($meal['meals'] as $mealItem) {
                    if (is_array($mealItem)) {
                        $mealName = $mealItem['meal'] ?? 'Meal';
                        $calories = isset($mealItem['calories']) ? " (" . $mealItem['calories'] . " cal)" : "";
                        $html .= "<li><strong>" . htmlspecialchars($mealName . $calories) . "</strong>";
                        
                        // Show foods
                        if (isset($mealItem['foods']) && is_array($mealItem['foods'])) {
                            $html .= "<ul>";
                            foreach ($mealItem['foods'] as $food) {
                                $foodName = is_string($food) ? $food : ($food['name'] ?? json_encode($food));
                                $html .= "<li>" . htmlspecialchars($foodName) . "</li>";
                            }
                            $html .= "</ul>";
                        }
                        
                        // Show items (alternative format)
                        if (isset($mealItem['items']) && is_array($mealItem['items'])) {
                            $html .= "<ul>";
                            foreach ($mealItem['items'] as $item) {
                                $html .= "<li>" . htmlspecialchars($item) . "</li>";
                            }
                            $html .= "</ul>";
                        }
                        
                        // Show description
                        if (isset($mealItem['description'])) {
                            $html .= "<p style=\"color:#666;font-size:11px;margin:4px 0;\">" . htmlspecialchars($mealItem['description']) . "</p>";
                        }
                        
                        $html .= "</li>";
                    } else {
                        $html .= "<li>" . htmlspecialchars($mealItem) . "</li>";
                    }
                }
                $html .= "</ul>";
            }
        } else {
            $html .= "<p style=\"color:#666;\">No meal plan available.</p>";
        }

        // ========== BODY CONDITION SECTION ==========
        $html .= "<h2 style=\"page-break-before:auto;margin-top:20px;\">Body Condition & Recommendations</h2>";
        
        if (is_array($bodyCondition)) {
            if (isset($bodyCondition['category'])) {
                $html .= "<div style=\"margin-bottom:8px;\"><strong>Category:</strong> " . htmlspecialchars($bodyCondition['category']) . "</div>";
            }
            
            if (isset($bodyCondition['assessment'])) {
                $html .= "<div style=\"background:#f9fafb;padding:10px;border-left:3px solid #667eea;margin-bottom:10px;\">";
                $html .= htmlspecialchars($bodyCondition['assessment']);
                $html .= "</div>";
            }
            
            if (isset($bodyCondition['recommendations']) && is_array($bodyCondition['recommendations'])) {
                $html .= "<h3>Recommendations</h3>";
                $html .= "<ul>";
                foreach ($bodyCondition['recommendations'] as $rec) {
                    $html .= "<li>" . htmlspecialchars($rec) . "</li>";
                }
                $html .= "</ul>";
            }
        } else {
            $html .= "<p style=\"color:#666;\">No body condition information available.</p>";
        }

        $html .= "</div>"; // end pdf-body
        $html .= "</body></html>";

        // Try to generate PDF via dompdf; if not available, fall back to text file
        try {
            // Ensure autoload is present
            $autoload = __DIR__ . '/../../vendor/autoload.php';
            if (file_exists($autoload)) require_once $autoload;

            if (class_exists('\Dompdf\\Dompdf')) {
                $dompdf = new Dompdf();
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->loadHtml($html);
                $dompdf->render();

                // Add page numbers via canvas if available
                try {
                    $canvas = $dompdf->get_canvas();
                    if ($canvas) {
                        $font = $dompdf->getFontMetrics()->get_font('Helvetica', 'normal');
                        $text = 'Page {PAGE_NUM} of {PAGE_COUNT}';
                        $w = $canvas->get_width();
                        $h = $canvas->get_height();
                        // place page number centered at bottom
                        $canvas->page_text($w/2 - 40, $h - 30, $text, $font, 10, array(0,0,0));
                    }
                } catch (Throwable $e) {
                    // ignore canvas/page number errors
                }

                $output = $dompdf->output();
                file_put_contents($pdfPath, $output);

                return "/pdfs/$pdfFilename";
            }
        } catch (Exception $e) {
            // ignore and fall back
        }

        // Fallback: write a simple TXT file with the same naming pattern
        $txtFilename = "workout_" . $id . ".txt";
        $txtPath = "$dir/$txtFilename";
        $content = "=== PERSONALIZED WORKOUT PLAN ===\n";
        $content .= "Generated: " . $created . "\n\n";
        $content .= "BMI: {$bmi}\n";
        $content .= "BMR: {$bmr}\n";
        $content .= "TDEE: {$tdee}\n\n";
        $content .= "--- Workout Result ---\n";
        if (is_array($workout)) {
            $content .= print_r($workout, true) . "\n\n";
        } else {
            $content .= $workout . "\n\n";
        }
        file_put_contents($txtPath, $content);
        return "/pdfs/$txtFilename";
    }
}
