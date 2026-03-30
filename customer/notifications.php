<?php
/**
 * UsafiKonect - Customer: Notifications
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('customer');

$db = getDB();
$userId = get_user_id();

// Mark all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    if (validate_csrf_token()) {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        set_flash('success', 'All notifications marked as read.');
        redirect(APP_URL . '/customer/notifications.php');
    }
}

// Mark single as read
if (isset($_GET['read'])) {
    $nid = (int)$_GET['read'];
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$nid, $userId]);
}

// Fetch
$countStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$countStmt->execute([$userId]);
$total = $countStmt->fetchColumn();
$pagination = paginate($total, 20);

$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$unreadCount = get_unread_notification_count($userId);

$page_title = 'Notifications';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-bell text-orange-500 mr-2"></i>Notifications</h1>
            <p class="text-gray-500 text-sm mt-1"><?= $unreadCount ?> unread</p>
        </div>
        <?php if ($unreadCount > 0): ?>
        <form method="POST">
            <?= csrf_field() ?>
            <button type="submit" name="mark_all_read" class="px-4 py-2 text-sm text-orange-500 border border-orange-300 rounded-lg hover:bg-orange-50 transition-colors">
                <i class="fas fa-check-double mr-1"></i> Mark All Read
            </button>
        </form>
        <?php endif; ?>
    </div>
    
    <?= render_flash() ?>
    
    <?php if (empty($notifications)): ?>
    <div class="bg-white rounded-2xl shadow-md p-10 text-center">
        <div class="text-5xl mb-4">🔔</div>
        <p class="text-gray-500">No notifications yet.</p>
    </div>
    <?php else: ?>
    <div class="space-y-2">
        <?php foreach ($notifications as $n): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-start gap-4 <?= !$n['is_read'] ? 'border-l-4 border-l-orange-500 bg-orange-50/30' : '' ?>">
            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 <?php
                echo match($n['type']) {
                    'booking' => 'bg-blue-100 text-blue-600',
                    'payment' => 'bg-green-100 text-green-600',
                    'rating' => 'bg-yellow-100 text-yellow-600',
                    'security' => 'bg-red-100 text-red-600',
                    'system' => 'bg-purple-100 text-purple-600',
                    default => 'bg-gray-100 text-gray-600'
                };
            ?>">
                <i class="fas <?php
                    echo match($n['type']) {
                        'booking' => 'fa-calendar-check',
                        'payment' => 'fa-credit-card',
                        'rating' => 'fa-star',
                        'security' => 'fa-shield-alt',
                        'system' => 'fa-cog',
                        default => 'fa-bell'
                    };
                ?>"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-gray-800 text-sm"><?= e($n['title']) ?></div>
                <p class="text-sm text-gray-600 mt-0.5"><?= e($n['message']) ?></p>
                <div class="text-xs text-gray-400 mt-1"><?= time_ago($n['created_at']) ?></div>
            </div>
            <?php if (!$n['is_read']): ?>
            <a href="?read=<?= $n['id'] ?>" class="text-xs text-orange-500 hover:underline flex-shrink-0">Mark read</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-6"><?= render_pagination($pagination) ?></div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
