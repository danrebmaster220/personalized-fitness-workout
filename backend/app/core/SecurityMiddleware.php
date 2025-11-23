<?php
/**
 * Security Middleware
 * Provides CSRF protection, rate limiting, and security utilities
 */

class SecurityMiddleware {
    
    /**
     * Generate and store CSRF token in session
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token from request
     */
    public static function validateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        
        // Check header first, then POST body
        $requestToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
        
        if (!$sessionToken || !$requestToken) {
            return false;
        }
        
        return hash_equals($sessionToken, $requestToken);
    }
    
    /**
     * Simple IP-based rate limiter
     * @param string $key Unique identifier for the rate limit (e.g., "login", "api")
     * @param int $maxRequests Maximum requests allowed
     * @param int $windowSeconds Time window in seconds
     * @return bool True if within limits, false if exceeded
     */
    public static function checkRateLimit($key, $maxRequests = 10, $windowSeconds = 60) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateLimitKey = "rate_limit_{$key}_{$ip}";
        
        if (!isset($_SESSION[$rateLimitKey])) {
            $_SESSION[$rateLimitKey] = [
                'count' => 1,
                'start_time' => time()
            ];
            return true;
        }
        
        $data = $_SESSION[$rateLimitKey];
        $elapsed = time() - $data['start_time'];
        
        // Reset if window expired
        if ($elapsed > $windowSeconds) {
            $_SESSION[$rateLimitKey] = [
                'count' => 1,
                'start_time' => time()
            ];
            return true;
        }
        
        // Increment count
        $data['count']++;
        $_SESSION[$rateLimitKey] = $data;
        
        return $data['count'] <= $maxRequests;
    }
    
    /**
     * Validate email format
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Sanitize filename for safe storage
     */
    public static function sanitizeFilename($filename) {
        // Remove any path traversal attempts
        $filename = basename($filename);
        // Remove special characters except dots and dashes
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        // Prevent double extensions (e.g., .php.jpg)
        $filename = preg_replace('/\.+/', '.', $filename);
        return $filename;
    }
    
    /**
     * Validate numeric range
     */
    public static function validateRange($value, $min, $max, $fieldName = "Value") {
        if (!is_numeric($value)) {
            return ["valid" => false, "error" => "$fieldName must be numeric"];
        }
        $value = floatval($value);
        if ($value < $min || $value > $max) {
            return ["valid" => false, "error" => "$fieldName must be between $min and $max"];
        }
        return ["valid" => true, "value" => $value];
    }
    
    /**
     * Verify file is a real image (magic bytes check)
     */
    public static function isValidImage($filePath) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($mimeType, $allowedMimes);
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin($db) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $uid = $_SESSION['user_id'] ?? null;
        if (!$uid) return false;
        
        require_once __DIR__ . '/../models/Users.php';
        $userModel = new User($db);
        $user = $userModel->findById($uid);
        
        return $user && (($user['Role'] ?? '') === 'admin');
    }
    
    /**
     * Require admin role (call at start of admin endpoints)
     */
    public static function requireAdmin($db) {
        if (!self::isAdmin($db)) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Admin access required"]);
            exit();
        }
    }
    
    /**
     * Regenerate session ID (call after login/privilege change)
     */
    public static function regenerateSession() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_regenerate_id(true);
    }
    
    /**
     * Set secure headers
     */
    public static function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        // Consider adding CSP header in production
        // header("Content-Security-Policy: default-src 'self'");
    }
}
