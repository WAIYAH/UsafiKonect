<?php
require_once __DIR__ . '/config/functions.php';
$page_title = 'Terms of Service';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<main class="py-16 bg-gray-50 min-h-screen">
<div class="container mx-auto px-4 max-w-3xl">
<h1 class="text-3xl font-bold text-gray-900 mb-8">Terms of Service</h1>
<div class="bg-white rounded-2xl shadow-md p-8 prose prose-sm max-w-none text-gray-600">
<p class="text-sm text-gray-400 mb-6">Last updated: <?= date('F j, Y') ?></p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">1. Acceptance of Terms</h2>
<p>By accessing and using UsafiKonect ("the Platform"), you agree to be bound by these Terms of Service. If you do not agree, please do not use the Platform.</p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">2. Description of Service</h2>
<p>UsafiKonect is a marketplace that connects customers with laundry service providers in Nairobi, Kenya. We facilitate bookings, payments, and communication but do not directly provide laundry services.</p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">3. User Accounts</h2>
<ul class="list-disc pl-5 space-y-2">
<li>You must provide accurate, complete information when registering.</li>
<li>You are responsible for maintaining account security.</li>
<li>One account per person. Duplicate accounts may be suspended.</li>
<li>You must be at least 18 years old to create an account.</li>
</ul>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">4. Provider Terms</h2>
<p>Providers must deliver quality services as described. Providers set their own pricing and are responsible for the quality of their work. UsafiKonect reserves the right to remove providers who consistently receive poor reviews.</p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">5. Payments</h2>
<p>Payments are processed via M-Pesa and our internal wallet system. By making a payment, you authorize the transaction. Refunds are subject to our Refund Policy.</p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">6. Limitation of Liability</h2>
<p>UsafiKonect acts as a marketplace. We are not liable for the quality of laundry services provided by third-party providers. Disputes should be reported through our support system.</p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">7. Changes to Terms</h2>
<p>We may update these Terms at any time. Continued use of the Platform constitutes acceptance of the updated Terms.</p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">8. Contact</h2>
<p>For questions about these Terms, contact us at <a href="mailto:legal@usafikonect.co.ke" class="text-orange-500 hover:underline">legal@usafikonect.co.ke</a>.</p>
</div>
</div>
</main>
<script src="<?= APP_URL ?>/assets/js/gsap-init.js" defer></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
