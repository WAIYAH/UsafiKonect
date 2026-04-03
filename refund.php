<?php
require_once __DIR__ . '/config/functions.php';
$page_title = 'Refund Policy';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<main class="py-16 bg-gray-50 min-h-screen">
<div class="container mx-auto px-4 max-w-3xl">
<h1 class="text-3xl font-bold text-gray-900 mb-8">Refund Policy</h1>
<div class="bg-white rounded-2xl shadow-md p-8 prose prose-sm max-w-none text-gray-600">
<p class="text-sm text-gray-400 mb-6">Last updated: <?= date('F j, Y') ?></p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">When Refunds Apply</h2>
<ul class="list-disc pl-5 space-y-2">
<li><strong>Cancelled before pickup:</strong> Full refund to wallet within 24 hours.</li>
<li><strong>Service quality issues:</strong> Report within 24 hours of delivery. Partial or full refund after investigation.</li>
<li><strong>Provider no-show:</strong> Full refund automatically processed if provider fails to collect.</li>
<li><strong>Damaged items:</strong> Compensation up to the item's declared value, subject to investigation.</li>
</ul>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">How to Request a Refund</h2>
<p>Go to your booking details in your dashboard and click "Report Issue." Our team will review within 48 hours. Alternatively, contact support@usafikonect.co.ke.</p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">Refund Methods</h2>
<p>Refunds are credited to your UsafiKonect wallet. You may request M-Pesa withdrawal for refunds above KES 500.</p>

<h2 class="text-xl font-bold text-gray-800 mt-6 mb-3">Subscriptions</h2>
<p>Unused subscription bookings are not refundable. However, if you cancel mid-cycle, you retain access until the period ends.</p>
</div>
</div>
</main>
<script src="<?= APP_URL ?>/assets/js/gsap-init.js" defer></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
