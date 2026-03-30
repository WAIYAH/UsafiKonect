<?php
/**
 * UsafiKonect - Notifications API
 * AJAX polling endpoint for notification count + list
 * Used by NotificationManager in notifications.js
 */

require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = getDB();
$userId = get_user_id();
$action = sanitize_input($_GET['action'] ?? '');

switch ($action) {
    case 'count':
        $count = get_unread_notification_count($userId);
        echo json_encode(['count' => (int)$count]);
        break;
        
    case 'list':
        $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
        $stmt = $db->prepare("SELECT id, type, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$userId, $limit]);
        $notifs = $stmt->fetchAll();
        echo json_encode(['notifications' => $notifs]);
        break;
        
    case 'mark_read':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $nid = (int)($input['id'] ?? 0);
        if ($nid > 0) {
            $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([$nid, $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Invalid ID']);
        }
        break;
        
    case 'mark_all_read':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$userId]);
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
