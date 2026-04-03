<?php
/**
 * UsafiKonect - Customer Dashboard
 * Overview with stats, recent bookings, quick actions
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('customer');

$db = getDB();
$userId = get_user_id();

// Stats
$totalBookings = $db->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ?");
$totalBookings->execute([$userId]);
$totalBookings = $totalBookings->fetchColumn();

$activeBookings = $db->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ? AND status IN ('pending','confirmed','processing','ready')");
$activeBookings->execute([$userId]);
$activeBookings = $activeBookings->fetchColumn();

$totalSpent = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE customer_id = ? AND payment_status = 'paid'");
$totalSpent->execute([$userId]);
$totalSpent = $totalSpent->fetchColumn();

$loyaltyPoints = $db->prepare("SELECT COALESCE(SUM(points), 0) FROM loyalty_points WHERE user_id = ?");
$loyaltyPoints->execute([$userId]);
$loyaltyPoints = $loyaltyPoints->fetchColumn();

$walletBalance = get_wallet_balance($userId);

// Recent bookings
$recentBookings = $db->prepare("
    SELECT b.*, u.full_name as provider_name 
    FROM bookings b 
    JOIN users u ON b.provider_id = u.id 
    WHERE b.customer_id = ? 
    ORDER BY b.created_at DESC 
    LIMIT 5
");
$recentBookings->execute([$userId]);
$recentBookings = $recentBookings->fetchAll();

// Recent notifications
$notifications = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notifications->execute([$userId]);
$notifications = $notifications->fetchAll();

$page_title = 'Dashboard';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <?= render_flash() ?>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="dash-card-orange text-white rounded-2xl p-5 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <i class="fas fa-calendar-check text-2xl opacity-80"></i>
                <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full">Total</span>
            </div>
            <div class="text-3xl font-bold counter-value"><?= $totalBookings ?></div>
            <div class="text-sm text-white/80">Bookings</div>
        </div>
        
        <div class="dash-card-blue text-white rounded-2xl p-5 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <i class="fas fa-spinner text-2xl opacity-80"></i>
                <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full">Active</span>
            </div>
            <div class="text-3xl font-bold counter-value"><?= $activeBookings ?></div>
            <div class="text-sm text-white/80">In Progress</div>
        </div>
        
        <div class="dash-card-teal text-white rounded-2xl p-5 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <i class="fas fa-wallet text-2xl opacity-80"></i>
                <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full">Balance</span>
            </div>
            <div class="text-2xl font-bold counter-value"><?= format_currency($walletBalance) ?></div>
            <div class="text-sm text-white/80">Wallet</div>
        </div>
        
        <div class="dash-card-purple text-white rounded-2xl p-5 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <i class="fas fa-star text-2xl opacity-80"></i>
                <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full">Points</span>
            </div>
            <div class="text-3xl font-bold counter-value"><?= $loyaltyPoints ?></div>
            <div class="text-sm text-white/80">Loyalty Points</div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="mb-8">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="<?= APP_URL ?>/customer/book.php" class="bg-white rounded-xl p-4 text-center hover:shadow-md transition-shadow border border-gray-100 card-hover">
                <div class="w-12 h-12 bg-orange-100 text-orange-500 rounded-full flex items-center justify-center mx-auto mb-3 text-xl">
                    <i class="fas fa-plus"></i>
                </div>
                <span class="text-sm font-medium text-gray-700">New Booking</span>
            </a>
            <a href="<?= APP_URL ?>/customer/bookings.php" class="bg-white rounded-xl p-4 text-center hover:shadow-md transition-shadow border border-gray-100 card-hover">
                <div class="w-12 h-12 bg-blue-100 text-deepblue-800 rounded-full flex items-center justify-center mx-auto mb-3 text-xl">
                    <i class="fas fa-history"></i>
                </div>
                <span class="text-sm font-medium text-gray-700">My Bookings</span>
            </a>
            <a href="<?= APP_URL ?>/customer/wallet.php" class="bg-white rounded-xl p-4 text-center hover:shadow-md transition-shadow border border-gray-100 card-hover">
                <div class="w-12 h-12 bg-teal-100 text-teal-600 rounded-full flex items-center justify-center mx-auto mb-3 text-xl">
                    <i class="fas fa-wallet"></i>
                </div>
                <span class="text-sm font-medium text-gray-700">My Wallet</span>
            </a>
            <a href="<?= APP_URL ?>/customer/profile.php" class="bg-white rounded-xl p-4 text-center hover:shadow-md transition-shadow border border-gray-100 card-hover">
                <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-3 text-xl">
                    <i class="fas fa-user-cog"></i>
                </div>
                <span class="text-sm font-medium text-gray-700">Profile</span>
            </a>
        </div>
    </div>
    
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Recent Bookings -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800"><i class="fas fa-clock text-orange-500 mr-2"></i>Recent Bookings</h2>
                <a href="<?= APP_URL ?>/customer/bookings.php" class="text-sm text-orange-500 hover:underline">View All</a>
            </div>
            
            <?php if (empty($recentBookings)): ?>
            <div class="p-8 text-center">
                <div class="text-4xl mb-3">🧺</div>
                <p class="text-gray-500">No bookings yet. Time to get your laundry done!</p>
                <a href="<?= APP_URL ?>/customer/book.php" class="inline-block mt-4 px-6 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors text-sm font-semibold">
                    <i class="fas fa-plus mr-1"></i> Book Now
                </a>
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-50">
                <?php foreach ($recentBookings as $b): ?>
                <a href="<?= APP_URL ?>/customer/booking-detail.php?id=<?= $b['id'] ?>" class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 transition-colors">
                    <div class="w-10 h-10 bg-orange-100 text-orange-500 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-gray-800 truncate"><?= e($b['booking_number']) ?></div>
                        <div class="text-xs text-gray-500"><?= e($b['provider_name']) ?> &middot; <?= e($b['service_type']) ?> &middot; <?= time_ago($b['created_at']) ?></div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <?= booking_status_badge($b['status']) ?>
                        <div class="text-sm font-semibold text-gray-700 mt-1"><?= format_currency($b['total_amount']) ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Notifications -->
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800"><i class="fas fa-bell text-orange-500 mr-2"></i>Notifications</h2>
                <a href="<?= APP_URL ?>/customer/notifications.php" class="text-sm text-orange-500 hover:underline">View All</a>
            </div>
            
            <?php if (empty($notifications)): ?>
            <div class="p-6 text-center text-gray-400 text-sm">No notifications yet.</div>
            <?php else: ?>
            <div class="divide-y divide-gray-50 max-h-80 overflow-y-auto">
                <?php foreach ($notifications as $n): ?>
                <div class="px-4 py-3 <?= !$n['is_read'] ? 'bg-orange-50/50' : '' ?>">
                    <?php 
                    $notifTitle = match($n['type']) {
                        'booking' => 'Booking Update',
                        'payment' => 'Payment Update',
                        'rating' => 'New Review',
                        'security' => 'Security Alert',
                        'system' => 'System Notice',
                        default => 'Notification'
                    };
                    ?>
                    <div class="font-medium text-sm text-gray-800"><?= e($notifTitle) ?></div>
                    <div class="text-xs text-gray-500 mt-0.5"><?= e(mb_strlen($n['message']) > 80 ? mb_substr($n['message'], 0, 80) . '...' : $n['message']) ?></div>
                    <div class="text-xs text-gray-400 mt-1"><?= time_ago($n['created_at']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Free Booking Banner -->
    <?php if (has_free_booking($userId)): ?>
    <div class="mt-6 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-2xl p-6 flex items-center justify-between flex-wrap gap-4">
        <div>
            <h3 class="font-bold text-lg"><i class="fas fa-gift mr-2"></i>You've earned a FREE booking!</h3>
            <p class="text-teal-100 text-sm">Your loyalty has paid off. Book now and your next wash is on us!</p>
        </div>
        <a href="<?= APP_URL ?>/customer/book.php?free=1" class="px-6 py-3 bg-white text-teal-600 font-bold rounded-xl hover:bg-teal-50 transition-all shadow-md">
            Claim Free Booking
        </a>
    </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
