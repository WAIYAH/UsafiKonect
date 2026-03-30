<?php
/**
 * UsafiKonect - Customer: Find Providers
 * Browse and search approved laundry providers
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('customer');

$db = getDB();
$userId = get_user_id();

// Get customer's estate
$customer = $db->prepare("SELECT estate FROM users WHERE id = ?");
$customer->execute([$userId]);
$customerEstate = $customer->fetchColumn();

// Filters
$search = sanitize_input($_GET['search'] ?? '');
$estate = sanitize_input($_GET['estate'] ?? '');
$type = sanitize_input($_GET['type'] ?? '');
$sort = sanitize_input($_GET['sort'] ?? 'rating');

$estates = ['Roysambu','Umoja','Donholm','Kilimani','Langata','Westlands','South B','South C','Embakasi','Kasarani','Ruaka','Rongai','Kibera','Kawangware','Eastleigh','Buruburu','Pangani','Ngara','Parklands','Lavington','Karen','Kileleshwa','Upperhill','Hurlingham','Thika Road','Mombasa Road','Jogoo Road','Outer Ring','Pipeline','Kayole'];

// Build query
$where = "u.role = 'provider' AND u.is_active = 1 AND pd.is_approved = 1";
$params = [];

if ($search) {
    $where .= " AND (u.full_name LIKE ? OR pd.business_name LIKE ? OR pd.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($estate && in_array($estate, $estates)) {
    $where .= " AND u.estate = ?";
    $params[] = $estate;
}
if ($type && in_array($type, ['individual', 'shop'])) {
    $where .= " AND pd.business_type = ?";
    $params[] = $type;
}

$orderBy = match ($sort) {
    'price_low'  => 'pd.price_per_kg ASC',
    'price_high' => 'pd.price_per_kg DESC',
    'reviews'    => 'review_count DESC',
    'name'       => 'pd.business_name ASC',
    default      => 'avg_rating DESC',
};

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM users u JOIN provider_details pd ON u.id = pd.user_id WHERE {$where}");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pagination = paginate($total, 12);

// Fetch providers
$stmt = $db->prepare("
    SELECT u.id, u.full_name, u.profile_image, u.estate, u.phone,
        pd.business_name, pd.business_type, pd.price_per_kg, pd.description,
        (SELECT ROUND(AVG(r.rating), 1) FROM ratings r WHERE r.provider_id = u.id) as avg_rating,
        (SELECT COUNT(*) FROM ratings r WHERE r.provider_id = u.id) as review_count,
        (SELECT COUNT(*) FROM bookings b WHERE b.provider_id = u.id AND b.status = 'delivered') as completed_bookings
    FROM users u 
    JOIN provider_details pd ON u.id = pd.user_id 
    WHERE {$where}
    ORDER BY {$orderBy}
    LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}
");
$stmt->execute($params);
$providers = $stmt->fetchAll();

$page_title = 'Find Providers';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-search text-orange-500 mr-2"></i>Find Providers</h1>
        <p class="text-gray-500 mt-1">Browse trusted laundry providers across Nairobi. <?= $total ?> provider<?= $total !== 1 ? 's' : '' ?> available.</p>
    </div>
    
    <?= render_flash() ?>
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-4 mb-6">
        <form method="GET" class="flex flex-col md:flex-row gap-3 flex-wrap">
            <div class="flex-1 min-w-0">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by name or description..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 text-sm">
            </div>
            <select name="estate" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 text-sm">
                <option value="">All Estates</option>
                <?php foreach ($estates as $est): ?>
                    <option value="<?= $est ?>" <?= $estate === $est ? 'selected' : '' ?>><?= $est ?></option>
                <?php endforeach; ?>
            </select>
            <select name="type" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 text-sm">
                <option value="">All Types</option>
                <option value="individual" <?= $type === 'individual' ? 'selected' : '' ?>>Individual (Mama Fua)</option>
                <option value="shop" <?= $type === 'shop' ? 'selected' : '' ?>>Laundry Shop</option>
            </select>
            <select name="sort" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 text-sm">
                <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Top Rated</option>
                <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="reviews" <?= $sort === 'reviews' ? 'selected' : '' ?>>Most Reviews</option>
                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A-Z</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-deepblue-800 text-white rounded-lg hover:bg-deepblue-900 transition-colors text-sm">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
            <?php if ($search || $estate || $type || $sort !== 'rating'): ?>
            <a href="<?= APP_URL ?>/customer/providers.php" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors text-sm text-center">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Providers Grid -->
    <?php if (empty($providers)): ?>
    <div class="bg-white rounded-2xl shadow-md p-10 text-center">
        <div class="text-5xl mb-4">🔍</div>
        <h3 class="text-lg font-bold text-gray-700 mb-2">No providers found</h3>
        <p class="text-gray-500 text-sm">Try adjusting your search filters or check back later.</p>
    </div>
    <?php else: ?>
    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">
        <?php foreach ($providers as $p): ?>
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow card-hover">
            <!-- Provider Header -->
            <div class="p-5">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 bg-orange-100 text-orange-500 rounded-full flex items-center justify-center font-bold text-xl flex-shrink-0">
                        <?php if ($p['profile_image'] && $p['profile_image'] !== 'avatar.png'): ?>
                            <img src="<?= APP_URL ?>/assets/uploads/profiles/<?= e($p['profile_image']) ?>" alt="<?= e($p['business_name']) ?>" class="w-full h-full rounded-full object-cover">
                        <?php else: ?>
                            <?= mb_substr($p['full_name'], 0, 1) ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-gray-800 truncate"><?= e($p['business_name'] ?: $p['full_name']) ?></h3>
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-map-pin mr-1 text-orange-400"></i><?= e($p['estate']) ?>
                        </div>
                        <div class="flex items-center gap-1 mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $p['business_type'] === 'shop' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' ?>">
                                <i class="fas <?= $p['business_type'] === 'shop' ? 'fa-store' : 'fa-user' ?> mr-1"></i>
                                <?= $p['business_type'] === 'shop' ? 'Laundry Shop' : 'Mama Fua' ?>
                            </span>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-lg font-bold text-orange-600"><?= format_currency($p['price_per_kg']) ?></div>
                        <div class="text-xs text-gray-400">per kg</div>
                    </div>
                </div>
                
                <?php if ($p['description']): ?>
                <p class="text-sm text-gray-600 mt-3 line-clamp-2"><?= e(mb_substr($p['description'], 0, 120)) ?><?= mb_strlen($p['description']) > 120 ? '...' : '' ?></p>
                <?php endif; ?>
                
                <!-- Rating & Stats -->
                <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100">
                    <div class="flex items-center gap-1">
                        <div class="flex gap-0.5 text-yellow-400 text-sm">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?= $i > round($p['avg_rating'] ?? 0) ? ' text-gray-300' : '' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="text-sm font-medium text-gray-700 ml-1"><?= $p['avg_rating'] ?? '0.0' ?></span>
                        <span class="text-xs text-gray-400">(<?= $p['review_count'] ?> review<?= $p['review_count'] !== 1 ? 's' : '' ?>)</span>
                    </div>
                    <div class="text-xs text-gray-500">
                        <i class="fas fa-check-circle text-green-500 mr-1"></i><?= $p['completed_bookings'] ?> completed
                    </div>
                </div>
                
                <?php if ($p['estate'] === $customerEstate): ?>
                <div class="mt-2">
                    <span class="text-xs text-teal-600 font-medium"><i class="fas fa-map-marker-alt mr-1"></i>In your estate!</span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Action -->
            <div class="px-5 pb-5">
                <a href="<?= APP_URL ?>/customer/book.php?provider=<?= $p['id'] ?>" class="block w-full py-2.5 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition-all text-center text-sm">
                    <i class="fas fa-calendar-plus mr-1"></i> Book Now
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <div class="mt-6">
        <?= render_pagination($pagination) ?>
    </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
