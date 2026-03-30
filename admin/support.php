<?php
/**
 * UsafiKonect - Admin Support Tickets
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('admin');

$db = getDB();

// Handle ticket actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $action = sanitize_input($_POST['action'] ?? '');
    
    if ($action === 'close') {
        $db->prepare("UPDATE support_tickets SET status = 'closed', updated_at = NOW() WHERE id = ?")->execute([$ticketId]);
        // Notify user
        $ticket = $db->prepare("SELECT user_id, subject FROM support_tickets WHERE id = ?")->execute([$ticketId]);
        $t = $db->prepare("SELECT user_id, subject FROM support_tickets WHERE id = ?");
        $t->execute([$ticketId]);
        $t = $t->fetch();
        if ($t && $t['user_id']) {
            create_notification($t['user_id'], 'system', 
                'Your support ticket "' . $t['subject'] . '" has been resolved and closed.',
                null);
        }
        set_flash('success', 'Ticket closed.');
    } elseif ($action === 'reply') {
        $reply = sanitize_input($_POST['reply'] ?? '');
        if (!empty($reply)) {
            // Store reply as admin_reply column or just update status
            $db->prepare("UPDATE support_tickets SET admin_reply = ?, status = 'replied', updated_at = NOW() WHERE id = ?")->execute([$reply, $ticketId]);
            $t = $db->prepare("SELECT user_id, subject FROM support_tickets WHERE id = ?");
            $t->execute([$ticketId]);
            $t = $t->fetch();
            if ($t && $t['user_id']) {
                create_notification($t['user_id'], 'system',
                    'Admin replied to your support ticket "' . $t['subject'] . '".',
                    null);
            }
            set_flash('success', 'Reply sent.');
        }
    }
    header('Location: ' . APP_URL . '/admin/support.php');
    exit;
}

$statusFilter = isset($_GET['status']) && in_array($_GET['status'], ['open','replied','closed']) ? $_GET['status'] : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$where = "WHERE 1=1";
$params = [];
if ($statusFilter) { $where .= " AND t.status = ?"; $params[] = $statusFilter; }

$countStmt = $db->prepare("SELECT COUNT(*) FROM support_tickets t $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pagination = paginate($page, $total, $per_page);

$stmt = $db->prepare("
    SELECT t.*, u.full_name, u.email as user_email 
    FROM support_tickets t LEFT JOIN users u ON t.user_id = u.id
    $where ORDER BY FIELD(t.status, 'open','replied','closed'), t.created_at DESC
    LIMIT {$pagination['offset']}, {$pagination['per_page']}
");
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$page_title = 'Support Tickets';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Support Tickets</h1>
        <div class="text-sm text-gray-500"><?= $total ?> tickets</div>
    </div>
    
    <?= render_flash() ?>
    
    <!-- Filters -->
    <div class="flex gap-2 mb-6 flex-wrap">
        <a href="?" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= !$statusFilter ? 'bg-orange-500 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">All</a>
        <a href="?status=open" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $statusFilter === 'open' ? 'bg-red-500 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
            <i class="fas fa-exclamation-circle mr-1"></i>Open
        </a>
        <a href="?status=replied" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $statusFilter === 'replied' ? 'bg-blue-500 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
            <i class="fas fa-reply mr-1"></i>Replied
        </a>
        <a href="?status=closed" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $statusFilter === 'closed' ? 'bg-green-500 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
            <i class="fas fa-check-circle mr-1"></i>Closed
        </a>
    </div>
    
    <?php if (empty($tickets)): ?>
    <div class="bg-white rounded-2xl shadow-md p-12 text-center">
        <i class="fas fa-headset text-gray-300 text-5xl mb-4"></i>
        <p class="text-gray-400">No support tickets.</p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($tickets as $t): 
            $statusBadges = [
                'open' => 'bg-red-100 text-red-700',
                'replied' => 'bg-blue-100 text-blue-700',
                'closed' => 'bg-green-100 text-green-700'
            ];
        ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium <?= $statusBadges[$t['status']] ?? '' ?>"><?= ucfirst($t['status']) ?></span>
                    <h3 class="font-bold text-gray-800"><?= e($t['subject']) ?></h3>
                    <span class="text-xs text-gray-400 ml-auto"><?= time_ago($t['created_at']) ?></span>
                </div>
                
                <div class="text-sm text-gray-600 mb-3"><?= nl2br(e($t['message'])) ?></div>
                
                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-400">
                    <?php if ($t['full_name']): ?>
                    <span><i class="fas fa-user mr-1"></i><?= e($t['full_name']) ?> (<?= e($t['user_email']) ?>)</span>
                    <?php else: ?>
                    <span><i class="fas fa-user mr-1"></i><?= e($t['name'] ?? 'Guest') ?> (<?= e($t['email']) ?>)</span>
                    <?php endif; ?>
                    <?php if ($t['phone'] ?? null): ?>
                    <span><i class="fas fa-phone mr-1"></i><?= e($t['phone']) ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($t['admin_reply'] ?? null): ?>
                <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="text-xs font-bold text-blue-700 mb-1"><i class="fas fa-reply mr-1"></i>Admin Reply:</div>
                    <p class="text-sm text-blue-800"><?= nl2br(e($t['admin_reply'])) ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($t['status'] !== 'closed'): ?>
                <div class="mt-4 border-t border-gray-100 pt-4">
                    <form method="POST" class="flex flex-col sm:flex-row gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                        <input type="text" name="reply" placeholder="Type a reply..." 
                               class="flex-1 px-4 py-2 rounded-lg border border-gray-200 text-sm focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                        <button type="submit" name="action" value="reply" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-reply mr-1"></i>Reply
                        </button>
                        <button type="submit" name="action" value="close" onclick="return confirm('Close this ticket?')"
                                class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-check mr-1"></i>Close
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="mt-6">
        <?= render_pagination($page, $pagination['total_pages'], '?' . ($statusFilter ? "status=$statusFilter&" : '')) ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
