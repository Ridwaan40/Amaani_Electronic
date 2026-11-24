<?php
require_once __DIR__ . '/config/db.php';
$pdo = getDB();

function safeRedirect($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit;
    } else {
        echo "<script>window.location.href='" . addslashes($url) . "';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($url) . "'></noscript>";
        exit;
    }
}

// Handle Delete Customer
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);
    $delStmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $delStmt->execute([$delId]);
    safeRedirect("customers.php?deleted=1");
}

// Handle Update Customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_customer'])) {
    $editId = intval($_POST['customer_id'] ?? 0);
    $name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $balance = floatval($_POST['balance'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($editId > 0 && !empty($name)) {
        $upd = $pdo->prepare("UPDATE customers SET name = ?, phone = ?, address = ?, balance = ?, description = ? WHERE id = ?");
        $upd->execute([$name, $phone, $address, $balance, $description, $editId]);
        safeRedirect("customers.php?updated=1");
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$successMsg = '';
$errorMsg = '';

if (isset($_GET['created'])) {
    $successMsg = "Macmiilka cusub si guul leh ayaa loo diiwaangeliyay (Customer successfully added).";
}
if (isset($_GET['updated'])) {
    $successMsg = "Macmiilka xogtiisa si guul leh ayaa loo cusbooneysiiyay (Customer updated successfully).";
}
if (isset($_GET['deleted'])) {
    $successMsg = "Macmiilka waa la tirtiray (Customer deleted successfully).";
}

// Filtering
$filterName = trim($_GET['customer_name'] ?? '');
$filterPhone = trim($_GET['phone_number'] ?? '');
$filterAddress = trim($_GET['address'] ?? '');
$filterStatus = trim($_GET['balance_status'] ?? 'All Customers');
$search = trim($_GET['search'] ?? '');
$perPage = intval($_GET['per_page'] ?? 10);
if ($perPage < 5) $perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$whereClauses = ["1=1"];
$params = [];

if (!empty($filterName)) {
    $whereClauses[] = "name LIKE ?";
    $params[] = "%{$filterName}%";
}
if (!empty($filterPhone)) {
    $whereClauses[] = "phone LIKE ?";
    $params[] = "%{$filterPhone}%";
}
if (!empty($filterAddress)) {
    $whereClauses[] = "address LIKE ?";
    $params[] = "%{$filterAddress}%";
}
if ($filterStatus === 'Has Balance/Debt' || $filterStatus === 'debt') {
    $whereClauses[] = "balance > 0";
} elseif ($filterStatus === 'Zero Balance' || $filterStatus === 'zero') {
    $whereClauses[] = "balance <= 0";
}
if (!empty($search)) {
    $whereClauses[] = "(name LIKE ? OR phone LIKE ? OR code LIKE ? OR address LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereSql = implode(" AND ", $whereClauses);

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE {$whereSql}");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Fetch page
$sql = "SELECT * FROM customers WHERE {$whereSql} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="flex-1 min-w-0 space-y-4">
    <!-- Top Header matching Image 3 -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-black text-red-600 tracking-tight">Customer Management</h1>
            <p class="text-xs text-slate-400 font-medium mt-0.5">Manage customers, track debt balances, and contact details</p>
        </div>
        <div>
            <a href="add_customer.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-black text-xs shadow-md transition-colors cursor-pointer">
                <span>Add Customer</span>
            </a>
        </div>
    </div>

    <?php if (!empty($successMsg)): ?>
        <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between">
            <span><?= htmlspecialchars($successMsg) ?></span>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">×</button>
        </div>
    <?php endif; ?>

    <!-- Filter Card matching Image 3 -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-4 sm:p-5">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4 items-end text-xs">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Customer Name</label>
                <input type="text" name="customer_name" value="<?= htmlspecialchars($filterName) ?>" placeholder="Search Customer Name"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-900 placeholder:text-slate-400 focus:border-red-600 outline-none transition-all">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Phone Number</label>
                <input type="text" name="phone_number" value="<?= htmlspecialchars($filterPhone) ?>" placeholder="Search Phone"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-900 placeholder:text-slate-400 focus:border-red-600 outline-none transition-all">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($filterAddress) ?>" placeholder="Search Address"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-900 placeholder:text-slate-400 focus:border-red-600 outline-none transition-all">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Balance Status</label>
                <select name="balance_status" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-900 focus:border-red-600 outline-none transition-all">
                    <option value="All Customers" <?= $filterStatus === 'All Customers' ? 'selected' : '' ?>>All Customers</option>
                    <option value="Has Balance/Debt" <?= $filterStatus === 'Has Balance/Debt' ? 'selected' : '' ?>>Has Balance/Debt</option>
                    <option value="Zero Balance" <?= $filterStatus === 'Zero Balance' ? 'selected' : '' ?>>Zero Balance</option>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-black text-xs shadow-sm transition-colors flex items-center justify-center gap-1.5 cursor-pointer">
                    <span>Filter</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Table Card matching Image 3 -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
        <!-- Controls: Show per page & Search -->
        <div class="p-4 flex flex-col sm:flex-row items-center justify-between gap-3 border-b border-slate-100 text-xs">
            <div class="flex items-center gap-2 text-slate-600">
                <span>Show per page : Show</span>
                <form id="perPageForm" method="GET" class="inline-block">
                    <?php if (!empty($filterName)): ?><input type="hidden" name="customer_name" value="<?= htmlspecialchars($filterName) ?>"><?php endif; ?>
                    <?php if (!empty($filterPhone)): ?><input type="hidden" name="phone_number" value="<?= htmlspecialchars($filterPhone) ?>"><?php endif; ?>
                    <?php if (!empty($filterAddress)): ?><input type="hidden" name="address" value="<?= htmlspecialchars($filterAddress) ?>"><?php endif; ?>
                    <?php if (!empty($filterStatus)): ?><input type="hidden" name="balance_status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
                    <select name="per_page" onchange="document.getElementById('perPageForm').submit()" class="px-2 py-1 rounded-lg border border-slate-200 text-slate-700 font-bold focus:border-red-600 outline-none cursor-pointer">
                        <option value="10" <?= $perPage == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $perPage == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </form>
                <span>entries</span>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-slate-600">Search:</span>
                <form method="GET" class="inline-block">
                    <?php if (!empty($filterName)): ?><input type="hidden" name="customer_name" value="<?= htmlspecialchars($filterName) ?>"><?php endif; ?>
                    <?php if (!empty($filterPhone)): ?><input type="hidden" name="phone_number" value="<?= htmlspecialchars($filterPhone) ?>"><?php endif; ?>
                    <?php if (!empty($filterAddress)): ?><input type="hidden" name="address" value="<?= htmlspecialchars($filterAddress) ?>"><?php endif; ?>
                    <?php if (!empty($filterStatus)): ?><input type="hidden" name="balance_status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder=""
                           class="px-2.5 py-1 rounded-lg border border-slate-200 text-xs text-slate-800 outline-none focus:border-red-600">
                </form>
            </div>
        </div>

        <!-- Red Table matching Image 3 -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-[#c00000] text-white font-bold text-[11px] tracking-wider select-none">
                    <tr>
                        <th class="py-3 px-3.5"># <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Customer Name <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Phone <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Address <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Code <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Balance <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Description <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Created User <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Joined At <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="10" class="py-8 text-center text-slate-400 font-medium">No customers found matching the criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $c): 
                            $dispId = '#' . ($c['id'] + 500); // Visual ID format matching #532
                            $isDebt = floatval($c['balance']) > 0;
                            $userDisplay = !empty($c['created_user']) ? $c['created_user'] : 'N/A';
                            $joinedDate = !empty($c['created_at']) ? date('Y-m-d', strtotime($c['created_at'])) : date('Y-m-d');
                        ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-3.5 font-bold text-red-600 font-mono"><?= $dispId ?></td>
                            <td class="py-3 px-3.5 font-bold text-slate-800 uppercase"><?= htmlspecialchars($c['name']) ?></td>
                            <td class="py-3 px-3.5 font-mono text-slate-600"><?= !empty($c['phone']) ? htmlspecialchars($c['phone']) : '-' ?></td>
                            <td class="py-3 px-3.5 text-slate-600"><?= !empty($c['address']) ? htmlspecialchars($c['address']) : 'Boorama' ?></td>
                            <td class="py-3 px-3.5 font-mono text-slate-500 font-medium"><?= htmlspecialchars($c['code'] ?: 'MC-' . str_pad($c['id'], 3, '0', STR_PAD_LEFT)) ?></td>
                            <td class="py-3 px-3.5 font-bold <?= $isDebt ? 'text-[#c00000]' : 'text-emerald-600' ?>">
                                $<?= number_format($c['balance'], 2, '.', '') ?>
                            </td>
                            <td class="py-3 px-3.5 text-slate-500 max-w-[120px] truncate"><?= !empty($c['description']) ? htmlspecialchars($c['description']) : '-' ?></td>
                            <td class="py-3 px-3.5 text-slate-600">
                                <span class="inline-flex items-center gap-1 text-slate-700">
                                    <i data-lucide="user" class="w-3.5 h-3.5 text-cyan-600 inline"></i>
                                    <span><?= htmlspecialchars($userDisplay) ?></span>
                                </span>
                            </td>
                            <td class="py-3 px-3.5 font-mono text-slate-500"><?= $joinedDate ?></td>
                            <td class="py-3 px-3.5 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" 
                                            onclick='openEditModal(<?= json_encode($c) ?>)'
                                            class="px-2.5 py-1 rounded bg-[#c00000] hover:bg-red-700 text-white font-bold text-[11px] transition-colors cursor-pointer">
                                        Edit
                                    </button>
                                    <a href="customers.php?delete_id=<?= $c['id'] ?>" 
                                       onclick="return confirm('Ma hubtaa inaad tirtirto macmiilkan: <?= htmlspecialchars(addslashes($c['name'])) ?>?');"
                                       class="px-2.5 py-1 rounded border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-[11px] transition-colors">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer / Pagination matching Image 3 -->
        <div class="p-4 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-100 text-xs">
            <div class="text-slate-500">
                <?php 
                $startItem = $totalRecords > 0 ? $offset + 1 : 0;
                $endItem = min($offset + $perPage, $totalRecords);
                ?>
                Showing <?= $startItem ?> to <?= $endItem ?> of <?= $totalRecords ?> entries
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="inline-flex items-center gap-1">
                <!-- Previous -->
                <a href="?page=<?= max(1, $page - 1) ?>&per_page=<?= $perPage ?>&customer_name=<?= urlencode($filterName) ?>&phone_number=<?= urlencode($filterPhone) ?>&address=<?= urlencode($filterAddress) ?>&balance_status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($search) ?>" 
                   class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 <?= $page <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
                    Previous
                </a>

                <?php for ($p = 1; $p <= min(5, $totalPages); $p++): ?>
                    <a href="?page=<?= $p ?>&per_page=<?= $perPage ?>&customer_name=<?= urlencode($filterName) ?>&phone_number=<?= urlencode($filterPhone) ?>&address=<?= urlencode($filterAddress) ?>&balance_status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($search) ?>" 
                       class="px-3 py-1.5 rounded-lg font-bold <?= $p === $page ? 'bg-[#16a34a] text-white shadow-xs' : 'bg-slate-500 text-white hover:bg-slate-600' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($totalPages > 5): ?>
                    <span class="px-2 text-slate-400 font-bold">...</span>
                    <a href="?page=<?= $totalPages ?>&per_page=<?= $perPage ?>&customer_name=<?= urlencode($filterName) ?>&phone_number=<?= urlencode($filterPhone) ?>&address=<?= urlencode($filterAddress) ?>&balance_status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($search) ?>" 
                       class="px-3 py-1.5 rounded-lg font-bold <?= $totalPages === $page ? 'bg-[#16a34a] text-white shadow-xs' : 'bg-slate-500 text-white hover:bg-slate-600' ?>">
                        <?= $totalPages ?>
                    </a>
                <?php endif; ?>

                <!-- Next -->
                <a href="?page=<?= min($totalPages, $page + 1) ?>&per_page=<?= $perPage ?>&customer_name=<?= urlencode($filterName) ?>&phone_number=<?= urlencode($filterPhone) ?>&address=<?= urlencode($filterAddress) ?>&balance_status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($search) ?>" 
                   class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 <?= $page >= $totalPages ? 'pointer-events-none opacity-50' : '' ?>">
                    Next
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Edit Customer Modal -->
<div id="editCustomerModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white rounded-2xl max-w-md w-full border border-slate-200 shadow-2xl overflow-hidden p-6 space-y-4">
        <div class="flex items-center justify-between border-b pb-3">
            <h3 class="text-sm font-black text-red-600 uppercase tracking-tight">Edit Customer Details</h3>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-700 text-lg font-black cursor-pointer">×</button>
        </div>
        <form method="POST" class="space-y-4 text-xs">
            <input type="hidden" name="update_customer" value="1">
            <input type="hidden" name="customer_id" id="edit_customer_id" value="">

            <div>
                <label class="block font-bold text-slate-700 mb-1">Customer Name <span class="text-red-500">*</span></label>
                <input type="text" name="customer_name" id="edit_name" required class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-red-600 outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Phone Number</label>
                    <input type="text" name="phone_number" id="edit_phone" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-red-600 outline-none font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Address</label>
                    <input type="text" name="address" id="edit_address" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-red-600 outline-none">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Debt Balance ($)</label>
                <input type="number" step="0.01" name="balance" id="edit_balance" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-red-600 outline-none font-bold text-red-600">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Description / Notes</label>
                <textarea name="description" id="edit_description" rows="3" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-red-600 outline-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 font-bold hover:bg-slate-50 cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-black cursor-pointer">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(c) {
    document.getElementById('edit_customer_id').value = c.id;
    document.getElementById('edit_name').value = c.name || '';
    document.getElementById('edit_phone').value = c.phone || '';
    document.getElementById('edit_address').value = c.address || '';
    document.getElementById('edit_balance').value = c.balance || '0.00';
    document.getElementById('edit_description').value = c.description || '';
    document.getElementById('editCustomerModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editCustomerModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
