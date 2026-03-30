<?php
/**
 * UsafiKonect - Dashboard Top Header Bar
 * Shows search, notifications, and user profile for authenticated dashboard pages
 * Included by sidebar.php — $current_page, $user_role, $user_data, $notification_count are already set
 */

// Page titles per file
$page_titles = [
    'dashboard.php' => 'Dashboard',
    'users.php' => 'Users',
    'providers.php' => ($user_role === 'admin') ? 'Providers' : 'Find Providers',
    'bookings.php' => 'Bookings',
    'booking-detail.php' => 'Booking Details',
    'book.php' => 'New Booking',
    'subscriptions.php' => 'Subscriptions',
    'reports.php' => 'Reports',
    'support.php' => 'Support',
    'notifications.php' => 'Notifications',
    'settings.php' => 'Settings',
    'profile.php' => 'Profile',
    'wallet.php' => 'Wallet',
    'loyalty.php' => 'Loyalty Points',
    'earnings.php' => 'Earnings',
    'reviews.php' => 'Reviews',
    'pay.php' => 'Payment',
    'booking-action.php' => 'Booking Action',
];
$header_title = $page_titles[$current_page] ?? 'Dashboard';

// Personalized subtitle
$header_subtitle = ucfirst($user_role) . ' Panel';
if ($current_page === 'dashboard.php') {
    $greetHour = (int)date('G');
    $greeting = $greetHour < 12 ? 'Good morning' : ($greetHour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = explode(' ', $user_data['full_name'] ?? 'User')[0];
    $header_title = $greeting . ', ' . e($firstName) . '!';
    $header_subtitle = 'Here\'s your overview for today';
}
?>

<!-- Dashboard Top Header -->
<header class="lg:ml-64 sticky top-0 z-40 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between h-16 px-4 lg:px-8">
        <!-- Left: Mobile menu + Page title -->
        <div class="flex items-center gap-3">
            <!-- Mobile sidebar toggle -->
            <button id="sidebarToggleBtn" class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                <i class="fas fa-bars text-lg"></i>
            </button>
            
            <div>
                <h1 class="text-lg font-bold text-gray-800 dark:text-white"><?= e($header_title) ?></h1>
                <p class="text-xs text-gray-400 hidden sm:block"><?= e($header_subtitle) ?></p>
            </div>
        </div>
        
        <!-- Right: Search, Notifications, Profile -->
        <div class="flex items-center gap-2 sm:gap-3">
            <!-- Search -->
            <div class="relative hidden sm:block" id="dashSearchWrap">
                <form id="dashSearchForm" action="<?= APP_URL ?>/<?= $user_role ?>/<?= $user_role === 'customer' ? 'providers.php' : ($user_role === 'admin' ? 'users.php' : 'bookings.php') ?>" method="GET">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" name="search" placeholder="Search..." 
                               class="w-48 lg:w-64 pl-9 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:border-orange-400 focus:ring-2 focus:ring-orange-100 dark:focus:ring-orange-900 focus:bg-white dark:focus:bg-gray-600 transition-all"
                               id="dashSearchInput">
                    </div>
                </form>
            </div>
            
            <!-- Mobile search toggle -->
            <button id="mobileSearchToggle" class="sm:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                <i class="fas fa-search"></i>
            </button>
            
            <!-- Dark Mode -->
            <button id="dashDarkToggle" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Toggle Dark Mode">
                <i class="fas fa-moon dark:hidden"></i>
                <i class="fas fa-sun hidden dark:inline text-yellow-400"></i>
            </button>
            
            <!-- Notifications -->
            <a href="<?= APP_URL ?>/<?= $user_role ?>/notifications.php" 
               class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Notifications">
                <i class="fas fa-bell text-lg"></i>
                <?php if ($notification_count > 0): ?>
                <span class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-red-500 text-white text-[10px] rounded-full flex items-center justify-center font-bold badge-pulse">
                    <?= $notification_count > 99 ? '99+' : $notification_count ?>
                </span>
                <?php endif; ?>
            </a>
            
            <!-- User Profile Dropdown -->
            <div class="relative" id="dashUserDropdown">
                <button class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" 
                        onclick="document.getElementById('dashDropdownMenu').classList.toggle('hidden')">
                    <img src="<?= APP_URL ?>/assets/uploads/profiles/<?= e($user_data['profile_image'] ?? 'avatar.png') ?>" 
                         alt="Profile" 
                         class="w-8 h-8 rounded-full object-cover border-2 border-orange-300" 
                         onerror="this.src='<?= APP_URL ?>/assets/images/avatar.png'">
                    <div class="hidden md:block text-left">
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-200 leading-tight"><?= e($user_data['full_name'] ?? 'User') ?></div>
                        <div class="text-[10px] text-orange-500 capitalize"><?= e($user_role) ?></div>
                    </div>
                    <i class="fas fa-chevron-down text-xs text-gray-400 hidden md:block"></i>
                </button>
                
                <div id="dashDropdownMenu" class="hidden absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-xl ring-1 ring-black/5 dark:ring-white/10 py-1 z-50">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white"><?= e($user_data['full_name'] ?? '') ?></p>
                        <p class="text-xs text-gray-500 truncate"><?= e($user_data['email'] ?? '') ?></p>
                    </div>
                    <a href="<?= APP_URL ?>/<?= $user_role ?>/dashboard.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-tachometer-alt w-4 text-center text-gray-400"></i> Dashboard
                    </a>
                    <a href="<?= APP_URL ?>/<?= $user_role ?>/profile.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-user w-4 text-center text-gray-400"></i> My Profile
                    </a>
                    <?php if ($user_role === 'customer'): ?>
                    <a href="<?= APP_URL ?>/customer/wallet.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-wallet w-4 text-center text-gray-400"></i> Wallet
                    </a>
                    <?php endif; ?>
                    <a href="<?= APP_URL ?>/index.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-home w-4 text-center text-gray-400"></i> Back to Home
                    </a>
                    <hr class="my-1 border-gray-100 dark:border-gray-700">
                    <a href="<?= APP_URL ?>/auth/logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <i class="fas fa-sign-out-alt w-4 text-center"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile Search Bar (hidden by default) -->
    <div id="mobileSearchBar" class="hidden sm:hidden px-4 pb-3">
        <form action="<?= APP_URL ?>/<?= $user_role ?>/<?= $user_role === 'customer' ? 'providers.php' : ($user_role === 'admin' ? 'users.php' : 'bookings.php') ?>" method="GET">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" name="search" placeholder="Search..." 
                       class="w-full pl-9 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
            </div>
        </form>
    </div>
</header>

<script>
// Dashboard header interactions
document.addEventListener('DOMContentLoaded', function() {
    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('dashUserDropdown');
        const menu = document.getElementById('dashDropdownMenu');
        if (dropdown && menu && !dropdown.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
    
    // Mobile search toggle
    const searchToggle = document.getElementById('mobileSearchToggle');
    const searchBar = document.getElementById('mobileSearchBar');
    if (searchToggle && searchBar) {
        searchToggle.addEventListener('click', function() {
            searchBar.classList.toggle('hidden');
            if (!searchBar.classList.contains('hidden')) {
                searchBar.querySelector('input')?.focus();
            }
        });
    }
    
    // Dark mode toggle
    const darkToggle = document.getElementById('dashDarkToggle');
    if (darkToggle) {
        darkToggle.addEventListener('click', function() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
        });
    }
    
    // Mobile sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggleBtn');
    const mobileSidebar = document.getElementById('mobileSidebarOverlay');
    if (sidebarToggle && mobileSidebar) {
        sidebarToggle.addEventListener('click', function() {
            mobileSidebar.classList.toggle('hidden');
        });
    }
});
</script>
