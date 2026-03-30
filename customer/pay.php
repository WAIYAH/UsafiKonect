<?php
/**
 * UsafiKonect - Customer: Pay
 * M-Pesa STK Push payment and wallet top-up
 */

require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/mpesa.php';
require_login();
require_role('customer');

$db = getDB();
$userId = get_user_id();
$errors = [];
$action = $_GET['action'] ?? 'pay';
$bookingId = (int)($_GET['booking_id'] ?? 0);

$booking = null;
if ($bookingId) {
    $stmt = $db->prepare("SELECT b.*, u.full_name as provider_name FROM bookings b JOIN users u ON b.provider_id = u.id WHERE b.id = ? AND b.customer_id = ?");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch();
    if (!$booking) {
        set_flash('error', 'Booking not found.');
        redirect(APP_URL . '/customer/bookings.php');
    }
}

// Get user phone
$userPhone = $db->prepare("SELECT phone FROM users WHERE id = ?");
$userPhone->execute([$userId]);
$userPhone = $userPhone->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        $errors[] = 'Invalid security token.';
    } else {
        $paymentMethod = $_POST['payment_method'] ?? 'mpesa';
        $phone = sanitize_input($_POST['phone'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        
        if ($paymentMethod === 'mpesa') {
            if (empty($phone)) $errors[] = 'Phone number is required.';
            if ($amount < 1) $errors[] = 'Invalid amount.';
            
            if (empty($errors)) {
                $result = mpesa_stk_push($phone, $amount, $booking ? $booking['booking_number'] : 'TOPUP-' . time());
                
                if ($result['success']) {
                    if ($booking) {
                        $stmt = $db->prepare("UPDATE bookings SET payment_status = 'paid', mpesa_receipt = ? WHERE id = ?");
                        $stmt->execute([$result['receipt'] ?? $result['checkout_id'], $bookingId]);
                        
                        // Wallet transaction
                        add_wallet_transaction($userId, 'payment', -$amount, "Payment for booking #{$booking['booking_number']}");
                        
                        create_notification($userId, 'payment', "Payment of " . format_currency($amount) . " for booking #{$booking['booking_number']} received.");
                        create_notification($booking['provider_id'], 'payment', "Payment for booking #{$booking['booking_number']} has been received.");
                        
                        set_flash('success', 'Payment of ' . format_currency($amount) . ' processed successfully!');
                        redirect(APP_URL . '/customer/booking-detail.php?id=' . $bookingId);
                    } else {
                        // Top up wallet
                        add_wallet_transaction($userId, 'top_up', $amount, 'M-Pesa wallet top-up');
                        create_notification($userId, 'payment', "Wallet topped up with " . format_currency($amount));
                        
                        set_flash('success', 'Wallet topped up with ' . format_currency($amount) . '!');
                        redirect(APP_URL . '/customer/wallet.php');
                    }
                } else {
                    $errors[] = $result['error'] ?? 'Payment failed. Please try again.';
                }
            }
        } elseif ($paymentMethod === 'wallet') {
            $balance = get_wallet_balance($userId);
            if ($balance < $amount) {
                $errors[] = 'Insufficient wallet balance. Current balance: ' . format_currency($balance);
            } elseif ($booking) {
                add_wallet_transaction($userId, 'payment', -$amount, "Wallet payment for booking #{$booking['booking_number']}");
                
                $stmt = $db->prepare("UPDATE bookings SET payment_status = 'paid' WHERE id = ?");
                $stmt->execute([$bookingId]);
                
                create_notification($userId, 'payment', "Wallet payment of " . format_currency($amount) . " for booking #{$booking['booking_number']}.");
                create_notification($booking['provider_id'], 'payment', "Wallet payment for booking #{$booking['booking_number']} has been received.");
                
                set_flash('success', 'Paid ' . format_currency($amount) . ' from wallet!');
                redirect(APP_URL . '/customer/booking-detail.php?id=' . $bookingId);
            }
        }
    }
}

$page_title = $booking ? 'Pay for Booking' : 'Top Up Wallet';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <a href="<?= $booking ? APP_URL . '/customer/booking-detail.php?id=' . $bookingId : APP_URL . '/customer/wallet.php' ?>" class="inline-flex items-center text-gray-500 hover:text-orange-500 mb-4 text-sm">
        <i class="fas fa-arrow-left mr-2"></i> Back
    </a>
    
    <h1 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fas fa-credit-card text-orange-500 mr-2"></i>
        <?= $booking ? 'Pay for Booking #' . e($booking['booking_number']) : 'Top Up Wallet' ?>
    </h1>
    
    <?= render_flash() ?>
    
    <?php if (!empty($errors)): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
        <ul class="text-sm text-red-700 list-disc list-inside">
            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="max-w-lg mx-auto">
        <?php if ($booking): ?>
        <div class="bg-white rounded-2xl shadow-md p-6 mb-6">
            <h3 class="font-bold text-gray-800 mb-3">Booking Summary</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Booking</span><span class="font-medium"><?= e($booking['booking_number']) ?></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Provider</span><span class="font-medium"><?= e($booking['provider_name']) ?></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Service</span><span class="font-medium"><?= ucwords(str_replace('_', ' & ', $booking['service_type'])) ?></span></div>
                <div class="flex justify-between border-t border-gray-100 pt-2 mt-2"><span class="text-gray-800 font-bold">Total</span><span class="font-bold text-orange-600 text-lg"><?= format_currency($booking['total_amount']) ?></span></div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-2xl shadow-md p-6">
            <form method="POST" class="space-y-5">
                <?= csrf_field() ?>
                
                <?php if ($booking): ?>
                <!-- Payment Method Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="mpesa" checked class="hidden peer">
                            <div class="border-2 border-gray-200 rounded-xl p-4 text-center peer-checked:border-green-500 peer-checked:bg-green-50 transition-all">
                                <div class="text-2xl mb-1">📱</div>
                                <div class="font-semibold text-sm text-gray-800">M-Pesa</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="wallet" class="hidden peer">
                            <div class="border-2 border-gray-200 rounded-xl p-4 text-center peer-checked:border-teal-500 peer-checked:bg-teal-50 transition-all">
                                <div class="text-2xl mb-1">💰</div>
                                <div class="font-semibold text-sm text-gray-800">Wallet</div>
                                <div class="text-xs text-gray-500"><?= format_currency(get_wallet_balance($userId)) ?></div>
                            </div>
                        </label>
                    </div>
                </div>
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">M-Pesa Phone Number</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3.5 text-gray-400"><i class="fas fa-phone"></i></span>
                        <input type="tel" name="phone" value="<?= e($userPhone) ?>" required
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" placeholder="0712345678">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount (KES)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3.5 text-gray-400 font-bold">KES</span>
                        <input type="number" name="amount" min="1" step="1" 
                            value="<?= $booking ? $booking['total_amount'] : '' ?>" 
                            <?= $booking ? 'readonly' : 'required' ?>
                            class="w-full pl-14 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 <?= $booking ? 'bg-gray-50' : '' ?>">
                    </div>
                </div>
                
                <button type="submit" class="w-full py-3 bg-green-600 text-white font-bold rounded-xl hover:bg-green-700 transition-all shadow-lg text-lg">
                    <i class="fas fa-mobile-alt mr-2"></i> <?= $booking ? 'Pay Now' : 'Top Up' ?>
                </button>
                
                <p class="text-xs text-gray-400 text-center">You'll receive an M-Pesa STK push prompt on your phone. Enter your M-Pesa PIN to complete the transaction.</p>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
