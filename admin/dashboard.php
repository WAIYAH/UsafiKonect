<?php
/**
 * UsafiKonect - Admin Dashboard
 * Platform-wide overview with charts
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('admin');

$db = getDB();

// Platform stats
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
$totalCustomers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$totalProviders = $db->query("SELECT COUNT(*) FROM users WHERE role = 'provider'")->fetchColumn();
$pendingProviders = $db->query("SELECT COUNT(*) FROM provider_details WHERE is_approved = 0")->fetchColumn();

$totalBookings = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$activeBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status IN ('pending','confirmed','processing','ready')")->fetchColumn();
$completedBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'delivered'")->fetchColumn();

$totalRevenue = $db->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE payment_status = 'paid'")->fetchColumn();
$monthRevenue = $db->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE payment_status = 'paid' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetchColumn();

$openTickets = $db->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn();

// Monthly revenue chart (12 months)
$revenueChart = $db->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as total, COUNT(*) as cnt
    FROM bookings WHERE payment_status = 'paid'
    GROUP BY month ORDER BY month DESC LIMIT 12
")->fetchAll();
$revenueChart = array_reverse($revenueChart);

// Bookings by status
$statusChart = $db->query("SELECT status, COUNT(*) as cnt FROM bookings GROUP BY status")->fetchAll();

// Recent bookings
$recentBookings = $db->query("
    SELECT b.*, c.full_name as customer_name, p.full_name as provider_name
    FROM bookings b 
    JOIN users c ON b.customer_id = c.id 
    JOIN users p ON b.provider_id = p.id
    ORDER BY b.created_at DESC LIMIT 8
")->fetchAll();

// Recent registrations
$recentUsers = $db->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC LIMIT 5")->fetchAll();

$page_title = 'Admin Dashboard';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <?= render_flash() ?>
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="dash-card-orange text-white rounded-2xl p-5 shadow-lg">
            <i class="fas fa-users text-xl opacity-80 mb-2"></i>
            <div class="text-2xl font-bold"><?= $totalUsers ?></div>
            <div class="text-xs text-white/80">Total Users</div>
            <div class="text-xs text-white/60 mt-1"><?= $totalCustomers ?> customers &middot; <?= $totalProviders ?> providers</div>
        </div>
        <div class="dash-card-blue text-white rounded-2xl p-5 shadow-lg relative">
            <?php if ($pendingProviders > 0): ?>
            <span class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center text-xs font-bold badge-pulse"><?= $pendingProviders ?></span>
            <?php endif; ?>
            <i class="fas fa-store text-xl opacity-80 mb-2"></i>
            <div class="text-2xl font-bold"><?= $totalProviders ?></div>
            <div class="text-xs text-white/80">Providers</div>
            <div class="text-xs text-white/60 mt-1"><?= $pendingProviders ?> pending approval</div>
        </div>
        <div class="dash-card-teal text-white rounded-2xl p-5 shadow-lg">
            <i class="fas fa-calendar-check text-xl opacity-80 mb-2"></i>
            <div class="text-2xl font-bold"><?= $totalBookings ?></div>
            <div class="text-xs text-white/80">Total Bookings</div>
            <div class="text-xs text-white/60 mt-1"><?= $activeBookings ?> active &middot; <?= $completedBookings ?> completed</div>
        </div>
        <div class="dash-card-purple text-white rounded-2xl p-5 shadow-lg">
            <i class="fas fa-money-bill-wave text-xl opacity-80 mb-2"></i>
            <div class="text-xl font-bold"><?= format_currency($totalRevenue) ?></div>
            <div class="text-xs text-white/80">Total Revenue</div>
            <div class="text-xs text-white/60 mt-1"><?= format_currency($monthRevenue) ?> this month</div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="flex flex-wrap gap-3 mb-8">
        <?php if ($pendingProviders > 0): ?>
        <a href="<?= APP_URL ?>/admin/providers.php?status=pending" class="px-4 py-2 bg-red-500 text-white text-sm rounded-lg hover:bg-red-600 transition-colors shadow-sm">
            <i class="fas fa-user-check mr-1"></i>Approve Providers (<?= $pendingProviders ?>)
        </a>
        <?php endif; ?>
        <?php if ($openTickets > 0): ?>
        <a href="<?= APP_URL ?>/admin/support.php" class="px-4 py-2 bg-blue-500 text-white text-sm rounded-lg hover:bg-blue-600 transition-colors shadow-sm">
            <i class="fas fa-headset mr-1"></i>Support Tickets (<?= $openTickets ?>)
        </a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/admin/reports.php" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fas fa-chart-bar mr-1"></i>Reports
        </a>
        <a href="<?= APP_URL ?>/admin/settings.php" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fas fa-cog mr-1"></i>Settings
        </a>
    </div>
    
    <div class="grid lg:grid-cols-3 gap-6 mb-8">
        <!-- Revenue Chart -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-md p-6 border border-gray-100">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-chart-line text-teal-600 mr-2"></i>Monthly Revenue</h2>
            <canvas id="revenueChart" height="100"></canvas>
        </div>
        
        <!-- Booking Status Pie -->
        <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-chart-pie text-orange-500 mr-2"></i>Booking Status</h2>
            <canvas id="statusChart" height="200"></canvas>
        </div>
    </div>
    
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Recent Bookings -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800"><i class="fas fa-clock text-blue-500 mr-2"></i>Recent Bookings</h2>
                <a href="<?= APP_URL ?>/admin/bookings.php" class="text-sm text-orange-500 hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-2 text-left">Booking</th>
                            <th class="px-4 py-2 text-left">Customer</th>
                            <th class="px-4 py-2 text-left">Provider</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($recentBookings as $b): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2.5 font-medium text-gray-800">#<?= e($b['booking_number']) ?></td>
                            <td class="px-4 py-2.5 text-gray-600"><?= e($b['customer_name']) ?></td>
                            <td class="px-4 py-2.5 text-gray-600"><?= e($b['provider_name']) ?></td>
                            <td class="px-4 py-2.5"><?= booking_status_badge($b['status']) ?></td>
                            <td class="px-4 py-2.5 text-right font-medium"><?= format_currency($b['total_amount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Users -->
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800"><i class="fas fa-user-plus text-green-500 mr-2"></i>New Users</h2>
                <a href="<?= APP_URL ?>/admin/users.php" class="text-sm text-orange-500 hover:underline">View All</a>
            </div>
            <div class="divide-y divide-gray-50">
                <?php foreach ($recentUsers as $u): ?>
                <div class="px-4 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-xs font-bold text-orange-600 flex-shrink-0">
                        <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-sm text-gray-800 truncate"><?= e($u['full_name']) ?></div>
                        <div class="text-xs text-gray-400"><?= $u['role'] ?> &middot; <?= time_ago($u['created_at']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: [<?= implode(',', array_map(fn($m) => "'" . date('M Y', strtotime($m['month'] . '-01')) . "'", $revenueChart)) ?>],
            datasets: [{
                label: 'Revenue (KES)',
                data: [<?= implode(',', array_map(fn($m) => $m['total'], $revenueChart)) ?>],
                borderColor: '#0D9488',
                backgroundColor: 'rgba(13,148,136,0.1)',
                fill: true, tension: 0.4, borderWidth: 3
            }, {
                label: 'Bookings',
                data: [<?= implode(',', array_map(fn($m) => $m['cnt'], $revenueChart)) ?>],
                borderColor: '#F97316',
                backgroundColor: 'rgba(249,115,22,0.1)',
                fill: false, tension: 0.4, borderWidth: 2,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => 'KES ' + v.toLocaleString() } },
                y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } }
            }
        }
    });

    // Status Pie Chart
    <?php
    $statusColors = ['pending' => '#EAB308', 'confirmed' => '#3B82F6', 'processing' => '#8B5CF6', 'ready' => '#0D9488', 'delivered' => '#22C55E', 'cancelled' => '#EF4444'];
    ?>
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: [<?= implode(',', array_map(fn($s) => "'" . ucfirst($s['status']) . "'", $statusChart)) ?>],
            datasets: [{
                data: [<?= implode(',', array_map(fn($s) => $s['cnt'], $statusChart)) ?>],
                backgroundColor: [<?= implode(',', array_map(fn($s) => "'" . ($statusColors[$s['status']] ?? '#9CA3AF') . "'", $statusChart)) ?>],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } } }
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
