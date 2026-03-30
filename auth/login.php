<?php
/**
 * UsafiKonect - User Login
 * Email/password authentication with rate limiting and remember-me
 */

require_once __DIR__ . '/../config/functions.php';

if (is_logged_in()) {
    redirect(APP_URL . '/' . get_user_role() . '/dashboard.php');
}

$errors = [];
$old_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        $old_email = $email;
        
        // Rate limiting check
        if (!check_rate_limit('login', 5, 15)) {
            $errors[] = 'Too many login attempts. Please try again in 15 minutes.';
        } elseif (empty($email) || empty($password)) {
            $errors[] = 'Please enter both email and password.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if account is active
                if (!$user['is_active']) {
                    $errors[] = 'Your account has been deactivated. Please contact support.';
                } else {
                    // Check if provider is approved
                    if ($user['role'] === 'provider') {
                        $stmt = $db->prepare("SELECT is_approved FROM provider_details WHERE user_id = ?");
                        $stmt->execute([$user['id']]);
                        $provider = $stmt->fetch();
                        if ($provider && !$provider['is_approved']) {
                            $errors[] = 'Your provider account is pending approval. We will notify you once it\'s approved.';
                        }
                    }
                    
                    if (empty($errors)) {
                        // Clear failed login attempts
                        clear_login_attempts();
                        
                        // Set session
                        set_user_session($user);
                        
                        // Remember me - create persistent token
                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            $hashedToken = hash('sha256', $token);
                            $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                            
                            $stmt = $db->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $user['id'], $hashedToken,
                                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                                $_SERVER['HTTP_USER_AGENT'] ?? '',
                                $expiry
                            ]);
                            
                            setcookie('remember_token', $token, [
                                'expires' => strtotime('+30 days'),
                                'path' => '/',
                                'httponly' => true,
                                'samesite' => 'Lax',
                            ]);
                            
                            $stmt = $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                            $stmt->execute([$hashedToken, $user['id']]);
                        }
                        
                        set_flash('success', 'Karibu tena! Welcome back, ' . $user['full_name'] . '!');
                        redirect(APP_URL . '/' . $user['role'] . '/dashboard.php');
                    }
                }
            } else {
                record_login_attempt();
                $errors[] = 'Invalid email or password. Please try again.';
            }
        }
    }
}

$page_title = 'Login';
$page_description = 'Login to your UsafiKonect account to manage bookings and laundry services.';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main id="main-content" class="min-h-screen bg-gradient-to-br from-orange-50 via-cream to-teal-50 flex items-center justify-center py-8 px-4">
    <div class="w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🧺</div>
            <h1 class="text-3xl font-bold text-gray-800">Welcome Back!</h1>
            <p class="text-gray-600 mt-2">Login to your <span class="text-orange-500 font-semibold">UsafiKonect</span> account</p>
        </div>

        <!-- Flash Messages -->
        <?= render_flash() ?>

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

        <!-- Login Form -->
        <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
            <form method="POST" action="" class="space-y-5">
                <?= csrf_field() ?>
                
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3.5 text-gray-400"><i class="fas fa-envelope"></i></span>
                        <input type="email" id="email" name="email" value="<?= e($old_email) ?>" required autofocus
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" placeholder="you@example.com">
                    </div>
                </div>
                
                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3.5 text-gray-400"><i class="fas fa-lock"></i></span>
                        <input type="password" id="password" name="password" required
                            class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" placeholder="Enter your password">
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="remember" class="mr-2 text-orange-500 focus:ring-orange-500 rounded">
                        <span class="text-sm text-gray-600">Remember me</span>
                    </label>
                    <a href="<?= APP_URL ?>/auth/forgot-password.php" class="text-sm text-orange-500 hover:underline">Forgot password?</a>
                </div>
                
                <!-- Submit -->
                <button type="submit" class="w-full py-3 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5 flex items-center justify-center">
                    <i class="fas fa-sign-in-alt mr-2"></i> Login
                </button>
            </form>
            
            <!-- Divider -->
            <div class="flex items-center my-6">
                <hr class="flex-1 border-gray-200">
                <span class="px-3 text-xs text-gray-400 uppercase">New here?</span>
                <hr class="flex-1 border-gray-200">
            </div>
            
            <!-- Register Link -->
            <a href="<?= APP_URL ?>/auth/register.php" class="block w-full py-3 border-2 border-deepblue-800 text-deepblue-800 font-semibold rounded-lg hover:bg-deepblue-800 hover:text-white transition-all text-center">
                <i class="fas fa-user-plus mr-2"></i> Create an Account
            </a>
        </div>
        
        <!-- Demo credentials -->
        <?php if (APP_DEBUG): ?>
        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-xs font-semibold text-blue-700 mb-2"><i class="fas fa-info-circle mr-1"></i> Test Credentials:</p>
            <div class="grid grid-cols-1 gap-1 text-xs text-blue-600">
                <p><strong>Admin:</strong> admin@usafikonect.co.ke / Admin@123</p>
                <p><strong>Customer:</strong> john@example.com / Password@123</p>
                <p><strong>Provider:</strong> mama.fua@example.com / Password@123</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
