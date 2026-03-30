<?php
/**
 * UsafiKonect - Admin Users Management
 * View, search, activate/deactivate all users
 */

require_once __DIR__ . '/../config/functions.php';
require_login();
require_role('admin');

$db = getDB();

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $targetId = (int)($_POST['user_id'] ?? 0);
    $action = sanitize_input($_POST['action'] ?? '');
    
    // Prevent self-modification
    if ($targetId === get_user_id()) {
        set_flash('error', 'Cannot modify your own account from here.');
        header('Location: ' . APP_URL . '/admin/users.php');
        exit;
    }
    
    if ($action === 'activate') {
        $db->prepare("UPDATE users SET is_active = 1 WHERE id = ?")->execute([$targetId]);
        set_flash('success', 'User activated.');
    } elseif ($action === 'deactivate') {
        $db->prepare("UPDATE users SET is_active = 0 WHERE id = ?")->execute([$targetId]);
        set_flash('success', 'User deactivated.');
    }
    header('Location: ' . APP_URL . '/admin/users.php?' . http_build_query(array_filter(['role' => $_POST['filter_role'] ?? '', 'search' => $_POST['filter_search'] ?? '', 'page' => $_POST['filter_page'] ?? 1])));
    exit;
}

$role = isset($_GET['role']) && in_array($_GET['role'], ['customer','provider']) ? $_GET['role'] : '';
$search = sanitize_input($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$where = "WHERE role != 'admin'";
$params = [];
if ($role) {
    $where .= " AND role = ?";
    $params[] = $role;
}
if ($search) {
    $where .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR estate LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pagination = paginate($page, $total, $per_page);

$stmt = $db->prepare("
    SELECT u.*, pd.business_name, pd.is_approved,
           (SELECT COUNT(*) FROM bookings WHERE customer_id = u.id OR provider_id = u.id) as booking_count
    FROM users u LEFT JOIN provider_details pd ON u.id = pd.user_id
    $where ORDER BY u.created_at DESC
    LIMIT {$pagination['offset']}, {$pagination['per_page']}
");
$stmt->execute($params);
$users = $stmt->fetchAll();

$page_title = 'Manage Users';
$body_class = 'bg-gray-100';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="lg:ml-64 p-4 lg:p-8 dashboard-content">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-3">
        <h1 class="text-2xl font-bold text-gray-800">Manage Users</h1>
        <div class="text-sm text-gray-500"><?= $total ?> users found</div>
    </div>
    
    <?= render_flash() ?>
    
    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-md p-4 mb-6 border border-gray-100">
        <form method="GET" class="flex flex-col sm:flex-row gap-3">
            <select name="role" class="px-4 py-2 rounded-lg border border-gray-200 text-sm focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                <option value="">All Roles</option>
                <option value="customer" <?= $role === 'customer' ? 'selected' : '' ?>>Customers</option>
                <option value="provider" <?= $role === 'provider' ? 'selected' : '' ?>>Providers</option>
            </select>
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, email, phone, estate..."
                       class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-200 text-sm focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
            </div>
            <button type="submit" class="px-6 py-2 bg-orange-500 text-white text-sm rounded-lg hover:bg-orange-600 transition-colors">Search</button>
        </form>
    </div>
    
    <!-- Users Table -->
    <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-left">Contact</th>
                        <th class="px-4 py-3 text-left">Role</th>
                        <th class="px-4 py-3 text-left">Estate</th>
                        <th class="px-4 py-3 text-center">Bookings</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-left">Joined</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-xs font-bold text-orange-600 flex-shrink-0 overflow-hidden">
                                    <?php if ($u['profile_image']): ?>
                                    <img src="<?= APP_URL . '/' . e($u['profile_image']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                    <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-800"><?= e($u['full_name']) ?></div>
                                    <?php if ($u['business_name']): ?>
                                    <div class="text-xs text-gray-400"><?= e($u['business_name']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-xs text-gray-600"><?= e($u['email']) ?></div>
                            <div class="text-xs text-gray-400"><?= e($u['phone']) ?></div>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($u['role'] === 'provider'): ?>
                            <span class="inline-flex px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full font-medium">Provider</span>
                            <?php else: ?>
                            <span class="inline-flex px-2 py-0.5 bg-teal-100 text-teal-700 text-xs rounded-full font-medium">Customer</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs"><?= e($u['estate']) ?></td>
                        <td class="px-4 py-3 text-center text-gray-600"><?= $u['booking_count'] ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($u['is_active']): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full"><i class="fas fa-check-circle"></i>Active</span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full"><i class="fas fa-ban"></i>Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        <td class="px-4 py-3 text-center">
                            <form method="POST" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="filter_role" value="<?= e($role) ?>">
                                <input type="hidden" name="filter_search" value="<?= e($search) ?>">
                                <input type="hidden" name="filter_page" value="<?= $page ?>">
                                <?php if ($u['is_active']): ?>
                                <button type="submit" name="action" value="deactivate" onclick="return confirm('Deactivate this user?')"
                                        class="px-3 py-1 bg-red-50 text-red-600 text-xs rounded-lg hover:bg-red-100 border border-red-200">
                                    <i class="fas fa-ban mr-1"></i>Deactivate
                                </button>
                                <?php else: ?>
                                <button type="submit" name="action" value="activate"
                                        class="px-3 py-1 bg-green-50 text-green-600 text-xs rounded-lg hover:bg-green-100 border border-green-200">
                                    <i class="fas fa-check mr-1"></i>Activate
                                </button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100">
            <?= render_pagination($page, $pagination['total_pages'], '?' . http_build_query(array_filter(['role' => $role, 'search' => $search])) . '&') ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
