<?php
/**
 * UsafiKonect - Helper Functions
 * Authentication, flash messages, booking, formatting, pagination, email, image upload
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';

// Initialize session and security headers
init_session();
set_security_headers();

// =====================================================
// AUTHENTICATION HELPERS
// =====================================================

/**
 * Check if user is logged in
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function get_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

/**
 * Get current user role
 */
function get_user_role(): string {
    return $_SESSION['user_role'] ?? '';
}

/**
 * Get current user data from session
 */
function get_current_user(): array {
    return $_SESSION['user_data'] ?? [];
}

/**
 * Require user to be logged in, redirect to login if not
 */
function require_login(): void {
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to continue.');
        redirect(APP_URL . '/auth/login.php');
    }
}

/**
 * Require specific role, redirect if unauthorized
 */
function require_role(string ...$roles): void {
    require_login();
    if (!in_array(get_user_role(), $roles)) {
        set_flash('error', 'You do not have permission to access this page.');
        $role = get_user_role();
        redirect(APP_URL . "/{$role}/dashboard.php");
    }
}

/**
 * Set user session data after login
 */
function set_user_session(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_data'] = [
        'id' => $user['id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'role' => $user['role'],
        'estate' => $user['estate'],
        'profile_image' => $user['profile_image'],
    ];
    $_SESSION['last_activity'] = time();
    $_SESSION['created_at'] = time();
}

/**
 * Destroy user session (logout)
 */
function destroy_session(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// =====================================================
// FLASH MESSAGES
// =====================================================

/**
 * Set a flash message
 */
function set_flash(string $type, string $message): void {
    init_session();
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get and clear flash messages
 */
function get_flash(): array {
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Render flash messages as HTML
 */
function render_flash(): string {
    $flash = get_flash();
    $html = '';
    $icons = [
        'success' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
        'error' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
        'warning' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
        'info' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>',
    ];
    $colors = [
        'success' => 'bg-green-50 border-green-400 text-green-800',
        'error' => 'bg-red-50 border-red-400 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-400 text-yellow-800',
        'info' => 'bg-blue-50 border-blue-400 text-blue-800',
    ];
    
    foreach ($flash as $type => $message) {
        $color = $colors[$type] ?? $colors['info'];
        $icon = $icons[$type] ?? $icons['info'];
        $html .= '<div class="flash-message border-l-4 p-4 mb-4 rounded-r-lg ' . $color . '" role="alert">
            <div class="flex items-center">
                <div class="flex-shrink-0">' . $icon . '</div>
                <div class="ml-3"><p class="text-sm font-medium">' . e($message) . '</p></div>
                <div class="ml-auto pl-3">
                    <button type="button" onclick="this.closest(\'.flash-message\').remove()" class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2 hover:opacity-75">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>
            </div>
        </div>';
    }
    return $html;
}

// =====================================================
// REDIRECT
// =====================================================

/**
 * Redirect to URL
 */
function redirect(string $url): void {
    header("Location: {$url}");
    exit;
}

// =====================================================
// FORMATTING HELPERS
// =====================================================

/**
 * Format amount as KES currency
 */
function format_currency(float $amount): string {
    return 'KES ' . number_format($amount, 2);
}

/**
 * Format date for display
 */
function format_date(string $date, string $format = 'M j, Y'): string {
    return date($format, strtotime($date));
}

/**
 * Format time for display
 */
function format_time(string $time): string {
    return date('g:i A', strtotime($time));
}

/**
 * Time ago helper (e.g., "2 hours ago")
 */
function time_ago(string $datetime): string {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

// =====================================================
// BOOKING HELPERS
// =====================================================

/**
 * Generate unique booking number: USK-YYYYMMDD-XXXX
 */
function generate_booking_number(): string {
    $db = getDB();
    $date = date('Ymd');
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $count = (int)$stmt->fetchColumn() + 1;
    
    return 'USK-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

/**
 * Get booking status badge HTML
 */
function booking_status_badge(string $status): string {
    $badges = [
        'pending'    => 'bg-yellow-100 text-yellow-800',
        'confirmed'  => 'bg-blue-100 text-blue-800',
        'processing' => 'bg-purple-100 text-purple-800',
        'ready'      => 'bg-indigo-100 text-indigo-800',
        'delivered'  => 'bg-green-100 text-green-800',
        'cancelled'  => 'bg-red-100 text-red-800',
    ];
    $class = $badges[$status] ?? 'bg-gray-100 text-gray-800';
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $class . '">' . ucfirst(e($status)) . '</span>';
}

/**
 * Get payment status badge HTML
 */
function payment_status_badge(string $status): string {
    $badges = [
        'pending'  => 'bg-yellow-100 text-yellow-800',
        'paid'     => 'bg-green-100 text-green-800',
        'refunded' => 'bg-blue-100 text-blue-800',
        'failed'   => 'bg-red-100 text-red-800',
    ];
    $class = $badges[$status] ?? 'bg-gray-100 text-gray-800';
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $class . '">' . ucfirst(e($status)) . '</span>';
}

// =====================================================
// PAGINATION
// =====================================================

/**
 * Get pagination data
 */
function paginate(int $total, int $perPage = 10, int $currentPage = 1): array {
    $totalPages = max(1, (int)ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
    ];
}

/**
 * Render pagination HTML
 */
function render_pagination(array $pagination, string $baseUrl = '?'): string {
    if ($pagination['total_pages'] <= 1) return '';
    
    $separator = str_contains($baseUrl, '?') ? '&' : '?';
    $html = '<nav class="flex items-center justify-between mt-6"><div class="flex items-center gap-2">';
    
    // Previous
    if ($pagination['has_prev']) {
        $html .= '<a href="' . $baseUrl . $separator . 'page=' . ($pagination['current_page'] - 1) . '" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">&laquo; Previous</a>';
    }
    
    // Page numbers
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i === $pagination['current_page']) {
            $html .= '<span class="px-3 py-2 text-sm font-medium text-white bg-orange-500 border border-orange-500 rounded-md">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $baseUrl . $separator . 'page=' . $i . '" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">' . $i . '</a>';
        }
    }
    
    // Next
    if ($pagination['has_next']) {
        $html .= '<a href="' . $baseUrl . $separator . 'page=' . ($pagination['current_page'] + 1) . '" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Next &raquo;</a>';
    }
    
    $html .= '</div><div class="text-sm text-gray-500">Page ' . $pagination['current_page'] . ' of ' . $pagination['total_pages'] . ' (' . $pagination['total'] . ' results)</div></nav>';
    
    return $html;
}

// =====================================================
// IMAGE UPLOAD
// =====================================================

/**
 * Handle profile image upload
 * Returns filename on success, false on failure
 */
function upload_image(array $file, string $subDir = ''): string|false {
    $validation = validate_upload($file);
    if (!$validation['success']) {
        set_flash('error', $validation['error']);
        return false;
    }
    
    $targetDir = UPLOAD_PATH . ($subDir ? $subDir . DIRECTORY_SEPARATOR : '');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $filename = $validation['filename'];
    $targetPath = $targetDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }
    
    set_flash('error', 'Failed to save uploaded file.');
    return false;
}

// =====================================================
// NOTIFICATION HELPERS
// =====================================================

/**
 * Create a notification for a user
 */
function create_notification(int $userId, string $type, string $message, ?string $link = null): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $type, $message, $link]);
    } catch (PDOException $e) {
        error_log("Failed to create notification: " . $e->getMessage());
    }
}

/**
 * Get unread notification count for a user
 */
function get_unread_notification_count(int $userId): int {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// =====================================================
// WALLET HELPERS
// =====================================================

/**
 * Get user wallet balance
 */
function get_wallet_balance(int $userId): float {
    $db = getDB();
    $stmt = $db->prepare("SELECT balance_after FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 1");
    $stmt->execute([$userId]);
    $balance = $stmt->fetchColumn();
    return $balance !== false ? (float)$balance : 0.00;
}

/**
 * Add wallet transaction
 */
function add_wallet_transaction(int $userId, string $type, float $amount, string $description, ?string $reference = null, ?string $mpesaId = null): bool {
    $db = getDB();
    $currentBalance = get_wallet_balance($userId);
    $newBalance = $currentBalance + $amount;
    
    if ($newBalance < 0) {
        return false;
    }
    
    $stmt = $db->prepare("INSERT INTO wallet_transactions (user_id, type, amount, balance_after, reference, mpesa_transaction_id, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$userId, $type, $amount, $newBalance, $reference, $mpesaId, $description]);
}

// =====================================================
// LOYALTY HELPERS
// =====================================================

/**
 * Update loyalty points after a completed booking
 */
function update_loyalty_points(int $userId, int $points = 10, string $description = ''): array {
    $db = getDB();
    
    // Get or create loyalty record
    $stmt = $db->prepare("SELECT * FROM loyalty_points WHERE user_id = ?");
    $stmt->execute([$userId]);
    $loyalty = $stmt->fetch();
    
    if (!$loyalty) {
        $stmt = $db->prepare("INSERT INTO loyalty_points (user_id, points, total_bookings) VALUES (?, ?, 1)");
        $stmt->execute([$userId, $points]);
        return ['points' => $points, 'total_bookings' => 1, 'free_earned' => false];
    }
    
    $newPoints = $loyalty['points'] + $points;
    $newTotal = $loyalty['total_bookings'] + 1;
    $freeEarned = ($newTotal % 5 === 0);
    $newFreeBookings = $loyalty['free_bookings_earned'] + ($freeEarned ? 1 : 0);
    
    $stmt = $db->prepare("UPDATE loyalty_points SET points = ?, total_bookings = ?, free_bookings_earned = ? WHERE user_id = ?");
    $stmt->execute([$newPoints, $newTotal, $newFreeBookings, $userId]);
    
    return ['points' => $newPoints, 'total_bookings' => $newTotal, 'free_earned' => $freeEarned];
}

/**
 * Check if user has free bookings available
 */
function has_free_booking(int $userId): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT (free_bookings_earned - free_bookings_used) as available FROM loyalty_points WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetchColumn();
    return $result !== false && (int)$result > 0;
}

// =====================================================
// SITE SETTINGS
// =====================================================

/**
 * Get a site setting value
 */
function get_setting(string $key, string $default = ''): string {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Update a site setting
 */
function update_setting(string $key, string $value): bool {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

// =====================================================
// EMAIL HELPERS (PHPMailer wrapper)
// =====================================================

/**
 * Send email using PHPMailer
 */
function send_email(string $to, string $subject, string $body, string $recipientName = ''): bool {
    // Check if PHPMailer is available
    $autoloadPath = BASE_PATH . 'vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        error_log("PHPMailer not installed. Run: composer install");
        // In test mode, just log the email
        error_log("EMAIL TO: {$to} | SUBJECT: {$subject} | BODY: " . strip_tags($body));
        return true; // Return true in dev mode so flow continues
    }
    
    require_once $autoloadPath;
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = get_setting('smtp_host', 'smtp.gmail.com');
        $mail->SMTPAuth = true;
        $mail->Username = get_setting('smtp_username', '');
        $mail->Password = get_setting('smtp_password', '');
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int)get_setting('smtp_port', '587');
        
        $mail->setFrom(get_setting('site_email', 'noreply@usafikonect.co.ke'), APP_NAME);
        $mail->addAddress($to, $recipientName);
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = get_email_template($subject, $body);
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Wrap email body in HTML template
 */
function get_email_template(string $title, string $content): string {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;background:#FEF3C7;font-family:Arial,sans-serif;">
    <div style="max-width:600px;margin:20px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.1);">
        <div style="background:linear-gradient(135deg,#F97316,#EA580C);padding:30px;text-align:center;">
            <h1 style="color:#fff;margin:0;font-size:24px;">🧺 UsafiKonect</h1>
            <p style="color:rgba(255,255,255,0.9);margin:5px 0 0;font-size:14px;">Your Trusted Laundry Partner</p>
        </div>
        <div style="padding:30px;">
            <h2 style="color:#1E3A8A;margin-top:0;">' . htmlspecialchars($title) . '</h2>
            ' . $content . '
        </div>
        <div style="background:#f8f9fa;padding:20px;text-align:center;border-top:1px solid #e9ecef;">
            <p style="color:#6b7280;font-size:12px;margin:0;">© ' . date('Y') . ' UsafiKonect. Nairobi, Kenya.</p>
            <p style="color:#6b7280;font-size:12px;margin:5px 0 0;">Asante for choosing UsafiKonect!</p>
        </div>
    </div></body></html>';
}

// =====================================================
// ERROR HANDLER
// =====================================================

/**
 * Custom error handler - logs to file
 */
function custom_error_handler(int $errno, string $errstr, string $errfile, int $errline): bool {
    $logMessage = date('[Y-m-d H:i:s]') . " [{$errno}] {$errstr} in {$errfile} on line {$errline}" . PHP_EOL;
    error_log($logMessage, 3, LOG_PATH . 'error.log');
    
    if (APP_DEBUG) {
        return false; // Let PHP handle it in debug mode
    }
    return true;
}

set_error_handler('custom_error_handler');

/**
 * Get provider by user ID
 */
function get_provider_details(int $userId): array|false {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.*, pd.* 
        FROM users u 
        JOIN provider_details pd ON u.id = pd.user_id 
        WHERE u.id = ? AND u.role = 'provider'
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Get the star rating HTML
 */
function star_rating(float $rating, bool $showNumber = true): string {
    $html = '<div class="flex items-center gap-1">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($rating)) {
            $html .= '<svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>';
        } elseif ($i - 0.5 <= $rating) {
            $html .= '<svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>';
        } else {
            $html .= '<svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>';
        }
    }
    if ($showNumber) {
        $html .= '<span class="text-sm text-gray-600 ml-1">' . number_format($rating, 1) . '</span>';
    }
    $html .= '</div>';
    return $html;
}
