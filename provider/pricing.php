<?php
/**
 * UsafiKonect - Provider Pricing
 * Manage service pricing and business settings
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('provider');

$db = getDB();
$userId = get_user_id();

// Get provider details
$stmt = $db->prepare("
    SELECT pd.*, u.full_name, u.estate 
    FROM provider_details pd JOIN users u ON pd.user_id = u.id 
    WHERE pd.user_id = ?
");
$stmt->execute([$userId]);
$provider = $stmt->fetch();

if (!$provider) {
    set_flash('error', 'Provider profile not found.');
    redirect(APP_URL . '/provider/dashboard.php');
}

$errors = [];

// Service multipliers
$serviceTypes = [
    'wash_fold'  => ['label' => 'Wash & Fold',  'multiplier' => 1.0],
    'wash_iron'  => ['label' => 'Wash & Iron',  'multiplier' => 1.3],
    'dry_clean'  => ['label' => 'Dry Cleaning', 'multiplier' => 2.0],
    'iron_only'  => ['label' => 'Iron Only',    'multiplier' => 0.5],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        $errors[] = 'Invalid security token.';
    } else {
        $price_per_kg = (float)($_POST['price_per_kg'] ?? 0);
        $description = sanitize_input($_POST['description'] ?? '');

        if ($price_per_kg < 50 || $price_per_kg > 10000) {
            $errors[] = 'Price per kg must be between KES 50 and KES 10,000.';
        }

        if (empty($errors)) {
            try {
                $upd = $db->prepare("UPDATE provider_details SET price_per_kg = ?, description = ?, updated_at = NOW() WHERE user_id = ?");
                $upd->execute([$price_per_kg, $description, $userId]);

                set_flash('success', 'Pricing updated successfully!');
                redirect(APP_URL . '/provider/pricing.php');
            } catch (Exception $e) {
                error_log("Pricing update error: " . $e->getMessage());
                $errors[] = 'An error occurred. Please try again.';
            }
        }
    }
}

$page_title = 'My Pricing';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-tags text-orange-500 mr-2"></i>My Pricing</h1>
        <p class="text-gray-500 text-sm mt-1">Set your base price and manage service rates</p>
    </div>

    <?= render_flash() ?>

    <?php if (!empty($errors)): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
        <ul class="text-sm text-red-700 list-disc list-inside">
            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Pricing Form -->
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-edit text-deepblue-800 mr-2"></i>Update Pricing</h2>
            <form method="POST" class="space-y-5">
                <?= csrf_field() ?>
                <div>
                    <label for="price_per_kg" class="block text-sm font-medium text-gray-700 mb-1">Base Price per KG (KES)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400 text-sm">KES</span>
                        <input type="number" id="price_per_kg" name="price_per_kg" step="10" min="50" max="10000"
                            value="<?= e($provider['price_per_kg']) ?>"
                            class="w-full pl-14 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-lg font-bold">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">This is your Wash & Fold base rate. Other services are calculated with multipliers.</p>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Service Description</label>
                    <textarea id="description" name="description" rows="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm resize-y"
                        placeholder="Describe your laundry services, specialties, turnaround time..."><?= e($provider['description'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="w-full py-3 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition-all shadow-md">
                    <i class="fas fa-save mr-2"></i>Save Pricing
                </button>
            </form>
        </div>

        <!-- Pricing Preview -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
                <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-eye text-orange-500 mr-2"></i>Service Rates Preview</h2>
                <p class="text-sm text-gray-500 mb-4">This is how customers see your pricing:</p>
                <div class="space-y-3">
                    <?php foreach ($serviceTypes as $key => $service): 
                        $price = $provider['price_per_kg'] * $service['multiplier'];
                    ?>
                    <div class="flex items-center justify-between py-3 px-4 rounded-lg <?= $key === 'wash_fold' ? 'bg-orange-50 border border-orange-200' : 'bg-gray-50' ?>">
                        <div>
                            <div class="font-semibold text-gray-800"><?= e($service['label']) ?></div>
                            <div class="text-xs text-gray-500"><?= $service['multiplier'] ?>x base rate</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-lg <?= $key === 'wash_fold' ? 'text-orange-600' : 'text-gray-800' ?>"><?= format_currency($price) ?></div>
                            <div class="text-xs text-gray-400">per kg</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Business Info -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
                <h2 class="font-bold text-gray-800 mb-3"><i class="fas fa-store text-deepblue-800 mr-2"></i>Business Info</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Business Name</span><span class="font-medium"><?= e($provider['business_name']) ?></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Type</span><span class="font-medium"><?= ucfirst($provider['business_type']) ?></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Location</span><span class="font-medium"><?= e($provider['estate']) ?></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Status</span>
                        <?php if ($provider['is_approved']): ?>
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-bold">Approved</span>
                        <?php else: ?>
                        <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs font-bold">Pending</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
