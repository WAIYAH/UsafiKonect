<?php
/**
 * UsafiKonect - Provider Analytics
 * Performance charts and business insights
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('provider');

$db = getDB();
$userId = get_user_id();

// Summary Stats
$monthBookings = $db->prepare("SELECT COUNT(*) FROM bookings WHERE provider_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
$monthBookings->execute([$userId]);
$monthBookings = (int)$monthBookings->fetchColumn();

$monthRevenue = $db->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE provider_id = ? AND payment_status = 'paid' AND status = 'delivered' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
$monthRevenue->execute([$userId]);
$monthRevenue = (float)$monthRevenue->fetchColumn();

$avgRating = $db->prepare("SELECT ROUND(AVG(rating),1) FROM ratings WHERE provider_id = ?");
$avgRating->execute([$userId]);
$avgRating = $avgRating->fetchColumn() ?: 0;

// Repeat customer rate
$totalCustomers = $db->prepare("SELECT COUNT(DISTINCT customer_id) FROM bookings WHERE provider_id = ?");
$totalCustomers->execute([$userId]);
$totalCustomers = (int)$totalCustomers->fetchColumn();

$repeatCustomers = $db->prepare("SELECT COUNT(*) FROM (SELECT customer_id FROM bookings WHERE provider_id = ? GROUP BY customer_id HAVING COUNT(*) > 1) AS repeats");
$repeatCustomers->execute([$userId]);
$repeatCustomers = (int)$repeatCustomers->fetchColumn();

$repeatRate = $totalCustomers > 0 ? round(($repeatCustomers / $totalCustomers) * 100) : 0;

// Monthly booking trend (last 6 months)
$trendStmt = $db->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
           COUNT(*) as bookings, 
           COALESCE(SUM(CASE WHEN payment_status = 'paid' AND status = 'delivered' THEN total_amount ELSE 0 END),0) as revenue
    FROM bookings WHERE provider_id = ?
    GROUP BY month ORDER BY month DESC LIMIT 6
");
$trendStmt->execute([$userId]);
$trendData = array_reverse($trendStmt->fetchAll());

// Service type breakdown
$serviceStmt = $db->prepare("SELECT service_type, COUNT(*) as cnt FROM bookings WHERE provider_id = ? GROUP BY service_type ORDER BY cnt DESC");
$serviceStmt->execute([$userId]);
$serviceData = $serviceStmt->fetchAll();

// Rating distribution
$ratingStmt = $db->prepare("SELECT rating, COUNT(*) as cnt FROM ratings WHERE provider_id = ? GROUP BY rating ORDER BY rating");
$ratingStmt->execute([$userId]);
$ratingData = $ratingStmt->fetchAll();

// Top customer estates
$estateStmt = $db->prepare("
    SELECT u.estate, COUNT(*) as cnt 
    FROM bookings b JOIN users u ON b.customer_id = u.id 
    WHERE b.provider_id = ? AND u.estate IS NOT NULL
    GROUP BY u.estate ORDER BY cnt DESC LIMIT 5
");
$estateStmt->execute([$userId]);
$topEstates = $estateStmt->fetchAll();

$page_title = 'Analytics';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$serviceLabels = [];
$serviceCounts = [];
foreach ($serviceData as $s) {
    $serviceLabels[] = ucwords(str_replace('_', ' & ', $s['service_type']));
    $serviceCounts[] = (int)$s['cnt'];
}

$ratingLabels = [];
$ratingCounts = [];
for ($i = 1; $i <= 5; $i++) {
    $ratingLabels[] = "$i Star";
    $found = false;
    foreach ($ratingData as $r) {
        if ((int)$r['rating'] === $i) { $ratingCounts[] = (int)$r['cnt']; $found = true; break; }
    }
    if (!$found) $ratingCounts[] = 0;
}

$trendLabels = [];
$trendBookings = [];
$trendRevenue = [];
foreach ($trendData as $t) {
    $trendLabels[] = date('M Y', strtotime($t['month'] . '-01'));
    $trendBookings[] = (int)$t['bookings'];
    $trendRevenue[] = (float)$t['revenue'];
}
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-chart-line text-orange-500 mr-2"></i>Analytics</h1>
        <p class="text-gray-500 text-sm mt-1">Performance insights for your business</p>
    </div>

    <?= render_flash() ?>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-5">
            <div class="text-sm text-gray-500 mb-1"><i class="fas fa-calendar-check text-orange-400 mr-1"></i>This Month</div>
            <div class="text-2xl font-bold text-gray-800"><?= $monthBookings ?></div>
            <div class="text-xs text-gray-400">bookings</div>
        </div>
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-5">
            <div class="text-sm text-gray-500 mb-1"><i class="fas fa-money-bill-wave text-green-400 mr-1"></i>Revenue</div>
            <div class="text-2xl font-bold text-gray-800"><?= format_currency($monthRevenue) ?></div>
            <div class="text-xs text-gray-400">this month</div>
        </div>
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-5">
            <div class="text-sm text-gray-500 mb-1"><i class="fas fa-star text-yellow-400 mr-1"></i>Rating</div>
            <div class="text-2xl font-bold text-gray-800"><?= $avgRating ?>/5</div>
            <div class="text-xs text-gray-400">average</div>
        </div>
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-5">
            <div class="text-sm text-gray-500 mb-1"><i class="fas fa-redo text-blue-400 mr-1"></i>Repeat</div>
            <div class="text-2xl font-bold text-gray-800"><?= $repeatRate ?>%</div>
            <div class="text-xs text-gray-400">return customers</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid lg:grid-cols-2 gap-6 mb-8">
        <!-- Booking Trend -->
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-chart-bar text-deepblue-800 mr-2"></i>Monthly Booking Trend</h2>
            <?php if (!empty($trendData)): ?>
            <canvas id="trendChart" height="200"></canvas>
            <?php else: ?>
            <div class="text-center py-10 text-gray-400">
                <i class="fas fa-chart-bar text-4xl mb-3"></i>
                <p>Not enough data yet. Complete some bookings!</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Service Breakdown -->
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-pie-chart text-orange-500 mr-2"></i>Service Type Breakdown</h2>
            <?php if (!empty($serviceData)): ?>
            <canvas id="serviceChart" height="200"></canvas>
            <?php else: ?>
            <div class="text-center py-10 text-gray-400">
                <i class="fas fa-chart-pie text-4xl mb-3"></i>
                <p>No booking data available yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Rating Distribution -->
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-star-half-alt text-yellow-500 mr-2"></i>Rating Distribution</h2>
            <?php if (!empty($ratingData)): ?>
            <canvas id="ratingChart" height="200"></canvas>
            <?php else: ?>
            <div class="text-center py-10 text-gray-400">
                <i class="fas fa-star text-4xl mb-3"></i>
                <p>No reviews yet.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Top Estates -->
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-map-marker-alt text-red-500 mr-2"></i>Top Customer Locations</h2>
            <?php if (!empty($topEstates)): ?>
            <div class="space-y-3">
                <?php 
                $maxEstate = max(array_column($topEstates, 'cnt'));
                foreach ($topEstates as $idx => $est): 
                    $pct = $maxEstate > 0 ? round(($est['cnt'] / $maxEstate) * 100) : 0;
                ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-gray-700"><?= e($est['estate']) ?></span>
                        <span class="text-gray-500"><?= $est['cnt'] ?> order<?= $est['cnt'] > 1 ? 's' : '' ?></span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-orange-500 rounded-full" style="width: <?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-10 text-gray-400">
                <i class="fas fa-map text-4xl mb-3"></i>
                <p>No location data yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
<?php if (!empty($trendData)): ?>
new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($trendLabels) ?>,
        datasets: [{
            label: 'Bookings',
            data: <?= json_encode($trendBookings) ?>,
            backgroundColor: 'rgba(249, 115, 22, 0.7)',
            borderRadius: 6,
            yAxisID: 'y'
        }, {
            label: 'Revenue (KES)',
            data: <?= json_encode($trendRevenue) ?>,
            type: 'line',
            borderColor: '#1E3A8A',
            backgroundColor: 'rgba(30, 58, 138, 0.1)',
            fill: true,
            tension: 0.4,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: {
            y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Bookings' } },
            y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Revenue (KES)' } }
        }
    }
});
<?php endif; ?>

<?php if (!empty($serviceData)): ?>
new Chart(document.getElementById('serviceChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($serviceLabels) ?>,
        datasets: [{
            data: <?= json_encode($serviceCounts) ?>,
            backgroundColor: ['#F97316', '#1E3A8A', '#10B981', '#8B5CF6'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
<?php endif; ?>

<?php if (!empty($ratingData)): ?>
new Chart(document.getElementById('ratingChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($ratingLabels) ?>,
        datasets: [{
            label: 'Reviews',
            data: <?= json_encode($ratingCounts) ?>,
            backgroundColor: ['#EF4444', '#F97316', '#EAB308', '#22C55E', '#10B981'],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
