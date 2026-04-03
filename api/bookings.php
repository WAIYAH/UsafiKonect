<?php
/**
 * UsafiKonect - Bookings API
 * AJAX endpoints for booking operations
 */

require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = getDB();
$userId = get_user_id();
$role = get_user_role();
$action = sanitize_input($_GET['action'] ?? '');

switch ($action) {
    case 'check_payment':
        // Check if a booking payment has been confirmed (for polling after STK push)
        $bookingId = (int)($_GET['booking_id'] ?? 0);
        $where = $role === 'admin' ? "id = ?" : ($role === 'provider' ? "id = ? AND provider_id = $userId" : "id = ? AND customer_id = $userId");
        $stmt = $db->prepare("SELECT payment_status, mpesa_receipt FROM bookings WHERE $where");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if ($booking) {
            echo json_encode([
                'status' => $booking['payment_status'],
                'receipt' => $booking['mpesa_receipt'],
                'paid' => $booking['payment_status'] === 'paid'
            ]);
        } else {
            echo json_encode(['error' => 'Booking not found']);
        }
        break;
        
    case 'provider_details':
        // Get provider price/details for booking form
        $providerId = (int)($_GET['provider_id'] ?? 0);
        $stmt = $db->prepare("
            SELECT u.full_name, u.estate, pd.business_name, pd.business_type, pd.price_per_kg, pd.description,
                   (SELECT ROUND(AVG(rating),1) FROM ratings WHERE provider_id = u.id) as avg_rating,
                   (SELECT COUNT(*) FROM ratings WHERE provider_id = u.id) as review_count
            FROM users u JOIN provider_details pd ON u.id = pd.user_id
            WHERE u.id = ? AND u.role = 'provider' AND u.is_active = 1 AND pd.is_approved = 1
        ");
        $stmt->execute([$providerId]);
        $provider = $stmt->fetch();
        
        if ($provider) {
            echo json_encode($provider);
        } else {
            echo json_encode(['error' => 'Provider not found']);
        }
        break;
        
    case 'calculate_price':
        // Calculate booking price
        $providerId = (int)($_GET['provider_id'] ?? 0);
        $weight = (float)($_GET['weight'] ?? 0);
        $serviceType = sanitize_input($_GET['service_type'] ?? 'wash_fold');
        
        $stmt = $db->prepare("SELECT price_per_kg FROM provider_details WHERE user_id = ?");
        $stmt->execute([$providerId]);
        $pricePerKg = $stmt->fetchColumn();
        
        if (!$pricePerKg) {
            echo json_encode(['error' => 'Provider not found']);
            break;
        }
        
        $multipliers = ['wash_fold' => 1.0, 'wash_iron' => 1.3, 'dry_clean' => 2.0, 'iron_only' => 0.5];
        $multiplier = $multipliers[$serviceType] ?? 1.0;
        $basePrice = $pricePerKg * $weight * $multiplier;
        
        // Check subscription discount
        $discount = 0;
        if ($role === 'customer') {
            $sub = $db->prepare("SELECT plan_type FROM subscriptions WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE()");
            $sub->execute([$userId]);
            $plan = $sub->fetchColumn();
            $discounts = ['weekly' => 0.10, 'monthly' => 0.15, 'yearly' => 0.20];
            $discount = $discounts[$plan] ?? 0;
        }
        
        $discountAmount = $basePrice * $discount;
        $total = $basePrice - $discountAmount;
        
        echo json_encode([
            'base_price' => round($basePrice, 2),
            'discount_percent' => $discount * 100,
            'discount_amount' => round($discountAmount, 2),
            'total' => round($total, 2),
            'formatted_total' => format_currency($total)
        ]);
        break;
    
    case 'stats':
        // Dashboard quick stats
        if ($role === 'customer') {
            $activeStmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ? AND status IN ('pending','confirmed','processing','ready')");
            $activeStmt->execute([$userId]);
            echo json_encode([
                'active_bookings' => (int)$activeStmt->fetchColumn(),
                'wallet' => get_wallet_balance($userId),
                'unread_notifications' => get_unread_notification_count($userId)
            ]);
        } elseif ($role === 'provider') {
            $pending = $db->prepare("SELECT COUNT(*) FROM bookings WHERE provider_id = ? AND status = 'pending'");
            $pending->execute([$userId]);
            echo json_encode([
                'pending_bookings' => (int)$pending->fetchColumn(),
                'unread_notifications' => get_unread_notification_count($userId)
            ]);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
