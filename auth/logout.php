<?php
/**
 * UsafiKonect - Logout
 * Destroys session, clears remember-me token, redirects to home
 */

require_once __DIR__ . '/../config/functions.php';

if (is_logged_in()) {
    try {
        $db = getDB();
        $userId = get_user_id();
        
        // Clear remember-me token from DB
        $stmt = $db->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Remove user sessions
        $stmt = $db->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->execute([$userId]);
    } catch (Exception $e) {
        error_log('Logout DB error: ' . $e->getMessage());
    }
    
    // Clear remember cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    
    // Destroy session
    destroy_session();
}

set_flash('success', 'You have been logged out successfully. Kwaheri! 👋');
redirect(APP_URL . '/auth/login.php');
