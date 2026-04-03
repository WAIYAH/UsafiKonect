<?php
require_once __DIR__ . '/config/functions.php';
$page_title = 'Cookie Policy';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<main class="py-16 bg-gray-50 min-h-screen">
<div class="container mx-auto px-4 max-w-3xl">
<h1 class="text-3xl font-bold text-gray-900 mb-8">Cookie Policy</h1>
<div class="bg-white rounded-2xl shadow-md p-8 prose prose-sm max-w-none text-gray-600">
<p class="text-sm text-gray-400 mb-6">Last updated: <?= date('F j, Y') ?></p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">What Are Cookies</h2>
<p>Cookies are small text files stored on your device when you visit our website. They help us provide a better experience.</p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">Essential Cookies</h2>
<p>These are required for the platform to function: session cookies (PHPSESSID), CSRF protection tokens, and login persistence (remember_token). These cannot be disabled.</p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">Preference Cookies</h2>
<p>Dark mode preference and cookie consent status stored in localStorage.</p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">Managing Cookies</h2>
<p>You can manage cookies through your browser settings. Disabling essential cookies may affect platform functionality.</p>
</div>
</div>
</main>
<script src="<?= APP_URL ?>/assets/js/gsap-init.js" defer></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
