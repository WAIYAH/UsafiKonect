<?php
/**
 * UsafiKonect - Admin Provider Management
 * Approve/reject providers, view details
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('admin');

$db = getDB();

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $providerId = (int)($_POST['provider_id'] ?? 0);
    $action = sanitize_input($_POST['action'] ?? '');
    
    $check = $db->prepare("SELECT u.*, pd.business_name FROM users u JOIN provider_details pd ON u.id = pd.user_id WHERE u.id = ? AND u.role = 'provider'");
    $check->execute([$providerId]);
    $prov = $check->fetch();
    
    if ($prov) {
        if ($action === 'approve') {
            $db->prepare("UPDATE provider_details SET is_approved = 1 WHERE user_id = ?")->execute([$providerId]);
            create_notification($providerId, 'system', 
                'Congratulations! Your provider account has been approved. You can now receive bookings!',
                APP_URL . '/provider/dashboard.php');
            set_flash('success', $prov['business_name'] . ' has been approved.');
        } elseif ($action === 'reject') {
            $db->prepare("UPDATE provider_details SET is_approved = 0 WHERE user_id = ?")->execute([$providerId]);
            $db->prepare("UPDATE users SET is_active = 0 WHERE id = ?")->execute([$providerId]);
            create_notification($providerId, 'system',
                'Your provider application was not approved at this time. Please contact support for more information.',
                APP_URL . '/contact.php');
            set_flash('success', $prov['business_name'] . ' has been rejected and deactivated.');
        }
    }
    header('Location: ' . APP_URL . '/admin/providers.php?' . http_build_query(array_filter(['status' => $_POST['filter_status'] ?? ''])));
    exit;
}

$statusFilter = isset($_GET['status']) && in_array($_GET['status'], ['pending','approved']) ? $_GET['status'] : '';
$search = sanitize_input($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;

$where = "WHERE u.role = 'provider'";
$params = [];
if ($statusFilter === 'pending') {
    $where .= " AND pd.is_approved = 0";
} elseif ($statusFilter === 'approved') {
    $where .= " AND pd.is_approved = 1";
}
if ($search) {
    $where .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR pd.business_name LIKE ? OR u.estate LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM users u JOIN provider_details pd ON u.id = pd.user_id $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pagination = paginate($total, $per_page, $page);

$stmt = $db->prepare("
    SELECT u.*, pd.business_name, pd.business_type, pd.price_per_kg, pd.description, pd.is_approved,
           (SELECT COUNT(*) FROM bookings WHERE provider_id = u.id) as booking_count,
           (SELECT ROUND(AVG(rating),1) FROM ratings WHERE provider_id = u.id) as avg_rating,
           (SELECT COUNT(*) FROM ratings WHERE provider_id = u.id) as review_count,
           (SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE provider_id = u.id AND payment_status = 'paid' AND status = 'delivered') as total_earnings
    FROM users u JOIN provider_details pd ON u.id = pd.user_id
    $where ORDER BY pd.is_approved ASC, u.created_at DESC
    LIMIT {$pagination['offset']}, {$pagination['per_page']}
");
$stmt->execute($params);
$providers = $stmt->fetchAll();

$page_title = 'Manage Providers';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-3">
        <h1 class="text-2xl font-bold text-gray-800">Manage Providers</h1>
        <div class="text-sm text-gray-500"><?= $total ?> providers</div>
    </div>
    
    <?= render_flash() ?>
    
    <!-- Filters -->
    <div class="flex flex-wrap gap-3 mb-6">
        <a href="?" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= !$statusFilter ? 'bg-orange-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' ?>">All</a>
        <a href="?status=pending" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $statusFilter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' ?>">
            <i class="fas fa-clock mr-1"></i>Pending Approval
        </a>
        <a href="?status=approved" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $statusFilter === 'approved' ? 'bg-green-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' ?>">
            <i class="fas fa-check mr-1"></i>Approved
        </a>
        
        <form method="GET" class="flex-1 min-w-48 flex gap-2">
            <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= e($statusFilter) ?>"><?php endif; ?>
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search providers..."
                       class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-200 text-sm focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
            </div>
        </form>
    </div>
    
    <!-- Provider Cards -->
    <?php if (empty($providers)): ?>
    <div class="bg-white rounded-2xl shadow-md p-12 text-center">
        <i class="fas fa-store-slash text-gray-300 text-5xl mb-4"></i>
        <p class="text-gray-400">No providers found.</p>
    </div>
    <?php else: ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($providers as $p): ?>
        <div class="bg-white rounded-2xl shadow-sm border <?= $p['is_approved'] ? 'border-gray-100' : 'border-yellow-300 border-2' ?> overflow-hidden hover:shadow-md transition-shadow">
            <?php if (!$p['is_approved']): ?>
            <div class="bg-yellow-50 px-4 py-2 text-center">
                <span class="text-xs font-bold text-yellow-700"><i class="fas fa-clock mr-1"></i>PENDING APPROVAL</span>
            </div>
            <?php endif; ?>
            
            <div class="p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center text-lg font-bold text-orange-600 flex-shrink-0 overflow-hidden">
                        <?php if ($p['profile_image']): ?>
                        <img src="<?= APP_URL . '/' . e($p['profile_image']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                        <?= strtoupper(substr($p['full_name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-gray-800 truncate"><?= e($p['business_name']) ?></h3>
                        <p class="text-xs text-gray-500"><?= e($p['full_name']) ?> &middot; <?= ucfirst($p['business_type']) ?></p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                    <div class="bg-gray-50 rounded-lg p-2 text-center">
                        <div class="font-bold text-gray-700"><?= $p['booking_count'] ?></div>
                        <div class="text-gray-400">Bookings</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-2 text-center">
                        <div class="font-bold text-gray-700"><?= $p['avg_rating'] ?: 'N/A' ?> <i class="fas fa-star text-yellow-400"></i></div>
                        <div class="text-gray-400"><?= $p['review_count'] ?> reviews</div>
                    </div>
                </div>
                
                <div class="text-xs text-gray-500 space-y-1 mb-4">
                    <div><i class="fas fa-map-marker-alt text-orange-400 w-4"></i><?= e($p['estate']) ?></div>
                    <div><i class="fas fa-tag text-blue-400 w-4"></i><?= format_currency($p['price_per_kg']) ?>/kg</div>
                    <div><i class="fas fa-phone text-teal-400 w-4"></i><?= e($p['phone']) ?></div>
                    <div><i class="fas fa-money-bill text-green-400 w-4"></i>Earned: <?= format_currency($p['total_earnings']) ?></div>
                </div>
                
                <?php if ($p['description']): ?>
                <p class="text-xs text-gray-500 italic border-t border-gray-100 pt-2 mb-3 line-clamp-2">"<?= e(mb_substr($p['description'], 0, 120)) ?>"</p>
                <?php endif; ?>
                
                <div class="flex gap-2">
                    <?php if (!$p['is_approved']): ?>
                    <form method="POST" class="flex-1">
                        <?= csrf_field() ?>
                        <input type="hidden" name="provider_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="filter_status" value="<?= e($statusFilter) ?>">
                        <button type="submit" name="action" value="approve" class="w-full px-3 py-2 bg-green-600 text-white text-xs font-bold rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-check mr-1"></i>Approve
                        </button>
                    </form>
                    <form method="POST" class="flex-1">
                        <?= csrf_field() ?>
                        <input type="hidden" name="provider_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="filter_status" value="<?= e($statusFilter) ?>">
                        <button type="submit" name="action" value="reject" onclick="return confirm('Reject and deactivate this provider?')"
                                class="w-full px-3 py-2 bg-red-50 text-red-600 border border-red-200 text-xs font-bold rounded-lg hover:bg-red-100 transition-colors">
                            <i class="fas fa-times mr-1"></i>Reject
                        </button>
                    </form>
                    <?php else: ?>
                    <span class="flex-1 text-center px-3 py-2 bg-green-50 text-green-700 text-xs font-bold rounded-lg">
                        <i class="fas fa-check-circle mr-1"></i>Approved
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="mt-6">
        <?= render_pagination($pagination, '?' . http_build_query(array_filter(['status' => $statusFilter, 'search' => $search])) . '&') ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
