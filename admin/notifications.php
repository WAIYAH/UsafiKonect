<?php
/**
 * UsafiKonect - Admin Notifications
 * View admin notifications and send broadcast messages
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('admin');

$db = getDB();
$adminId = get_user_id();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $action = sanitize_input($_POST['action'] ?? '');

    if ($action === 'mark_all_read') {
        $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$adminId]);
        set_flash('success', 'All notifications marked as read.');
    } elseif ($action === 'broadcast') {
        $message = sanitize_input($_POST['message'] ?? '');
        if (mb_strlen($message) > 500) $message = mb_substr($message, 0, 500);
        $target = sanitize_input($_POST['target'] ?? 'all');

        if (empty($message)) {
            set_flash('error', 'Message cannot be empty.');
        } else {
            $validTargets = ['all', 'customer', 'provider'];
            if (!in_array($target, $validTargets)) $target = 'all';

            $targetWhere = $target === 'all' ? "role IN ('customer','provider')" : "role = ?";
            $targetParams = $target === 'all' ? [] : [$target];

            $users = $db->prepare("SELECT id FROM users WHERE $targetWhere AND is_active = 1");
            $users->execute($targetParams);
            $userIds = $users->fetchAll(PDO::FETCH_COLUMN);

            $sent = 0;
            foreach ($userIds as $uid) {
                create_notification($uid, 'system', $message, null);
                $sent++;
            }
            set_flash('success', "Broadcast sent to $sent users.");
        }
    } elseif ($action === 'delete_read') {
        $db->prepare("DELETE FROM notifications WHERE user_id = ? AND is_read = 1")->execute([$adminId]);
        set_flash('success', 'Read notifications cleared.');
    }
    header('Location: ' . APP_URL . '/admin/notifications.php');
    exit;
}

// Mark single as read (POST only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_single_read'])) {
    if (validate_csrf_token()) {
        $nid = (int)$_POST['mark_single_read'];
        $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([$nid, $adminId]);
    }
    header('Location: ' . APP_URL . '/admin/notifications.php');
    exit;
}

// Filter
$typeFilter = isset($_GET['type']) && in_array($_GET['type'], ['booking','payment','rating','security','system']) ? $_GET['type'] : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;

$where = "WHERE user_id = ?";
$params = [$adminId];
if ($typeFilter) { $where .= " AND type = ?"; $params[] = $typeFilter; }

$countStmt = $db->prepare("SELECT COUNT(*) FROM notifications $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pagination = paginate($total, $per_page, $page);

$stmt = $db->prepare("
    SELECT * FROM notifications $where
    ORDER BY created_at DESC
    LIMIT {$pagination['offset']}, {$pagination['per_page']}
");
$stmt->execute($params);
$notifications = $stmt->fetchAll();

$unreadCount = get_unread_notification_count($adminId);

$page_title = 'Notifications';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Notifications</h1>
            <p class="text-gray-500 text-sm mt-1"><?= $unreadCount ?> unread &middot; <?= $total ?> total</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <?php if ($unreadCount > 0): ?>
            <form method="POST" class="inline">
                <?= csrf_field() ?>
                <button type="submit" name="action" value="mark_all_read" class="px-4 py-2 text-sm text-orange-600 border border-orange-300 rounded-lg hover:bg-orange-50 transition-colors">
                    <i class="fas fa-check-double mr-1"></i>Mark All Read
                </button>
            </form>
            <?php endif; ?>
            <form method="POST" class="inline" onsubmit="return confirm('Delete all read notifications?')">
                <?= csrf_field() ?>
                <button type="submit" name="action" value="delete_read" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-trash-alt mr-1"></i>Clear Read
                </button>
            </form>
        </div>
    </div>

    <?= render_flash() ?>

    <!-- Broadcast Card -->
    <div class="bg-white rounded-2xl shadow-md p-5 border border-gray-100 mb-6">
        <h2 class="font-bold text-gray-800 mb-3"><i class="fas fa-bullhorn text-orange-500 mr-2"></i>Send Broadcast</h2>
        <form method="POST" class="flex flex-col sm:flex-row gap-3">
            <?= csrf_field() ?>
            <input type="text" name="message" placeholder="Type a broadcast message..." required maxlength="500"
                   class="flex-1 px-4 py-2.5 rounded-lg border border-gray-200 text-sm focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
            <select name="target" class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                <option value="all">All Users</option>
                <option value="customer">Customers Only</option>
                <option value="provider">Providers Only</option>
            </select>
            <button type="submit" name="action" value="broadcast" class="px-5 py-2.5 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition-colors">
                <i class="fas fa-paper-plane mr-1"></i>Send
            </button>
        </form>
    </div>

    <!-- Type Filters -->
    <div class="flex gap-2 mb-6 flex-wrap">
        <a href="?" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors <?= !$typeFilter ? 'bg-orange-500 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">All</a>
        <?php
        $typeIcons = ['booking' => 'fa-calendar-check', 'payment' => 'fa-credit-card', 'rating' => 'fa-star', 'security' => 'fa-shield-alt', 'system' => 'fa-cog'];
        foreach ($typeIcons as $t => $icon): ?>
        <a href="?type=<?= $t ?>" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors <?= $typeFilter === $t ? 'bg-orange-500 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
            <i class="fas <?= $icon ?> mr-1"></i><?= ucfirst($t) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Notifications List -->
    <?php if (empty($notifications)): ?>
    <div class="bg-white rounded-2xl shadow-md p-12 text-center">
        <i class="fas fa-bell-slash text-gray-300 text-5xl mb-4"></i>
        <p class="text-gray-400">No notifications.</p>
    </div>
    <?php else: ?>
    <div class="space-y-2">
        <?php foreach ($notifications as $n):
            $typeColors = [
                'booking' => 'bg-blue-100 text-blue-600',
                'payment' => 'bg-green-100 text-green-600',
                'rating' => 'bg-yellow-100 text-yellow-600',
                'security' => 'bg-red-100 text-red-600',
                'system' => 'bg-purple-100 text-purple-600',
            ];
            $typeIconMap = [
                'booking' => 'fa-calendar-check',
                'payment' => 'fa-credit-card',
                'rating' => 'fa-star',
                'security' => 'fa-shield-alt',
                'system' => 'fa-cog',
            ];
            $notifTitle = match($n['type']) {
                'booking' => 'Booking Update',
                'payment' => 'Payment Update',
                'rating' => 'New Review',
                'security' => 'Security Alert',
                'system' => 'System Notice',
                default => 'Notification'
            };
        ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-start gap-4 <?= !$n['is_read'] ? 'border-l-4 border-l-orange-500 bg-orange-50/30' : '' ?>">
            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 <?= $typeColors[$n['type']] ?? 'bg-gray-100 text-gray-600' ?>">
                <i class="fas <?= $typeIconMap[$n['type']] ?? 'fa-bell' ?>"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-gray-800 text-sm"><?= e($notifTitle) ?></div>
                <p class="text-sm text-gray-600 mt-0.5"><?= e($n['message']) ?></p>
                <div class="text-xs text-gray-400 mt-1"><?= time_ago($n['created_at']) ?></div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <?php if (!empty($n['link'])): ?>
                <a href="<?= e($n['link']) ?>" class="text-xs text-blue-500 hover:underline">View</a>
                <?php endif; ?>
                <?php if (!$n['is_read']): ?>
                <form method="POST" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="mark_single_read"><input type="hidden" name="mark_single_read" value="<?= $n['id'] ?>"><button type="submit" class="text-xs text-orange-500 hover:underline">Mark read</button></form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="mt-6">
        <?= render_pagination($pagination, '?' . ($typeFilter ? "type=$typeFilter&" : '')) ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
