<?php
/**
 * UsafiKonect - Reset Password
 * Validates token and allows setting new password
 */

require_once __DIR__ . '/../config/functions.php';

if (is_logged_in()) {
    redirect(APP_URL . '/' . get_user_role() . '/dashboard.php');
}

$errors = [];
$valid_token = false;
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

// Validate token on GET
if (!empty($token) && !empty($email)) {
    $hashedToken = hash('sha256', $token);
    $db = getDB();
    $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ? AND reset_token = ? AND reset_expires > NOW() AND is_active = 1");
    $stmt->execute([filter_var($email, FILTER_SANITIZE_EMAIL), $hashedToken]);
    $user = $stmt->fetch();
    
    if ($user) {
        $valid_token = true;
    } else {
        $errors[] = 'This reset link is invalid or has expired. Please request a new one.';
    }
} else {
    $errors[] = 'Invalid reset link. Please request a new password reset.';
}

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    if (!validate_csrf_token()) {
        $errors[] = 'Invalid security token. Please try again.';
        $valid_token = false;
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';
        $postToken = $_POST['token'] ?? '';
        $postEmail = $_POST['email'] ?? '';
        
        if (empty($password) || strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || 
                   !preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*]/', $password)) {
            $errors[] = 'Password must include uppercase, lowercase, number, and special character (!@#$%^&*).';
        } elseif ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        } else {
            // Re-validate token from POST data
            $hashedToken = hash('sha256', $postToken);
            $db = getDB();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_expires > NOW()");
            $stmt->execute([filter_var($postEmail, FILTER_SANITIZE_EMAIL), $hashedToken]);
            $user = $stmt->fetch();
            
            if ($user) {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $db->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                $stmt->execute([$hash, $user['id']]);
                
                // Create notification
                create_notification($user['id'], 'security', 'Your password was successfully changed. If you didn\'t do this, please contact support immediately.');
                
                set_flash('success', 'Password reset successfully! You can now login with your new password.');
                redirect(APP_URL . '/auth/login.php');
            } else {
                $errors[] = 'Token expired during reset. Please request a new reset link.';
                $valid_token = false;
            }
        }
    }
}

$page_title = 'Reset Password';
$page_description = 'Set your new UsafiKonect password.';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main id="main-content" class="min-h-screen bg-gradient-to-br from-orange-50 via-cream to-teal-50 flex items-center justify-center py-8 px-4">
    <div class="w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🔐</div>
            <h1 class="text-3xl font-bold text-gray-800">Reset Password</h1>
            <p class="text-gray-600 mt-2">Create a strong new password for your account.</p>
        </div>

        <!-- Errors -->
        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
            <ul class="text-sm text-red-700 list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($valid_token): ?>
        <!-- Reset Form -->
        <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
            <form method="POST" action="" class="space-y-5">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <input type="hidden" name="email" value="<?= e($email) ?>">
                
                <!-- New Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3.5 text-gray-400"><i class="fas fa-lock"></i></span>
                        <input type="password" id="password" name="password" required
                            class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" placeholder="New password">
                        <button type="button" onclick="togglePw('password','eye1')" class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye" id="eye1"></i>
                        </button>
                    </div>
                    <!-- Strength indicator -->
                    <div class="mt-2 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                        <div id="strengthBar" class="h-full w-0 rounded-full transition-all duration-300"></div>
                    </div>
                    <p id="strengthText" class="text-xs mt-1 text-gray-400"></p>
                </div>
                
                <!-- Confirm Password -->
                <div>
                    <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3.5 text-gray-400"><i class="fas fa-lock"></i></span>
                        <input type="password" id="password_confirm" name="password_confirm" required
                            class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" placeholder="Confirm new password">
                        <button type="button" onclick="togglePw('password_confirm','eye2')" class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye" id="eye2"></i>
                        </button>
                    </div>
                    <p id="matchText" class="text-xs mt-1"></p>
                </div>
                
                <!-- Password Requirements -->
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs font-medium text-gray-600 mb-2">Password requirements:</p>
                    <ul class="text-xs text-gray-500 space-y-1">
                        <li id="req-length"><i class="fas fa-circle text-[6px] mr-2"></i>At least 8 characters</li>
                        <li id="req-upper"><i class="fas fa-circle text-[6px] mr-2"></i>One uppercase letter</li>
                        <li id="req-lower"><i class="fas fa-circle text-[6px] mr-2"></i>One lowercase letter</li>
                        <li id="req-number"><i class="fas fa-circle text-[6px] mr-2"></i>One number</li>
                        <li id="req-special"><i class="fas fa-circle text-[6px] mr-2"></i>One special character (!@#$%^&*)</li>
                    </ul>
                </div>
                
                <button type="submit" class="w-full py-3 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition-all shadow-md hover:shadow-lg">
                    <i class="fas fa-key mr-2"></i> Reset Password
                </button>
            </form>
        </div>
        <?php else: ?>
        <!-- Invalid Token -->
        <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">Invalid or Expired Link</h2>
            <p class="text-gray-600 mb-6 text-sm">This password reset link is no longer valid. Please request a new one.</p>
            <div class="space-y-3">
                <a href="<?= APP_URL ?>/auth/forgot-password.php" class="block w-full py-3 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition-all text-center">
                    <i class="fas fa-redo mr-2"></i> Request New Reset Link
                </a>
                <a href="<?= APP_URL ?>/auth/login.php" class="block text-sm text-gray-500 hover:text-orange-500 transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Login
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === 'password') { input.type = 'text'; icon.classList.replace('fa-eye', 'fa-eye-slash'); }
    else { input.type = 'password'; icon.classList.replace('fa-eye-slash', 'fa-eye'); }
}

const pw = document.getElementById('password');
const confirm = document.getElementById('password_confirm');
if (pw) {
    pw.addEventListener('input', function() {
        const val = this.value;
        let score = 0;
        const checks = {
            'req-length': val.length >= 8,
            'req-upper': /[A-Z]/.test(val),
            'req-lower': /[a-z]/.test(val),
            'req-number': /[0-9]/.test(val),
            'req-special': /[!@#$%^&*]/.test(val)
        };
        
        Object.entries(checks).forEach(([id, passed]) => {
            const el = document.getElementById(id);
            if (el) {
                const icon = el.querySelector('i');
                if (passed) {
                    el.classList.add('text-green-600');
                    el.classList.remove('text-gray-500');
                    icon.classList.replace('fa-circle', 'fa-check-circle');
                    score++;
                } else {
                    el.classList.remove('text-green-600');
                    el.classList.add('text-gray-500');
                    icon.classList.replace('fa-check-circle', 'fa-circle');
                }
            }
        });
        
        const bar = document.getElementById('strengthBar');
        const text = document.getElementById('strengthText');
        const pct = (score / 5) * 100;
        bar.style.width = pct + '%';
        if (score <= 1) { bar.className = 'h-full w-0 rounded-full transition-all duration-300 bg-red-500'; text.textContent = 'Weak'; text.className = 'text-xs mt-1 text-red-500'; }
        else if (score <= 3) { bar.className = 'h-full rounded-full transition-all duration-300 bg-yellow-500'; text.textContent = 'Fair'; text.className = 'text-xs mt-1 text-yellow-500'; }
        else if (score <= 4) { bar.className = 'h-full rounded-full transition-all duration-300 bg-blue-500'; text.textContent = 'Good'; text.className = 'text-xs mt-1 text-blue-500'; }
        else { bar.className = 'h-full rounded-full transition-all duration-300 bg-green-500'; text.textContent = 'Strong'; text.className = 'text-xs mt-1 text-green-500'; }
        bar.style.width = pct + '%';
        checkMatch();
    });
}

if (confirm) {
    confirm.addEventListener('input', checkMatch);
}

function checkMatch() {
    const mt = document.getElementById('matchText');
    if (!confirm || !confirm.value) { mt.textContent = ''; return; }
    if (pw.value === confirm.value) { mt.textContent = 'Passwords match ✓'; mt.className = 'text-xs mt-1 text-green-600'; }
    else { mt.textContent = 'Passwords do not match'; mt.className = 'text-xs mt-1 text-red-500'; }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
