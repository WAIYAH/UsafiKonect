<?php
/**
 * UsafiKonect - Admin Bookings Management
 * View all platform bookings with filters
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('admin');

$db = getDB();

$status = isset($_GET['status']) && in_array($_GET['status'], ['pending','confirmed','processing','ready','delivered','cancelled']) ? $_GET['status'] : '';
$payment = isset($_GET['payment']) && in_array($_GET['payment'], ['pending','paid','refunded']) ? $_GET['payment'] : '';
$search = sanitize_input($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$where = "WHERE 1=1";
$params = [];
if ($status) { $where .= " AND b.status = ?"; $params[] = $status; }
if ($payment) { $where .= " AND b.payment_status = ?"; $params[] = $payment; }
if ($search) {
    $where .= " AND (b.booking_number LIKE ? OR c.full_name LIKE ? OR p.full_name LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM bookings b JOIN users c ON b.customer_id = c.id JOIN users p ON b.provider_id = p.id $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pagination = paginate($page, $total, $per_page);

$stmt = $db->prepare("
    SELECT b.*, c.full_name as customer_name, c.estate as customer_estate, p.full_name as provider_name,
           pd.business_name
    FROM bookings b
    JOIN users c ON b.customer_id = c.id
    JOIN users p ON b.provider_id = p.id
    LEFT JOIN provider_details pd ON p.id = pd.user_id
    $where ORDER BY b.created_at DESC
    LIMIT {$pagination['offset']}, {$pagination['per_page']}
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$page_title = 'All Bookings';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-3">
        <h1 class="text-2xl font-bold text-gray-800">All Bookings</h1>
        <div class="text-sm text-gray-500"><?= $total ?> bookings</div>
    </div>
    
    <?= render_flash() ?>
    
    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-md p-4 mb-6 border border-gray-100">
        <form method="GET" class="flex flex-col sm:flex-row gap-3">
            <select name="status" class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                <option value="">All Statuses</option>
                <?php foreach (['pending','confirmed','processing','ready','delivered','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="payment" class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                <option value="">All Payments</option>
                <option value="pending" <?= $payment === 'pending' ? 'selected' : '' ?>>Unpaid</option>
                <option value="paid" <?= $payment === 'paid' ? 'selected' : '' ?>>Paid</option>
                <option value="refunded" <?= $payment === 'refunded' ? 'selected' : '' ?>>Refunded</option>
            </select>
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Booking#, customer, provider..."
                       class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-200 text-sm focus:border-orange-400">
            </div>
            <button type="submit" class="px-6 py-2 bg-orange-500 text-white text-sm rounded-lg hover:bg-orange-600">Search</button>
        </form>
    </div>
    
    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Booking#</th>
                        <th class="px-4 py-3 text-left">Customer</th>
                        <th class="px-4 py-3 text-left">Provider</th>
                        <th class="px-4 py-3 text-left">Service</th>
                        <th class="px-4 py-3 text-center">Weight</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Payment</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-left">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($bookings)): ?>
                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">No bookings found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($bookings as $b): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">#<?= e($b['booking_number']) ?></td>
                        <td class="px-4 py-3">
                            <div class="text-gray-700"><?= e($b['customer_name']) ?></div>
                            <div class="text-xs text-gray-400"><?= e($b['customer_estate']) ?></div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-gray-700"><?= e($b['business_name'] ?? $b['provider_name']) ?></div>
                        </td>
                        <td class="px-4 py-3 text-gray-600"><?= ucwords(str_replace('_', ' ', $b['service_type'])) ?></td>
                        <td class="px-4 py-3 text-center text-gray-600"><?= $b['weight_kg'] ?>kg</td>
                        <td class="px-4 py-3"><?= booking_status_badge($b['status']) ?></td>
                        <td class="px-4 py-3"><?= payment_status_badge($b['payment_status']) ?></td>
                        <td class="px-4 py-3 text-right font-medium text-gray-800"><?= format_currency($b['total_amount']) ?></td>
                        <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap"><?= date('M j, Y', strtotime($b['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100">
            <?= render_pagination($page, $pagination['total_pages'], '?' . http_build_query(array_filter(['status' => $status, 'payment' => $payment, 'search' => $search])) . '&') ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
