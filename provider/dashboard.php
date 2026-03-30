<?php
/**
 * UsafiKonect - Provider Dashboard
 * Overview: stats, pending bookings, earnings chart, recent reviews
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('provider');

$db = getDB();
$userId = get_user_id();

// Check if approved
$pd = $db->prepare("SELECT is_approved FROM provider_details WHERE user_id = ?");
$pd->execute([$userId]);
$provider = $pd->fetch();

// Stats
$totalBookings = $db->prepare("SELECT COUNT(*) FROM bookings WHERE provider_id = ?");
$totalBookings->execute([$userId]); $totalBookings = $totalBookings->fetchColumn();

$pendingBookings = $db->prepare("SELECT COUNT(*) FROM bookings WHERE provider_id = ? AND status = 'pending'");
$pendingBookings->execute([$userId]); $pendingBookings = $pendingBookings->fetchColumn();

$activeBookings = $db->prepare("SELECT COUNT(*) FROM bookings WHERE provider_id = ? AND status IN ('confirmed','processing','ready')");
$activeBookings->execute([$userId]); $activeBookings = $activeBookings->fetchColumn();

$totalEarnings = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE provider_id = ? AND payment_status = 'paid' AND status = 'delivered'");
$totalEarnings->execute([$userId]); $totalEarnings = $totalEarnings->fetchColumn();

$avgRating = $db->prepare("SELECT ROUND(AVG(rating), 1) FROM ratings WHERE provider_id = ?");
$avgRating->execute([$userId]); $avgRating = $avgRating->fetchColumn() ?: 0;

$reviewCount = $db->prepare("SELECT COUNT(*) FROM ratings WHERE provider_id = ?");
$reviewCount->execute([$userId]); $reviewCount = $reviewCount->fetchColumn();

// Pending orders (action needed)
$pendingOrders = $db->prepare("
    SELECT b.*, u.full_name as customer_name, u.phone as customer_phone, u.estate as customer_estate
    FROM bookings b JOIN users u ON b.customer_id = u.id
    WHERE b.provider_id = ? AND b.status = 'pending'
    ORDER BY b.created_at ASC LIMIT 5
");
$pendingOrders->execute([$userId]);
$pendingOrders = $pendingOrders->fetchAll();

// Monthly earnings (last 6 months for chart)
$monthlyEarnings = $db->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as total 
    FROM bookings WHERE provider_id = ? AND payment_status = 'paid' AND status = 'delivered'
    GROUP BY month ORDER BY month DESC LIMIT 6
");
$monthlyEarnings->execute([$userId]);
$monthlyEarnings = array_reverse($monthlyEarnings->fetchAll());

// Recent reviews
$recentReviews = $db->prepare("
    SELECT r.*, u.full_name as customer_name 
    FROM ratings r JOIN users u ON r.customer_id = u.id
    WHERE r.provider_id = ? ORDER BY r.created_at DESC LIMIT 5
");
$recentReviews->execute([$userId]);
$recentReviews = $recentReviews->fetchAll();

$page_title = 'Provider Dashboard';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <?= render_flash() ?>
    
    <?php if ($provider && !$provider['is_approved']): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6 rounded-r-lg">
        <div class="flex items-center gap-3">
            <i class="fas fa-clock text-yellow-500 text-xl"></i>
            <div>
                <h3 class="font-bold text-yellow-800">Account Pending Approval</h3>
                <p class="text-sm text-yellow-700">Your provider account is under review. You'll receive a notification once approved.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="dash-card-orange text-white rounded-2xl p-5 shadow-lg">
            <i class="fas fa-calendar-alt text-xl opacity-80 mb-2"></i>
            <div class="text-2xl font-bold"><?= $totalBookings ?></div>
            <div class="text-xs text-white/80">Total Bookings</div>
        </div>
        <div class="dash-card-blue text-white rounded-2xl p-5 shadow-lg relative">
            <?php if ($pendingBookings > 0): ?>
            <span class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs font-bold badge-pulse"><?= $pendingBookings ?></span>
            <?php endif; ?>
            <i class="fas fa-hourglass-half text-xl opacity-80 mb-2"></i>
            <div class="text-2xl font-bold"><?= $pendingBookings ?></div>
            <div class="text-xs text-white/80">Pending</div>
        </div>
        <div class="dash-card-teal text-white rounded-2xl p-5 shadow-lg">
            <i class="fas fa-spinner text-xl opacity-80 mb-2"></i>
            <div class="text-2xl font-bold"><?= $activeBookings ?></div>
            <div class="text-xs text-white/80">Active</div>
        </div>
        <div class="dash-card-purple text-white rounded-2xl p-5 shadow-lg">
            <i class="fas fa-money-bill-wave text-xl opacity-80 mb-2"></i>
            <div class="text-xl font-bold"><?= format_currency($totalEarnings) ?></div>
            <div class="text-xs text-white/80">Total Earnings</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
            <i class="fas fa-star text-yellow-500 text-xl mb-2"></i>
            <div class="text-2xl font-bold text-gray-800"><?= $avgRating ?> <span class="text-sm text-gray-400">/5</span></div>
            <div class="text-xs text-gray-500"><?= $reviewCount ?> reviews</div>
        </div>
    </div>
    
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Pending Orders (Action Required) -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800"><i class="fas fa-bell text-red-500 mr-2"></i>Action Required</h2>
                <a href="<?= APP_URL ?>/provider/bookings.php?status=pending" class="text-sm text-orange-500 hover:underline">View All</a>
            </div>
            
            <?php if (empty($pendingOrders)): ?>
            <div class="p-8 text-center text-gray-400">
                <i class="fas fa-check-circle text-green-400 text-4xl mb-3"></i>
                <p>All caught up! No pending orders.</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-50">
                <?php foreach ($pendingOrders as $o): ?>
                <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-gray-800"><?= e($o['booking_number']) ?></div>
                        <div class="text-sm text-gray-500">
                            <?= e($o['customer_name']) ?> &middot; <?= e($o['customer_estate']) ?> &middot; 
                            <?= ucwords(str_replace('_', ' & ', $o['service_type'])) ?> &middot; <?= $o['weight_kg'] ?>kg
                        </div>
                        <div class="text-xs text-gray-400">Pickup: <?= date('M j', strtotime($o['pickup_date'])) ?> at <?= date('g:i A', strtotime($o['pickup_time'])) ?></div>
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        <form method="POST" action="<?= APP_URL ?>/provider/booking-action.php" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="booking_id" value="<?= $o['id'] ?>">
                            <button type="submit" name="action" value="confirm" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-check mr-1"></i>Accept
                            </button>
                        </form>
                        <form method="POST" action="<?= APP_URL ?>/provider/booking-action.php" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="booking_id" value="<?= $o['id'] ?>">
                            <button type="submit" name="action" value="decline" class="px-4 py-2 bg-red-50 text-red-600 border border-red-200 text-sm rounded-lg hover:bg-red-100 transition-colors">
                                <i class="fas fa-times mr-1"></i>Decline
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Reviews -->
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-800"><i class="fas fa-star text-yellow-500 mr-2"></i>Recent Reviews</h2>
            </div>
            <?php if (empty($recentReviews)): ?>
            <div class="p-6 text-center text-gray-400 text-sm">No reviews yet.</div>
            <?php else: ?>
            <div class="divide-y divide-gray-50 max-h-80 overflow-y-auto">
                <?php foreach ($recentReviews as $r): ?>
                <div class="px-4 py-3">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="flex gap-0.5 text-yellow-400 text-xs">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?= $i > $r['rating'] ? ' text-gray-300' : '' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="text-xs text-gray-500">by <?= e($r['customer_name']) ?></span>
                    </div>
                    <?php if ($r['review']): ?>
                    <p class="text-sm text-gray-600 italic">"<?= e(mb_substr($r['review'], 0, 100)) ?>"</p>
                    <?php endif; ?>
                    <div class="text-xs text-gray-400 mt-1"><?= time_ago($r['created_at']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Earnings Chart -->
    <?php if (!empty($monthlyEarnings)): ?>
    <div class="mt-6 bg-white rounded-2xl shadow-md border border-gray-100 p-6">
        <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-chart-line text-teal-600 mr-2"></i>Monthly Earnings</h2>
        <canvas id="earningsChart" height="100"></canvas>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new Chart(document.getElementById('earningsChart'), {
            type: 'bar',
            data: {
                labels: [<?= implode(',', array_map(fn($m) => "'" . date('M Y', strtotime($m['month'] . '-01')) . "'", $monthlyEarnings)) ?>],
                datasets: [{
                    label: 'Earnings (KES)',
                    data: [<?= implode(',', array_map(fn($m) => $m['total'], $monthlyEarnings)) ?>],
                    backgroundColor: 'rgba(249,115,22,0.7)',
                    borderColor: '#F97316',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => 'KES ' + v.toLocaleString() } } }
            }
        });
    });
    </script>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
