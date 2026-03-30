<?php
/**
 * UsafiKonect - Contact Us Page
 */

require_once __DIR__ . '/config/functions.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        $errors[] = 'Invalid security token.';
    } else {
        $name = sanitize_input($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $subject = sanitize_input($_POST['subject'] ?? '');
        $message = sanitize_input($_POST['message'] ?? '');
        
        if (empty($name) || strlen($name) < 2) $errors[] = 'Please enter your name.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
        if (empty($subject) || strlen($subject) < 3) $errors[] = 'Please enter a subject.';
        if (empty($message) || strlen($message) < 10) $errors[] = 'Message must be at least 10 characters.';
        
        if (empty($errors)) {
            // Store as support ticket
            $db = getDB();
            $userId = is_logged_in() ? get_user_id() : null;
            $stmt = $db->prepare("INSERT INTO support_tickets (user_id, subject, message, status, priority) VALUES (?, ?, ?, 'open', 'medium')");
            $stmt->execute([$userId, $subject, "From: {$name} ({$email})\n\n{$message}"]);
            
            // Send notification email to admin
            send_email(
                'admin@usafikonect.co.ke',
                'Admin',
                "New Contact Form: {$subject}",
                "<p><strong>From:</strong> {$name} ({$email})</p><p><strong>Subject:</strong> {$subject}</p><p>{$message}</p>"
            );
            
            $success = true;
        }
    }
}

$page_title = 'Contact Us';
$page_description = 'Get in touch with UsafiKonect. We\'re here to help with any questions about our laundry services.';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- Hero -->
<section class="bg-gradient-to-br from-teal-700 to-teal-900 text-white py-16">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl font-extrabold mb-3">Contact <span class="text-orange-400">Us</span></h1>
        <p class="text-teal-100 text-lg">Have questions? We'd love to hear from you.</p>
    </div>
</section>

<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="grid lg:grid-cols-3 gap-12 max-w-6xl mx-auto">
            <!-- Contact Info -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl p-6 shadow-md border border-gray-100 card-hover">
                    <div class="w-12 h-12 bg-orange-100 text-orange-500 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-map-marker-alt text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-1">Visit Us</h3>
                    <p class="text-sm text-gray-600">Nairobi, Kenya<br>Moi Avenue, CBD</p>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-md border border-gray-100 card-hover">
                    <div class="w-12 h-12 bg-teal-100 text-teal-600 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-phone text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-1">Call Us</h3>
                    <p class="text-sm text-gray-600">+254 700 000 000<br>Mon-Fri 8am-6pm</p>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-md border border-gray-100 card-hover">
                    <div class="w-12 h-12 bg-blue-100 text-deepblue-800 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-envelope text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-1">Email Us</h3>
                    <p class="text-sm text-gray-600">support@usafikonect.co.ke<br>info@usafikonect.co.ke</p>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="lg:col-span-2">
                <?php if ($success): ?>
                <div class="bg-white rounded-2xl shadow-lg p-10 text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-check text-green-600 text-3xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-3">Message Sent!</h2>
                    <p class="text-gray-600 mb-6">Thank you for reaching out. We'll get back to you within 24 hours.</p>
                    <a href="<?= APP_URL ?>" class="inline-flex items-center px-6 py-3 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition-all">
                        <i class="fas fa-home mr-2"></i> Back to Home
                    </a>
                </div>
                <?php else: ?>
                <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Send us a Message</h2>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
                        <ul class="text-sm text-red-700 list-disc list-inside">
                            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-5">
                        <?= csrf_field() ?>
                        <div class="grid md:grid-cols-2 gap-5">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" id="name" name="name" value="<?= e($_POST['name'] ?? (is_logged_in() ? ($_SESSION['full_name'] ?? '') : '')) ?>" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? (is_logged_in() ? ($_SESSION['email'] ?? '') : '')) ?>" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            </div>
                        </div>
                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                            <input type="text" id="subject" name="subject" value="<?= e($_POST['subject'] ?? '') ?>" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                            <textarea id="message" name="message" rows="5" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 resize-y"><?= e($_POST['message'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="w-full py-3 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition-all shadow-md">
                            <i class="fas fa-paper-plane mr-2"></i> Send Message
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="<?= APP_URL ?>/assets/js/gsap-init.js"></script>
