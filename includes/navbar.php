<?php
/**
 * UsafiKonect - Responsive Navbar
 * Auth-aware with role-based links
 */
$current_page = basename($_SERVER['PHP_SELF']);
$is_auth = is_logged_in();
$user_role = $is_auth ? get_user_role() : '';
$user_data = $is_auth ? get_current_user_data() : [];
$notification_count = $is_auth ? get_unread_notification_count(get_user_id()) : 0;
?>

<nav class="bg-white dark:bg-gray-800 shadow-md sticky top-0 z-50 transition-all duration-300" id="main-navbar">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <a href="<?= APP_URL ?>/index.php" class="flex items-center space-x-2 group">
                <span class="text-3xl">🧺</span>
                <span class="text-xl font-bold">
                    <span class="text-orange-500 group-hover:text-orange-600 transition-colors">Usafi</span><span class="text-deepblue-800 dark:text-blue-300 group-hover:text-deepblue-900 transition-colors">Konect</span>
                </span>
            </a>
            
            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-1">
                <?php if (!$is_auth): ?>
                    <a href="<?= APP_URL ?>/index.php" class="nav-link <?= $current_page === 'index.php' ? 'nav-active' : '' ?>">Home</a>
                    <a href="<?= APP_URL ?>/about.php" class="nav-link <?= $current_page === 'about.php' ? 'nav-active' : '' ?>">About</a>
                    <a href="<?= APP_URL ?>/pricing.php" class="nav-link <?= $current_page === 'pricing.php' ? 'nav-active' : '' ?>">Pricing</a>
                    <a href="<?= APP_URL ?>/contact.php" class="nav-link <?= $current_page === 'contact.php' ? 'nav-active' : '' ?>">Contact</a>
                <?php else: ?>
                    <a href="<?= APP_URL ?>/index.php" class="nav-link <?= $current_page === 'index.php' ? 'nav-active' : '' ?>">
                        <i class="fas fa-home mr-1"></i> Home
                    </a>
                    <a href="<?= APP_URL ?>/<?= $user_role ?>/dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'nav-active' : '' ?>">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                    </a>
                    <?php if ($user_role === 'customer'): ?>
                        <a href="<?= APP_URL ?>/customer/providers.php" class="nav-link <?= $current_page === 'providers.php' ? 'nav-active' : '' ?>">Find Providers</a>
                        <a href="<?= APP_URL ?>/customer/bookings.php" class="nav-link <?= $current_page === 'bookings.php' ? 'nav-active' : '' ?>">My Bookings</a>
                    <?php elseif ($user_role === 'provider'): ?>
                        <a href="<?= APP_URL ?>/provider/bookings.php" class="nav-link <?= $current_page === 'bookings.php' ? 'nav-active' : '' ?>">Bookings</a>
                        <a href="<?= APP_URL ?>/provider/earnings.php" class="nav-link <?= $current_page === 'earnings.php' ? 'nav-active' : '' ?>">Earnings</a>
                    <?php elseif ($user_role === 'admin'): ?>
                        <a href="<?= APP_URL ?>/admin/users.php" class="nav-link <?= $current_page === 'users.php' ? 'nav-active' : '' ?>">Users</a>
                        <a href="<?= APP_URL ?>/admin/bookings.php" class="nav-link <?= $current_page === 'bookings.php' ? 'nav-active' : '' ?>">Bookings</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Right Side (Auth Buttons / User Menu) -->
            <div class="hidden md:flex items-center space-x-3">
                <!-- Dark Mode Toggle -->
                <button id="darkModeToggle" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Toggle Dark Mode">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:inline"></i>
                </button>
                
                <?php if (!$is_auth): ?>
                    <a href="<?= APP_URL ?>/auth/login.php" class="px-4 py-2 text-sm font-medium text-deepblue-800 hover:text-orange-500 transition-colors">Login</a>
                    <a href="<?= APP_URL ?>/auth/register.php" class="px-5 py-2.5 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5">Get Started</a>
                <?php else: ?>
                    <!-- Notification Bell -->
                    <a href="<?= APP_URL ?>/<?= $user_role ?>/notifications.php" class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-bell text-lg"></i>
                        <span id="notif-badge" class="<?= $notification_count > 0 ? '' : 'hidden' ?> absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold"><?= $notification_count ?></span>
                    </a>
                    
                    <!-- User Dropdown -->
                    <div class="relative" id="userDropdown">
                        <button class="flex items-center space-x-2 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" onclick="document.getElementById('dropdownMenu').classList.toggle('hidden')">
                            <img src="<?= APP_URL ?>/assets/uploads/profiles/<?= e($user_data['profile_image'] ?? 'avatar.png') ?>" alt="Profile" class="w-8 h-8 rounded-full object-cover border-2 border-orange-300" onerror="this.src='<?= APP_URL ?>/assets/images/avatar.png'">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200"><?= e($user_data['full_name'] ?? 'User') ?></span>
                            <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                        </button>
                        <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-lg ring-1 ring-black ring-opacity-5 py-2 z-50">
                            <div class="px-4 py-2 border-b dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-900 dark:text-white"><?= e($user_data['full_name'] ?? '') ?></p>
                                <p class="text-xs text-gray-500"><?= e($user_data['email'] ?? '') ?></p>
                            </div>
                            <a href="<?= APP_URL ?>/<?= $user_role ?>/dashboard.php" class="dropdown-link"><i class="fas fa-tachometer-alt w-5"></i> Dashboard</a>
                            <a href="<?= APP_URL ?>/<?= $user_role ?>/profile.php" class="dropdown-link"><i class="fas fa-user w-5"></i> Profile</a>
                            <?php if ($user_role === 'customer'): ?>
                                <a href="<?= APP_URL ?>/customer/wallet.php" class="dropdown-link"><i class="fas fa-wallet w-5"></i> Wallet</a>
                                <a href="<?= APP_URL ?>/customer/loyalty.php" class="dropdown-link"><i class="fas fa-gift w-5"></i> Loyalty Points</a>
                            <?php endif; ?>
                            <hr class="my-1 dark:border-gray-700">
                            <a href="<?= APP_URL ?>/auth/logout.php" class="dropdown-link text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"><i class="fas fa-sign-out-alt w-5"></i> Logout</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Mobile Menu Button -->
            <div class="md:hidden flex items-center space-x-2">
                <?php if ($is_auth): ?>
                    <a href="<?= APP_URL ?>/<?= $user_role ?>/notifications.php" class="relative p-2">
                        <i class="fas fa-bell text-gray-600 dark:text-gray-300"></i>
                        <span id="notif-badge-mobile" class="<?= $notification_count > 0 ? '' : 'hidden' ?> absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center"><?= $notification_count ?></span>
                    </a>
                <?php endif; ?>
                <button id="mobileMenuBtn" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="fas fa-bars text-xl" id="menuIcon"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Mobile Menu -->
    <div id="mobileMenu" class="hidden md:hidden bg-white dark:bg-gray-800 border-t dark:border-gray-700 shadow-lg">
        <div class="px-4 py-3 space-y-1">
            <?php if (!$is_auth): ?>
                <a href="<?= APP_URL ?>/index.php" class="mobile-nav-link">Home</a>
                <a href="<?= APP_URL ?>/about.php" class="mobile-nav-link">About</a>
                <a href="<?= APP_URL ?>/pricing.php" class="mobile-nav-link">Pricing</a>
                <a href="<?= APP_URL ?>/contact.php" class="mobile-nav-link">Contact</a>
                <hr class="dark:border-gray-700">
                <a href="<?= APP_URL ?>/auth/login.php" class="mobile-nav-link text-deepblue-800">Login</a>
                <a href="<?= APP_URL ?>/auth/register.php" class="block w-full text-center py-2.5 bg-orange-500 text-white rounded-lg font-medium hover:bg-orange-600">Get Started</a>
            <?php else: ?>
                <div class="flex items-center space-x-3 pb-3 border-b dark:border-gray-700">
                    <img src="<?= APP_URL ?>/assets/uploads/profiles/<?= e($user_data['profile_image'] ?? 'avatar.png') ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover border-2 border-orange-300" onerror="this.src='<?= APP_URL ?>/assets/images/avatar.png'">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white"><?= e($user_data['full_name'] ?? '') ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?= e($user_role) ?></p>
                    </div>
                </div>
                <a href="<?= APP_URL ?>/<?= $user_role ?>/dashboard.php" class="mobile-nav-link"><i class="fas fa-tachometer-alt w-6"></i> Dashboard</a>
                <?php if ($user_role === 'customer'): ?>
                    <a href="<?= APP_URL ?>/index.php" class="mobile-nav-link"><i class="fas fa-home w-6"></i> Home</a>
                    <a href="<?= APP_URL ?>/customer/providers.php" class="mobile-nav-link"><i class="fas fa-search w-6"></i> Find Providers</a>
                    <a href="<?= APP_URL ?>/customer/bookings.php" class="mobile-nav-link"><i class="fas fa-clipboard-list w-6"></i> My Bookings</a>
                    <a href="<?= APP_URL ?>/customer/wallet.php" class="mobile-nav-link"><i class="fas fa-wallet w-6"></i> Wallet</a>
                    <a href="<?= APP_URL ?>/customer/loyalty.php" class="mobile-nav-link"><i class="fas fa-gift w-6"></i> Loyalty</a>
                <?php elseif ($user_role === 'provider'): ?>
                    <a href="<?= APP_URL ?>/provider/bookings.php" class="mobile-nav-link"><i class="fas fa-clipboard-list w-6"></i> Bookings</a>
                    <a href="<?= APP_URL ?>/provider/earnings.php" class="mobile-nav-link"><i class="fas fa-money-bill-wave w-6"></i> Earnings</a>
                    <a href="<?= APP_URL ?>/provider/subscription.php" class="mobile-nav-link"><i class="fas fa-crown w-6"></i> Subscription</a>
                <?php elseif ($user_role === 'admin'): ?>
                    <a href="<?= APP_URL ?>/admin/users.php" class="mobile-nav-link"><i class="fas fa-users w-6"></i> Users</a>
                    <a href="<?= APP_URL ?>/admin/providers.php" class="mobile-nav-link"><i class="fas fa-store w-6"></i> Providers</a>
                    <a href="<?= APP_URL ?>/admin/bookings.php" class="mobile-nav-link"><i class="fas fa-clipboard-list w-6"></i> Bookings</a>
                    <a href="<?= APP_URL ?>/admin/reports.php" class="mobile-nav-link"><i class="fas fa-chart-line w-6"></i> Reports</a>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/<?= $user_role ?>/profile.php" class="mobile-nav-link"><i class="fas fa-user w-6"></i> Profile</a>
                <hr class="dark:border-gray-700">
                <a href="<?= APP_URL ?>/auth/logout.php" class="mobile-nav-link text-red-600"><i class="fas fa-sign-out-alt w-6"></i> Logout</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
// Mobile menu toggle
document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
    const menu = document.getElementById('mobileMenu');
    const icon = document.getElementById('menuIcon');
    menu.classList.toggle('hidden');
    icon.classList.toggle('fa-bars');
    icon.classList.toggle('fa-times');
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('userDropdown');
    const menu = document.getElementById('dropdownMenu');
    if (dropdown && menu && !dropdown.contains(e.target)) {
        menu.classList.add('hidden');
    }
});

// Navbar scroll effect
let lastScroll = 0;
window.addEventListener('scroll', () => {
    const navbar = document.getElementById('main-navbar');
    if (!navbar) return;
    const currentScroll = window.pageYOffset;
    if (currentScroll > 50) {
        navbar.classList.add('shadow-lg');
    } else {
        navbar.classList.remove('shadow-lg');
    }
    lastScroll = currentScroll;
});
</script>
