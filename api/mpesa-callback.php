<?php
/**
 * UsafiKonect - M-Pesa Callback
 * Receives STK Push results from Safaricom Daraja API
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/mpesa.php';

// Log raw callback
$rawInput = file_get_contents('php://input');
$logFile = __DIR__ . '/../logs/mpesa_callback_' . date('Y-m-d') . '.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $rawInput . "\n", FILE_APPEND | LOCK_EX);

header('Content-Type: application/json');

// Safaricom IP whitelist (bypass in debug mode)
$safaricomIPs = ['196.201.214.200', '196.201.214.206', '196.201.213.114', '196.201.214.207',
                  '196.201.214.208', '196.201.213.44', '196.201.212.127', '196.201.212.138',
                  '196.201.212.129', '196.201.212.136', '196.201.212.74', '196.201.212.69'];
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
if (!APP_DEBUG && !in_array($clientIP, $safaricomIPs)) {
    http_response_code(403);
    error_log("M-Pesa callback from unauthorized IP: {$clientIP}");
    exit;
}

$data = json_decode($rawInput, true);

if (!$data || !isset($data['Body']['stkCallback'])) {
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid payload']);
    exit;
}

$callback = $data['Body']['stkCallback'];
$resultCode = $callback['ResultCode'] ?? 1;
$merchantRequestID = $callback['MerchantRequestID'] ?? '';
$checkoutRequestID = $callback['CheckoutRequestID'] ?? '';

$db = getDB();

// Find the booking with this checkout request
// We store checkout_request_id during STK push initiation
$booking = $db->prepare("SELECT * FROM bookings WHERE mpesa_checkout_id = ?");
$booking->execute([$checkoutRequestID]);
$booking = $booking->fetch();

if (!$booking) {
    // Try wallet top-up
    $wt = $db->prepare("SELECT * FROM wallet_transactions WHERE reference = ?");
    $wt->execute([$checkoutRequestID]);
    $walletTx = $wt->fetch();
    
    if ($walletTx && $resultCode === 0) {
        // Confirm wallet top-up
        $items = $callback['CallbackMetadata']['Item'] ?? [];
        $mpesaReceipt = '';
        foreach ($items as $item) {
            if ($item['Name'] === 'MpesaReceiptNumber') {
                $mpesaReceipt = $item['Value'] ?? '';
            }
        }
        $db->prepare("UPDATE wallet_transactions SET description = CONCAT(description, ' - Receipt: ', ?) WHERE id = ?")->execute([$mpesaReceipt, $walletTx['id']]);
    }
    
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    exit;
}

if ($resultCode === 0) {
    // Payment successful
    $items = $callback['CallbackMetadata']['Item'] ?? [];
    $mpesaReceipt = '';
    $amount = 0;
    $phone = '';
    
    foreach ($items as $item) {
        switch ($item['Name']) {
            case 'MpesaReceiptNumber': $mpesaReceipt = $item['Value'] ?? ''; break;
            case 'Amount': $amount = $item['Value'] ?? 0; break;
            case 'PhoneNumber': $phone = $item['Value'] ?? ''; break;
        }
    }
    
    $db->beginTransaction();
    try {
        // Update booking
        $update = $db->prepare("UPDATE bookings SET payment_status = 'paid', mpesa_receipt = ?, updated_at = NOW() WHERE id = ?");
        $update->execute([$mpesaReceipt, $booking['id']]);
        
        // Create wallet transaction record
        add_wallet_transaction($booking['customer_id'], 'payment', -$amount, 
            "M-Pesa payment for booking #" . $booking['booking_number'] . " - Receipt: $mpesaReceipt");
        
        // Notify customer
        create_notification($booking['customer_id'], 'payment',
            "Payment of " . format_currency($amount) . " received for booking #" . $booking['booking_number'] . ". Receipt: $mpesaReceipt",
            APP_URL . '/customer/booking-detail.php?id=' . $booking['id']);
        
        // Notify provider
        create_notification($booking['provider_id'], 'payment',
            "Payment received for booking #" . $booking['booking_number'] . ". Amount: " . format_currency($amount),
            APP_URL . '/provider/booking-detail.php?id=' . $booking['id']);
        
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        error_log("M-Pesa callback error: " . $e->getMessage());
    }
} else {
    // Payment failed or cancelled
    error_log("M-Pesa payment failed for booking {$booking['booking_number']}: ResultCode=$resultCode");
}

echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
