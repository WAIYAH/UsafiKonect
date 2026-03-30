<?php
/**
 * UsafiKonect - Customer: Profile
 * View/edit profile, change password
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('customer');

$db = getDB();
$userId = get_user_id();
$errors = [];

// Fetch user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$estates = ['Roysambu','Umoja','Donholm','Kilimani','Langata','Westlands','South B','South C','Embakasi','Kasarani','Ruaka','Rongai','Kibera','Kawangware','Eastleigh','Buruburu','Pangani','Ngara','Parklands','Lavington','Karen','Kileleshwa','Upperhill','Hurlingham','Thika Road','Mombasa Road','Jogoo Road','Outer Ring','Pipeline','Kayole'];

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!validate_csrf_token()) {
        $errors[] = 'Invalid security token.';
    } else {
        $fullName = sanitize_input($_POST['full_name'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $estate = sanitize_input($_POST['estate'] ?? '');
        
        if (empty($fullName) || strlen($fullName) < 2) $errors[] = 'Name is required.';
        if (empty($phone) || !preg_match('/^(?:\+254|0)[17]\d{8}$/', $phone)) $errors[] = 'Valid Kenyan phone number required.';
        if (!in_array($estate, $estates)) $errors[] = 'Select a valid estate.';
        
        // Handle profile image upload
        $profileImage = $user['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $newFilename = upload_image($_FILES['profile_image'], 'profiles');
            if ($newFilename) {
                $profileImage = $newFilename;
            } else {
                $errors[] = 'Failed to upload profile image.';
            }
        }
        
        if (empty($errors)) {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ?, estate = ?, profile_image = ? WHERE id = ?");
            $stmt->execute([$fullName, $phone, $estate, $profileImage, $userId]);
            $_SESSION['full_name'] = $fullName;
            set_flash('success', 'Profile updated successfully!');
            redirect(APP_URL . '/customer/profile.php');
        }
    }
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!validate_csrf_token()) {
        $errors[] = 'Invalid security token.';
    } else {
        $current = $_POST['current_password'] ?? '';
        $newPw = $_POST['new_password'] ?? '';
        $confirmPw = $_POST['confirm_password'] ?? '';
        
        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($newPw) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $newPw) || !preg_match('/[a-z]/', $newPw) || !preg_match('/[0-9]/', $newPw) || !preg_match('/[!@#$%^&*]/', $newPw)) {
            $errors[] = 'Password must include uppercase, lowercase, number, and special character.';
        } elseif ($newPw !== $confirmPw) {
            $errors[] = 'New passwords do not match.';
        } else {
            $hash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);
            set_flash('success', 'Password changed successfully!');
            redirect(APP_URL . '/customer/profile.php');
        }
    }
}

$page_title = 'My Profile';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <h1 class="text-2xl font-bold text-gray-800 mb-6"><i class="fas fa-user-cog text-orange-500 mr-2"></i>My Profile</h1>
    
    <?= render_flash() ?>
    
    <?php if (!empty($errors)): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
        <ul class="text-sm text-red-700 list-disc list-inside">
            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Profile Card -->
        <div class="bg-white rounded-2xl shadow-md p-6 text-center">
            <div class="w-24 h-24 bg-orange-100 text-orange-500 rounded-full flex items-center justify-center text-4xl font-bold mx-auto mb-4">
                <?php if ($user['profile_image'] && $user['profile_image'] !== 'avatar.png'): ?>
                    <img src="<?= APP_URL ?>/assets/uploads/profiles/<?= e($user['profile_image']) ?>" alt="Profile" class="w-full h-full rounded-full object-cover">
                <?php else: ?>
                    <?= mb_substr($user['full_name'], 0, 1) ?>
                <?php endif; ?>
            </div>
            <h2 class="text-xl font-bold text-gray-800"><?= e($user['full_name']) ?></h2>
            <p class="text-sm text-gray-500"><?= e($user['email']) ?></p>
            <p class="text-sm text-gray-500"><i class="fas fa-map-pin mr-1"></i><?= e($user['estate']) ?></p>
            <div class="mt-4 pt-4 border-t border-gray-100 text-sm text-gray-400">
                Member since <?= date('M Y', strtotime($user['created_at'])) ?>
            </div>
        </div>
        
        <!-- Edit Profile -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-gray-800">Edit Profile</h3>
                </div>
                <form method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
                    <?= csrf_field() ?>
                    <div class="grid md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input type="tel" name="phone" value="<?= e($user['phone']) ?>" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" placeholder="0712345678">
                        </div>
                    </div>
                    <div class="grid md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estate</label>
                            <select name="estate" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                                <?php foreach ($estates as $est): ?>
                                <option value="<?= $est ?>" <?= $user['estate'] === $est ? 'selected' : '' ?>><?= $est ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Profile Image</label>
                            <input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:bg-orange-50 file:text-orange-600 file:font-medium">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email (cannot be changed)</label>
                        <input type="email" value="<?= e($user['email']) ?>" disabled class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                    <button type="submit" name="update_profile" class="px-6 py-2.5 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition-all text-sm">
                        <i class="fas fa-save mr-1"></i> Save Changes
                    </button>
                </form>
            </div>
            
            <!-- Change Password -->
            <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-gray-800">Change Password</h3>
                </div>
                <form method="POST" class="p-6 space-y-5">
                    <?= csrf_field() ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                        <input type="password" name="current_password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div class="grid md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <input type="password" name="new_password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                            <input type="password" name="confirm_password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                        </div>
                    </div>
                    <button type="submit" name="change_password" class="px-6 py-2.5 bg-deepblue-800 text-white font-semibold rounded-lg hover:bg-deepblue-900 transition-all text-sm">
                        <i class="fas fa-key mr-1"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
