<?php
/**
 * UsafiKonect - Homepage / Landing Page
 * Animated hero, features, how-it-works, providers, pricing, testimonials, CTA
 */

require_once __DIR__ . '/config/functions.php';

// Allow logged-in users to view the landing page
// They can navigate here via the Home link

// Fetch active providers and stats
$db = getDB();
$providerCount = $db->query("SELECT COUNT(*) FROM users WHERE role='provider' AND is_active=1")->fetchColumn();
$customerCount = $db->query("SELECT COUNT(*) FROM users WHERE role='customer' AND is_active=1")->fetchColumn();
$bookingCount = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

$page_title = 'Fresh Laundry, Delivered — Nairobi\'s Trusted Laundry Platform';
$page_description = 'UsafiKonect connects you with trusted laundry providers in Nairobi. Book washing, ironing & dry cleaning services. Fast pickup & delivery to your doorstep.';
$page_keywords = 'laundry nairobi, laundry service kenya, wash and fold nairobi, dry cleaning nairobi, mama fua';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- ==================== HERO SECTION ==================== -->
<section class="relative overflow-hidden animated-gradient min-h-screen flex items-center">
    <!-- Decorative elements -->
    <div class="absolute top-20 left-10 w-20 h-20 bg-orange-200 rounded-full opacity-30 float hero-float parallax" data-speed="0.3"></div>
    <div class="absolute bottom-32 right-20 w-32 h-32 bg-teal-200 rounded-full opacity-20 float-delay hero-float parallax" data-speed="0.5"></div>
    <div class="absolute top-1/3 right-1/4 w-12 h-12 bg-deepblue-200 rounded-lg opacity-20 float-delay-2 hero-float spin-slow"></div>

    <div class="container mx-auto px-4 py-20 lg:py-0">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <!-- Left: Text Content -->
            <div class="max-w-xl">
                <div class="hero-badge inline-flex items-center gap-2 bg-orange-100 text-orange-700 px-4 py-1.5 rounded-full text-sm font-medium mb-6">
                    <span class="w-2 h-2 bg-orange-500 rounded-full animate-pulse"></span>
                    Nairobi's #1 Laundry Platform
                </div>
                
                <h1 class="hero-title text-4xl md:text-5xl lg:text-6xl font-extrabold text-gray-900 leading-tight mb-6">
                    Fresh Laundry,
                    <span class="gradient-text">Delivered</span>
                    to Your Door
                </h1>
                
                <p class="hero-subtitle text-lg text-gray-600 mb-8 leading-relaxed">
                    Connect with trusted <strong>mama fuas</strong> and professional laundry shops across Nairobi. 
                    Book pickup, track progress, and get fresh clothes delivered — all from your phone.
                </p>
                
                <div class="hero-cta flex flex-col sm:flex-row gap-4 mb-10">
                    <a href="<?= APP_URL ?>/auth/register.php" class="pulse-ring inline-flex items-center justify-center px-8 py-4 bg-orange-500 text-white font-bold rounded-xl hover:bg-orange-600 transition-all shadow-lg hover:shadow-xl text-lg">
                        <i class="fas fa-rocket mr-2"></i> Get Started Free
                    </a>
                    <a href="#how-it-works" class="inline-flex items-center justify-center px-8 py-4 bg-white text-deepblue-800 font-bold rounded-xl hover:bg-gray-50 transition-all border-2 border-deepblue-800 text-lg">
                        <i class="fas fa-play-circle mr-2"></i> How It Works
                    </a>
                </div>
                
                <!-- Stats -->
                <div class="hero-stats flex gap-8">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 count-up" data-count="<?= $providerCount ?>"><?= $providerCount ?></div>
                        <div class="text-sm text-gray-500">Providers</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 count-up" data-count="<?= $customerCount ?>"><?= $customerCount ?></div>
                        <div class="text-sm text-gray-500">Happy Customers</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 count-up" data-count="<?= $bookingCount ?>"><?= $bookingCount ?></div>
                        <div class="text-sm text-gray-500">Bookings Done</div>
                    </div>
                </div>
            </div>
            
            <!-- Right: Hero Image / Illustration -->
            <div class="hero-image relative hidden lg:block">
                <div class="relative bg-white rounded-3xl shadow-2xl p-8 transform rotate-2 hover:rotate-0 transition-transform duration-500">
                    <div class="absolute -top-4 -right-4 bg-orange-500 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg">
                        <i class="fas fa-star mr-1"></i> 4.9 Rating
                    </div>
                    
                    <div class="text-center mb-6">
                        <div class="text-7xl mb-4">👕</div>
                        <h3 class="text-xl font-bold text-gray-800">Your Laundry Journey</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-center gap-3 bg-orange-50 p-3 rounded-xl">
                            <div class="w-10 h-10 bg-orange-500 text-white rounded-full flex items-center justify-center font-bold">1</div>
                            <div><div class="font-semibold text-gray-800">Book Pickup</div><div class="text-xs text-gray-500">Choose date & time</div></div>
                            <i class="fas fa-check-circle text-green-500 ml-auto"></i>
                        </div>
                        <div class="flex items-center gap-3 bg-blue-50 p-3 rounded-xl">
                            <div class="w-10 h-10 bg-deepblue-800 text-white rounded-full flex items-center justify-center font-bold">2</div>
                            <div><div class="font-semibold text-gray-800">We Wash & Iron</div><div class="text-xs text-gray-500">Professional care</div></div>
                            <i class="fas fa-check-circle text-green-500 ml-auto"></i>
                        </div>
                        <div class="flex items-center gap-3 bg-teal-50 p-3 rounded-xl">
                            <div class="w-10 h-10 bg-teal-600 text-white rounded-full flex items-center justify-center font-bold">3</div>
                            <div><div class="font-semibold text-gray-800">Fresh Delivery</div><div class="text-xs text-gray-500">To your doorstep</div></div>
                            <div class="ml-auto"><span class="badge-pulse inline-block w-3 h-3 bg-orange-500 rounded-full"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scroll indicator -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 text-gray-400 animate-bounce">
        <i class="fas fa-chevron-down text-2xl"></i>
    </div>
</section>

<!-- ==================== FEATURES SECTION ==================== -->
<section id="features" class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16 section-title">
            <span class="text-orange-500 font-semibold text-sm uppercase tracking-wider">Why UsafiKonect</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Laundry Made <span class="gradient-text">Simple</span></h2>
            <p class="text-gray-600 max-w-2xl mx-auto">No more laundry stress. We connect you with verified providers who deliver quality service every time.</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 stagger-container">
            <!-- Feature 1 -->
            <div class="stagger-item card-hover bg-gradient-to-br from-orange-50 to-white p-6 rounded-2xl border border-orange-100 text-center">
                <div class="w-14 h-14 bg-orange-100 text-orange-500 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4 scale-in">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">30+ Nairobi Estates</h3>
                <p class="text-sm text-gray-600">Roysambu, Kilimani, Westlands, Umoja, South B & more — we cover all major areas.</p>
            </div>
            
            <!-- Feature 2 -->
            <div class="stagger-item card-hover bg-gradient-to-br from-blue-50 to-white p-6 rounded-2xl border border-blue-100 text-center">
                <div class="w-14 h-14 bg-blue-100 text-deepblue-800 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4 scale-in">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">Verified Providers</h3>
                <p class="text-sm text-gray-600">Every provider is vetted and reviewed. See ratings and reviews before you book.</p>
            </div>
            
            <!-- Feature 3 -->
            <div class="stagger-item card-hover bg-gradient-to-br from-teal-50 to-white p-6 rounded-2xl border border-teal-100 text-center">
                <div class="w-14 h-14 bg-teal-100 text-teal-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4 scale-in">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">M-Pesa Payments</h3>
                <p class="text-sm text-gray-600">Pay securely via M-Pesa STK Push. Wallet system with easy top-up and cashback.</p>
            </div>
            
            <!-- Feature 4 -->
            <div class="stagger-item card-hover bg-gradient-to-br from-purple-50 to-white p-6 rounded-2xl border border-purple-100 text-center">
                <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4 scale-in">
                    <i class="fas fa-gift"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">Loyalty Rewards</h3>
                <p class="text-sm text-gray-600">Earn points with every booking. 10 bookings = 1 free wash! Plus subscription discounts.</p>
            </div>
        </div>
    </div>
</section>

<!-- ==================== HOW IT WORKS ==================== -->
<section id="how-it-works" class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16 section-title">
            <span class="text-orange-500 font-semibold text-sm uppercase tracking-wider">Simple Process</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">How It <span class="gradient-text">Works</span></h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Three easy steps to fresh, clean laundry.</p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8 max-w-4xl mx-auto stagger-container">
            <!-- Step 1 -->
            <div class="stagger-item text-center relative">
                <div class="w-20 h-20 bg-orange-500 text-white rounded-full flex items-center justify-center text-3xl mx-auto mb-6 shadow-lg scale-in">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="hidden md:block absolute top-10 left-[60%] w-[80%] border-t-2 border-dashed border-orange-200"></div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">1. Book a Service</h3>
                <p class="text-gray-600 text-sm">Choose your estate, select a provider, pick your service type and schedule a convenient pickup time.</p>
            </div>
            
            <!-- Step 2 -->
            <div class="stagger-item text-center relative">
                <div class="w-20 h-20 bg-deepblue-800 text-white rounded-full flex items-center justify-center text-3xl mx-auto mb-6 shadow-lg scale-in">
                    <i class="fas fa-tshirt"></i>
                </div>
                <div class="hidden md:block absolute top-10 left-[60%] w-[80%] border-t-2 border-dashed border-blue-200"></div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">2. We Handle It</h3>
                <p class="text-gray-600 text-sm">Your provider picks up, washes, irons and carefully packages your clothes. Track progress in real-time.</p>
            </div>
            
            <!-- Step 3 -->
            <div class="stagger-item text-center">
                <div class="w-20 h-20 bg-teal-600 text-white rounded-full flex items-center justify-center text-3xl mx-auto mb-6 shadow-lg scale-in">
                    <i class="fas fa-truck"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">3. Fresh Delivery</h3>
                <p class="text-gray-600 text-sm">Get your clean clothes delivered back to your doorstep. Pay conveniently via M-Pesa or wallet.</p>
            </div>
        </div>
    </div>
</section>

<!-- ==================== SERVICES ==================== -->
<section id="services" class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16 section-title">
            <span class="text-orange-500 font-semibold text-sm uppercase tracking-wider">Our Services</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">What We <span class="gradient-text">Offer</span></h2>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 stagger-container">
            <?php
            $services = [
                ['icon' => 'fa-water', 'title' => 'Wash & Fold', 'desc' => 'Regular washing with folding. Per kg or per item pricing.', 'color' => 'orange', 'price' => 'From KES 50/kg'],
                ['icon' => 'fa-iron', 'title' => 'Wash & Iron', 'desc' => 'Complete wash with professional ironing and packaging.', 'color' => 'blue', 'price' => 'From KES 80/kg'],
                ['icon' => 'fa-gem', 'title' => 'Dry Cleaning', 'desc' => 'Delicate fabrics, suits, curtains and special garments.', 'color' => 'purple', 'price' => 'From KES 200/item'],
                ['icon' => 'fa-hand-sparkles', 'title' => 'Ironing Only', 'desc' => 'Professional pressing and steaming for crisp results.', 'color' => 'teal', 'price' => 'From KES 30/item'],
            ];
            foreach ($services as $s): ?>
            <div class="stagger-item card-hover bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
                <div class="h-2 bg-<?= $s['color'] ?>-500"></div>
                <div class="p-6 text-center">
                    <div class="text-4xl text-<?= $s['color'] ?>-500 mb-4"><i class="fas <?= $s['icon'] ?>"></i></div>
                    <h3 class="font-bold text-gray-800 mb-2"><?= $s['title'] ?></h3>
                    <p class="text-sm text-gray-600 mb-4"><?= $s['desc'] ?></p>
                    <span class="text-sm font-bold text-<?= $s['color'] ?>-600 bg-<?= $s['color'] ?>-50 px-3 py-1 rounded-full"><?= $s['price'] ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==================== PRICING SECTION ==================== -->
<section id="pricing" class="py-20 bg-gradient-to-br from-deepblue-900 to-deepblue-800 text-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16 section-title">
            <span class="text-orange-400 font-semibold text-sm uppercase tracking-wider">Save More</span>
            <h2 class="text-3xl md:text-4xl font-bold mt-2 mb-4">Subscription Plans</h2>
            <p class="text-gray-300 max-w-2xl mx-auto">Subscribe and save up to 20% on every booking. Cancel anytime.</p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto stagger-container">
            <!-- Weekly -->
            <div class="stagger-item card-hover bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
                <h3 class="text-xl font-bold mb-2">Weekly Plan</h3>
                <div class="flex items-end gap-1 mb-6">
                    <span class="text-4xl font-extrabold">KES 500</span>
                    <span class="text-gray-300 text-sm mb-1">/week</span>
                </div>
                <ul class="space-y-3 mb-8 text-sm text-gray-200">
                    <li><i class="fas fa-check text-green-400 mr-2"></i> 1 booking per week</li>
                    <li><i class="fas fa-check text-green-400 mr-2"></i> Up to 5kg per booking</li>
                    <li><i class="fas fa-check text-green-400 mr-2"></i> 10% discount on extras</li>
                    <li><i class="fas fa-check text-green-400 mr-2"></i> Priority pickup</li>
                </ul>
                <a href="<?= APP_URL ?>/auth/register.php" class="block w-full py-3 border-2 border-white text-white font-semibold rounded-xl hover:bg-white hover:text-deepblue-800 transition-all text-center">
                    Get Started
                </a>
            </div>
            
            <!-- Monthly (Popular) -->
            <div class="stagger-item card-hover bg-orange-500 rounded-2xl p-8 border-2 border-orange-400 relative transform md:-translate-y-4 shadow-2xl">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-yellow-400 text-yellow-900 px-4 py-1 rounded-full text-xs font-bold uppercase">
                    Most Popular
                </div>
                <h3 class="text-xl font-bold mb-2">Monthly Plan</h3>
                <div class="flex items-end gap-1 mb-6">
                    <span class="text-4xl font-extrabold">KES 1,800</span>
                    <span class="text-orange-100 text-sm mb-1">/month</span>
                </div>
                <ul class="space-y-3 mb-8 text-sm text-orange-50">
                    <li><i class="fas fa-check text-yellow-300 mr-2"></i> 4 bookings per month</li>
                    <li><i class="fas fa-check text-yellow-300 mr-2"></i> Up to 8kg per booking</li>
                    <li><i class="fas fa-check text-yellow-300 mr-2"></i> 15% discount on extras</li>
                    <li><i class="fas fa-check text-yellow-300 mr-2"></i> Free pickup & delivery</li>
                    <li><i class="fas fa-check text-yellow-300 mr-2"></i> 2x loyalty points</li>
                </ul>
                <a href="<?= APP_URL ?>/auth/register.php" class="block w-full py-3 bg-white text-orange-600 font-bold rounded-xl hover:bg-orange-50 transition-all text-center shadow-lg">
                    Get Started
                </a>
            </div>
            
            <!-- Yearly -->
            <div class="stagger-item card-hover bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
                <h3 class="text-xl font-bold mb-2">Yearly Plan</h3>
                <div class="flex items-end gap-1 mb-6">
                    <span class="text-4xl font-extrabold">KES 18,000</span>
                    <span class="text-gray-300 text-sm mb-1">/year</span>
                </div>
                <div class="bg-green-500/20 text-green-300 text-xs font-bold px-3 py-1 rounded-full inline-block mb-4">Save KES 3,600/yr</div>
                <ul class="space-y-3 mb-8 text-sm text-gray-200">
                    <li><i class="fas fa-check text-green-400 mr-2"></i> 4 bookings per month</li>
                    <li><i class="fas fa-check text-green-400 mr-2"></i> Up to 10kg per booking</li>
                    <li><i class="fas fa-check text-green-400 mr-2"></i> 20% discount on all</li>
                    <li><i class="fas fa-check text-green-400 mr-2"></i> Free pickup & delivery</li>
                    <li><i class="fas fa-check text-green-400 mr-2"></i> 3x loyalty points</li>
                    <li><i class="fas fa-check text-green-400 mr-2"></i> Priority support</li>
                </ul>
                <a href="<?= APP_URL ?>/auth/register.php" class="block w-full py-3 border-2 border-white text-white font-semibold rounded-xl hover:bg-white hover:text-deepblue-800 transition-all text-center">
                    Get Started
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ==================== TESTIMONIALS ==================== -->
<section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16 section-title">
            <span class="text-orange-500 font-semibold text-sm uppercase tracking-wider">Testimonials</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">What Our <span class="gradient-text">Customers Say</span></h2>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto stagger-container">
            <?php
            $testimonials = [
                ['name' => 'Wanjiku M.', 'estate' => 'Roysambu', 'text' => 'UsafiKonect saved me so much time! The mama fua near me does an amazing job, and I love the M-Pesa payments. So convenient!', 'rating' => 5],
                ['name' => 'Kevin O.', 'estate' => 'Kilimani', 'text' => 'As a bachelor, this app is a lifesaver. I book every Sunday and my clothes are back by Tuesday. Fresh and well-ironed. Highly recommend!', 'rating' => 5],
                ['name' => 'Grace N.', 'estate' => 'Umoja', 'text' => 'I registered as a provider and my business has grown significantly. The platform makes it easy to manage bookings and get paid on time.', 'rating' => 4],
            ];
            foreach ($testimonials as $t): ?>
            <div class="stagger-item card-hover bg-white rounded-2xl p-6 shadow-md border border-gray-100">
                <div class="flex gap-1 text-yellow-400 mb-4">
                    <?php for ($i = 0; $i < $t['rating']; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                    <?php for ($i = $t['rating']; $i < 5; $i++): ?><i class="far fa-star text-gray-300"></i><?php endfor; ?>
                </div>
                <p class="text-gray-600 text-sm mb-6 italic">"<?= $t['text'] ?>"</p>
                <div class="flex items-center gap-3 border-t border-gray-100 pt-4">
                    <div class="w-10 h-10 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center font-bold">
                        <?= mb_substr($t['name'], 0, 1) ?>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-800 text-sm"><?= $t['name'] ?></div>
                        <div class="text-xs text-gray-500"><i class="fas fa-map-pin mr-1"></i><?= $t['estate'] ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==================== CTA SECTION ==================== -->
<section class="py-20 bg-orange-500 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-10 left-10 text-white text-9xl font-bold opacity-20 -rotate-12">👕</div>
        <div class="absolute bottom-10 right-10 text-white text-9xl font-bold opacity-20 rotate-12">🧺</div>
    </div>
    
    <div class="container mx-auto px-4 text-center relative z-10">
        <div class="max-w-3xl mx-auto reveal-up">
            <h2 class="text-3xl md:text-5xl font-extrabold text-white mb-6">Ready for Fresh, Clean Laundry?</h2>
            <p class="text-xl text-orange-100 mb-10">Join thousands of Nairobians who trust UsafiKonect for their laundry needs. Sign up in 30 seconds.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?= APP_URL ?>/auth/register.php" class="inline-flex items-center justify-center px-10 py-4 bg-white text-orange-600 font-bold rounded-xl hover:bg-orange-50 transition-all shadow-xl text-lg">
                    <i class="fas fa-user-plus mr-2"></i> Sign Up as Customer
                </a>
                <a href="<?= APP_URL ?>/auth/register.php?role=provider" class="inline-flex items-center justify-center px-10 py-4 bg-deepblue-800 text-white font-bold rounded-xl hover:bg-deepblue-900 transition-all shadow-xl text-lg">
                    <i class="fas fa-store mr-2"></i> Register as Provider
                </a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="<?= APP_URL ?>/assets/js/gsap-init.js"></script>
