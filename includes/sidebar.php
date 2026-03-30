<?php
/**
 * UsafiKonect - Dashboard Sidebar
 * Role-aware sidebar for customer, provider, and admin dashboards
 */

$current_page = basename($_SERVER['PHP_SELF']);
$user_role = get_user_role();
$user_data = get_current_user();
$notification_count = get_unread_notification_count(get_user_id());

// Sidebar menu items per role
$sidebar_items = [];

if ($user_role === 'customer') {
    $sidebar_items = [
        ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard', 'url' => 'customer/dashboard.php'],
        ['icon' => 'fa-search', 'label' => 'Find Providers', 'url' => 'customer/providers.php'],
        ['icon' => 'fa-plus-circle', 'label' => 'New Booking', 'url' => 'customer/new-booking.php'],
        ['icon' => 'fa-clipboard-list', 'label' => 'My Bookings', 'url' => 'customer/bookings.php'],
        ['icon' => 'fa-wallet', 'label' => 'Wallet', 'url' => 'customer/wallet.php'],
        ['icon' => 'fa-gift', 'label' => 'Loyalty Points', 'url' => 'customer/loyalty.php'],
        ['icon' => 'fa-bell', 'label' => 'Notifications', 'url' => 'customer/notifications.php', 'badge' => $notification_count],
        ['icon' => 'fa-user', 'label' => 'Profile', 'url' => 'customer/profile.php'],
    ];
} elseif ($user_role === 'provider') {
    $sidebar_items = [
        ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard', 'url' => 'provider/dashboard.php'],
        ['icon' => 'fa-clipboard-list', 'label' => 'Bookings', 'url' => 'provider/bookings.php'],
        ['icon' => 'fa-money-bill-wave', 'label' => 'Earnings', 'url' => 'provider/earnings.php'],
        ['icon' => 'fa-tags', 'label' => 'Pricing', 'url' => 'provider/pricing.php'],
        ['icon' => 'fa-star', 'label' => 'Reviews', 'url' => 'provider/reviews.php'],
        ['icon' => 'fa-crown', 'label' => 'Subscription', 'url' => 'provider/subscription.php'],
        ['icon' => 'fa-chart-line', 'label' => 'Analytics', 'url' => 'provider/analytics.php'],
        ['icon' => 'fa-bell', 'label' => 'Notifications', 'url' => 'provider/notifications.php', 'badge' => $notification_count],
        ['icon' => 'fa-user', 'label' => 'Profile', 'url' => 'provider/profile.php'],
    ];
} elseif ($user_role === 'admin') {
    $sidebar_items = [
        ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard', 'url' => 'admin/dashboard.php'],
        ['icon' => 'fa-users', 'label' => 'Users', 'url' => 'admin/users.php'],
        ['icon' => 'fa-store', 'label' => 'Providers', 'url' => 'admin/providers.php'],
        ['icon' => 'fa-clipboard-list', 'label' => 'Bookings', 'url' => 'admin/bookings.php'],
        ['icon' => 'fa-crown', 'label' => 'Subscriptions', 'url' => 'admin/subscriptions.php'],
        ['icon' => 'fa-chart-bar', 'label' => 'Reports', 'url' => 'admin/reports.php'],
        ['icon' => 'fa-bell', 'label' => 'Notifications', 'url' => 'admin/notifications.php', 'badge' => $notification_count],
        ['icon' => 'fa-cog', 'label' => 'Settings', 'url' => 'admin/settings.php'],
    ];
}
?>

<!-- Desktop Sidebar -->
<aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 lg:pt-16 bg-white dark:bg-gray-800 border-r dark:border-gray-700 shadow-sm z-40">
    <!-- User Info -->
    <div class="p-4 border-b dark:border-gray-700">
        <div class="flex items-center space-x-3">
            <img src="<?= APP_URL ?>/assets/uploads/<?= e($user_data['profile_image'] ?? 'avatar.png') ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover border-2 border-orange-300" onerror="this.src='<?= APP_URL ?>/assets/images/avatar.png'">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate"><?= e($user_data['full_name'] ?? 'User') ?></p>
                <p class="text-xs text-orange-500 capitalize"><?= e($user_role) ?></p>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <?php foreach ($sidebar_items as $item): 
            $isActive = basename($item['url']) === $current_page;
        ?>
        <a href="<?= APP_URL ?>/<?= $item['url'] ?>" 
           class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                  <?= $isActive 
                      ? 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400 border-l-4 border-orange-500' 
                      : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' ?>">
            <i class="fas <?= $item['icon'] ?> w-5 text-center mr-3 <?= $isActive ? 'text-orange-500' : '' ?>"></i>
            <span class="flex-1"><?= e($item['label']) ?></span>
            <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                <span class="bg-red-500 text-white text-xs rounded-full px-2 py-0.5 font-bold"><?= $item['badge'] ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
    
    <!-- Sidebar Footer -->
    <div class="p-4 border-t dark:border-gray-700">
        <a href="<?= APP_URL ?>/auth/logout.php" class="flex items-center px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
            <i class="fas fa-sign-out-alt w-5 mr-3"></i> Logout
        </a>
    </div>
</aside>

<!-- Mobile Bottom Navigation -->
<nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t dark:border-gray-700 shadow-lg z-50">
    <div class="flex justify-around items-center h-16 px-2">
        <?php 
        // Show only 5 key items for mobile
        $mobileItems = array_slice($sidebar_items, 0, 5);
        foreach ($mobileItems as $item): 
            $isActive = basename($item['url']) === $current_page;
        ?>
        <a href="<?= APP_URL ?>/<?= $item['url'] ?>" 
           class="flex flex-col items-center justify-center flex-1 py-1 <?= $isActive ? 'text-orange-500' : 'text-gray-500 dark:text-gray-400' ?>">
            <div class="relative">
                <i class="fas <?= $item['icon'] ?> text-lg"></i>
                <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center"><?= min($item['badge'], 9) ?></span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] mt-1 font-medium"><?= e($item['label']) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</nav>
