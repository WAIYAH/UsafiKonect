<?php
/**
 * UsafiKonect - Admin Subscriptions Management
 * View, filter, and cancel user subscriptions
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('admin');

$db = getDB();

// Handle cancel action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $subId = (int)($_POST['subscription_id'] ?? 0);
    $action = sanitize_input($_POST['action'] ?? '');

    if ($action === 'cancel' && $subId > 0) {
        $sub = $db->prepare("SELECT s.*, u.full_name FROM subscriptions s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
        $sub->execute([$subId]);
        $sub = $sub->fetch();

        if ($sub && $sub['status'] === 'active') {
            $db->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE id = ?")->execute([$subId]);
            create_notification($sub['user_id'], 'system',
                'Your ' . $sub['plan_type'] . ' subscription has been cancelled by admin.',
                null);
            set_flash('success', 'Subscription #' . $subId . ' cancelled.');
        }
    }
    header('Location: ' . APP_URL . '/admin/subscriptions.php?' . http_build_query(array_filter($_GET)));
    exit;
}

// Filters
$planFilter = isset($_GET['plan']) && in_array($_GET['plan'], ['weekly','monthly','yearly']) ? $_GET['plan'] : '';
$statusFilter = isset($_GET['status']) && in_array($_GET['status'], ['active','expired','cancelled']) ? $_GET['status'] : '';
$search = sanitize_input($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$where = "WHERE 1=1";
$params = [];
if ($planFilter) { $where .= " AND s.plan_type = ?"; $params[] = $planFilter; }
if ($statusFilter) { $where .= " AND s.status = ?"; $params[] = $statusFilter; }
if ($search) { $where .= " AND (u.full_name LIKE ? OR u.email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$countStmt = $db->prepare("SELECT COUNT(*) FROM subscriptions s JOIN users u ON s.user_id = u.id $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pagination = paginate($page, $total, $per_page);

$stmt = $db->prepare("
    SELECT s.*, u.full_name, u.email, u.phone, u.role
    FROM subscriptions s JOIN users u ON s.user_id = u.id
    $where ORDER BY s.created_at DESC
    LIMIT {$pagination['offset']}, {$pagination['per_page']}
");
$stmt->execute($params);
$subscriptions = $stmt->fetchAll();

// Stats
$activeSubs = $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'")->fetchColumn();
$totalRevenue = $db->query("SELECT COALESCE(SUM(amount),0) FROM subscriptions WHERE payment_status = 'paid'")->fetchColumn();
$monthRevenue = $db->query("SELECT COALESCE(SUM(amount),0) FROM subscriptions WHERE payment_status = 'paid' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetchColumn();

$page_title = 'Subscriptions';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-3">
        <h1 class="text-2xl font-bold text-gray-800">Subscriptions</h1>
        <div class="text-sm text-gray-500"><?= $total ?> subscriptions</div>
    </div>

    <?= render_flash() ?>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-md p-5 border border-gray-100">
            <div class="text-xs text-gray-400 uppercase mb-1">Active</div>
            <div class="text-xl font-bold text-green-600"><?= $activeSubs ?></div>
        </div>
        <div class="bg-white rounded-2xl shadow-md p-5 border border-gray-100">
            <div class="text-xs text-gray-400 uppercase mb-1">Total Revenue</div>
            <div class="text-xl font-bold text-teal-600"><?= format_currency($totalRevenue) ?></div>
        </div>
        <div class="bg-white rounded-2xl shadow-md p-5 border border-gray-100">
            <div class="text-xs text-gray-400 uppercase mb-1">This Month</div>
            <div class="text-xl font-bold text-orange-600"><?= format_currency($monthRevenue) ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-md p-4 border border-gray-100 mb-6">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Name or email..."
                       class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Plan</label>
                <select name="plan" class="px-3 py-2 rounded-lg border border-gray-200 text-sm focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                    <option value="">All Plans</option>
                    <option value="weekly" <?= $planFilter === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                    <option value="monthly" <?= $planFilter === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    <option value="yearly" <?= $planFilter === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status" class="px-3 py-2 rounded-lg border border-gray-200 text-sm focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                    <option value="">All Status</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-orange-500 text-white text-sm rounded-lg hover:bg-orange-600 transition-colors">
                <i class="fas fa-search mr-1"></i>Filter
            </button>
            <?php if ($search || $planFilter || $statusFilter): ?>
            <a href="?" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <?php if (empty($subscriptions)): ?>
    <div class="bg-white rounded-2xl shadow-md p-12 text-center">
        <i class="fas fa-crown text-gray-300 text-5xl mb-4"></i>
        <p class="text-gray-400">No subscriptions found.</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-left">Plan</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Payment</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-left">Period</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($subscriptions as $s):
                        $statusBadges = [
                            'active' => 'bg-green-100 text-green-700',
                            'expired' => 'bg-gray-100 text-gray-600',
                            'cancelled' => 'bg-red-100 text-red-700',
                        ];
                        $payBadges = [
                            'paid' => 'bg-green-100 text-green-700',
                            'pending' => 'bg-yellow-100 text-yellow-700',
                            'failed' => 'bg-red-100 text-red-700',
                        ];
                        $planIcons = ['weekly' => 'fa-calendar-week', 'monthly' => 'fa-calendar-alt', 'yearly' => 'fa-calendar'];
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800"><?= e($s['full_name']) ?></div>
                            <div class="text-xs text-gray-400"><?= e($s['email']) ?></div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 text-gray-700">
                                <i class="fas <?= $planIcons[$s['plan_type']] ?? 'fa-calendar' ?> text-orange-400 text-xs"></i>
                                <?= ucfirst($s['plan_type']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium <?= $statusBadges[$s['status']] ?? '' ?>"><?= ucfirst($s['status']) ?></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium <?= $payBadges[$s['payment_status']] ?? '' ?>"><?= ucfirst($s['payment_status']) ?></span>
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-800"><?= format_currency($s['amount']) ?></td>
                        <td class="px-4 py-3">
                            <div class="text-xs text-gray-600"><?= date('M j, Y', strtotime($s['start_date'])) ?></div>
                            <div class="text-xs text-gray-400">to <?= date('M j, Y', strtotime($s['end_date'])) ?></div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($s['status'] === 'active'): ?>
                            <form method="POST" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="subscription_id" value="<?= $s['id'] ?>">
                                <button type="submit" name="action" value="cancel" onclick="return confirm('Cancel this subscription?')"
                                        class="px-3 py-1 text-xs text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                                    <i class="fas fa-times mr-1"></i>Cancel
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="text-xs text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="mt-6">
        <?= render_pagination($page, $pagination['total_pages'], '?' . http_build_query(array_filter(['plan' => $planFilter, 'status' => $statusFilter, 'search' => $search])) . '&') ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
