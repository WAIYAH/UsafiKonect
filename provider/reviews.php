<?php
/**
 * UsafiKonect - Provider Reviews
 * View all ratings and reviews from customers
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('provider');

$db = getDB();
$userId = get_user_id();

// Stats
$stats = $db->prepare("SELECT COUNT(*) as total, ROUND(AVG(rating),1) as avg_rating FROM ratings WHERE provider_id = ?");
$stats->execute([$userId]);
$stats = $stats->fetch();

// Rating distribution
$dist = $db->prepare("SELECT rating, COUNT(*) as cnt FROM ratings WHERE provider_id = ? GROUP BY rating ORDER BY rating DESC");
$dist->execute([$userId]);
$distribution = [];
foreach ($dist->fetchAll() as $d) $distribution[$d['rating']] = $d['cnt'];

// Filter
$ratingFilter = isset($_GET['rating']) && in_array((int)$_GET['rating'], [1,2,3,4,5]) ? (int)$_GET['rating'] : null;
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;

$where = "WHERE r.provider_id = ?";
$params = [$userId];
if ($ratingFilter) {
    $where .= " AND r.rating = ?";
    $params[] = $ratingFilter;
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM ratings r $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pagination = paginate($page, $total, $per_page);

$stmt = $db->prepare("
    SELECT r.*, u.full_name as customer_name, u.profile_image as customer_image, b.booking_number, b.service_type
    FROM ratings r 
    JOIN users u ON r.customer_id = u.id
    JOIN bookings b ON r.booking_id = b.id
    $where ORDER BY r.created_at DESC
    LIMIT {$pagination['offset']}, {$pagination['per_page']}
");
$stmt->execute($params);
$reviews = $stmt->fetchAll();

$page_title = 'Reviews';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Customer Reviews</h1>
    
    <!-- Rating Summary -->
    <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100 mb-6">
        <div class="grid sm:grid-cols-2 gap-6">
            <!-- Average -->
            <div class="text-center sm:text-left">
                <div class="text-5xl font-bold text-gray-800"><?= $stats['avg_rating'] ?: '0.0' ?></div>
                <div class="flex gap-1 text-yellow-400 text-xl justify-center sm:justify-start mt-1">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star<?= $i > round($stats['avg_rating'] ?? 0) ? ' text-gray-300' : '' ?>"></i>
                    <?php endfor; ?>
                </div>
                <p class="text-sm text-gray-500 mt-1"><?= $stats['total'] ?> total reviews</p>
            </div>
            
            <!-- Distribution -->
            <div class="space-y-2">
                <?php for ($star = 5; $star >= 1; $star--): 
                    $cnt = $distribution[$star] ?? 0;
                    $pct = $stats['total'] > 0 ? ($cnt / $stats['total']) * 100 : 0;
                ?>
                <a href="?rating=<?= $star ?>" class="flex items-center gap-2 group hover:bg-orange-50 -mx-2 px-2 py-0.5 rounded-lg transition-colors <?= $ratingFilter === $star ? 'bg-orange-50' : '' ?>">
                    <div class="flex items-center gap-1 w-16 text-sm">
                        <span class="font-medium text-gray-700"><?= $star ?></span>
                        <i class="fas fa-star text-yellow-400 text-xs"></i>
                    </div>
                    <div class="flex-1 bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-yellow-400 h-full rounded-full transition-all" style="width: <?= $pct ?>%"></div>
                    </div>
                    <span class="text-xs text-gray-500 w-8 text-right"><?= $cnt ?></span>
                </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    
    <!-- Filter Active -->
    <?php if ($ratingFilter): ?>
    <div class="flex items-center gap-2 mb-4">
        <span class="text-sm text-gray-500">Showing <?= $ratingFilter ?>-star reviews</span>
        <a href="<?= APP_URL ?>/provider/reviews.php" class="text-xs text-orange-500 hover:underline">Clear filter</a>
    </div>
    <?php endif; ?>
    
    <!-- Reviews List -->
    <?php if (empty($reviews)): ?>
    <div class="bg-white rounded-2xl shadow-md p-12 text-center">
        <i class="fas fa-star text-gray-300 text-5xl mb-4"></i>
        <h3 class="text-lg font-semibold text-gray-600 mb-1">No reviews yet</h3>
        <p class="text-gray-400 text-sm">Complete more orders to receive customer reviews.</p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($reviews as $r): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-full bg-orange-100 flex-shrink-0 flex items-center justify-center overflow-hidden">
                    <?php if ($r['customer_image']): ?>
                    <img src="<?= APP_URL . '/' . e($r['customer_image']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <span class="text-orange-600 font-bold"><?= strtoupper(substr($r['customer_name'], 0, 1)) ?></span>
                    <?php endif; ?>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <span class="font-bold text-gray-800"><?= e($r['customer_name']) ?></span>
                        <div class="flex gap-0.5 text-yellow-400 text-xs">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?= $i > $r['rating'] ? ' text-gray-300' : '' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="text-xs text-gray-400">&middot; <?= time_ago($r['created_at']) ?></span>
                    </div>
                    
                    <?php if ($r['review']): ?>
                    <p class="text-gray-600 mb-2"><?= e($r['review']) ?></p>
                    <?php endif; ?>
                    
                    <div class="text-xs text-gray-400">
                        <i class="fas fa-receipt mr-1"></i>Booking #<?= e($r['booking_number']) ?> &middot; 
                        <?= ucwords(str_replace('_', ' & ', $r['service_type'])) ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="mt-6">
        <?= render_pagination($page, $pagination['total_pages'], '?' . ($ratingFilter ? "rating=$ratingFilter&" : '')) ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
