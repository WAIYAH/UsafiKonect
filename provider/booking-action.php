<?php
/**
 * UsafiKonect - Provider Booking Actions
 * Handle status updates: confirm, decline, processing, ready, delivered
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('provider');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/provider/bookings.php');
    exit;
}

validate_csrf();

$db = getDB();
$userId = get_user_id();
$bookingId = (int)($_POST['booking_id'] ?? 0);
$action = sanitize_input($_POST['action'] ?? '');
$redirect = ($_POST['redirect'] ?? '') === 'detail' ? 'detail' : 'list';

// Verify ownership
$stmt = $db->prepare("SELECT b.*, u.full_name as customer_name FROM bookings b JOIN users u ON b.customer_id = u.id WHERE b.id = ? AND b.provider_id = ?");
$stmt->execute([$bookingId, $userId]);
$booking = $stmt->fetch();

if (!$booking) {
    set_flash('error', 'Booking not found.');
    header('Location: ' . APP_URL . '/provider/bookings.php');
    exit;
}

$redirectUrl = $redirect === 'detail' 
    ? APP_URL . '/provider/booking-detail.php?id=' . $bookingId 
    : APP_URL . '/provider/bookings.php';

$validTransitions = [
    'confirm' => ['from' => 'pending', 'to' => 'confirmed'],
    'decline' => ['from' => 'pending', 'to' => 'cancelled'],
    'status_processing' => ['from' => 'confirmed', 'to' => 'processing'],
    'status_ready' => ['from' => 'processing', 'to' => 'ready'],
    'status_delivered' => ['from' => 'ready', 'to' => 'delivered'],
];

if (!isset($validTransitions[$action])) {
    set_flash('error', 'Invalid action.');
    header('Location: ' . $redirectUrl);
    exit;
}

$transition = $validTransitions[$action];
if ($booking['status'] !== $transition['from']) {
    set_flash('error', 'Cannot perform this action on the current booking status.');
    header('Location: ' . $redirectUrl);
    exit;
}

$newStatus = $transition['to'];

try {
    $db->beginTransaction();
    
    $update = $db->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
    $update->execute([$newStatus, $bookingId]);
    
    // Notifications
    $providerName = $_SESSION['user_data']['full_name'] ?? 'Your Provider';
    $bn = $booking['booking_number'];
    
    switch ($newStatus) {
        case 'confirmed':
            create_notification($booking['customer_id'], 'booking', 
                "Booking #$bn has been confirmed by $providerName. They'll pick up your laundry as scheduled.",
                APP_URL . '/customer/booking-detail.php?id=' . $bookingId);
            $flashMsg = 'Booking accepted! Customer has been notified.';
            break;
            
        case 'cancelled':
            // Provider declined
            $cancelStmt = $db->prepare("UPDATE bookings SET cancelled_reason = 'Declined by provider', cancelled_by = 'provider' WHERE id = ?");
            $cancelStmt->execute([$bookingId]);
            
            // Refund if paid
            if ($booking['payment_status'] === 'paid') {
                $refunded = add_wallet_transaction($booking['customer_id'], 'refund', $booking['total_amount'], 
                    "Refund for declined booking #$bn");
                if (!$refunded) {
                    throw new Exception('Wallet refund failed for booking #' . $bn);
                }
                $refundStmt = $db->prepare("UPDATE bookings SET payment_status = 'refunded' WHERE id = ?");
                $refundStmt->execute([$bookingId]);
            }
            
            create_notification($booking['customer_id'], 'booking',
                "Sorry, provider $providerName declined booking #$bn. Please try another provider.",
                APP_URL . '/customer/book.php');
            $flashMsg = 'Booking declined. Customer notified.';
            break;
            
        case 'processing':
            create_notification($booking['customer_id'], 'booking',
                "Your laundry from booking #$bn is now being processed! We'll notify you when it's ready.",
                APP_URL . '/customer/booking-detail.php?id=' . $bookingId);
            $flashMsg = 'Booking marked as processing.';
            break;
            
        case 'ready':
            create_notification($booking['customer_id'], 'booking',
                "Great news! Your laundry (booking #$bn) is ready for delivery!",
                APP_URL . '/customer/booking-detail.php?id=' . $bookingId);
            $flashMsg = 'Booking marked as ready for delivery.';
            break;
            
        case 'delivered':
            create_notification($booking['customer_id'], 'booking',
                "Your laundry (booking #$bn) has been delivered. Please rate your experience!",
                APP_URL . '/customer/booking-detail.php?id=' . $bookingId);
            
            // Award loyalty points
            $pointsPerBooking = (int)get_setting('loyalty_points_per_booking', 10);
            update_loyalty_points($booking['customer_id'], $pointsPerBooking, 'Points for completed booking #' . $bn);
            $flashMsg = 'Booking marked as delivered! Customer has been notified.';
            break;
    }
    
    $db->commit();
    set_flash('success', $flashMsg);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Booking action error: " . $e->getMessage());
    set_flash('error', 'Something went wrong. Please try again.');
}

header('Location: ' . $redirectUrl);
exit;
