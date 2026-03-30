<?php
/**
 * UsafiKonect - About Us Page
 */

require_once __DIR__ . '/config/functions.php';

$page_title = 'About Us — Our Story';
$page_description = 'Learn about UsafiKonect, Nairobi\'s trusted laundry service platform connecting customers with quality laundry providers.';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- Hero -->
<section class="bg-gradient-to-br from-deepblue-900 to-deepblue-800 text-white py-20">
    <div class="container mx-auto px-4 text-center max-w-3xl reveal-up">
        <span class="text-orange-400 font-semibold text-sm uppercase tracking-wider">Our Story</span>
        <h1 class="text-4xl md:text-5xl font-extrabold mt-2 mb-6">About <span class="text-orange-400">UsafiKonect</span></h1>
        <p class="text-gray-300 text-lg leading-relaxed">We're on a mission to revolutionize laundry services in Nairobi by connecting hardworking providers with customers who value fresh, clean clothes.</p>
    </div>
</section>

<!-- Mission & Vision -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-2 gap-12 max-w-5xl mx-auto">
            <div class="reveal-left">
                <div class="w-14 h-14 bg-orange-100 text-orange-500 rounded-2xl flex items-center justify-center text-2xl mb-4">
                    <i class="fas fa-bullseye"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Our Mission</h2>
                <p class="text-gray-600 leading-relaxed">To empower mama fuas and laundry providers across Nairobi with technology, while giving customers a seamless, affordable, and reliable laundry experience. We believe clean clothes shouldn't be a luxury — they should be accessible to everyone.</p>
            </div>
            <div class="reveal-right">
                <div class="w-14 h-14 bg-teal-100 text-teal-600 rounded-2xl flex items-center justify-center text-2xl mb-4">
                    <i class="fas fa-eye"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Our Vision</h2>
                <p class="text-gray-600 leading-relaxed">To be East Africa's leading laundry service marketplace — creating jobs, uplifting communities, and making professional laundry care available in every neighborhood from Roysambu to Langata.</p>
            </div>
        </div>
    </div>
</section>

<!-- Values -->
<section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-gray-900">Our <span class="gradient-text">Values</span></h2>
        </div>
        <div class="grid md:grid-cols-3 gap-8 max-w-4xl mx-auto stagger-container">
            <?php
            $values = [
                ['icon' => 'fa-handshake', 'title' => 'Trust & Transparency', 'desc' => 'Every provider is verified. Honest pricing, real reviews, and secure payments.', 'color' => 'orange'],
                ['icon' => 'fa-users', 'title' => 'Community First', 'desc' => 'We empower local providers — especially mama fuas — to grow their business and income.', 'color' => 'deepblue'],
                ['icon' => 'fa-leaf', 'title' => 'Quality & Care', 'desc' => 'We believe in quality service. Your clothes are treated with care and respect.', 'color' => 'teal'],
            ];
            foreach ($values as $v): ?>
            <div class="stagger-item card-hover bg-white rounded-2xl p-8 text-center shadow-md border border-gray-100">
                <div class="w-16 h-16 bg-<?= $v['color'] ?>-100 text-<?= $v['color'] ?>-600 rounded-full flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fas <?= $v['icon'] ?>"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2"><?= $v['title'] ?></h3>
                <p class="text-sm text-gray-600"><?= $v['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-16 bg-orange-500">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold text-white mb-4">Join the UsafiKonect Family</h2>
        <p class="text-orange-100 mb-8 max-w-xl mx-auto">Whether you're looking for a laundry provider or want to offer your services, we'd love to have you.</p>
        <a href="<?= APP_URL ?>/auth/register.php" class="inline-flex items-center px-8 py-4 bg-white text-orange-600 font-bold rounded-xl hover:bg-orange-50 transition-all shadow-lg text-lg">
            <i class="fas fa-rocket mr-2"></i> Get Started Today
        </a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="<?= APP_URL ?>/assets/js/gsap-init.js"></script>
