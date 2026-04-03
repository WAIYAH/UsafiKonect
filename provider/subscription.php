<?php
/**
 * UsafiKonect - Provider Subscription
 * View current plan, upgrade/renew subscription
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('provider');

$db = getDB();
$userId = get_user_id();

// Current active subscription
$stmt = $db->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1");
$stmt->execute([$userId]);
$currentSub = $stmt->fetch();

// Plan pricing from settings
$plans = [
    'weekly'  => ['name' => 'Weekly',  'price' => (int)get_setting('sub_price_weekly', '500'),  'days' => 7,   'discount' => '10%'],
    'monthly' => ['name' => 'Monthly', 'price' => (int)get_setting('sub_price_monthly', '1800'), 'days' => 30,  'discount' => '15%'],
    'yearly'  => ['name' => 'Yearly',  'price' => (int)get_setting('sub_price_yearly', '18000'), 'days' => 365, 'discount' => '20%'],
];

// Subscription history
$page_num = max(1, (int)($_GET['page'] ?? 1));
$countStmt = $db->prepare("SELECT COUNT(*) FROM subscriptions WHERE user_id = ?");
$countStmt->execute([$userId]);
$total = $countStmt->fetchColumn();
$pagination = paginate($total, 10, $page_num);

$histStmt = $db->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$histStmt->execute([$userId]);
$history = $histStmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        $errors[] = 'Invalid security token.';
    } else {
        $planType = $_POST['plan_type'] ?? '';
        if (!isset($plans[$planType])) {
            $errors[] = 'Invalid subscription plan.';
        }

        if (empty($errors)) {
            $plan = $plans[$planType];
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime("+{$plan['days']} days"));

            try {
                $db->beginTransaction();

                // Expire current subscription if any
                if ($currentSub) {
                    $db->prepare("UPDATE subscriptions SET status = 'expired' WHERE id = ?")->execute([$currentSub['id']]);
                }

                // Create new subscription
                $ins = $db->prepare("INSERT INTO subscriptions (user_id, plan_type, amount, status, start_date, end_date, payment_status) VALUES (?, ?, ?, 'active', ?, ?, 'paid')");
                $ins->execute([$userId, $planType, $plan['price'], $startDate, $endDate]);

                $db->commit();

                create_notification($userId, 'subscription', "You've subscribed to the {$plan['name']} plan! Valid until " . date('M j, Y', strtotime($endDate)) . ".");
                set_flash('success', "Subscribed to {$plan['name']} plan! Your customers now get a {$plan['discount']} discount.");
                redirect(APP_URL . '/provider/subscription.php');
            } catch (Exception $e) {
                $db->rollBack();
                error_log("Subscription error: " . $e->getMessage());
                $errors[] = 'An error occurred. Please try again.';
            }
        }
    }
}

$page_title = 'Subscription';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-crown text-orange-500 mr-2"></i>Subscription</h1>
        <p class="text-gray-500 text-sm mt-1">Manage your platform subscription plan</p>
    </div>

    <?= render_flash() ?>

    <?php if (!empty($errors)): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
        <ul class="text-sm text-red-700 list-disc list-inside">
            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Current Plan -->
    <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 mb-6">
        <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-id-badge text-deepblue-800 mr-2"></i>Current Plan</h2>
        <?php if ($currentSub): 
            $daysLeft = max(0, (int)((strtotime($currentSub['end_date']) - time()) / 86400));
            $planInfo = $plans[$currentSub['plan_type']] ?? null;
        ?>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-orange-100 text-orange-500 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-crown text-2xl"></i>
                </div>
                <div>
                    <div class="font-bold text-lg text-gray-800"><?= ucfirst($currentSub['plan_type']) ?> Plan</div>
                    <div class="text-sm text-gray-500">
                        <?= date('M j, Y', strtotime($currentSub['start_date'])) ?> — <?= date('M j, Y', strtotime($currentSub['end_date'])) ?>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <div class="text-2xl font-bold text-orange-500"><?= $daysLeft ?></div>
                    <div class="text-xs text-gray-500">days left</div>
                </div>
                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-bold">Active</span>
            </div>
        </div>
        <?php if ($daysLeft <= 7): ?>
        <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-700">
            <i class="fas fa-exclamation-triangle mr-1"></i>Your subscription expires soon. Renew below to keep your listing active.
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="text-center py-6">
            <div class="text-4xl mb-3">👑</div>
            <p class="text-gray-600 font-medium">No active subscription</p>
            <p class="text-sm text-gray-400 mt-1">Subscribe to a plan to get listed and offer discounts to customers.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Plan Cards -->
    <h2 class="font-bold text-gray-800 mb-4 text-lg">Choose a Plan</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <?php foreach ($plans as $key => $plan): 
            $isCurrent = $currentSub && $currentSub['plan_type'] === $key;
            $isPopular = $key === 'monthly';
        ?>
        <div class="relative bg-white rounded-2xl shadow-md border <?= $isPopular ? 'border-orange-300 ring-2 ring-orange-200' : 'border-gray-100' ?> p-6">
            <?php if ($isPopular): ?>
            <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 bg-orange-500 text-white text-xs font-bold rounded-full">Most Popular</div>
            <?php endif; ?>
            <div class="text-center mb-4">
                <h3 class="font-bold text-lg text-gray-800"><?= $plan['name'] ?></h3>
                <div class="mt-2">
                    <span class="text-3xl font-extrabold text-gray-800"><?= format_currency($plan['price']) ?></span>
                    <span class="text-gray-500 text-sm">/<?= $key === 'yearly' ? 'year' : ($key === 'monthly' ? 'month' : 'week') ?></span>
                </div>
            </div>
            <ul class="text-sm text-gray-600 space-y-2 mb-6">
                <li><i class="fas fa-check text-green-500 mr-2"></i>Listed on platform</li>
                <li><i class="fas fa-check text-green-500 mr-2"></i><?= $plan['discount'] ?> customer discount</li>
                <li><i class="fas fa-check text-green-500 mr-2"></i><?= $plan['days'] ?> days duration</li>
                <li><i class="fas fa-check text-green-500 mr-2"></i>Priority support</li>
            </ul>
            <?php if ($isCurrent): ?>
            <button disabled class="w-full py-2.5 bg-gray-100 text-gray-500 font-semibold rounded-lg text-sm cursor-not-allowed">
                <i class="fas fa-check mr-1"></i>Current Plan
            </button>
            <?php else: ?>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="plan_type" value="<?= $key ?>">
                <button type="submit" class="w-full py-2.5 <?= $isPopular ? 'bg-orange-500 hover:bg-orange-600' : 'bg-deepblue-800 hover:bg-deepblue-900' ?> text-white font-semibold rounded-lg text-sm transition-all">
                    <i class="fas fa-crown mr-1"></i>Subscribe
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Subscription History -->
    <?php if (!empty($history)): ?>
    <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-800"><i class="fas fa-history text-deepblue-800 mr-2"></i>Subscription History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left">Plan</th>
                        <th class="px-6 py-3 text-left">Amount</th>
                        <th class="px-6 py-3 text-left">Period</th>
                        <th class="px-6 py-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($history as $sub): ?>
                    <tr>
                        <td class="px-6 py-3 font-medium"><?= ucfirst($sub['plan_type']) ?></td>
                        <td class="px-6 py-3"><?= format_currency($sub['amount']) ?></td>
                        <td class="px-6 py-3 text-gray-500"><?= date('M j', strtotime($sub['start_date'])) ?> — <?= date('M j, Y', strtotime($sub['end_date'])) ?></td>
                        <td class="px-6 py-3">
                            <?php if ($sub['status'] === 'active'): ?>
                            <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-bold">Active</span>
                            <?php elseif ($sub['status'] === 'expired'): ?>
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs font-bold">Expired</span>
                            <?php else: ?>
                            <span class="px-2 py-0.5 bg-red-100 text-red-600 rounded-full text-xs font-bold">Cancelled</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4"><?= render_pagination($pagination) ?></div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
