<?php
/**
 * UsafiKonect - Provider Booking Detail
 * View booking detail + update status
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('provider');

$db = getDB();
$userId = get_user_id();
$bookingId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT b.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone, u.estate as customer_estate,
           (SELECT ROUND(AVG(rating),1) FROM ratings WHERE booking_id = b.id) as booking_rating,
           (SELECT review FROM ratings WHERE booking_id = b.id LIMIT 1) as booking_review
    FROM bookings b JOIN users u ON b.customer_id = u.id
    WHERE b.id = ? AND b.provider_id = ?
");
$stmt->execute([$bookingId, $userId]);
$booking = $stmt->fetch();

if (!$booking) {
    set_flash('error', 'Booking not found.');
    header('Location: ' . APP_URL . '/provider/bookings.php');
    exit;
}

$page_title = 'Booking #' . $booking['booking_number'];
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$statuses = ['pending','confirmed','processing','ready','delivered'];
$currentIdx = array_search($booking['status'], $statuses);
if ($currentIdx === false) $currentIdx = -1;

// Next possible status
$nextStatus = null;
if ($currentIdx >= 0 && $currentIdx < 4 && $booking['status'] !== 'cancelled') {
    $nextStatus = $statuses[$currentIdx + 1];
}
$nextLabels = [
    'confirmed' => 'Accept Order',
    'processing' => 'Start Processing',
    'ready' => 'Mark Ready',
    'delivered' => 'Mark Delivered'
];
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= APP_URL ?>/provider/bookings.php" class="w-10 h-10 flex items-center justify-center bg-white rounded-lg border border-gray-200 hover:bg-gray-50"><i class="fas fa-arrow-left text-gray-600"></i></a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Booking #<?= e($booking['booking_number']) ?></h1>
            <p class="text-sm text-gray-500">Created <?= time_ago($booking['created_at']) ?></p>
        </div>
    </div>
    
    <?= render_flash() ?>
    
    <!-- Status Timeline -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-6 border border-gray-100">
        <h2 class="font-bold text-gray-700 mb-4">Order Progress</h2>
        <?php if ($booking['status'] === 'cancelled'): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
            <i class="fas fa-times-circle text-red-500 text-2xl mb-2"></i>
            <div class="font-bold text-red-700">Order Cancelled</div>
            <?php if ($booking['cancelled_reason']): ?>
            <p class="text-sm text-red-600 mt-1"><?= e($booking['cancelled_reason']) ?></p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="flex justify-between items-center relative" id="timeline">
            <div class="absolute top-5 left-0 right-0 h-1 bg-gray-200 rounded -z-0"></div>
            <div class="absolute top-5 left-0 h-1 bg-orange-500 rounded -z-0" style="width: <?= max(0, $currentIdx) * 25 ?>%"></div>
            
            <?php 
            $icons = ['fas fa-clock','fas fa-check','fas fa-cog','fas fa-box','fas fa-truck'];
            $labels = ['Pending','Confirmed','Processing','Ready','Delivered'];
            foreach ($statuses as $i => $s): 
                $done = $i <= $currentIdx;
                $active = $i === $currentIdx;
            ?>
            <div class="flex flex-col items-center relative z-10">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm
                    <?= $done ? ($active ? 'bg-orange-500 text-white ring-4 ring-orange-100' : 'bg-green-500 text-white') : 'bg-gray-200 text-gray-400' ?>">
                    <i class="<?= $done && !$active ? 'fas fa-check' : $icons[$i] ?>"></i>
                </div>
                <span class="text-xs mt-2 font-medium <?= $done ? 'text-gray-800' : 'text-gray-400' ?>"><?= $labels[$i] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Action Button -->
        <?php if ($nextStatus): ?>
        <form method="POST" action="<?= APP_URL ?>/provider/booking-action.php" class="mt-6 text-center">
            <?= csrf_field() ?>
            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
            <input type="hidden" name="redirect" value="detail">
            <button type="submit" name="action" value="<?= $nextStatus === 'confirmed' ? 'confirm' : 'status_' . $nextStatus ?>" 
                    class="px-6 py-3 bg-orange-500 text-white font-bold rounded-xl hover:bg-orange-600 transition-colors shadow-md">
                <i class="fas fa-arrow-right mr-2"></i><?= $nextLabels[$nextStatus] ?>
            </button>
            <?php if ($booking['status'] === 'pending'): ?>
            <button type="submit" name="action" value="decline" class="ml-2 px-6 py-3 bg-white border border-red-300 text-red-600 font-bold rounded-xl hover:bg-red-50 transition-colors">
                <i class="fas fa-times mr-2"></i>Decline
            </button>
            <?php endif; ?>
        </form>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Booking Info -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
                <h2 class="font-bold text-gray-700 mb-4"><i class="fas fa-info-circle text-blue-500 mr-2"></i>Booking Details</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <div>
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Service</span>
                            <div class="font-medium text-gray-700"><?= ucwords(str_replace('_', ' & ', $booking['service_type'])) ?></div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Weight</span>
                            <div class="font-medium text-gray-700"><?= $booking['weight_kg'] ?> kg</div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Total Amount</span>
                            <div class="font-bold text-lg text-orange-600"><?= format_currency($booking['total_amount']) ?></div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Payment</span>
                            <div><?= payment_status_badge($booking['payment_status']) ?></div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Pickup Date</span>
                            <div class="font-medium text-gray-700"><?= date('D, M j, Y', strtotime($booking['pickup_date'])) ?></div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Pickup Time</span>
                            <div class="font-medium text-gray-700"><?= date('g:i A', strtotime($booking['pickup_time'])) ?></div>
                        </div>
                        <?php if ($booking['delivery_date']): ?>
                        <div>
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Delivery Date</span>
                            <div class="font-medium text-gray-700"><?= date('D, M j, Y', strtotime($booking['delivery_date'])) ?></div>
                        </div>
                        <?php endif; ?>
                        <div>
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Pickup Address</span>
                            <div class="text-gray-700"><?= e($booking['pickup_address'] ?? $booking['customer_estate']) ?></div>
                        </div>
                    </div>
                </div>
                <?php if ($booking['special_instructions']): ?>
                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="text-xs text-yellow-700 font-bold mb-1"><i class="fas fa-sticky-note mr-1"></i>Special Instructions</div>
                    <p class="text-sm text-yellow-800"><?= e($booking['special_instructions']) ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Customer Rating -->
            <?php if ($booking['booking_rating']): ?>
            <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
                <h2 class="font-bold text-gray-700 mb-3"><i class="fas fa-star text-yellow-500 mr-2"></i>Customer Rating</h2>
                <div class="flex items-center gap-2 mb-2">
                    <div class="flex gap-1 text-yellow-400">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star text-lg <?= $i > $booking['booking_rating'] ? 'text-gray-300' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="text-lg font-bold text-gray-700"><?= $booking['booking_rating'] ?>/5</span>
                </div>
                <?php if ($booking['booking_review']): ?>
                <p class="text-gray-600 italic">"<?= e($booking['booking_review']) ?>"</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Customer Info Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
                <h2 class="font-bold text-gray-700 mb-4"><i class="fas fa-user text-teal-500 mr-2"></i>Customer</h2>
                <div class="space-y-3">
                    <div>
                        <span class="text-xs text-gray-400">Name</span>
                        <div class="font-medium text-gray-800"><?= e($booking['customer_name']) ?></div>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400">Phone</span>
                        <a href="tel:<?= e($booking['customer_phone']) ?>" class="font-medium text-orange-600 hover:underline block"><?= e($booking['customer_phone']) ?></a>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400">Estate</span>
                        <div class="text-gray-700"><?= e($booking['customer_estate']) ?></div>
                    </div>
                </div>
            </div>
            
            <?php if ($booking['mpesa_receipt']): ?>
            <div class="bg-green-50 rounded-2xl border border-green-200 p-6">
                <h3 class="font-bold text-green-800 mb-2"><i class="fas fa-mobile-alt mr-2"></i>M-Pesa Payment</h3>
                <div class="text-sm text-green-700 font-mono"><?= e($booking['mpesa_receipt']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
