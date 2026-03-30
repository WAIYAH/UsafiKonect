<?php
/**
 * UsafiKonect - Provider Earnings
 * Earnings overview, wallet balance, transaction history
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('provider');

$db = getDB();
$userId = get_user_id();

$walletBalance = get_wallet_balance($userId);

// Total earnings (all time)
$totalEarnings = $db->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE provider_id = ? AND payment_status = 'paid' AND status = 'delivered'");
$totalEarnings->execute([$userId]); $totalEarnings = $totalEarnings->fetchColumn();

// This month earnings
$monthEarnings = $db->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE provider_id = ? AND payment_status = 'paid' AND status = 'delivered' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
$monthEarnings->execute([$userId]); $monthEarnings = $monthEarnings->fetchColumn();

// This week
$weekEarnings = $db->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE provider_id = ? AND payment_status = 'paid' AND status = 'delivered' AND YEARWEEK(created_at) = YEARWEEK(NOW())");
$weekEarnings->execute([$userId]); $weekEarnings = $weekEarnings->fetchColumn();

// Pending payments (completed but not yet paid bookings)
$pendingPayments = $db->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE provider_id = ? AND status = 'delivered' AND payment_status = 'pending'");
$pendingPayments->execute([$userId]); $pendingPayments = $pendingPayments->fetchColumn();

// Wallet transactions
$page_num = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$countStmt = $db->prepare("SELECT COUNT(*) FROM wallet_transactions WHERE user_id = ?");
$countStmt->execute([$userId]);
$total = $countStmt->fetchColumn();
$pagination = paginate($page_num, $total, $per_page);

$txStmt = $db->prepare("SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT {$pagination['offset']}, {$pagination['per_page']}");
$txStmt->execute([$userId]);
$transactions = $txStmt->fetchAll();

// Monthly chart data
$chartData = $db->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as total
    FROM bookings WHERE provider_id = ? AND payment_status = 'paid' AND status = 'delivered'
    GROUP BY month ORDER BY month DESC LIMIT 12
");
$chartData->execute([$userId]);
$chartData = array_reverse($chartData->fetchAll());

$page_title = 'Earnings';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Earnings & Wallet</h1>
    
    <?= render_flash() ?>
    
    <!-- Earnings Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-2xl shadow-md p-5 border border-gray-100">
            <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Wallet Balance</div>
            <div class="text-xl font-bold text-green-600"><?= format_currency($walletBalance) ?></div>
        </div>
        <div class="bg-white rounded-2xl shadow-md p-5 border border-gray-100">
            <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total Earned</div>
            <div class="text-xl font-bold text-gray-800"><?= format_currency($totalEarnings) ?></div>
        </div>
        <div class="bg-white rounded-2xl shadow-md p-5 border border-gray-100">
            <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">This Month</div>
            <div class="text-xl font-bold text-orange-600"><?= format_currency($monthEarnings) ?></div>
        </div>
        <div class="bg-white rounded-2xl shadow-md p-5 border border-gray-100">
            <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">This Week</div>
            <div class="text-xl font-bold text-blue-600"><?= format_currency($weekEarnings) ?></div>
        </div>
        <div class="bg-white rounded-2xl shadow-md p-5 border border-gray-100">
            <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Pending</div>
            <div class="text-xl font-bold text-yellow-600"><?= format_currency($pendingPayments) ?></div>
        </div>
    </div>
    
    <!-- Earnings Chart -->
    <?php if (!empty($chartData)): ?>
    <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100 mb-8">
        <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-chart-bar text-teal-600 mr-2"></i>Monthly Earnings</h2>
        <canvas id="earningsChart" height="80"></canvas>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new Chart(document.getElementById('earningsChart'), {
            type: 'line',
            data: {
                labels: [<?= implode(',', array_map(fn($m) => "'" . date('M Y', strtotime($m['month'] . '-01')) . "'", $chartData)) ?>],
                datasets: [{
                    label: 'Earnings (KES)',
                    data: [<?= implode(',', array_map(fn($m) => $m['total'], $chartData)) ?>],
                    borderColor: '#F97316',
                    backgroundColor: 'rgba(249,115,22,0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 5,
                    pointBackgroundColor: '#F97316'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => 'KES ' + v.toLocaleString() } } }
            }
        });
    });
    </script>
    <?php endif; ?>
    
    <!-- Transaction History -->
    <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-800"><i class="fas fa-history text-blue-500 mr-2"></i>Transaction History</h2>
        </div>
        
        <?php if (empty($transactions)): ?>
        <div class="p-8 text-center text-gray-400">
            <i class="fas fa-receipt text-4xl mb-3"></i>
            <p>No transactions yet.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-5 py-3 text-left">Date</th>
                        <th class="px-5 py-3 text-left">Type</th>
                        <th class="px-5 py-3 text-left">Description</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($transactions as $tx): 
                        $isCredit = in_array($tx['type'], ['payment','top_up','refund']);
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-gray-500 whitespace-nowrap"><?= date('M j, Y g:i A', strtotime($tx['created_at'])) ?></td>
                        <td class="px-5 py-3">
                            <?php
                            $badges = ['payment' => 'bg-blue-100 text-blue-700', 'top_up' => 'bg-green-100 text-green-700', 'refund' => 'bg-yellow-100 text-yellow-700', 'withdrawal' => 'bg-red-100 text-red-700'];
                            $badge = $badges[$tx['type']] ?? 'bg-gray-100 text-gray-700';
                            ?>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium <?= $badge ?>"><?= ucfirst($tx['type']) ?></span>
                        </td>
                        <td class="px-5 py-3 text-gray-600"><?= e($tx['description']) ?></td>
                        <td class="px-5 py-3 text-right font-bold <?= $isCredit ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $isCredit ? '+' : '-' ?><?= format_currency($tx['amount']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100">
            <?= render_pagination($page_num, $pagination['total_pages'], '?') ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
