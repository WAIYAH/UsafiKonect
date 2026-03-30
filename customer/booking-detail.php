<?php
/**
 * UsafiKonect - Customer: Booking Detail
 * View booking info, track status, rate provider, cancel
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('customer');

$db = getDB();
$userId = get_user_id();
$bookingId = (int)($_GET['id'] ?? 0);

// Fetch booking
$stmt = $db->prepare("
    SELECT b.*, u.full_name as provider_name, u.phone as provider_phone, u.profile_image as provider_image,
        pd.business_name, pd.business_type
    FROM bookings b 
    JOIN users u ON b.provider_id = u.id 
    LEFT JOIN provider_details pd ON u.id = pd.user_id
    WHERE b.id = ? AND b.customer_id = ?
");
$stmt->execute([$bookingId, $userId]);
$booking = $stmt->fetch();

if (!$booking) {
    set_flash('error', 'Booking not found.');
    redirect(APP_URL . '/customer/bookings.php');
}

// Check if already rated
$existingRating = $db->prepare("SELECT * FROM ratings WHERE booking_id = ? AND customer_id = ?");
$existingRating->execute([$bookingId, $userId]);
$existingRating = $existingRating->fetch();

// Handle cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    if (validate_csrf_token() && in_array($booking['status'], ['pending', 'confirmed'])) {
        $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$bookingId]);
        
        // Refund if paid
        if ($booking['payment_status'] === 'paid') {
            add_wallet_transaction($userId, 'refund', $booking['total_amount'], "Refund for cancelled booking #{$booking['booking_number']}");
            $stmt = $db->prepare("UPDATE bookings SET payment_status = 'refunded' WHERE id = ?");
            $stmt->execute([$bookingId]);
        }
        
        create_notification($booking['provider_id'], 'booking', "Booking #{$booking['booking_number']} has been cancelled by the customer.", APP_URL . '/provider/bookings.php');
        set_flash('success', 'Booking cancelled successfully.' . ($booking['payment_status'] === 'paid' ? ' Refund added to wallet.' : ''));
        redirect(APP_URL . '/customer/booking-detail.php?id=' . $bookingId);
    }
}

// Handle rating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    if (validate_csrf_token() && $booking['status'] === 'delivered' && !$existingRating) {
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 0)));
        $review = sanitize_input($_POST['review'] ?? '');
        
        $stmt = $db->prepare("INSERT INTO ratings (booking_id, customer_id, provider_id, rating, review) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$bookingId, $userId, $booking['provider_id'], $rating, $review]);
        
        create_notification($booking['provider_id'], 'rating', "You received a {$rating}-star review for booking #{$booking['booking_number']}.", APP_URL . '/provider/reviews.php');
        set_flash('success', 'Thank you for your review!');
        redirect(APP_URL . '/customer/booking-detail.php?id=' . $bookingId);
    }
}

// Status timeline
$statusFlow = ['pending', 'confirmed', 'processing', 'ready', 'delivered'];
$currentIndex = array_search($booking['status'], $statusFlow);
if ($currentIndex === false) $currentIndex = -1;

$page_title = 'Booking #' . $booking['booking_number'];
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <!-- Back -->
    <a href="<?= APP_URL ?>/customer/bookings.php" class="inline-flex items-center text-gray-500 hover:text-orange-500 mb-4 text-sm">
        <i class="fas fa-arrow-left mr-2"></i> Back to Bookings
    </a>
    
    <?= render_flash() ?>
    
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Detail -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-md p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-xl font-bold text-gray-800"><?= e($booking['booking_number']) ?></h1>
                        <p class="text-sm text-gray-500">Placed <?= date('M j, Y \a\t g:i A', strtotime($booking['created_at'])) ?></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <?= booking_status_badge($booking['status']) ?>
                        <?= payment_status_badge($booking['payment_status']) ?>
                    </div>
                </div>
                
                <!-- Status Timeline -->
                <?php if ($booking['status'] !== 'cancelled'): ?>
                <div class="flex items-center justify-between mb-2">
                    <?php foreach ($statusFlow as $i => $s): ?>
                    <div class="flex flex-col items-center flex-1 <?= $i <= $currentIndex ? 'text-orange-500' : 'text-gray-300' ?>">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold <?= $i <= $currentIndex ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-500' ?> <?= $i === $currentIndex ? 'ring-4 ring-orange-200' : '' ?>">
                            <?php if ($i < $currentIndex): ?><i class="fas fa-check text-xs"></i>
                            <?php else: echo $i + 1; endif; ?>
                        </div>
                        <span class="text-xs mt-1 capitalize hidden sm:block"><?= $s ?></span>
                    </div>
                    <?php if ($i < count($statusFlow) - 1): ?>
                    <div class="flex-1 h-0.5 <?= $i < $currentIndex ? 'bg-orange-500' : 'bg-gray-200' ?> mx-1"></div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="bg-red-50 text-red-700 rounded-lg p-4 text-center">
                    <i class="fas fa-times-circle mr-2"></i> This booking has been cancelled.
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Booking Info -->
            <div class="bg-white rounded-2xl shadow-md p-6">
                <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-info-circle text-deepblue-800 mr-2"></i>Booking Details</h2>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div><span class="text-gray-500">Service Type:</span> <span class="font-medium text-gray-800"><?= ucwords(str_replace('_', ' & ', $booking['service_type'])) ?></span></div>
                    <div><span class="text-gray-500">Weight:</span> <span class="font-medium text-gray-800"><?= $booking['weight_kg'] ?> kg</span></div>
                    <div><span class="text-gray-500">Pickup Date:</span> <span class="font-medium text-gray-800"><?= date('M j, Y', strtotime($booking['pickup_date'])) ?></span></div>
                    <div><span class="text-gray-500">Pickup Time:</span> <span class="font-medium text-gray-800"><?= date('g:i A', strtotime($booking['pickup_time'])) ?></span></div>
                    <div><span class="text-gray-500">Delivery Date:</span> <span class="font-medium text-gray-800"><?= date('M j, Y', strtotime($booking['delivery_date'])) ?></span></div>
                    <div><span class="text-gray-500">Total Amount:</span> <span class="font-bold text-orange-600 text-base"><?= format_currency($booking['total_amount']) ?></span></div>
                    <div class="md:col-span-2"><span class="text-gray-500">Pickup Address:</span> <span class="font-medium text-gray-800"><?= e($booking['pickup_address']) ?></span></div>
                    <?php if ($booking['special_instructions']): ?>
                    <div class="md:col-span-2"><span class="text-gray-500">Special Instructions:</span><br><span class="text-gray-700"><?= e($booking['special_instructions']) ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Rating Section -->
            <?php if ($booking['status'] === 'delivered'): ?>
            <div class="bg-white rounded-2xl shadow-md p-6">
                <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-star text-yellow-500 mr-2"></i>Rate This Service</h2>
                <?php if ($existingRating): ?>
                <div class="bg-yellow-50 rounded-lg p-4">
                    <div class="flex gap-1 text-yellow-400 mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?= $i > $existingRating['rating'] ? ' text-gray-300' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <?php if ($existingRating['review']): ?>
                    <p class="text-sm text-gray-600 italic">"<?= e($existingRating['review']) ?>"</p>
                    <?php endif; ?>
                    <p class="text-xs text-gray-400 mt-2">Reviewed <?= time_ago($existingRating['created_at']) ?></p>
                </div>
                <?php else: ?>
                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-4">
                        <div class="flex gap-2 text-3xl" id="starRating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" onclick="setRating(<?= $i ?>)" class="star-btn text-gray-300 hover:text-yellow-400 transition-colors" data-value="<?= $i ?>">
                                <i class="fas fa-star"></i>
                            </button>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" value="5">
                    </div>
                    <textarea name="review" rows="3" maxlength="500" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 text-sm resize-y mb-4" placeholder="Share your experience (optional)..."></textarea>
                    <button type="submit" name="submit_rating" class="px-6 py-2.5 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition-all text-sm">
                        <i class="fas fa-paper-plane mr-1"></i> Submit Review
                    </button>
                </form>
                <script>
                function setRating(val) {
                    document.getElementById('ratingInput').value = val;
                    document.querySelectorAll('.star-btn').forEach((btn, i) => {
                        btn.querySelector('i').className = 'fas fa-star' + (i < val ? '' : ' text-gray-300');
                        if (i < val) btn.classList.add('text-yellow-400');
                        else { btn.classList.remove('text-yellow-400'); btn.classList.add('text-gray-300'); }
                    });
                }
                setRating(5);
                </script>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Provider Info -->
            <div class="bg-white rounded-2xl shadow-md p-6">
                <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-store text-teal-600 mr-2"></i>Provider</h3>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-orange-100 text-orange-500 rounded-full flex items-center justify-center font-bold text-lg">
                        <?= mb_substr($booking['provider_name'], 0, 1) ?>
                    </div>
                    <div>
                        <div class="font-bold text-gray-800"><?= e($booking['business_name'] ?: $booking['provider_name']) ?></div>
                        <div class="text-xs text-gray-500 capitalize"><?= e(str_replace('_', ' ', $booking['business_type'])) ?></div>
                    </div>
                </div>
                <?php if ($booking['provider_phone']): ?>
                <a href="tel:<?= e($booking['provider_phone']) ?>" class="flex items-center gap-2 text-sm text-gray-600 hover:text-orange-500 mb-2">
                    <i class="fas fa-phone text-xs"></i> <?= e($booking['provider_phone']) ?>
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Payment -->
            <div class="bg-white rounded-2xl shadow-md p-6">
                <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-credit-card text-deepblue-800 mr-2"></i>Payment</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Amount</span><span class="font-bold text-gray-800"><?= format_currency($booking['total_amount']) ?></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Status</span><?= payment_status_badge($booking['payment_status']) ?></div>
                    <?php if ($booking['mpesa_receipt']): ?>
                    <div class="flex justify-between"><span class="text-gray-500">M-Pesa Receipt</span><span class="font-mono text-xs"><?= e($booking['mpesa_receipt']) ?></span></div>
                    <?php endif; ?>
                </div>
                
                <?php if ($booking['payment_status'] === 'unpaid' && !in_array($booking['status'], ['cancelled'])): ?>
                <a href="<?= APP_URL ?>/customer/pay.php?booking_id=<?= $booking['id'] ?>" class="mt-4 block w-full py-2.5 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-all text-center text-sm">
                    <i class="fas fa-mobile-alt mr-1"></i> Pay via M-Pesa
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Actions -->
            <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
            <div class="bg-white rounded-2xl shadow-md p-6">
                <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-cog text-gray-400 mr-2"></i>Actions</h3>
                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?')">
                    <?= csrf_field() ?>
                    <button type="submit" name="cancel_booking" class="w-full py-2.5 bg-red-50 text-red-600 border border-red-200 font-semibold rounded-lg hover:bg-red-100 transition-colors text-sm">
                        <i class="fas fa-times mr-1"></i> Cancel Booking
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
