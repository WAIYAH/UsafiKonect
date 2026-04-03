<?php
require_once __DIR__ . '/config/functions.php';
$page_title = 'Privacy Policy';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<main class="py-16 bg-gray-50 min-h-screen">
<div class="container mx-auto px-4 max-w-3xl">
<h1 class="text-3xl font-bold text-gray-900 mb-8">Privacy Policy</h1>
<div class="bg-white rounded-2xl shadow-md p-8 prose prose-sm max-w-none text-gray-600">
<p class="text-sm text-gray-400 mb-6">Last updated: <?= date('F j, Y') ?></p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">1. Information We Collect</h2>
<p>We collect information you provide during registration (name, email, phone, location), booking data, payment information, and usage analytics to improve our services.</p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">2. How We Use Your Information</h2>
<ul class="list-disc pl-5 space-y-2">
<li>To provide and improve our laundry marketplace services</li>
<li>To process bookings and payments</li>
<li>To communicate about your account and bookings</li>
<li>To send promotional offers (with your consent)</li>
<li>To ensure platform safety and security</li>
</ul>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">3. Data Sharing</h2>
<p>We share your name, phone, and estate with providers when you make a booking. We do not sell your personal data to third parties.</p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">4. Data Security</h2>
<p>We use industry-standard encryption (bcrypt for passwords, HTTPS for data transmission) to protect your information.</p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">5. Cookies</h2>
<p>We use essential cookies for session management and optional analytics cookies. See our Cookie Policy for details.</p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">6. Your Rights</h2>
<p>You may request access to, correction of, or deletion of your personal data by contacting our support team. Account deletion requests are processed within 30 days.</p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">7. Contact</h2>
<p>For privacy inquiries: <a href="mailto:privacy@usafikonect.co.ke" class="text-orange-500 hover:underline">privacy@usafikonect.co.ke</a></p>
</div>
</div>
</main>
<script src="<?= APP_URL ?>/assets/js/gsap-init.js" defer></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
