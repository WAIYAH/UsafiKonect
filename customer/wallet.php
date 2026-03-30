<?php
/**
 * UsafiKonect - Customer: Wallet
 * View balance, transaction history, top up
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('customer');

$db = getDB();
$userId = get_user_id();

$walletBalance = get_wallet_balance($userId);

// Transactions with pagination
$countStmt = $db->prepare("SELECT COUNT(*) FROM wallet_transactions WHERE user_id = ?");
$countStmt->execute([$userId]);
$total = $countStmt->fetchColumn();
$pagination = paginate($total, 15);

$stmt = $db->prepare("SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}");
$stmt->execute([$userId]);
$transactions = $stmt->fetchAll();

// Loyalty points
$loyaltyStmt = $db->prepare("SELECT COALESCE(SUM(points), 0) FROM loyalty_points WHERE user_id = ?");
$loyaltyStmt->execute([$userId]);
$totalPoints = $loyaltyStmt->fetchColumn();

$page_title = 'My Wallet';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <h1 class="text-2xl font-bold text-gray-800 mb-6"><i class="fas fa-wallet text-orange-500 mr-2"></i>My Wallet</h1>
    
    <?= render_flash() ?>
    
    <!-- Balance Cards -->
    <div class="grid md:grid-cols-3 gap-6 mb-8">
        <div class="dash-card-teal text-white rounded-2xl p-6 shadow-lg">
            <div class="text-sm text-teal-100 mb-1">Wallet Balance</div>
            <div class="text-3xl font-extrabold"><?= format_currency($walletBalance) ?></div>
            <div class="mt-4">
                <a href="<?= APP_URL ?>/customer/pay.php?action=topup" class="inline-flex items-center px-4 py-2 bg-white/20 rounded-lg text-sm hover:bg-white/30 transition-colors">
                    <i class="fas fa-plus mr-1"></i> Top Up
                </a>
            </div>
        </div>
        
        <div class="dash-card-purple text-white rounded-2xl p-6 shadow-lg">
            <div class="text-sm text-purple-200 mb-1">Loyalty Points</div>
            <div class="text-3xl font-extrabold"><?= number_format($totalPoints) ?></div>
            <div class="text-sm text-purple-200 mt-2">
                <?php if ($totalPoints >= 100): ?>
                    <i class="fas fa-gift mr-1"></i>You can redeem a free booking!
                <?php else: ?>
                    <?= 100 - $totalPoints ?> more points for free booking
                <?php endif; ?>
            </div>
        </div>
        
        <div class="dash-card-orange text-white rounded-2xl p-6 shadow-lg">
            <div class="text-sm text-orange-100 mb-1">Total Transactions</div>
            <div class="text-3xl font-extrabold"><?= $total ?></div>
        </div>
    </div>
    
    <!-- Transaction History -->
    <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-800">Transaction History</h2>
        </div>
        
        <?php if (empty($transactions)): ?>
        <div class="p-8 text-center text-gray-400">
            <i class="fas fa-receipt text-4xl mb-3"></i>
            <p>No transactions yet.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-6 py-3 text-left">Description</th>
                        <th class="px-6 py-3 text-left">Type</th>
                        <th class="px-6 py-3 text-right">Amount</th>
                        <th class="px-6 py-3 text-right">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($transactions as $t): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-sm text-gray-700"><?= e($t['description']) ?></td>
                        <td class="px-6 py-3">
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full <?php
                                echo match($t['type']) {
                                    'top_up' => 'bg-green-100 text-green-700',
                                    'payment' => 'bg-blue-100 text-blue-700',
                                    'refund' => 'bg-yellow-100 text-yellow-700',
                                    'withdrawal' => 'bg-red-100 text-red-700',
                                    default => 'bg-gray-100 text-gray-700'
                                };
                            ?>"><?= ucwords(str_replace('_', ' ', $t['type'])) ?></span>
                        </td>
                        <td class="px-6 py-3 text-right font-semibold <?= in_array($t['type'], ['top_up', 'refund']) ? 'text-green-600' : 'text-red-600' ?>">
                            <?= in_array($t['type'], ['top_up', 'refund']) ? '+' : '-' ?><?= format_currency(abs($t['amount'])) ?>
                        </td>
                        <td class="px-6 py-3 text-right text-sm text-gray-500"><?= date('M j, g:i A', strtotime($t['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-4"><?= render_pagination($pagination) ?></div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
