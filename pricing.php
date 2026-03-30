<?php
/**
 * UsafiKonect - Pricing Page
 */

require_once __DIR__ . '/config/functions.php';

$page_title = 'Pricing — Affordable Laundry Services';
$page_description = 'Transparent pricing for all UsafiKonect laundry services. Pay per kg, per item, or save with subscription plans.';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- Hero -->
<section class="bg-gradient-to-br from-deepblue-900 to-deepblue-800 text-white py-16">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl font-extrabold mb-3">Transparent <span class="text-orange-400">Pricing</span></h1>
        <p class="text-gray-300 text-lg max-w-2xl mx-auto">Quality laundry service at prices that won't break the bank. No hidden fees.</p>
    </div>
</section>

<!-- Service Pricing -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Service <span class="gradient-text">Rates</span></h2>
            <p class="text-gray-600">Prices vary by provider. Below are typical ranges across Nairobi.</p>
        </div>
        
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Service</th>
                            <th class="px-6 py-4 text-center text-sm font-bold text-gray-700">Unit</th>
                            <th class="px-6 py-4 text-center text-sm font-bold text-gray-700">Price Range</th>
                            <th class="px-6 py-4 text-center text-sm font-bold text-gray-700">Turnaround</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php
                        $services = [
                            ['Wash & Fold', 'Per kg', 'KES 50 - 150', '24 - 48 hrs', 'fa-water', 'orange'],
                            ['Wash & Iron', 'Per kg', 'KES 80 - 200', '24 - 48 hrs', 'fa-iron', 'blue'],
                            ['Ironing Only', 'Per item', 'KES 20 - 50', '12 - 24 hrs', 'fa-hand-sparkles', 'teal'],
                            ['Dry Cleaning', 'Per item', 'KES 200 - 800', '48 - 72 hrs', 'fa-gem', 'purple'],
                            ['Curtains & Duvets', 'Per item', 'KES 300 - 1,000', '48 - 72 hrs', 'fa-bed', 'pink'],
                            ['Express Service', 'Per kg', 'KES 150 - 350', '6 - 12 hrs', 'fa-bolt', 'yellow'],
                        ];
                        foreach ($services as $s): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 flex items-center gap-3">
                                <span class="text-<?= $s[5] ?>-500"><i class="fas <?= $s[4] ?>"></i></span>
                                <span class="font-medium text-gray-800"><?= $s[0] ?></span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600"><?= $s[1] ?></td>
                            <td class="px-6 py-4 text-center font-bold text-orange-600"><?= $s[2] ?></td>
                            <td class="px-6 py-4 text-center text-sm text-gray-500"><?= $s[3] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-400 mt-3 text-center">* Prices are estimates. Actual pricing is set by individual providers.</p>
        </div>
    </div>
</section>

<!-- Subscription Plans (same as homepage) -->
<section class="py-20 bg-gradient-to-br from-deepblue-900 to-deepblue-800 text-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <span class="text-orange-400 font-semibold text-sm uppercase tracking-wider">Save More</span>
            <h2 class="text-3xl md:text-4xl font-bold mt-2 mb-4">Subscription Plans</h2>
            <p class="text-gray-300 max-w-2xl mx-auto">Subscribe and save up to 20%. Perfect for regular laundry needs.</p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 card-hover">
                <h3 class="text-xl font-bold mb-2">Weekly</h3>
                <div class="text-4xl font-extrabold mb-1">KES 500</div>
                <div class="text-gray-300 text-sm mb-6">/week</div>
                <ul class="space-y-2 text-sm text-gray-200 mb-8">
                    <li><i class="fas fa-check text-green-400 mr-2"></i>1 booking/week up to 5kg</li>
                    <li><i class="fas fa-check text-green-400 mr-2"></i>10% off extras</li>
                    <li><i class="fas fa-check text-green-400 mr-2"></i>Priority pickup</li>
                </ul>
                <a href="<?= APP_URL ?>/auth/register.php" class="block w-full py-3 border border-white rounded-xl text-center hover:bg-white hover:text-deepblue-800 transition-all font-semibold">Subscribe</a>
            </div>
            
            <div class="bg-orange-500 rounded-2xl p-8 border-2 border-orange-400 relative md:-translate-y-4 shadow-2xl card-hover">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-yellow-400 text-yellow-900 px-3 py-0.5 rounded-full text-xs font-bold uppercase">Best Value</div>
                <h3 class="text-xl font-bold mb-2">Monthly</h3>
                <div class="text-4xl font-extrabold mb-1">KES 1,800</div>
                <div class="text-orange-100 text-sm mb-6">/month</div>
                <ul class="space-y-2 text-sm text-orange-50 mb-8">
                    <li><i class="fas fa-check text-yellow-300 mr-2"></i>4 bookings/month up to 8kg</li>
                    <li><i class="fas fa-check text-yellow-300 mr-2"></i>15% off extras</li>
                    <li><i class="fas fa-check text-yellow-300 mr-2"></i>Free pickup & delivery</li>
                    <li><i class="fas fa-check text-yellow-300 mr-2"></i>2x loyalty points</li>
                </ul>
                <a href="<?= APP_URL ?>/auth/register.php" class="block w-full py-3 bg-white text-orange-600 rounded-xl text-center font-bold hover:bg-orange-50 transition-all shadow-lg">Subscribe</a>
            </div>
            
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 card-hover">
                <h3 class="text-xl font-bold mb-2">Yearly</h3>
                <div class="text-4xl font-extrabold mb-1">KES 18,000</div>
                <div class="text-gray-300 text-sm mb-1">/year</div>
                <div class="text-green-300 text-xs font-bold mb-6">Save KES 3,600</div>
                <ul class="space-y-2 text-sm text-gray-200 mb-8">
                    <li><i class="fas fa-check text-green-400 mr-2"></i>4 bookings/month up to 10kg</li>
                    <li><i class="fas fa-check text-green-400 mr-2"></i>20% off everything</li>
                    <li><i class="fas fa-check text-green-400 mr-2"></i>3x loyalty points</li>
                    <li><i class="fas fa-check text-green-400 mr-2"></i>Priority support</li>
                </ul>
                <a href="<?= APP_URL ?>/auth/register.php" class="block w-full py-3 border border-white rounded-xl text-center hover:bg-white hover:text-deepblue-800 transition-all font-semibold">Subscribe</a>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4 max-w-3xl">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900">Frequently Asked <span class="gradient-text">Questions</span></h2>
        </div>
        
        <?php
        $faqs = [
            ['How are prices determined?', 'Each provider sets their own prices. You can compare rates and reviews before booking. The prices listed on this page are typical ranges.'],
            ['Are there any hidden fees?', 'No hidden fees. The price you see at booking is what you pay. Subscription members get additional discounts automatically applied.'],
            ['Can I cancel a subscription?', 'Yes, you can cancel anytime from your dashboard. You\'ll continue to have access until the end of your billing period.'],
            ['What payment methods do you accept?', 'We accept M-Pesa (STK Push), wallet balance, and cash on delivery for select providers.'],
            ['Is there a minimum order?', 'Most providers have a minimum of 2-3kg. This varies by provider and is shown at booking.'],
        ];
        ?>
        
        <div class="space-y-4">
            <?php foreach ($faqs as $i => $faq): ?>
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <button onclick="toggleFaq(<?= $i ?>)" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                    <span class="font-semibold text-gray-800"><?= $faq[0] ?></span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform" id="faq-icon-<?= $i ?>"></i>
                </button>
                <div id="faq-body-<?= $i ?>" class="hidden px-6 pb-4">
                    <p class="text-gray-600 text-sm"><?= $faq[1] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
function toggleFaq(i) {
    const body = document.getElementById('faq-body-' + i);
    const icon = document.getElementById('faq-icon-' + i);
    body.classList.toggle('hidden');
    icon.classList.toggle('rotate-180');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
