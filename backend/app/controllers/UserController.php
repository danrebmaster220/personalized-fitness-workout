<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/SecurityMiddleware.php';
require_once __DIR__ . '/../../app/models/Users.php';
require_once __DIR__ . '/../../app/models/WorkoutModel.php';
require_once __DIR__ . '/../../app/pdf/WorkoutPDF.php';
require_once __DIR__ . '/../../config/email.php';

class UserController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        $this->user = new User($this->db);
    }

    // GOOGLE OAUTH CALLBACK
    // Exchanges authorization code for user info and logs in / registers the user.
    public function googleCallback($code) {
        $clientId = getenv('GOOGLE_CLIENT_ID');
        $clientSecret = getenv('GOOGLE_CLIENT_SECRET');
        $redirectUri = getenv('GOOGLE_REDIRECT_URI');

        if (!$clientId || !$clientSecret || !$redirectUri) {
            return ["success" => false, "message" => "Google OAuth not configured on server."];
        }

        // Exchange code for tokens
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $post = http_build_query([
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ]);

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $post,
                'timeout' => 15
            ]
        ];

        $context = stream_context_create($opts);
        $resp = @file_get_contents($tokenUrl, false, $context);
        if ($resp === false) {
            return ["success" => false, "message" => "Failed to contact Google token endpoint."];
        }

        $tokens = json_decode($resp, true);
        if (!isset($tokens['access_token'])) {
            return ["success" => false, "message" => "Invalid token response from Google.", 'raw' => $tokens];
        }

        // Fetch user info
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer " . $tokens['access_token'] . "\r\n",
                'timeout' => 15
            ]
        ];
        $context = stream_context_create($opts);
        $ui = @file_get_contents($userInfoUrl, false, $context);
        if ($ui === false) {
            return ["success" => false, "message" => "Failed to fetch Google user info."];
        }

        $profile = json_decode($ui, true);
        $googleId = $profile['sub'] ?? null;
        $email = $profile['email'] ?? null;
        $firstName = $profile['given_name'] ?? ($profile['name'] ?? '');
        $lastName = $profile['family_name'] ?? '';
        $emailVerified = isset($profile['email_verified']) ? (bool)$profile['email_verified'] : true;

        if (!$googleId || !$email) {
            return ["success" => false, "message" => "Google did not return required profile information."];
        }

        // 1) Try to find by Google_ID
        $existing = $this->user->findByGoogleId($googleId);
        if ($existing) {
            // establish server-side session
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['user_id'] = $existing['User_ID'] ?? ($existing['User_Id'] ?? null);
            unset($existing['Password'], $existing['Verification_Token'], $existing['Reset_Token'], $existing['Reset_Expires']);
            return ["success" => true, "message" => "Login successful.", "user" => $existing];
        }

        // 2) Try to find by email
        $byEmail = $this->user->findByEmail($email);
        if ($byEmail) {
            // Link the Google ID to the existing user
            $this->user->linkGoogleToEmail($email, $googleId);
            $updated = $this->user->findByEmail($email);
            // establish server-side session
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['user_id'] = $updated['User_ID'] ?? ($updated['User_Id'] ?? null);
            unset($updated['Password'], $updated['Verification_Token'], $updated['Reset_Token'], $updated['Reset_Expires']);
            return ["success" => true, "message" => "Login successful (linked to Google).", "user" => $updated];
        }

        // 3) New user - create
        $newId = $this->user->createGoogleUser($firstName, $lastName, $email, $googleId);
        if (!$newId) {
            return ["success" => false, "message" => "Failed to create user from Google profile."];
        }

        $newUser = $this->user->findById($newId);
        // establish server-side session
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['user_id'] = $newUser['User_ID'] ?? ($newUser['User_Id'] ?? null);
        unset($newUser['Password'], $newUser['Verification_Token'], $newUser['Reset_Token'], $newUser['Reset_Expires']);
        return ["success" => true, "message" => "Account created via Google.", "user" => $newUser];
    }

    // LOGIN
    public function login($email, $password) {
        $user = $this->user->findByEmail($email);

        if (!$user) {
            return ["success" => false, "message" => "User not found."];
        }

        // Allow login even if unverified 
        if (!$user['Is_Verified']) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['user_id'] = $user['User_ID'] ?? ($user['User_Id'] ?? null);
            return [
                "success" => true,
                "message" => "Your account is not verified yet.",
                "user" => $user
            ];
        }

        // If the account uses Google login only, instruct to use Google Sign-in
        if (isset($user['Login_Method']) && $user['Login_Method'] === 'google' && empty($user['Password'])) {
            return ["success" => false, "message" => "This account is linked with Google. Please sign in with Google or link a password in profile settings."];
        }

        if (empty($user['Password']) || !password_verify($password, $user['Password'])) {
            return ["success" => false, "message" => "Incorrect password."];
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['user_id'] = $user['User_ID'] ?? ($user['User_Id'] ?? null);
        
        SecurityMiddleware::regenerateSession();
        
        unset($user['Password'], $user['Verification_Token'], $user['Reset_Token'], $user['Reset_Expires']);

        return [
            "success" => true,
            "message" => "Login successful.",
            "user" => $user
        ];
    }

    // REGISTER
    public function register($data) {

        if (!SecurityMiddleware::validateEmail($data['email'] ?? '')) {
            return ["success" => false, "message" => "Invalid email format."];
        }

        $ageCheck = SecurityMiddleware::validateRange($data['age'] ?? 0, 13, 120, "Age");
        if (!$ageCheck['valid']) return ["success" => false, "message" => $ageCheck['error']];
        
        $heightCheck = SecurityMiddleware::validateRange($data['height'] ?? 0, 50, 300, "Height");
        if (!$heightCheck['valid']) return ["success" => false, "message" => $heightCheck['error']];
        
        $weightCheck = SecurityMiddleware::validateRange($data['weight'] ?? 0, 20, 500, "Weight");
        if (!$weightCheck['valid']) return ["success" => false, "message" => $weightCheck['error']];

        if ($this->user->findByEmail($data['email'])) {
            return ["success" => false, "message" => "Email already registered."];
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $token = bin2hex(random_bytes(16));

        $success = $this->user->create(
            $data['firstName'],
            $data['lastName'],
            $data['email'],
            $hashedPassword,
            $ageCheck['value'],
            $heightCheck['value'],
            $weightCheck['value'],
            $data['gender'],
            $data['fitnessLevel'],
            $data['activityLevel'],
            $token
        );

        if ($success) {
            sendAppEmail($data['email'], 'verification', ['token' => $token]);

            return [
                "success" => true,
                "message" => "Registration successful! Please check your email to verify your account."
            ];
        }

        return ["success" => false, "message" => "Registration failed."];
    }

    // VERIFY EMAIL
    public function verify($token) {
        if ($this->user->verifyUser($token)) {
            return ["success" => true, "message" => "Account verified successfully!"];
        }
        return ["success" => false, "message" => "Invalid or expired verification token."];
    }

    // FORGOT PASSWORD
    public function forgot($email) {
        $user = $this->user->findByEmail($email);

        if (!$user)
            return ["success" => false, "message" => "Email not found."];

        $token = bin2hex(random_bytes(16));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        if (!$this->user->setResetToken($email, $token, $expires)) {
            return ["success" => false, "message" => "Failed to create reset token."];
        }

        sendAppEmail($email, "password-reset", ["token" => $token]);

        return ["success" => true, "message" => "Reset link sent to your email."];
    }

    // RESET PASSWORD
    public function reset($token, $newPassword) {
        $user = $this->user->findByResetToken($token);

        if (!$user)
            return ["success" => false, "message" => "Invalid or expired token."];

        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);

        if ($this->user->updatePassword($token, $hashed)) {
            return ["success" => true, "message" => "Password reset successful."];
        }

        return ["success" => false, "message" => "Failed to reset password."];
    }

    // GET PROFILE
    public function getUserProfile($userId) {
        $user = $this->user->findById($userId);

        if ($user) {
            unset($user['Password'], $user['Verification_Token'], $user['Reset_Token'], $user['Reset_Expires']);
            return ["success" => true, "profile" => $user];
        }

        return ["success" => false, "message" => "User not found."];
    }

    // Return current logged-in user based on PHP session
    public function me() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $uid = $_SESSION['user_id'] ?? null;
        if (!$uid) return ["success" => false, "message" => "Not authenticated"];
        $user = $this->user->findById($uid);
        if (!$user) return ["success" => false, "message" => "User not found"];
        unset($user['Password'], $user['Verification_Token'], $user['Reset_Token'], $user['Reset_Expires']);
        return ["success" => true, "user" => $user];
    }

    // Logout current user
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        // clear session
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        return ["success" => true, "message" => "Logged out"];
    }

    // NEW: UPDATE PROFILE (Fields only, JSON body)
    public function updateProfile($userId, $data) {
        $allowed = [
            "FirstName", "LastName", "Gender",
            "Height", "Weight", "Age",
            "Fitness_Level", "Activity_Level"
        ];

        $cleanData = [];
        foreach ($allowed as $key) {
            if (isset($data[$key])) {
                $cleanData[$key] = $data[$key];
            }
        }

        if (empty($cleanData)) {
            return ["success" => false, "message" => "No valid fields to update."];
        }

        if ($this->user->updateProfile($userId, $cleanData)) {
            $user = $this->user->findById($userId);
            unset($user['Password']);
            return ["success" => true, "user" => $user];
        }

        return ["success" => false, "message" => "Failed to update profile."];
    }

    // NEW: UPLOAD PROFILE IMAGE
    public function uploadImage($data, $files) {
        if (!isset($data['userId']) || !isset($files['image'])) {
            return ["success" => false, "message" => "Invalid request"];
        }

        $userId = $data['userId'];
        $image = $files['image'];

        // Security: Enforce ownership
        if (session_status() === PHP_SESSION_NONE) session_start();
        $sessionUid = $_SESSION['user_id'] ?? null;
        if (!$sessionUid || $sessionUid != $userId) {
            return ["success" => false, "message" => "Not authorized"];
        }

        // Validate upload errors
        if ($image["error"] !== UPLOAD_ERR_OK) {
            return ["success" => false, "message" => "Upload error: " . $image["error"]];
        }

        if (!SecurityMiddleware::isValidImage($image["tmp_name"])) {
            return ["success" => false, "message" => "Invalid image file. Only JPEG, PNG, GIF, and WebP allowed."];
        }

        $allowed = ["image/jpeg", "image/png", "image/jpg", "image/gif", "image/webp"];
        if (!in_array($image["type"], $allowed)) {
            return ["success" => false, "message" => "Invalid file type."];
        }

        // Enforce file size limit: 3MB
        $maxSize = 3 * 1024 * 1024; // 3MB
        if ($image["size"] > $maxSize) {
            return ["success" => false, "message" => "File too large. Maximum 3MB."];
        }

        // Create folder if not exists (inside public for web access)
        $uploadDir = __DIR__ . "/../../public/uploads/profiles/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true); // More secure permissions
        }

        // Security: Sanitize filename
        $ext = pathinfo($image["name"], PATHINFO_EXTENSION);
        $sanitizedName = SecurityMiddleware::sanitizeFilename(pathinfo($image["name"], PATHINFO_FILENAME));
        $fileName = "user_" . $userId . "_" . time() . "_" . $sanitizedName . "." . strtolower($ext);

        $filePath = $uploadDir . $fileName;
        $dbPath = "/uploads/profiles/" . $fileName;

        if (!move_uploaded_file($image["tmp_name"], $filePath)) {
            return ["success" => false, "message" => "Upload failed."];
        }

        // Set file permissions
        chmod($filePath, 0644);

        // Save to DB
        if ($this->user->uploadProfileImage($userId, $dbPath)) {
            return [
                "success" => true,
                "message" => "Profile image updated.",
                "image"   => $dbPath
            ];
        }

        return ["success" => false, "message" => "Database update failed."];
    }

    //  NEW: CHANGE PASSWORD (JSON Body)
    public function changePassword($userId, $old, $new) {
        $user = $this->user->findById($userId);

        if (!$user) return ["success" => false, "message" => "User not found"];
        
        // Special case: Google OAuth user setting up password for the first time
        if ($user['Password'] === null || $user['Password'] === '') {
            // No current password exists - allow setting a new one
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $this->user->updatePasswordById($userId, $hash);
            
            // Update login method to 'both' (can now use both Google and email login)
            if ($user['Login_Method'] === 'google') {
                $this->user->updateLoginMethod($userId, 'both');
            }
            
            return ["success" => true, "message" => "Password set successfully! You can now login with email + password."];
        }
        
        // Normal case: User has existing password, verify old one
        if (!password_verify($old, $user['Password'])) {
            return ["success" => false, "message" => "Incorrect old password"];
        }

        $hash = password_hash($new, PASSWORD_BCRYPT);
        $this->user->updatePasswordById($userId, $hash);

        return ["success" => true, "message" => "Password updated"];
    }

    // RESEND VERIFICATION EMAIL
    public function resendVerification($userId) {
        $user = $this->user->findById($userId);

        if (!$user)
            return ["success" => false, "message" => "User not found."];

        $token = bin2hex(random_bytes(16));

        if (!$this->user->updateVerificationToken($userId, $token)) {
            return ["success" => false, "message" => "Failed to generate verification token."];
        }

        // Send resend verification email with user name
        sendAppEmail(
            $user['Email'], 
            'resend-verification', 
            [
                'token' => $token,
                'name' => $user['FirstName'] ?? 'there'
            ]
        );

        return ["success" => true, "message" => "Verification email resent."];
    }

    // Download generated workout as PDF/TXT
    public function downloadWorkout($id) {
        if (!$id) return ["success" => false, "message" => "Missing id"];

        // Ensure user is authenticated and owns the workout
        if (session_status() === PHP_SESSION_NONE) session_start();
        $uid = $_SESSION['user_id'] ?? null;

        $wm = new WorkoutModel($this->db);
        $row = $wm->getWorkoutById($id);
        if (!$row) return ["success" => false, "message" => "Workout not found"];

        // Only allow owner or admin
        $isOwner = $uid && ($uid == $row['User_ID']);
        $isAdmin = false;
        if ($uid) {
            $u = $this->user->findById($uid);
            $isAdmin = ($u && (($u['Role'] ?? '') === 'admin'));
        }

        if (!$isOwner && !$isAdmin) {
            return ["success" => false, "message" => "Permission denied"];
        }

        // Prepare data for PDF generator (WorkoutPDF expects specific keys)
        $pdfData = [
            'Generate_ID' => $row['Generate_ID'],
            'Created_At' => $row['Created_At'],
            'BMI' => $row['BMI'],
            'BMR' => $row['BMR'],
            'TDEE' => $row['TDEE'],
            'Workout_Result' => $row['Workout_Result']
        ];

        $url = WorkoutPDF::generate($pdfData);

        return ["success" => true, "url" => $url];
    }

    // Stream the generated workout file to the client after auth/ownership check
    public function downloadWorkoutStream($id) {
        if (!$id) {
            header('Content-Type: application/json', true, 400);
            echo json_encode(["success" => false, "message" => "Missing id"]);
            exit();
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $uid = $_SESSION['user_id'] ?? null;
        $wm = new WorkoutModel($this->db);
        $row = $wm->getWorkoutById($id);
        if (!$row) {
            header('Content-Type: application/json', true, 404);
            echo json_encode(["success" => false, "message" => "Workout not found"]);
            exit();
        }

        // Check ownership or admin
        $isOwner = $uid && ($uid == $row['User_ID']);
        $isAdmin = false;
        if ($uid) {
            $u = $this->user->findById($uid);
            $isAdmin = ($u && (($u['Role'] ?? '') === 'admin'));
        }

        if (!$isOwner && !$isAdmin) {
            header('Content-Type: application/json', true, 403);
            echo json_encode(["success" => false, "message" => "Permission denied"]);
            exit();
        }

        // Generate the file (PDF or TXT). WorkoutPDF::generate returns a path like /pdfs/filename
        $pdfData = [
            'Generate_ID' => $row['Generate_ID'],
            'Created_At' => $row['Created_At'],
            'BMI' => $row['BMI'],
            'BMR' => $row['BMR'],
            'TDEE' => $row['TDEE'],
            'Workout_Result' => $row['Workout_Result']
        ];

        $url = WorkoutPDF::generate($pdfData);
        $filePath = realpath(__DIR__ . '/../../public' . $url);

        if (!$filePath || !file_exists($filePath)) {
            header('Content-Type: application/json', true, 500);
            echo json_encode(["success" => false, "message" => "File generation failed"]);
            exit();
        }

        // Determine content type and extension
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $mime = ($ext === 'pdf') ? 'application/pdf' : 'text/plain';

        // Stream file with protected headers
        header_remove();
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        // Stream
        readfile($filePath);
        exit();
    }

    // CHANGE EMAIL (user requests update email)
    public function changeEmail($userId, $newEmail) {
        // basic validation
        if (!$userId || !$newEmail) return ["success" => false, "message" => "Invalid parameters."];

        // ensure email not already used by another user
        $existing = $this->user->findByEmail($newEmail);
        if ($existing && $existing['User_ID'] != $userId) {
            return ["success" => false, "message" => "Email already in use."];
        }

        $token = bin2hex(random_bytes(16));

        $res = $this->user->updateEmailAndToken($userId, $newEmail, $token);
        if (is_array($res) && !$res['success']) {
            // surface DB error for easier debugging
            error_log("updateEmailAndToken failed: " . ($res['error'] ?? 'unknown'));
            return ["success" => false, "message" => "Database error: " . ($res['error'] ?? 'unknown')];
        }

        // send verification email to the new address
        $sent = sendAppEmail($newEmail, 'verification', ['token' => $token]);
        if (!$sent) {
            error_log("sendAppEmail returned false for changeEmail to: $newEmail");
            return ["success" => true, "message" => "Email updated but failed to send verification email. Please contact support."];
        }

        return ["success" => true, "message" => "Email updated. Verification sent to new address."];
    }
}
?>
