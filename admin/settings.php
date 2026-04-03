<?php
/**
 * UsafiKonect - Admin Site Settings
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('admin');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $settings = [
        'site_name' => sanitize_input($_POST['site_name'] ?? ''),
        'site_tagline' => sanitize_input($_POST['site_tagline'] ?? ''),
        'contact_email' => sanitize_input($_POST['contact_email'] ?? ''),
        'contact_phone' => sanitize_input($_POST['contact_phone'] ?? ''),
        'contact_address' => sanitize_input($_POST['contact_address'] ?? ''),
        'mpesa_env' => sanitize_input($_POST['mpesa_env'] ?? 'sandbox'),
        'mpesa_consumer_key' => sanitize_input($_POST['mpesa_consumer_key'] ?? ''),
        'mpesa_shortcode' => sanitize_input($_POST['mpesa_shortcode'] ?? ''),
        'smtp_host' => sanitize_input($_POST['smtp_host'] ?? ''),
        'smtp_email' => sanitize_input($_POST['smtp_email'] ?? ''),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        'loyalty_points_per_booking' => sanitize_input($_POST['loyalty_points_per_booking'] ?? '10'),
        'free_booking_threshold' => sanitize_input($_POST['free_booking_threshold'] ?? '100'),
        'max_booking_weight' => sanitize_input($_POST['max_booking_weight'] ?? '50'),
    ];
    
    // Only update sensitive fields if non-empty (leave blank to keep current)
    $sensitiveFields = ['mpesa_consumer_secret', 'mpesa_passkey', 'smtp_password'];
    foreach ($sensitiveFields as $field) {
        $val = $_POST[$field] ?? '';
        if (!empty($val)) {
            $settings[$field] = $field === 'smtp_password' ? $val : sanitize_input($val);
        }
    }
    
    foreach ($settings as $key => $value) {
        update_setting($key, $value);
    }
    
    set_flash('success', 'Settings saved successfully!');
    header('Location: ' . APP_URL . '/admin/settings.php');
    exit;
}

// Load current settings
$allSettings = $db->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

$page_title = 'Site Settings';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Site Settings</h1>
    
    <?= render_flash() ?>
    
    <form method="POST" class="space-y-6 max-w-4xl">
        <?= csrf_field() ?>
        
        <!-- General -->
        <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-globe text-orange-500 mr-2"></i>General Settings</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
                    <input type="text" name="site_name" value="<?= e($allSettings['site_name'] ?? 'UsafiKonect') ?>"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tagline</label>
                    <input type="text" name="site_tagline" value="<?= e($allSettings['site_tagline'] ?? '') ?>"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                    <input type="email" name="contact_email" value="<?= e($allSettings['contact_email'] ?? '') ?>"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                    <input type="tel" name="contact_phone" value="<?= e($allSettings['contact_phone'] ?? '') ?>"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" name="contact_address" value="<?= e($allSettings['contact_address'] ?? '') ?>"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>
            </div>
            <div class="mt-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="maintenance_mode" value="1" <?= ($allSettings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>
                           class="w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-400">
                    <span class="text-sm font-medium text-gray-700">Enable Maintenance Mode</span>
                </label>
                <p class="text-xs text-gray-400 ml-6">Only admins can access the site during maintenance.</p>
            </div>
        </div>
        
        <!-- M-Pesa -->
        <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-mobile-alt text-green-500 mr-2"></i>M-Pesa Settings</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Environment</label>
                    <select name="mpesa_env" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                        <option value="sandbox" <?= ($allSettings['mpesa_env'] ?? '') === 'sandbox' ? 'selected' : '' ?>>Sandbox (Test)</option>
                        <option value="production" <?= ($allSettings['mpesa_env'] ?? '') === 'production' ? 'selected' : '' ?>>Production (Live)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Business Shortcode</label>
                    <input type="text" name="mpesa_shortcode" value="<?= e($allSettings['mpesa_shortcode'] ?? '') ?>"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Consumer Key</label>
                    <input type="text" name="mpesa_consumer_key" value="<?= e($allSettings['mpesa_consumer_key'] ?? '') ?>"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Consumer Secret</label>
                    <input type="password" name="mpesa_consumer_secret" placeholder="(leave blank to keep current)" value=""
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Passkey</label>
                    <input type="password" name="mpesa_passkey" placeholder="(leave blank to keep current)" value=""
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>
            </div>
        </div>
        
        <!-- Email SMTP -->
        <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-envelope text-blue-500 mr-2"></i>Email (SMTP) Settings</h2>
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Host</label>
                    <input type="text" name="smtp_host" value="<?= e($allSettings['smtp_host'] ?? 'smtp.gmail.com') ?>"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Email</label>
                    <input type="email" name="smtp_email" value="<?= e($allSettings['smtp_email'] ?? '') ?>"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Password</label>
                    <input type="password" name="smtp_password" placeholder="(leave blank to keep current)" value=""
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>
            </div>
        </div>
        
        <!-- Booking Settings -->
        <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-sliders-h text-purple-500 mr-2"></i>Booking & Loyalty Settings</h2>
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Loyalty Points per Booking</label>
                    <input type="number" name="loyalty_points_per_booking" value="<?= e($allSettings['loyalty_points_per_booking'] ?? '10') ?>" min="0" max="1000"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Free Booking Threshold (pts)</label>
                    <input type="number" name="free_booking_threshold" value="<?= e($allSettings['free_booking_threshold'] ?? '100') ?>" min="1"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Booking Weight (kg)</label>
                    <input type="number" name="max_booking_weight" value="<?= e($allSettings['max_booking_weight'] ?? '50') ?>" min="1"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>
            </div>
        </div>
        
        <div class="flex gap-3">
            <button type="submit" class="px-8 py-3 bg-orange-500 text-white font-bold rounded-xl hover:bg-orange-600 transition-colors shadow-md">
                <i class="fas fa-save mr-2"></i>Save All Settings
            </button>
        </div>
    </form>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
