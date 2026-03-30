<?php
/**
 * UsafiKonect - M-Pesa Integration (Daraja API)
 * Supports sandbox testing and live production
 */

require_once __DIR__ . '/database.php';

// M-Pesa Configuration
define('MPESA_TEST_MODE', (bool)get_setting('mpesa_test_mode', '1'));
define('MPESA_ENV', get_setting('mpesa_env', 'sandbox'));
define('MPESA_CONSUMER_KEY', get_setting('mpesa_consumer_key', 'YOUR_CONSUMER_KEY'));
define('MPESA_CONSUMER_SECRET', get_setting('mpesa_consumer_secret', 'YOUR_CONSUMER_SECRET'));
define('MPESA_SHORTCODE', get_setting('mpesa_shortcode', '174379'));
define('MPESA_PASSKEY', get_setting('mpesa_passkey', 'YOUR_PASSKEY'));
define('MPESA_CALLBACK_URL', get_setting('mpesa_callback_url', APP_URL . '/api/mpesa-callback.php'));

// API Endpoints
define('MPESA_AUTH_URL', MPESA_ENV === 'live'
    ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
    : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
);
define('MPESA_STK_URL', MPESA_ENV === 'live'
    ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
    : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
);

/**
 * Get M-Pesa access token
 */
function mpesa_get_access_token(): string|false {
    if (MPESA_TEST_MODE) {
        return 'test_access_token_' . time();
    }
    
    $credentials = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);
    
    $ch = curl_init(MPESA_AUTH_URL);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $credentials],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("M-Pesa auth failed. HTTP {$httpCode}: {$response}");
        return false;
    }
    
    $result = json_decode($response, true);
    return $result['access_token'] ?? false;
}

/**
 * Initiate STK Push (Lipa Na M-Pesa)
 * 
 * @param string $phone Phone number (e.g., 254712345678)
 * @param float $amount Amount in KES
 * @param string $reference Account reference (e.g., booking number)
 * @param string $description Transaction description
 * @return array ['success' => bool, 'message' => string, 'data' => array]
 */
function mpesa_stk_push(string $phone, float $amount, string $reference, string $description = 'UsafiKonect Payment'): array {
    // Format phone number to 254XXXXXXXXX
    $phone = format_phone_for_mpesa($phone);
    
    // Test mode - simulate STK push
    if (MPESA_TEST_MODE) {
        $simulatedId = 'SIM_' . strtoupper(bin2hex(random_bytes(5)));
        
        // Log simulated transaction
        error_log("M-PESA SIMULATION: STK Push to {$phone} for KES {$amount} (Ref: {$reference}) - ID: {$simulatedId}");
        
        return [
            'success' => true,
            'message' => "STK Push sent to {$phone}. Please enter your M-Pesa PIN to complete payment.",
            'receipt' => 'SIM' . strtoupper(substr(md5($simulatedId), 0, 8)),
            'checkout_id' => $simulatedId,
            'data' => [
                'MerchantRequestID' => 'SIM_MR_' . time(),
                'CheckoutRequestID' => $simulatedId,
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success. Request accepted for processing (SIMULATED)',
                'CustomerMessage' => "Lipa Na M-Pesa request sent to {$phone}",
            ],
            'test_mode' => true,
            'simulated_id' => $simulatedId,
        ];
    }
    
    // Production mode
    $accessToken = mpesa_get_access_token();
    if (!$accessToken) {
        return ['success' => false, 'message' => 'Failed to authenticate with M-Pesa.', 'data' => []];
    }
    
    $timestamp = date('YmdHis');
    $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);
    
    $payload = [
        'BusinessShortCode' => MPESA_SHORTCODE,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => (int)ceil($amount),
        'PartyA' => $phone,
        'PartyB' => MPESA_SHORTCODE,
        'PhoneNumber' => $phone,
        'CallBackURL' => MPESA_CALLBACK_URL,
        'AccountReference' => $reference,
        'TransactionDesc' => $description,
    ];
    
    $ch = curl_init(MPESA_STK_URL);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
        return [
            'success' => true,
            'message' => $result['CustomerMessage'] ?? 'STK Push sent successfully.',
            'data' => $result,
        ];
    }
    
    error_log("M-Pesa STK Push failed. HTTP {$httpCode}: {$response}");
    return [
        'success' => false,
        'message' => $result['errorMessage'] ?? 'M-Pesa request failed. Please try again.',
        'data' => $result ?? [],
    ];
}

/**
 * Format phone number for M-Pesa (254XXXXXXXXX format)
 */
function format_phone_for_mpesa(string $phone): string {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (str_starts_with($phone, '0')) {
        $phone = '254' . substr($phone, 1);
    } elseif (str_starts_with($phone, '+254')) {
        $phone = substr($phone, 1);
    } elseif (!str_starts_with($phone, '254')) {
        $phone = '254' . $phone;
    }
    
    return $phone;
}

/**
 * Process M-Pesa callback data
 */
function process_mpesa_callback(array $data): array {
    $resultCode = $data['Body']['stkCallback']['ResultCode'] ?? -1;
    $resultDesc = $data['Body']['stkCallback']['ResultDesc'] ?? 'Unknown';
    $merchantRequestId = $data['Body']['stkCallback']['MerchantRequestID'] ?? '';
    $checkoutRequestId = $data['Body']['stkCallback']['CheckoutRequestID'] ?? '';
    
    $mpesaReceiptNumber = '';
    $amount = 0;
    $phone = '';
    
    if ($resultCode == 0 && isset($data['Body']['stkCallback']['CallbackMetadata'])) {
        foreach ($data['Body']['stkCallback']['CallbackMetadata']['Item'] as $item) {
            switch ($item['Name']) {
                case 'MpesaReceiptNumber':
                    $mpesaReceiptNumber = $item['Value'];
                    break;
                case 'Amount':
                    $amount = (float)$item['Value'];
                    break;
                case 'PhoneNumber':
                    $phone = (string)$item['Value'];
                    break;
            }
        }
    }
    
    return [
        'success' => $resultCode == 0,
        'result_code' => $resultCode,
        'result_desc' => $resultDesc,
        'merchant_request_id' => $merchantRequestId,
        'checkout_request_id' => $checkoutRequestId,
        'receipt_number' => $mpesaReceiptNumber,
        'amount' => $amount,
        'phone' => $phone,
    ];
}

/**
 * Simulate confirming a payment (admin function for test mode)
 */
function simulate_payment_confirmation(string $checkoutRequestId, string $type = 'booking', int $recordId = 0): bool {
    if (!MPESA_TEST_MODE) return false;
    
    $db = getDB();
    $simulatedReceipt = 'SIM' . strtoupper(substr(md5($checkoutRequestId), 0, 8));
    
    if ($type === 'booking') {
        $stmt = $db->prepare("UPDATE bookings SET payment_status = 'paid', mpesa_receipt = ? WHERE id = ?");
        return $stmt->execute([$simulatedReceipt, $recordId]);
    } elseif ($type === 'subscription') {
        $stmt = $db->prepare("UPDATE subscriptions SET payment_status = 'paid', mpesa_transaction_id = ? WHERE id = ?");
        return $stmt->execute([$simulatedReceipt, $recordId]);
    } elseif ($type === 'wallet') {
        // Wallet top-up handled separately through wallet_transactions
        return true;
    }
    
    return false;
}
