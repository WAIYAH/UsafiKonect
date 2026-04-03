<?php
/**
 * UsafiKonect - Admin Reports
 * Revenue, bookings, provider reports with charts
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('admin');

$db = getDB();

// Date range
$period = sanitize_input($_GET['period'] ?? '30');
$validPeriods = ['7' => '7 Days', '30' => '30 Days', '90' => '90 Days', '365' => '1 Year', 'all' => 'All Time'];
if (!array_key_exists($period, $validPeriods)) $period = '30';

$dateWhere = "";
if ($period !== 'all') {
    $dateWhere = " AND created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)";
}

// CSV export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_csv']) && validate_csrf_token()) {
    $rows = $db->query("
        SELECT b.booking_number, cu.full_name as customer, pu.full_name as provider,
               b.service_type, b.weight_kg, b.total_amount, b.status, b.payment_status, b.created_at
        FROM bookings b
        JOIN users cu ON b.customer_id = cu.id
        JOIN users pu ON b.provider_id = pu.id
        WHERE 1=1 $dateWhere
        ORDER BY b.created_at DESC
    ")->fetchAll();
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="usafikonect-report-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Booking #', 'Customer', 'Provider', 'Service', 'Weight (kg)', 'Amount (KES)', 'Status', 'Payment', 'Date']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['booking_number'], $r['customer'], $r['provider'],
            ucwords(str_replace('_', ' & ', $r['service_type'])),
            $r['weight_kg'], $r['total_amount'], ucfirst($r['status']),
            ucfirst($r['payment_status']), date('Y-m-d H:i', strtotime($r['created_at']))
        ]);
    }
    fclose($out);
    exit;
}

// Revenue stats
$rev = $db->query("SELECT COALESCE(SUM(total_amount),0) as total, COUNT(*) as cnt FROM bookings WHERE payment_status = 'paid' $dateWhere")->fetch();
$totalRevenue = $rev['total'];
$paidBookings = $rev['cnt'];

$totalBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE 1=1 $dateWhere")->fetchColumn();
$cancelledBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled' $dateWhere")->fetchColumn();
$avgOrderValue = $totalBookings > 0 ? $totalRevenue / max(1, $paidBookings) : 0;

// Daily revenue for chart
$dailyRevenue = $db->query("
    SELECT DATE(created_at) as day, SUM(total_amount) as total, COUNT(*) as cnt
    FROM bookings WHERE payment_status = 'paid' $dateWhere
    GROUP BY day ORDER BY day
")->fetchAll();

// Top providers
$topProviders = $db->query("
    SELECT p.full_name, pd.business_name, pd.business_type, 
           COUNT(b.id) as booking_count, SUM(b.total_amount) as revenue,
           (SELECT ROUND(AVG(rating),1) FROM ratings WHERE provider_id = p.id) as avg_rating
    FROM bookings b 
    JOIN users p ON b.provider_id = p.id
    LEFT JOIN provider_details pd ON p.id = pd.user_id
    WHERE b.payment_status = 'paid' AND b.status = 'delivered' $dateWhere
    GROUP BY p.id ORDER BY revenue DESC LIMIT 10
")->fetchAll();

// Service breakdown
$serviceBreakdown = $db->query("
    SELECT service_type, COUNT(*) as cnt, SUM(total_amount) as revenue
    FROM bookings WHERE 1=1 $dateWhere
    GROUP BY service_type ORDER BY cnt DESC
")->fetchAll();

// Estate breakdown
$estateBreakdown = $db->query("
    SELECT u.estate, COUNT(b.id) as cnt 
    FROM bookings b JOIN users u ON b.customer_id = u.id
    WHERE 1=1 $dateWhere
    GROUP BY u.estate ORDER BY cnt DESC LIMIT 10
")->fetchAll();

// Bookings by status
$statusData = $db->query("SELECT status, COUNT(*) as cnt FROM bookings WHERE 1=1 $dateWhere GROUP BY status")->fetchAll();

$page_title = 'Reports';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-3">
        <h1 class="text-2xl font-bold text-gray-800">Reports & Analytics</h1>
        <div class="flex gap-2 flex-wrap items-center">
            <form method="POST" class="inline"><?= csrf_field() ?><button type="submit" name="export_csv" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-green-600 text-white hover:bg-green-700 transition-colors"><i class="fas fa-download mr-1"></i>Export CSV</button></form>
            <?php foreach ($validPeriods as $k => $label): ?>
            <a href="?period=<?= $k ?>" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors <?= $period === $k ? 'bg-orange-500 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl shadow-md p-5 border border-gray-100">
            <div class="text-xs text-gray-400 uppercase mb-1">Revenue</div>
            <div class="text-xl font-bold text-green-600"><?= format_currency($totalRevenue) ?></div>
        </div>
        <div class="bg-white rounded-2xl shadow-md p-5 border border-gray-100">
            <div class="text-xs text-gray-400 uppercase mb-1">Total Bookings</div>
            <div class="text-xl font-bold text-gray-800"><?= $totalBookings ?></div>
        </div>
        <div class="bg-white rounded-2xl shadow-md p-5 border border-gray-100">
            <div class="text-xs text-gray-400 uppercase mb-1">Avg Order Value</div>
            <div class="text-xl font-bold text-blue-600"><?= format_currency($avgOrderValue) ?></div>
        </div>
        <div class="bg-white rounded-2xl shadow-md p-5 border border-gray-100">
            <div class="text-xs text-gray-400 uppercase mb-1">Cancelled</div>
            <div class="text-xl font-bold text-red-600"><?= $cancelledBookings ?></div>
            <?php if ($totalBookings > 0): ?>
            <div class="text-xs text-gray-400"><?= round(($cancelledBookings / $totalBookings) * 100, 1) ?>% rate</div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Revenue Chart -->
    <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100 mb-8">
        <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-chart-area text-teal-600 mr-2"></i>Daily Revenue</h2>
        <?php if (!empty($dailyRevenue)): ?>
        <canvas id="dailyChart" height="80"></canvas>
        <?php else: ?>
        <p class="text-gray-400 text-center py-8">No revenue data for this period.</p>
        <?php endif; ?>
    </div>
    
    <div class="grid lg:grid-cols-2 gap-6 mb-8">
        <!-- Service Breakdown -->
        <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-tshirt text-purple-500 mr-2"></i>Services Breakdown</h2>
            <?php if (!empty($serviceBreakdown)): ?>
            <div class="space-y-3">
                <?php foreach ($serviceBreakdown as $s):
                    $pct = $totalBookings > 0 ? ($s['cnt'] / $totalBookings) * 100 : 0;
                ?>
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="font-medium text-gray-700"><?= ucwords(str_replace('_', ' & ', $s['service_type'])) ?></span>
                        <span class="text-gray-500"><?= $s['cnt'] ?> bookings &middot; <?= format_currency($s['revenue']) ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-purple-500 h-full rounded-full" style="width: <?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-400 text-sm text-center py-4">No data.</p>
            <?php endif; ?>
        </div>
        
        <!-- Top Estates -->
        <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-map-marker-alt text-red-500 mr-2"></i>Top Estates</h2>
            <?php if (!empty($estateBreakdown)): ?>
            <canvas id="estateChart" height="200"></canvas>
            <?php else: ?>
            <p class="text-gray-400 text-sm text-center py-4">No data.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Top Providers -->
    <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-800"><i class="fas fa-trophy text-yellow-500 mr-2"></i>Top Providers</h2>
        </div>
        <?php if (!empty($topProviders)): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Provider</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-center">Bookings</th>
                        <th class="px-4 py-3 text-center">Rating</th>
                        <th class="px-4 py-3 text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($topProviders as $i => $tp): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-bold text-gray-400"><?= $i + 1 ?></td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800"><?= e($tp['business_name'] ?? $tp['full_name']) ?></div>
                        </td>
                        <td class="px-4 py-3 text-gray-500"><?= ucfirst($tp['business_type'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-center"><?= $tp['booking_count'] ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($tp['avg_rating']): ?>
                            <span class="text-yellow-500"><i class="fas fa-star text-xs"></i> <?= $tp['avg_rating'] ?></span>
                            <?php else: ?>
                            <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-green-600"><?= format_currency($tp['revenue']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="p-8 text-center text-gray-400">No provider data for this period.</div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($dailyRevenue)): ?>
    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: {
            labels: [<?= implode(',', array_map(fn($d) => "'" . date('M j', strtotime($d['day'])) . "'", $dailyRevenue)) ?>],
            datasets: [{
                label: 'Revenue (KES)',
                data: [<?= implode(',', array_map(fn($d) => $d['total'], $dailyRevenue)) ?>],
                borderColor: '#0D9488',
                backgroundColor: 'rgba(13,148,136,0.1)',
                fill: true, tension: 0.3, borderWidth: 2, pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => 'KES ' + v.toLocaleString() } } }
        }
    });
    <?php endif; ?>
    
    <?php if (!empty($estateBreakdown)): ?>
    new Chart(document.getElementById('estateChart'), {
        type: 'bar',
        data: {
            labels: [<?= implode(',', array_map(fn($e) => "'" . $e['estate'] . "'", $estateBreakdown)) ?>],
            datasets: [{
                label: 'Bookings',
                data: [<?= implode(',', array_map(fn($e) => $e['cnt'], $estateBreakdown)) ?>],
                backgroundColor: 'rgba(249,115,22,0.7)',
                borderColor: '#F97316',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
