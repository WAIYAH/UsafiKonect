<?php
/**
 * UsafiKonect - Forgot Password
 * Generates a secure reset token and sends email
 */

require_once __DIR__ . '/../config/functions.php';

if (is_logged_in()) {
    redirect(APP_URL . '/' . get_user_role() . '/dashboard.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, full_name, email FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            // Always show success to prevent email enumeration
            $success = true;
            
            if ($user) {
                // Generate secure token
                $token = bin2hex(random_bytes(32));
                $hashedToken = hash('sha256', $token);
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Invalidate previous tokens
                $stmt = $db->prepare("UPDATE users SET reset_token = NULL, reset_expires = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Store new token
                $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $stmt->execute([$hashedToken, $expiry, $user['id']]);
                
                // Build reset link
                $resetLink = APP_URL . '/auth/reset-password.php?token=' . $token . '&email=' . urlencode($email);
                
                // Send email
                $subject = 'Reset Your UsafiKonect Password';
                $body = get_email_template('password_reset', [
                    'name' => $user['full_name'],
                    'reset_link' => $resetLink,
                    'expiry' => '1 hour'
                ]);
                
                send_email($user['email'], $user['full_name'], $subject, $body);
            }
        }
    }
}

$page_title = 'Forgot Password';
$page_description = 'Reset your UsafiKonect password. Enter your email to receive a reset link.';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main id="main-content" class="min-h-screen bg-gradient-to-br from-orange-50 via-cream to-teal-50 flex items-center justify-center py-8 px-4">
    <div class="w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🔑</div>
            <h1 class="text-3xl font-bold text-gray-800">Forgot Password?</h1>
            <p class="text-gray-600 mt-2">No worries! Enter your email and we'll send you a reset link.</p>
        </div>

        <?php if ($success): ?>
        <!-- Success Message -->
        <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-envelope-open text-green-600 text-2xl"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">Check Your Email</h2>
            <p class="text-gray-600 mb-6 text-sm">If an account exists with that email, we've sent a password reset link. The link expires in <strong>1 hour</strong>.</p>
            <div class="space-y-3">
                <a href="<?= APP_URL ?>/auth/login.php" class="block w-full py-3 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition-all text-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Login
                </a>
                <button onclick="document.getElementById('resendForm').classList.toggle('hidden')" class="text-sm text-gray-500 hover:text-orange-500 transition-colors">
                    Didn't receive it? <span class="underline">Resend</span>
                </button>
            </div>
            <form id="resendForm" method="POST" action="" class="hidden mt-4">
                <?= csrf_field() ?>
                <input type="hidden" name="email" value="<?= e($_POST['email'] ?? '') ?>">
                <button type="submit" class="w-full py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm">
                    <i class="fas fa-redo mr-1"></i> Resend Reset Link
                </button>
            </form>
        </div>
        <?php else: ?>
        
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

        <!-- Reset Form -->
        <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
            <form method="POST" action="" class="space-y-5">
                <?= csrf_field() ?>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3.5 text-gray-400"><i class="fas fa-envelope"></i></span>
                        <input type="email" id="email" name="email" required autofocus
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" placeholder="you@example.com">
                    </div>
                </div>
                <button type="submit" class="w-full py-3 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition-all shadow-md hover:shadow-lg">
                    <i class="fas fa-paper-plane mr-2"></i> Send Reset Link
                </button>
            </form>
            <div class="mt-6 text-center">
                <a href="<?= APP_URL ?>/auth/login.php" class="text-sm text-gray-500 hover:text-orange-500 transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Login
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
