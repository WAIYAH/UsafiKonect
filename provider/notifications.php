<?php
/**
 * UsafiKonect - Provider Notifications
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('provider');

$db = getDB();
$userId = get_user_id();

// Mark single as read
if (isset($_GET['mark_read'])) {
    $nid = (int)$_GET['mark_read'];
    $upd = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $upd->execute([$nid, $userId]);
    header('Location: ' . APP_URL . '/provider/notifications.php');
    exit;
}

// Mark all as read
if (isset($_POST['mark_all_read'])) {
    validate_csrf();
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$userId]);
    set_flash('success', 'All notifications marked as read.');
    header('Location: ' . APP_URL . '/provider/notifications.php');
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$unread = get_unread_notification_count($userId);

$countStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$countStmt->execute([$userId]);
$total = $countStmt->fetchColumn();
$pagination = paginate($total, $per_page, $page);

$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY is_read ASC, created_at DESC LIMIT {$pagination['offset']}, {$pagination['per_page']}");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$page_title = 'Notifications';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$typeConfig = [
    'booking'  => ['icon' => 'fas fa-calendar-alt', 'color' => 'text-blue-500 bg-blue-100'],
    'payment'  => ['icon' => 'fas fa-money-bill-wave', 'color' => 'text-green-500 bg-green-100'],
    'rating'   => ['icon' => 'fas fa-star', 'color' => 'text-yellow-500 bg-yellow-100'],
    'system'   => ['icon' => 'fas fa-cog', 'color' => 'text-gray-500 bg-gray-100'],
    'security' => ['icon' => 'fas fa-shield-alt', 'color' => 'text-red-500 bg-red-100'],
];
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Notifications</h1>
            <?php if ($unread > 0): ?>
            <p class="text-sm text-gray-500"><?= $unread ?> unread</p>
            <?php endif; ?>
        </div>
        <?php if ($unread > 0): ?>
        <form method="POST">
            <?= csrf_field() ?>
            <button type="submit" name="mark_all_read" value="1" class="px-4 py-2 bg-white border border-gray-200 text-sm text-gray-600 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-check-double mr-1"></i>Mark all read
            </button>
        </form>
        <?php endif; ?>
    </div>
    
    <?= render_flash() ?>
    
    <?php if (empty($notifications)): ?>
    <div class="bg-white rounded-2xl shadow-md p-12 text-center">
        <i class="fas fa-bell-slash text-gray-300 text-5xl mb-4"></i>
        <p class="text-gray-400">No notifications yet.</p>
    </div>
    <?php else: ?>
    <div class="space-y-2">
        <?php foreach ($notifications as $n):
            $cfg = $typeConfig[$n['type']] ?? $typeConfig['system'];
        ?>
        <div class="bg-white rounded-xl border <?= $n['is_read'] ? 'border-gray-100' : 'border-orange-200 border-l-4 border-l-orange-500' ?> p-4 flex items-start gap-3 hover:shadow-sm transition-shadow">
            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 <?= $cfg['color'] ?>">
                <i class="<?= $cfg['icon'] ?>"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-700 <?= $n['is_read'] ? '' : 'font-medium' ?>"><?= e($n['message']) ?></p>
                <div class="flex items-center gap-3 mt-1">
                    <span class="text-xs text-gray-400"><?= time_ago($n['created_at']) ?></span>
                    <?php if (!$n['is_read']): ?>
                    <a href="?mark_read=<?= $n['id'] ?>" class="text-xs text-orange-500 hover:underline">Mark read</a>
                    <?php endif; ?>
                    <?php if ($n['link']): ?>
                    <a href="<?= e($n['link']) ?>" class="text-xs text-blue-500 hover:underline">View</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="mt-6">
        <?= render_pagination($pagination, '?') ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
