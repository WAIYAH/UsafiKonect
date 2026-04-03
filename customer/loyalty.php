<?php
/**
 * UsafiKonect - Customer: Loyalty Points
 * View points balance, history, redeem free bookings
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('customer');

$db = getDB();
$userId = get_user_id();

// Get loyalty record
$stmt = $db->prepare("SELECT * FROM loyalty_points WHERE user_id = ?");
$stmt->execute([$userId]);
$loyalty = $stmt->fetch();

if (!$loyalty) {
    $loyalty = [
        'points' => 0,
        'total_bookings' => 0,
        'free_bookings_earned' => 0,
        'free_bookings_used' => 0,
    ];
}

$availableFree = max(0, ($loyalty['free_bookings_earned'] ?? 0) - ($loyalty['free_bookings_used'] ?? 0));
$pointsToNext = 100 - ($loyalty['points'] % 100);
$bookingsToFree = 5 - ($loyalty['total_bookings'] % 5);

// Recent completed bookings that earned points
$recentBookings = $db->prepare("
    SELECT b.booking_number, b.service_type, b.total_amount, b.created_at, u.full_name as provider_name
    FROM bookings b 
    JOIN users u ON b.provider_id = u.id 
    WHERE b.customer_id = ? AND b.status = 'delivered'
    ORDER BY b.created_at DESC 
    LIMIT 10
");
$recentBookings->execute([$userId]);
$recentBookings = $recentBookings->fetchAll();

$page_title = 'Loyalty Points';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <h1 class="text-2xl font-bold text-gray-800 mb-6"><i class="fas fa-gift text-orange-500 mr-2"></i>Loyalty Points</h1>
    
    <?= render_flash() ?>
    
    <!-- Points Overview Cards -->
    <div class="grid md:grid-cols-3 gap-6 mb-8">
        <div class="dash-card-purple text-white rounded-2xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <i class="fas fa-star text-3xl opacity-80"></i>
                <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full">Balance</span>
            </div>
            <div class="text-4xl font-extrabold"><?= number_format($loyalty['points']) ?></div>
            <div class="text-sm text-purple-200 mt-1">Loyalty Points</div>
        </div>
        
        <div class="dash-card-orange text-white rounded-2xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <i class="fas fa-clipboard-check text-3xl opacity-80"></i>
                <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full">Completed</span>
            </div>
            <div class="text-4xl font-extrabold"><?= number_format($loyalty['total_bookings']) ?></div>
            <div class="text-sm text-orange-100 mt-1">Total Bookings</div>
        </div>
        
        <div class="dash-card-teal text-white rounded-2xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <i class="fas fa-gift text-3xl opacity-80"></i>
                <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full">Free</span>
            </div>
            <div class="text-4xl font-extrabold"><?= $availableFree ?></div>
            <div class="text-sm text-teal-100 mt-1">Free Bookings Available</div>
        </div>
    </div>
    
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Progress & How It Works -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Progress to Next Free Booking -->
            <div class="bg-white rounded-2xl shadow-md p-6">
                <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-chart-line text-orange-500 mr-2"></i>Progress to Next Free Booking</h2>
                
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-600">Bookings Progress</span>
                        <span class="font-semibold text-gray-800"><?= $loyalty['total_bookings'] % 5 ?> / 5</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-gradient-to-r from-orange-400 to-orange-600 h-3 rounded-full transition-all duration-500" 
                             style="width: <?= (($loyalty['total_bookings'] % 5) / 5) * 100 ?>%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        <?php if ($bookingsToFree === 5): ?>
                            Complete 5 bookings to earn your next free wash!
                        <?php else: ?>
                            <?= $bookingsToFree ?> more booking<?= $bookingsToFree > 1 ? 's' : '' ?> to earn a free wash!
                        <?php endif; ?>
                    </p>
                </div>
                
                <!-- Visual Step Tracker -->
                <div class="flex items-center justify-between mt-6">
                    <?php for ($i = 1; $i <= 5; $i++): 
                        $done = ($loyalty['total_bookings'] % 5) >= $i || ($loyalty['total_bookings'] % 5 === 0 && $loyalty['total_bookings'] > 0);
                    ?>
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold <?= $done ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-500' ?>">
                            <?php if ($done): ?><i class="fas fa-check text-xs"></i><?php else: echo $i; endif; ?>
                        </div>
                        <span class="text-xs text-gray-500 mt-1">Wash <?= $i ?></span>
                    </div>
                    <?php if ($i < 5): ?>
                    <div class="flex-1 h-0.5 <?= ($loyalty['total_bookings'] % 5) > $i || ($loyalty['total_bookings'] % 5 === 0 && $loyalty['total_bookings'] > 0) ? 'bg-orange-500' : 'bg-gray-200' ?> mx-1 mb-5"></div>
                    <?php endif; ?>
                    <?php endfor; ?>
                </div>
                
                <?php if ($loyalty['total_bookings'] % 5 === 0 && $loyalty['total_bookings'] > 0): ?>
                <div class="mt-4 text-center">
                    <div class="inline-flex items-center gap-2 text-3xl">🎊</div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent Earning History -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-bold text-gray-800"><i class="fas fa-history text-deepblue-800 mr-2"></i>Points Earning History</h2>
                </div>
                
                <?php if (empty($recentBookings)): ?>
                <div class="p-8 text-center text-gray-400">
                    <i class="fas fa-receipt text-4xl mb-3"></i>
                    <p>No completed bookings yet. Start booking to earn points!</p>
                </div>
                <?php else: ?>
                <div class="divide-y divide-gray-50">
                    <?php foreach ($recentBookings as $b): ?>
                    <div class="flex items-center gap-4 px-6 py-4">
                        <div class="w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-plus text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-800 text-sm"><?= e($b['booking_number']) ?></div>
                            <div class="text-xs text-gray-500">
                                <?= e($b['provider_name']) ?> &middot; <?= ucwords(str_replace('_', ' & ', $b['service_type'])) ?>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <div class="text-sm font-bold text-green-600">+10 pts</div>
                            <div class="text-xs text-gray-400"><?= time_ago($b['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sidebar Info -->
        <div class="space-y-6">
            <!-- Redeem Section -->
            <?php if ($availableFree > 0): ?>
            <div class="bg-gradient-to-br from-teal-500 to-teal-600 text-white rounded-2xl p-6 shadow-lg">
                <div class="text-center">
                    <div class="text-4xl mb-3">🎉</div>
                    <h3 class="text-lg font-bold">You have <?= $availableFree ?> FREE booking<?= $availableFree > 1 ? 's' : '' ?>!</h3>
                    <p class="text-teal-100 text-sm mt-2">Use it on your next laundry order.</p>
                    <a href="<?= APP_URL ?>/customer/book.php?free=1" class="inline-block mt-4 px-6 py-2.5 bg-white text-teal-600 font-bold rounded-lg hover:bg-teal-50 transition-colors text-sm">
                        <i class="fas fa-gift mr-1"></i> Redeem Now
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- How It Works -->
            <div class="bg-white rounded-2xl shadow-md p-6">
                <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-info-circle text-orange-500 mr-2"></i>How It Works</h3>
                <div class="space-y-4">
                    <div class="flex gap-3">
                        <div class="w-8 h-8 bg-orange-100 text-orange-500 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">1</div>
                        <div>
                            <h4 class="font-semibold text-gray-800 text-sm">Book & Complete</h4>
                            <p class="text-xs text-gray-500">Each completed booking earns you 10 loyalty points.</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="w-8 h-8 bg-orange-100 text-orange-500 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">2</div>
                        <div>
                            <h4 class="font-semibold text-gray-800 text-sm">Every 5th Booking</h4>
                            <p class="text-xs text-gray-500">After every 5 completed bookings, you earn a FREE wash!</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="w-8 h-8 bg-orange-100 text-orange-500 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">3</div>
                        <div>
                            <h4 class="font-semibold text-gray-800 text-sm">Redeem Anytime</h4>
                            <p class="text-xs text-gray-500">Use your free booking on any service with any provider.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Summary -->
            <div class="bg-white rounded-2xl shadow-md p-6">
                <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-trophy text-yellow-500 mr-2"></i>Your Stats</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Total Points Earned</span>
                        <span class="font-bold text-gray-800"><?= number_format($loyalty['points']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Total Bookings</span>
                        <span class="font-bold text-gray-800"><?= number_format($loyalty['total_bookings']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Free Bookings Earned</span>
                        <span class="font-bold text-green-600"><?= $loyalty['free_bookings_earned'] ?? 0 ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Free Bookings Used</span>
                        <span class="font-bold text-gray-800"><?= $loyalty['free_bookings_used'] ?? 0 ?></span>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-3">
                        <span class="text-gray-500">Points to Next Reward</span>
                        <span class="font-bold text-orange-600"><?= $pointsToNext ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
