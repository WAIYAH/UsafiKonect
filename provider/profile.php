<?php
/**
 * UsafiKonect - Provider Profile
 * Edit profile + business details
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('provider');

$db = getDB();
$userId = get_user_id();

// Get user + provider details
$stmt = $db->prepare("
    SELECT u.*, pd.business_name, pd.business_type, pd.price_per_kg, pd.description, pd.is_approved
    FROM users u LEFT JOIN provider_details pd ON u.id = pd.user_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $action = $_POST['action'] ?? 'profile';
    
    if ($action === 'profile') {
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $estate = sanitize_input($_POST['estate'] ?? '');
        $business_name = sanitize_input($_POST['business_name'] ?? '');
        $business_type = sanitize_input($_POST['business_type'] ?? '');
        $price_per_kg = (float)($_POST['price_per_kg'] ?? 0);
        $description = sanitize_input($_POST['description'] ?? '');
        
        if (empty($full_name)) $errors[] = 'Full name is required.';
        if (empty($phone)) $errors[] = 'Phone number is required.';
        if (empty($estate)) $errors[] = 'Estate is required.';
        if (empty($business_name)) $errors[] = 'Business name is required.';
        if (!in_array($business_type, ['individual','shop'])) $errors[] = 'Invalid business type.';
        if ($price_per_kg <= 0 || $price_per_kg > 10000) $errors[] = 'Price per kg must be between 1 and 10,000.';
        
        if (empty($errors)) {
            $profile_image = $user['profile_image'];
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $upload = upload_image($_FILES['profile_image'], 'profiles');
                if ($upload['success']) {
                    $profile_image = $upload['path'];
                } else {
                    $errors[] = $upload['error'];
                }
            }
            
            if (empty($errors)) {
                $db->beginTransaction();
                $upd = $db->prepare("UPDATE users SET full_name = ?, phone = ?, estate = ?, profile_image = ?, updated_at = NOW() WHERE id = ?");
                $upd->execute([$full_name, $phone, $estate, $profile_image, $userId]);
                
                $upd2 = $db->prepare("UPDATE provider_details SET business_name = ?, business_type = ?, price_per_kg = ?, description = ? WHERE user_id = ?");
                $upd2->execute([$business_name, $business_type, $price_per_kg, $description, $userId]);
                
                $db->commit();
                $_SESSION['full_name'] = $full_name;
                set_flash('success', 'Profile updated successfully!');
                header('Location: ' . APP_URL . '/provider/profile.php');
                exit;
            }
        }
    } elseif ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (!password_verify($current, $user['password_hash'])) $errors[] = 'Current password is incorrect.';
        if (strlen($new) < 8) $errors[] = 'New password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $new) || !preg_match('/[a-z]/', $new) || !preg_match('/[0-9]/', $new) || !preg_match('/[^A-Za-z0-9]/', $new)) {
            $errors[] = 'Password must contain uppercase, lowercase, number, and special character.';
        }
        if ($new !== $confirm) $errors[] = 'Passwords do not match.';
        
        if (empty($errors)) {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $upd = $db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$hash, $userId]);
            set_flash('success', 'Password changed successfully!');
            header('Location: ' . APP_URL . '/provider/profile.php');
            exit;
        }
    }
    // Refresh user data on errors
    if (!empty($errors)) {
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
    }
}

$estates = ['Buruburu','Dandora','Donholm','Eastleigh','Embakasi','Garden Estate','Huruma','Imara Daima','Jamhuri','Kahawa West','Kangemi','Karen','Kasarani','Kayole','Kibera','Kilimani','Kileleshwa','Komarock','Langata','Lavington','Madaraka','Mathare','Nairobi CBD','Ngara','Ngumo','Nyayo Estate','Pangani','Pipeline','Rongai','South B'];

$page_title = 'My Profile';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">My Profile & Business</h1>
    
    <?= render_flash() ?>
    
    <?php if (!empty($errors)): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
        <ul class="text-sm text-red-700 list-disc list-inside">
            <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Profile Card -->
        <div>
            <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100 text-center">
                <div class="w-24 h-24 rounded-full bg-orange-100 mx-auto mb-4 overflow-hidden">
                    <?php if ($user['profile_image']): ?>
                    <img src="<?= APP_URL . '/' . e($user['profile_image']) ?>" alt="Profile" class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-3xl font-bold text-orange-500">
                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <h2 class="text-xl font-bold text-gray-800"><?= e($user['full_name']) ?></h2>
                <p class="text-sm text-gray-500"><?= e($user['business_name'] ?? 'Provider') ?></p>
                <div class="mt-3 flex items-center justify-center gap-2">
                    <?php if ($user['is_approved']): ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">
                        <i class="fas fa-check-circle"></i>Approved
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full font-medium">
                        <i class="fas fa-clock"></i>Pending Approval
                    </span>
                    <?php endif; ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-700 text-xs rounded-full font-medium">
                        <i class="fas fa-tag"></i><?= ucfirst($user['business_type'] ?? 'individual') ?>
                    </span>
                </div>
                <div class="mt-4 border-t pt-4 text-xs text-gray-400">
                    <p><i class="fas fa-envelope mr-1"></i><?= e($user['email']) ?></p>
                    <p><i class="fas fa-phone mr-1"></i><?= e($user['phone']) ?></p>
                    <p><i class="fas fa-map-marker-alt mr-1"></i><?= e($user['estate']) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Edit Profile + Business -->
        <div class="lg:col-span-2 space-y-6">
            <form method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="profile">
                <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-user-edit text-orange-500 mr-2"></i>Personal & Business Info</h2>
                
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required
                               class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" name="phone" value="<?= e($user['phone']) ?>" required
                               class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estate</label>
                        <select name="estate" required class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                            <?php foreach ($estates as $est): ?>
                            <option value="<?= $est ?>" <?= ($user['estate'] === $est) ? 'selected' : '' ?>><?= $est ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Profile Image</label>
                        <input type="file" name="profile_image" accept="image/*"
                               class="w-full px-4 py-2 rounded-lg border border-gray-200 text-sm file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-orange-50 file:text-orange-600 file:font-medium">
                    </div>
                </div>
                
                <hr class="my-5">
                <h3 class="font-semibold text-gray-700 mb-3"><i class="fas fa-store text-blue-500 mr-2"></i>Business Details</h3>
                
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
                        <input type="text" name="business_name" value="<?= e($user['business_name'] ?? '') ?>" required
                               class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Business Type</label>
                        <select name="business_type" required class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                            <option value="individual" <?= ($user['business_type'] ?? '') === 'individual' ? 'selected' : '' ?>>Individual (Mama Fua)</option>
                            <option value="shop" <?= ($user['business_type'] ?? '') === 'shop' ? 'selected' : '' ?>>Laundry Shop</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price per KG (KES)</label>
                        <input type="number" name="price_per_kg" value="<?= e($user['price_per_kg'] ?? 100) ?>" min="1" max="10000" step="1" required
                               class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Business Description</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                              placeholder="Tell customers about your services, experience, and what makes you special..."><?= e($user['description'] ?? '') ?></textarea>
                </div>
                
                <div class="mt-5">
                    <button type="submit" class="px-6 py-2.5 bg-orange-500 text-white font-bold rounded-lg hover:bg-orange-600 transition-colors">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                </div>
            </form>
            
            <!-- Change Password -->
            <form method="POST" class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="password">
                <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-lock text-red-500 mr-2"></i>Change Password</h2>
                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                        <input type="password" name="current_password" required class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input type="password" name="new_password" required minlength="8" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input type="password" name="confirm_password" required class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="px-6 py-2.5 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-key mr-2"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
