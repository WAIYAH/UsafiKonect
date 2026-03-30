<?php
/**
 * UsafiKonect - Common Footer
 * Included at the bottom of every page
 */
?>

    <!-- Footer -->
    <footer class="bg-deepblue-800 text-white mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Brand -->
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <span class="text-3xl">🧺</span>
                        <span class="text-xl font-bold"><span class="text-orange-400">Usafi</span>Konect</span>
                    </div>
                    <p class="text-gray-300 text-sm mb-4">Your trusted laundry partner in Nairobi. Connecting customers with reliable laundry service providers across all estates.</p>
                    <p class="text-gray-400 text-sm italic">"Nguo safi, maisha safi" — Clean clothes, clean life</p>
                    <!-- Social Links -->
                    <div class="flex space-x-3 mt-4">
                        <a href="#" class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center hover:bg-orange-500 transition-colors" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center hover:bg-orange-500 transition-colors" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center hover:bg-green-500 transition-colors" aria-label="WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="#" class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center hover:bg-pink-500 transition-colors" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-orange-400">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="<?= APP_URL ?>/about.php" class="text-gray-300 hover:text-orange-400 transition-colors text-sm">About Us</a></li>
                        <li><a href="<?= APP_URL ?>/pricing.php" class="text-gray-300 hover:text-orange-400 transition-colors text-sm">Pricing</a></li>
                        <li><a href="<?= APP_URL ?>/contact.php" class="text-gray-300 hover:text-orange-400 transition-colors text-sm">Contact Us</a></li>
                        <li><a href="<?= APP_URL ?>/auth/register.php" class="text-gray-300 hover:text-orange-400 transition-colors text-sm">Become a Provider</a></li>
                    </ul>
                </div>
                
                <!-- Legal -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-orange-400">Legal</h3>
                    <ul class="space-y-2">
                        <li><a href="<?= APP_URL ?>/legal/terms.php" class="text-gray-300 hover:text-orange-400 transition-colors text-sm">Terms of Service</a></li>
                        <li><a href="<?= APP_URL ?>/legal/privacy.php" class="text-gray-300 hover:text-orange-400 transition-colors text-sm">Privacy Policy</a></li>
                        <li><a href="<?= APP_URL ?>/legal/cookies.php" class="text-gray-300 hover:text-orange-400 transition-colors text-sm">Cookie Policy</a></li>
                        <li><a href="<?= APP_URL ?>/legal/refund.php" class="text-gray-300 hover:text-orange-400 transition-colors text-sm">Refund Policy</a></li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-orange-400">Contact Us</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-map-marker-alt text-orange-400 mt-1"></i>
                            <span class="text-gray-300 text-sm">Nairobi, Kenya</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-phone text-orange-400 mt-1"></i>
                            <span class="text-gray-300 text-sm">+254 700 123 456</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-envelope text-orange-400 mt-1"></i>
                            <span class="text-gray-300 text-sm">info@usafikonect.co.ke</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-clock text-orange-400 mt-1"></i>
                            <span class="text-gray-300 text-sm">Mon - Sat: 6:00 AM - 8:00 PM</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Bottom Bar -->
            <div class="border-t border-white/10 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm">&copy; <?= date('Y') ?> UsafiKonect. All rights reserved. Made with ❤️ in Nairobi.</p>
                <div class="flex items-center space-x-2 mt-3 md:mt-0">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/M-PESA_LOGO-01.svg/120px-M-PESA_LOGO-01.svg.png" alt="M-Pesa" class="h-6 opacity-70">
                    <span class="text-gray-500 text-xs">Powered by Safaricom M-Pesa</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Cookie Consent Banner -->
    <div id="cookieConsent" class="hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 shadow-2xl border-t dark:border-gray-700 z-50 p-4 md:p-6">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex-1">
                <h4 class="font-semibold text-gray-900 dark:text-white text-sm">🍪 We use cookies</h4>
                <p class="text-gray-600 dark:text-gray-300 text-xs mt-1">We use cookies to enhance your experience. By continuing, you agree to our <a href="<?= APP_URL ?>/legal/cookies.php" class="text-orange-500 underline">Cookie Policy</a>.</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <button onclick="rejectCookies()" class="px-4 py-2 text-xs font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">Reject</button>
                <button onclick="acceptCookies()" class="px-4 py-2 text-xs font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600">Accept All</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed top-20 right-4 z-50 flex flex-col gap-2"></div>

    <!-- Scripts -->
    <script src="<?= APP_URL ?>/assets/js/main.js"></script>
    <?php if (is_logged_in()): ?>
    <script src="<?= APP_URL ?>/assets/js/notifications.js"></script>
    <?php endif; ?>
    
    <script>
    // Cookie Consent
    function acceptCookies() {
        localStorage.setItem('cookieConsent', 'accepted');
        document.getElementById('cookieConsent').classList.add('hidden');
    }
    function rejectCookies() {
        localStorage.setItem('cookieConsent', 'rejected');
        document.getElementById('cookieConsent').classList.add('hidden');
    }
    if (!localStorage.getItem('cookieConsent')) {
        document.getElementById('cookieConsent').classList.remove('hidden');
    }
    
    // Dark Mode
    const darkToggle = document.getElementById('darkModeToggle');
    if (darkToggle) {
        darkToggle.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
        });
    }
    if (localStorage.getItem('darkMode') === 'true') {
        document.documentElement.classList.add('dark');
    }
    </script>
</body>
</html>
