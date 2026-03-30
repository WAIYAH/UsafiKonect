<?php
/**
 * UsafiKonect - Security Functions
 * CSRF protection, sanitization, rate limiting, session management
 */

/**
 * Start secure session with proper settings
 */
function init_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.gc_maxlifetime', '3600');
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', '1');
        }
        
        session_start();
        
        // Session timeout - 1 hour of inactivity
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
            session_unset();
            session_destroy();
            session_start();
        }
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID periodically (every 30 minutes)
        if (!isset($_SESSION['created_at'])) {
            $_SESSION['created_at'] = time();
        } elseif (time() - $_SESSION['created_at'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created_at'] = time();
        }
    }
}

/**
 * Generate CSRF token and store in session
 */
function generate_csrf_token(): string {
    init_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF hidden input field
 */
function csrf_field(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate CSRF token from POST data
 */
function validate_csrf_token(): bool {
    init_session();
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    $valid = hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    // Regenerate token after validation
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $valid;
}

/**
 * Sanitize input - remove tags and trim
 */
function sanitize_input(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize output for HTML display
 */
function sanitize_output(?string $data): string {
    if ($data === null) return '';
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Alias for sanitize_output
 */
function e(?string $data): string {
    return sanitize_output($data);
}

/**
 * Check rate limiting for login attempts
 * Max 5 attempts per 15 minutes per IP
 */
function check_rate_limit(string $type = 'login', int $maxAttempts = 5, int $windowMinutes = 15): bool {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $email = sanitize_input($_POST['email'] ?? '');
    
    // Clean old attempts
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)");
    $stmt->execute([$windowMinutes]);
    
    // Count recent attempts
    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
    $stmt->execute([$ip, $windowMinutes]);
    $count = (int)$stmt->fetchColumn();
    
    return $count < $maxAttempts;
}

/**
 * Record a login attempt
 */
function record_login_attempt(): void {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $email = sanitize_input($_POST['email'] ?? '');
    
    $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, email, attempted_at) VALUES (?, ?, NOW())");
    $stmt->execute([$ip, $email]);
}

/**
 * Clear login attempts for an IP after successful login
 */
function clear_login_attempts(): void {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt->execute([$ip]);
}

/**
 * Check booking rate limit - max 10 bookings per hour per user
 */
function check_booking_rate_limit(int $userId): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([$userId]);
    $count = (int)$stmt->fetchColumn();
    
    return $count < 10;
}

/**
 * Validate file upload (images only)
 * Returns ['success' => bool, 'error' => string, 'filename' => string]
 */
function validate_upload(array $file, int $maxSizeMB = 2): array {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server limit.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        ];
        return ['success' => false, 'error' => $errors[$file['error']] ?? 'Upload error.', 'filename' => ''];
    }
    
    $maxSize = $maxSizeMB * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => "File must be under {$maxSizeMB}MB.", 'filename' => ''];
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Only JPG, PNG, and WebP images allowed.', 'filename' => ''];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        return ['success' => false, 'error' => 'Invalid file extension.', 'filename' => ''];
    }
    
    // Generate unique filename
    $newFilename = uniqid('img_', true) . '.' . $ext;
    
    return ['success' => true, 'error' => '', 'filename' => $newFilename];
}

/**
 * Set security headers
 */
function set_security_headers(): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // HTTPS redirect in production
    if (!APP_DEBUG && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
        $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirect, true, 301);
        exit;
    }
}

/**
 * Check if maintenance mode is active
 */
function check_maintenance_mode(): void {
    if (!is_logged_in() || get_user_role() !== 'admin') {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'maintenance_mode'");
            $stmt->execute();
            $mode = $stmt->fetchColumn();
            
            if ($mode === '1') {
                $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'maintenance_message'");
                $stmt->execute();
                $message = $stmt->fetchColumn() ?: 'We are currently performing scheduled maintenance.';
                
                http_response_code(503);
                include BASE_PATH . 'includes/maintenance.php';
                exit;
            }
        } catch (PDOException $e) {
            // If DB is down, don't block the maintenance check
        }
    }
}
