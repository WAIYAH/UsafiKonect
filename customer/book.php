<?php
/**
 * UsafiKonect - Customer: Book a Service
 * Browse providers, select service, schedule pickup
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('customer');

$db = getDB();
$userId = get_user_id();
$errors = [];
$step = (int)($_GET['step'] ?? 1);

// Get customer's estate
$customer = $db->prepare("SELECT estate, phone FROM users WHERE id = ?");
$customer->execute([$userId]);
$customer = $customer->fetch();

// Get approved providers
$providerQuery = "SELECT u.id, u.full_name, u.profile_image, u.estate, pd.business_name, pd.business_type, pd.price_per_kg, pd.description,
    (SELECT ROUND(AVG(r.rating),1) FROM ratings r WHERE r.provider_id = u.id) as avg_rating,
    (SELECT COUNT(*) FROM ratings r WHERE r.provider_id = u.id) as review_count
    FROM users u 
    JOIN provider_details pd ON u.id = pd.user_id 
    WHERE u.role = 'provider' AND u.is_active = 1 AND pd.is_approved = 1 
    ORDER BY avg_rating DESC";
$providers = $db->query($providerQuery)->fetchAll();

// Estates list
$estates = ['Roysambu','Umoja','Donholm','Kilimani','Langata','Westlands','South B','South C','Embakasi','Kasarani','Ruaka','Rongai','Kibera','Kawangware','Eastleigh','Buruburu','Pangani','Ngara','Parklands','Lavington','Karen','Kileleshwa','Upperhill','Hurlingham','Thika Road','Mombasa Road','Jogoo Road','Outer Ring','Pipeline','Kayole'];

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    if (!validate_csrf_token()) {
        $errors[] = 'Invalid security token.';
    } else {
        $providerId = (int)($_POST['provider_id'] ?? 0);
        $serviceType = sanitize_input($_POST['service_type'] ?? '');
        $weight = (float)($_POST['weight_kg'] ?? 0);
        $pickupDate = $_POST['pickup_date'] ?? '';
        $pickupTime = $_POST['pickup_time'] ?? '';
        $deliveryDate = $_POST['delivery_date'] ?? '';
        $specialInstructions = sanitize_input($_POST['special_instructions'] ?? '');
        $pickupAddress = sanitize_input($_POST['pickup_address'] ?? '');
        
        // Validate
        if (!$providerId) $errors[] = 'Please select a provider.';
        if (!in_array($serviceType, ['wash_fold', 'wash_iron', 'dry_clean', 'iron_only'])) $errors[] = 'Invalid service type.';
        if ($weight < 1 || $weight > 50) $errors[] = 'Weight must be between 1 and 50 kg.';
        if (empty($pickupDate) || strtotime($pickupDate) < strtotime('today')) $errors[] = 'Pickup date must be today or later.';
        if (empty($pickupTime)) $errors[] = 'Please select a pickup time.';
        if (empty($deliveryDate) || strtotime($deliveryDate) < strtotime($pickupDate)) $errors[] = 'Delivery date must be on or after pickup date.';
        if (empty($pickupAddress)) $errors[] = 'Please enter pickup address.';
        
        // Rate limit
        if (!check_booking_rate_limit($userId)) $errors[] = 'Too many bookings. Please try again later.';
        
        if (empty($errors)) {
            // Calculate amount
            $providerDetails = $db->prepare("SELECT price_per_kg FROM provider_details WHERE user_id = ?");
            $providerDetails->execute([$providerId]);
            $pricePerKg = $providerDetails->fetchColumn() ?: 100;
            $totalAmount = $weight * $pricePerKg;
            
            // Check if free booking applies
            $useFree = isset($_POST['use_free']) && has_free_booking($userId);
            if ($useFree) $totalAmount = 0;
            
            // Check subscription discount
            $activeSub = $db->prepare("SELECT plan_type FROM subscriptions WHERE user_id = ? AND status = 'active' AND end_date > NOW()");
            $activeSub->execute([$userId]);
            $sub = $activeSub->fetch();
            if ($sub && !$useFree) {
                $discounts = ['weekly' => 0.10, 'monthly' => 0.15, 'yearly' => 0.20];
                $discount = $discounts[$sub['plan_type']] ?? 0;
                $totalAmount *= (1 - $discount);
            }
            
            $bookingNumber = generate_booking_number();
            
            $stmt = $db->prepare("INSERT INTO bookings (booking_number, customer_id, provider_id, service_type, weight_kg, total_amount, pickup_date, pickup_time, delivery_date, pickup_address, special_instructions, status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid')");
            $stmt->execute([
                $bookingNumber, $userId, $providerId, $serviceType,
                $weight, $totalAmount, $pickupDate, $pickupTime,
                $deliveryDate, $pickupAddress, $specialInstructions
            ]);
            
            $bookingId = $db->lastInsertId();
            
            // Update loyalty points
            if (!$useFree) {
                update_loyalty_points($userId, 10, 'Booking #' . $bookingNumber);
            }
            
            // Notifications
            create_notification($userId, 'booking', "Your booking #{$bookingNumber} has been placed. We'll notify you when the provider confirms.", APP_URL . '/customer/booking-detail.php?id=' . $bookingId);
            create_notification($providerId, 'booking', "You have a new booking #{$bookingNumber}. Please confirm or decline.", APP_URL . '/provider/booking-detail.php?id=' . $bookingId);
            
            set_flash('success', "Booking #{$bookingNumber} placed successfully! Total: " . format_currency($totalAmount));
            redirect(APP_URL . '/customer/booking-detail.php?id=' . $bookingId);
        }
    }
}

$page_title = 'Book a Service';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-plus-circle text-orange-500 mr-2"></i>Book a Service</h1>
        <p class="text-gray-500 mt-1">Find a provider and schedule your laundry pickup.</p>
    </div>
    
    <?= render_flash() ?>
    
    <?php if (!empty($errors)): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
        <ul class="text-sm text-red-700 list-disc list-inside">
            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <?= csrf_field() ?>
        
        <!-- Step 1: Select Provider -->
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-orange-50">
                <h2 class="font-bold text-gray-800"><span class="text-orange-500 mr-2">①</span> Select a Provider</h2>
            </div>
            <div class="p-6">
                <!-- Filter by estate -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Estate</label>
                    <select id="estateFilter" onchange="filterProviders()" class="w-full md:w-64 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 text-sm">
                        <option value="">All Estates</option>
                        <?php foreach ($estates as $est): ?>
                            <option value="<?= $est ?>" <?= ($customer['estate'] ?? '') === $est ? 'selected' : '' ?>><?= $est ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="providerGrid" class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <?php foreach ($providers as $p): ?>
                    <label class="provider-card cursor-pointer block" data-estate="<?= e($p['estate']) ?>">
                        <input type="radio" name="provider_id" value="<?= $p['id'] ?>" class="hidden peer" required
                            <?= (isset($_POST['provider_id']) && $_POST['provider_id'] == $p['id']) ? 'checked' : '' ?>>
                        <div class="border-2 border-gray-200 rounded-xl p-4 transition-all peer-checked:border-orange-500 peer-checked:bg-orange-50 hover:border-orange-300">
                            <div class="flex items-start gap-3">
                                <div class="w-12 h-12 bg-orange-100 text-orange-500 rounded-full flex items-center justify-center font-bold text-lg flex-shrink-0">
                                    <?= mb_substr($p['full_name'], 0, 1) ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-gray-800 truncate"><?= e($p['business_name'] ?: $p['full_name']) ?></div>
                                    <div class="text-xs text-gray-500">
                                        <i class="fas fa-map-pin mr-1"></i><?= e($p['estate']) ?> &middot;
                                        <span class="capitalize"><?= e(str_replace('_', ' ', $p['business_type'])) ?></span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <div class="flex gap-0.5 text-yellow-400 text-xs">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?= $i > round($p['avg_rating'] ?? 0) ? ' text-gray-300' : '' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-xs text-gray-500">(<?= $p['review_count'] ?>)</span>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-sm font-bold text-orange-600"><?= format_currency($p['price_per_kg']) ?></div>
                                    <div class="text-xs text-gray-400">/kg</div>
                                </div>
                            </div>
                            <?php if ($p['description']): ?>
                            <p class="text-xs text-gray-500 mt-2 line-clamp-2"><?= e(mb_substr($p['description'], 0, 100)) ?></p>
                            <?php endif; ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($providers)): ?>
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-store-slash text-4xl mb-3"></i>
                    <p>No approved providers available yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Step 2: Service Details -->
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-blue-50">
                <h2 class="font-bold text-gray-800"><span class="text-deepblue-800 mr-2">②</span> Service Details</h2>
            </div>
            <div class="p-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Service Type</label>
                        <select name="service_type" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                            <option value="">Select service...</option>
                            <option value="wash_fold" <?= ($_POST['service_type'] ?? '') === 'wash_fold' ? 'selected' : '' ?>>Wash & Fold</option>
                            <option value="wash_iron" <?= ($_POST['service_type'] ?? '') === 'wash_iron' ? 'selected' : '' ?>>Wash & Iron</option>
                            <option value="dry_clean" <?= ($_POST['service_type'] ?? '') === 'dry_clean' ? 'selected' : '' ?>>Dry Cleaning</option>
                            <option value="iron_only" <?= ($_POST['service_type'] ?? '') === 'iron_only' ? 'selected' : '' ?>>Ironing Only</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Weight (kg)</label>
                        <input type="number" name="weight_kg" min="1" max="50" step="0.5" value="<?= e($_POST['weight_kg'] ?? '3') ?>" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Special Instructions</label>
                    <textarea name="special_instructions" rows="3" maxlength="500" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 resize-y text-sm" placeholder="E.g., Separate whites from colors, use cold water for delicates..."><?= e($_POST['special_instructions'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Step 3: Schedule -->
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-teal-50">
                <h2 class="font-bold text-gray-800"><span class="text-teal-600 mr-2">③</span> Schedule & Address</h2>
            </div>
            <div class="p-6">
                <div class="grid md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pickup Date</label>
                        <input type="date" name="pickup_date" min="<?= date('Y-m-d') ?>" value="<?= e($_POST['pickup_date'] ?? date('Y-m-d')) ?>" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pickup Time</label>
                        <select name="pickup_time" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                            <option value="">Select time...</option>
                            <?php foreach (['08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00'] as $t): ?>
                                <option value="<?= $t ?>" <?= ($_POST['pickup_time'] ?? '') === $t ? 'selected' : '' ?>><?= date('g:i A', strtotime($t)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expected Delivery Date</label>
                        <input type="date" name="delivery_date" min="<?= date('Y-m-d') ?>" value="<?= e($_POST['delivery_date'] ?? date('Y-m-d', strtotime('+2 days'))) ?>" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pickup Address</label>
                    <input type="text" name="pickup_address" value="<?= e($_POST['pickup_address'] ?? '') ?>" required maxlength="255"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" placeholder="E.g., Apartment 4B, Green Gardens, Roysambu">
                </div>
                
                <?php if (has_free_booking($userId)): ?>
                <div class="mt-4 bg-green-50 border border-green-200 rounded-lg p-3 flex items-center gap-3">
                    <input type="checkbox" name="use_free" id="use_free" class="text-green-500 focus:ring-green-500 rounded" <?= isset($_GET['free']) ? 'checked' : '' ?>>
                    <label for="use_free" class="text-sm text-green-700 cursor-pointer">
                        <strong><i class="fas fa-gift mr-1"></i>Use your FREE booking reward!</strong> You've earned a complimentary wash.
                    </label>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Submit -->
        <div class="flex justify-end">
            <button type="submit" name="submit_booking" class="px-8 py-3 bg-orange-500 text-white font-bold rounded-xl hover:bg-orange-600 transition-all shadow-lg hover:shadow-xl text-lg">
                <i class="fas fa-check-circle mr-2"></i> Place Booking
            </button>
        </div>
    </form>
</main>

<script>
function filterProviders() {
    const estate = document.getElementById('estateFilter').value.toLowerCase();
    document.querySelectorAll('.provider-card').forEach(card => {
        if (!estate || card.dataset.estate.toLowerCase() === estate) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
document.addEventListener('DOMContentLoaded', filterProviders);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
