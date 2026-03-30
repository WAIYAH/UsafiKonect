<?php
/**
 * UsafiKonect - Provider Bookings
 * List all bookings with status filters
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('provider');

$db = getDB();
$userId = get_user_id();

$status = isset($_GET['status']) && in_array($_GET['status'], ['pending','confirmed','processing','ready','delivered','cancelled']) ? $_GET['status'] : '';
$search = sanitize_input($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;

$where = "WHERE b.provider_id = ?";
$params = [$userId];

if ($status) {
    $where .= " AND b.status = ?";
    $params[] = $status;
}
if ($search) {
    $where .= " AND (b.booking_number LIKE ? OR u.full_name LIKE ? OR u.estate LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$countSql = "SELECT COUNT(*) FROM bookings b JOIN users u ON b.customer_id = u.id $where";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pagination = paginate($page, $total, $per_page);

$sql = "SELECT b.*, u.full_name as customer_name, u.phone as customer_phone, u.estate as customer_estate,
        (SELECT ROUND(AVG(rating),1) FROM ratings WHERE booking_id = b.id) as booking_rating
        FROM bookings b JOIN users u ON b.customer_id = u.id $where
        ORDER BY FIELD(b.status, 'pending','confirmed','processing','ready','delivered','cancelled'), b.created_at DESC
        LIMIT {$pagination['offset']}, {$pagination['per_page']}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Status counts
$counts = $db->prepare("
    SELECT status, COUNT(*) as cnt FROM bookings WHERE provider_id = ? GROUP BY status
");
$counts->execute([$userId]);
$statusCounts = [];
foreach ($counts->fetchAll() as $c) $statusCounts[$c['status']] = $c['cnt'];

$page_title = 'My Bookings';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-3">
        <h1 class="text-2xl font-bold text-gray-800">My Bookings</h1>
        <div class="text-sm text-gray-500"><?= $total ?> bookings found</div>
    </div>
    
    <?= render_flash() ?>
    
    <!-- Status Tabs -->
    <div class="flex gap-2 overflow-x-auto pb-2 mb-4 scrollbar-hide">
        <a href="?<?= $search ? 'search=' . urlencode($search) : '' ?>" class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-colors <?= !$status ? 'bg-orange-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
            All <span class="ml-1 text-xs opacity-75"><?= array_sum($statusCounts) ?></span>
        </a>
        <?php 
        $statusLabels = [
            'pending' => ['Pending','bg-yellow-500','text-yellow-600'],
            'confirmed' => ['Confirmed','bg-blue-500','text-blue-600'],
            'processing' => ['Processing','bg-purple-500','text-purple-600'],
            'ready' => ['Ready','bg-teal-500','text-teal-600'],
            'delivered' => ['Delivered','bg-green-500','text-green-600'],
            'cancelled' => ['Cancelled','bg-red-500','text-red-600']
        ];
        foreach ($statusLabels as $key => [$label, $activeBg, $textColor]):
            $cnt = $statusCounts[$key] ?? 0;
        ?>
        <a href="?status=<?= $key ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
           class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-colors <?= $status === $key ? "$activeBg text-white" : "bg-white $textColor hover:bg-gray-100" ?>">
            <?= $label ?> <span class="ml-1 text-xs opacity-75"><?= $cnt ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    
    <!-- Search -->
    <form method="GET" class="mb-6">
        <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
        <div class="relative max-w-md">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by booking#, customer, estate..." 
                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100 text-sm">
        </div>
    </form>
    
    <!-- Bookings List -->
    <?php if (empty($bookings)): ?>
    <div class="bg-white rounded-2xl shadow-md p-12 text-center">
        <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
        <h3 class="text-lg font-semibold text-gray-600 mb-1">No bookings found</h3>
        <p class="text-gray-400 text-sm">No bookings match your filters.</p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($bookings as $b): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
            <div class="flex flex-col lg:flex-row">
                <div class="flex-1 p-5">
                    <div class="flex flex-wrap items-center gap-3 mb-2">
                        <span class="font-bold text-gray-800">#<?= e($b['booking_number']) ?></span>
                        <?= booking_status_badge($b['status']) ?>
                        <?= payment_status_badge($b['payment_status']) ?>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                        <div>
                            <div class="text-gray-400 text-xs">Customer</div>
                            <div class="font-medium text-gray-700"><?= e($b['customer_name']) ?></div>
                        </div>
                        <div>
                            <div class="text-gray-400 text-xs">Estate</div>
                            <div class="text-gray-700"><?= e($b['customer_estate']) ?></div>
                        </div>
                        <div>
                            <div class="text-gray-400 text-xs">Service</div>
                            <div class="text-gray-700"><?= ucwords(str_replace('_', ' & ', $b['service_type'])) ?></div>
                        </div>
                        <div>
                            <div class="text-gray-400 text-xs">Weight</div>
                            <div class="text-gray-700"><?= $b['weight_kg'] ?>kg</div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-4 mt-2 text-xs text-gray-400">
                        <span><i class="fas fa-calendar-alt mr-1"></i>Pickup: <?= date('M j, g:i A', strtotime($b['pickup_date'] . ' ' . $b['pickup_time'])) ?></span>
                        <?php if ($b['delivery_date']): ?>
                        <span><i class="fas fa-truck mr-1"></i>Deliver: <?= date('M j', strtotime($b['delivery_date'])) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex flex-row lg:flex-col items-center justify-between lg:justify-center gap-2 px-5 py-4 lg:py-0 border-t lg:border-t-0 lg:border-l border-gray-100 bg-gray-50 lg:w-44">
                    <div class="text-right lg:text-center">
                        <div class="text-lg font-bold text-gray-800"><?= format_currency($b['total_amount']) ?></div>
                        <?php if ($b['booking_rating']): ?>
                        <div class="flex items-center gap-1 text-yellow-400 text-sm justify-end lg:justify-center">
                            <i class="fas fa-star text-xs"></i> <?= $b['booking_rating'] ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <a href="<?= APP_URL ?>/provider/booking-detail.php?id=<?= $b['id'] ?>" 
                       class="px-4 py-2 bg-orange-500 text-white text-sm rounded-lg hover:bg-orange-600 transition-colors">
                        <i class="fas fa-eye mr-1"></i>View
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="mt-6">
        <?= render_pagination($page, $pagination['total_pages'], '?' . http_build_query(array_filter(['status' => $status, 'search' => $search])) . '&') ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
