<?php
/**
 * UsafiKonect - User Registration
 * Supports customer and provider registration with role selection
 */

require_once __DIR__ . '/../config/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect(APP_URL . '/' . get_user_role() . '/dashboard.php');
}

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize inputs
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone = sanitize_input($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $role = sanitize_input($_POST['role'] ?? 'customer');
        $estate = sanitize_input($_POST['estate'] ?? '');
        
        // Provider-specific fields
        $business_name = sanitize_input($_POST['business_name'] ?? '');
        $provider_type = sanitize_input($_POST['provider_type'] ?? 'individual');
        $price_per_kg = (float)($_POST['price_per_kg'] ?? 100);
        $description = sanitize_input($_POST['description'] ?? '');
        
        $old = compact('full_name', 'email', 'phone', 'role', 'estate', 'business_name', 'provider_type', 'price_per_kg', 'description');
        
        // Validation
        if (empty($full_name) || strlen($full_name) < 3) $errors[] = 'Full name must be at least 3 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if (!preg_match('/^0[17]\d{8}$/', $phone)) $errors[] = 'Please enter a valid Kenyan phone number (e.g., 0712345678).';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password must contain at least one uppercase letter.';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must contain at least one number.';
        if ($password !== $password_confirm) $errors[] = 'Passwords do not match.';
        if (!in_array($role, ['customer', 'provider'])) $errors[] = 'Invalid role selected.';
        if (empty($estate)) $errors[] = 'Please select your estate.';
        
        if ($role === 'provider') {
            if (empty($business_name) || strlen($business_name) < 3) $errors[] = 'Business name must be at least 3 characters.';
            if ($price_per_kg <= 0 || $price_per_kg > 10000) $errors[] = 'Price per kg must be between KES 1 and KES 10,000.';
        }
        
        if (empty($errors)) {
            $db = getDB();
            
            // Check if email exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email already exists.';
            }
        }
        
        if (empty($errors)) {
            try {
                $db->beginTransaction();
                
                $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                
                $stmt = $db->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, provider_type, estate) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $full_name, $email, $phone, $password_hash, $role,
                    $role === 'provider' ? $provider_type : null,
                    $estate
                ]);
                $userId = (int)$db->lastInsertId();
                
                // Create provider details if provider
                if ($role === 'provider') {
                    $stmt = $db->prepare("INSERT INTO provider_details (user_id, business_name, business_type, price_per_kg, description) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$userId, $business_name, $provider_type, $price_per_kg, $description]);
                }
                
                // Create loyalty record for customers
                if ($role === 'customer') {
                    $stmt = $db->prepare("INSERT INTO loyalty_points (user_id) VALUES (?)");
                    $stmt->execute([$userId]);
                }
                
                // Create welcome notification
                create_notification($userId, 'system', 
                    $role === 'provider' 
                        ? 'Karibu! Your provider account has been created. It will be reviewed and approved by our team shortly.'
                        : 'Karibu! Welcome to UsafiKonect! Your account is ready! Start by browsing laundry providers in your area.'
                );
                
                // Notify admin of new provider registration
                if ($role === 'provider') {
                    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
                    $stmt->execute();
                    $admin = $stmt->fetch();
                    if ($admin) {
                        create_notification($admin['id'], 'system', 
                            "{$business_name} ({$full_name}) has registered as a provider and is awaiting approval.",
                            APP_URL . '/admin/providers.php'
                        );
                    }
                }
                
                $db->commit();
                
                // Send welcome email
                send_email($email, 'Welcome to UsafiKonect!', 
                    "<p>Habari {$full_name}! 👋</p>
                    <p>Thank you for joining UsafiKonect — Nairobi's trusted laundry service platform.</p>
                    <p>" . ($role === 'provider' 
                        ? "Your provider account is under review. We'll notify you once it's approved."
                        : "You can now browse laundry providers in your area and book your first service!") . "</p>
                    <p><a href='" . APP_URL . "/auth/login.php' style='display:inline-block;padding:12px 24px;background:#F97316;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold;'>Login to Your Account</a></p>
                    <p>Asante! — The UsafiKonect Team</p>"
                );
                
                // Auto-login for customers, redirect to dashboard
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if ($role === 'customer') {
                    set_user_session($user);
                    set_flash('success', 'Karibu! Your account has been created successfully. Welcome to UsafiKonect! 🎉');
                    redirect(APP_URL . '/customer/dashboard.php');
                } else {
                    set_flash('success', 'Your provider account has been created! It will be reviewed by our team. You can login once approved.');
                    redirect(APP_URL . '/auth/login.php');
                }
                
            } catch (PDOException $e) {
                $db->rollBack();
                error_log("Registration error: " . $e->getMessage());
                $errors[] = 'An error occurred during registration. Please try again.';
            }
        }
    }
}

// Nairobi estates list
$estates = ['Buruburu', 'Donholm', 'Eastleigh', 'Embakasi', 'Hurlingham', 'Imara Daima', 'Kahawa', 'Kasarani', 'Kibera', 'Kilimani', 'Kinoo', 'Kileleshwa', 'Langata', 'Lavington', 'Madaraka', 'Mathare', 'Nairobi CBD', 'Ngara', 'Ngumo', 'Parklands', 'Pipeline', 'Roysambu', 'Ruaka', 'Ruiru', 'South B', 'South C', 'Umoja', 'Upper Hill', 'Westlands', 'Zimmerman'];

$page_title = 'Create Account';
$page_description = 'Register for UsafiKonect - connect with trusted laundry service providers in Nairobi.';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main id="main-content" class="min-h-screen bg-gradient-to-br from-orange-50 via-cream to-teal-50 py-8 px-4">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Join <span class="text-orange-500">Usafi</span><span class="text-deepblue-800">Konect</span></h1>
            <p class="text-gray-600 mt-2">Create your account and get started</p>
        </div>

        <!-- Errors -->
        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
            <div class="flex items-start">
                <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-3"></i>
                <ul class="text-sm text-red-700 list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <!-- Role Selection Tabs -->
            <div class="flex border-b">
                <button type="button" id="tab-customer" onclick="switchRole('customer')" 
                    class="flex-1 py-4 text-center font-semibold text-sm transition-all border-b-2 border-orange-500 text-orange-600 bg-orange-50">
                    <i class="fas fa-user mr-2"></i> I need laundry service
                </button>
                <button type="button" id="tab-provider" onclick="switchRole('provider')" 
                    class="flex-1 py-4 text-center font-semibold text-sm transition-all border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                    <i class="fas fa-store mr-2"></i> I provide laundry service
                </button>
            </div>

            <form method="POST" action="" class="p-6 sm:p-8 space-y-5" id="registerForm">
                <?= csrf_field() ?>
                <input type="hidden" name="role" id="roleInput" value="<?= e($old['role'] ?? 'customer') ?>">
                
                <!-- Full Name -->
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" value="<?= e($old['full_name'] ?? '') ?>" required minlength="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" placeholder="Enter your full name">
                </div>
                
                <!-- Email & Phone -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" placeholder="you@example.com">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" value="<?= e($old['phone'] ?? '') ?>" required pattern="0[17][0-9]{8}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" placeholder="0712345678">
                    </div>
                </div>
                
                <!-- Estate -->
                <div>
                    <label for="estate" class="block text-sm font-medium text-gray-700 mb-1">Estate / Area *</label>
                    <select id="estate" name="estate" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                        <option value="">Select your estate</option>
                        <?php foreach ($estates as $est): ?>
                            <option value="<?= e($est) ?>" <?= ($old['estate'] ?? '') === $est ? 'selected' : '' ?>><?= e($est) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Provider-specific fields -->
                <div id="provider-fields" class="hidden space-y-5">
                    <hr class="border-gray-200">
                    <h3 class="font-semibold text-gray-700"><i class="fas fa-store text-orange-500 mr-2"></i> Business Details</h3>
                    
                    <div>
                        <label for="business_name" class="block text-sm font-medium text-gray-700 mb-1">Business Name *</label>
                        <input type="text" id="business_name" name="business_name" value="<?= e($old['business_name'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" placeholder="e.g., Mama Fua Cleaning Services">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Provider Type *</label>
                        <div class="flex gap-4">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="provider_type" value="individual" <?= ($old['provider_type'] ?? 'individual') === 'individual' ? 'checked' : '' ?> class="mr-2 text-orange-500 focus:ring-orange-500">
                                <span class="text-sm">Individual</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="provider_type" value="shop" <?= ($old['provider_type'] ?? '') === 'shop' ? 'checked' : '' ?> class="mr-2 text-orange-500 focus:ring-orange-500">
                                <span class="text-sm">Laundry Shop</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label for="price_per_kg" class="block text-sm font-medium text-gray-700 mb-1">Price per Kg (KES) *</label>
                        <input type="number" id="price_per_kg" name="price_per_kg" value="<?= e($old['price_per_kg'] ?? '100') ?>" min="1" max="10000" step="1"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Business Description</label>
                        <textarea id="description" name="description" rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" placeholder="Describe your laundry services..."><?= e($old['description'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <!-- Password -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required minlength="8"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors pr-10" placeholder="Min. 8 characters">
                            <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                        <input type="password" id="password_confirm" name="password_confirm" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" placeholder="Repeat password">
                    </div>
                </div>
                
                <!-- Password Strength Indicator -->
                <div id="password-strength" class="hidden">
                    <div class="flex gap-1">
                        <div class="h-1 flex-1 rounded bg-gray-200" id="str-1"></div>
                        <div class="h-1 flex-1 rounded bg-gray-200" id="str-2"></div>
                        <div class="h-1 flex-1 rounded bg-gray-200" id="str-3"></div>
                        <div class="h-1 flex-1 rounded bg-gray-200" id="str-4"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1" id="str-text">Enter a password</p>
                </div>
                
                <!-- Terms -->
                <div class="flex items-start">
                    <input type="checkbox" id="terms" required class="mt-1 mr-2 text-orange-500 focus:ring-orange-500 rounded">
                    <label for="terms" class="text-sm text-gray-600">
                        I agree to the <a href="<?= APP_URL ?>/legal/terms.php" class="text-orange-500 hover:underline">Terms of Service</a> and <a href="<?= APP_URL ?>/legal/privacy.php" class="text-orange-500 hover:underline">Privacy Policy</a>
                    </label>
                </div>
                
                <!-- Submit -->
                <button type="submit" class="w-full py-3 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5 flex items-center justify-center">
                    <i class="fas fa-user-plus mr-2"></i> Create Account
                </button>
                
                <!-- Login Link -->
                <p class="text-center text-sm text-gray-600">
                    Already have an account? <a href="<?= APP_URL ?>/auth/login.php" class="text-orange-500 hover:underline font-medium">Login here</a>
                </p>
            </form>
        </div>
    </div>
</main>

<script>
function switchRole(role) {
    document.getElementById('roleInput').value = role;
    const providerFields = document.getElementById('provider-fields');
    const tabCustomer = document.getElementById('tab-customer');
    const tabProvider = document.getElementById('tab-provider');
    
    if (role === 'provider') {
        providerFields.classList.remove('hidden');
        tabProvider.classList.add('border-orange-500', 'text-orange-600', 'bg-orange-50');
        tabProvider.classList.remove('border-transparent', 'text-gray-500');
        tabCustomer.classList.remove('border-orange-500', 'text-orange-600', 'bg-orange-50');
        tabCustomer.classList.add('border-transparent', 'text-gray-500');
    } else {
        providerFields.classList.add('hidden');
        tabCustomer.classList.add('border-orange-500', 'text-orange-600', 'bg-orange-50');
        tabCustomer.classList.remove('border-transparent', 'text-gray-500');
        tabProvider.classList.remove('border-orange-500', 'text-orange-600', 'bg-orange-50');
        tabProvider.classList.add('border-transparent', 'text-gray-500');
    }
}

function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// Password strength indicator
document.getElementById('password')?.addEventListener('input', function() {
    const val = this.value;
    const container = document.getElementById('password-strength');
    container.classList.remove('hidden');
    
    let strength = 0;
    if (val.length >= 8) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;
    
    const colors = ['bg-red-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500'];
    const texts = ['Weak', 'Fair', 'Good', 'Strong'];
    
    for (let i = 1; i <= 4; i++) {
        const bar = document.getElementById('str-' + i);
        bar.className = 'h-1 flex-1 rounded ' + (i <= strength ? colors[strength - 1] : 'bg-gray-200');
    }
    document.getElementById('str-text').textContent = val.length > 0 ? texts[strength - 1] || 'Too weak' : 'Enter a password';
});

// Initialize role tabs from old data
<?php if (($old['role'] ?? 'customer') === 'provider'): ?>
switchRole('provider');
<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
