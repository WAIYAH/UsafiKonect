<?php
/**
 * UsafiKonect - Customer: My Bookings
 * List of all bookings with filters and pagination
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('customer');

$db = getDB();
$userId = get_user_id();

// Filters
$status = $_GET['status'] ?? '';
$search = sanitize_input($_GET['search'] ?? '');
$validStatuses = ['pending','confirmed','processing','ready','delivered','cancelled'];

// Build query
$where = "b.customer_id = ?";
$params = [$userId];

if ($status && in_array($status, $validStatuses)) {
    $where .= " AND b.status = ?";
    $params[] = $status;
}
if ($search) {
    $where .= " AND (b.booking_number LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM bookings b JOIN users u ON b.provider_id = u.id WHERE {$where}");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$page = max(1, (int)($_GET['page'] ?? 1));
$pagination = paginate($total, 10, $page);

// Fetch
$stmt = $db->prepare("
    SELECT b.*, u.full_name as provider_name, pd.business_name
    FROM bookings b 
    JOIN users u ON b.provider_id = u.id 
    LEFT JOIN provider_details pd ON u.id = pd.user_id
    WHERE {$where}
    ORDER BY b.created_at DESC 
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$page_title = 'My Bookings';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-list-alt text-orange-500 mr-2"></i>My Bookings</h1>
            <p class="text-gray-500 text-sm mt-1"><?= $total ?> total booking<?= (int)$total !== 1 ? 's' : '' ?></p>
        </div>
        <a href="<?= APP_URL ?>/customer/book.php" class="inline-flex items-center px-5 py-2.5 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition-all shadow-md text-sm">
            <i class="fas fa-plus mr-2"></i> New Booking
        </a>
    </div>
    
    <?= render_flash() ?>
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-4 mb-6">
        <form method="GET" class="flex flex-col md:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search booking # or provider..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 text-sm">
            </div>
            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 text-sm">
                <option value="">All Status</option>
                <?php foreach ($validStatuses as $s): ?>
                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="px-4 py-2 bg-deepblue-800 text-white rounded-lg hover:bg-deepblue-900 transition-colors text-sm">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
            <?php if ($search || $status): ?>
            <a href="<?= APP_URL ?>/customer/bookings.php" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors text-sm text-center">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Bookings List -->
    <?php if (empty($bookings)): ?>
    <div class="bg-white rounded-2xl shadow-md p-10 text-center">
        <div class="text-5xl mb-4">📋</div>
        <h3 class="text-lg font-bold text-gray-700 mb-2">No bookings found</h3>
        <p class="text-gray-500 text-sm mb-6"><?= $search || $status ? 'Try adjusting your filters.' : 'Ready to get your laundry done?' ?></p>
        <a href="<?= APP_URL ?>/customer/book.php" class="inline-flex items-center px-6 py-2.5 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors text-sm font-semibold">
            <i class="fas fa-plus mr-1"></i> Book Now
        </a>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($bookings as $b): ?>
        <a href="<?= APP_URL ?>/customer/booking-detail.php?id=<?= $b['id'] ?>" class="block bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow overflow-hidden">
            <div class="flex flex-col md:flex-row md:items-center p-4 md:p-5 gap-4">
                <div class="flex items-center gap-4 flex-1">
                    <div class="w-12 h-12 bg-orange-100 text-orange-500 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-tshirt text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="font-bold text-gray-800"><?= e($b['booking_number']) ?></div>
                        <div class="text-sm text-gray-500">
                            <?= e($b['business_name'] ?: $b['provider_name']) ?> &middot; 
                            <?= ucwords(str_replace('_', ' & ', $b['service_type'])) ?> &middot; 
                            <?= $b['weight_kg'] ?>kg
                        </div>
                        <div class="text-xs text-gray-400 mt-0.5">
                            <i class="far fa-calendar mr-1"></i>Pickup: <?= date('M j, Y', strtotime($b['pickup_date'])) ?> at <?= date('g:i A', strtotime($b['pickup_time'])) ?>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4 md:gap-6">
                    <div class="text-right">
                        <?= booking_status_badge($b['status']) ?>
                        <div class="text-xs mt-1"><?= payment_status_badge($b['payment_status']) ?></div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-gray-800"><?= format_currency($b['total_amount']) ?></div>
                        <div class="text-xs text-gray-400"><?= time_ago($b['created_at']) ?></div>
                    </div>
                    <i class="fas fa-chevron-right text-gray-300 hidden md:block"></i>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <div class="mt-6">
        <?= render_pagination($pagination) ?>
    </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
