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

// Handle Delete Payment (reverse credit record back to customer balance)
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);
    $pStmt = $pdo->prepare("SELECT customer_name, amount, discount FROM payments WHERE id = ?");
    $pStmt->execute([$delId]);
    $pRow = $pStmt->fetch(PDO::FETCH_ASSOC);
    if ($pRow) {
        $reverseAmt = floatval($pRow['amount']) + floatval($pRow['discount']);
        if ($reverseAmt > 0 && !empty($pRow['customer_name'])) {
            $pdo->prepare("UPDATE customers SET balance = balance + ? WHERE name = ?")->execute([$reverseAmt, $pRow['customer_name']]);
        }
    }
    $delStmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
    $delStmt->execute([$delId]);
    safeRedirect("payments.php?deleted=1");
}

// Handle Delete Debtor Balance (clear unpaid debt record)
if (isset($_GET['clear_debt_id'])) {
    $custId = intval($_GET['clear_debt_id']);
    if ($custId > 0) {
        $pdo->prepare("UPDATE customers SET balance = 0.00 WHERE id = ?")->execute([$custId]);
    }
    safeRedirect("payments.php?deleted=1");
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$successMsg = '';

if (isset($_GET['success'])) {
    $successMsg = "Lacag-bixinta deynta si guul leh ayaa loo diiwaangeliyay (Payment saved successfully).";
}
if (isset($_GET['deleted'])) {
    $successMsg = "Lacag-bixinta waa la tirtiray (Payment deleted successfully).";
}

// Fetch all unique customers and users for filter dropdowns
$customersList = $pdo->query("SELECT DISTINCT name FROM customers WHERE name IS NOT NULL AND name != '' ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$usersList = $pdo->query("SELECT DISTINCT created_user FROM payments WHERE created_user IS NOT NULL AND created_user != '' ORDER BY created_user ASC")->fetchAll(PDO::FETCH_COLUMN);

// Filter params
$filterCust = trim($_GET['customer'] ?? '');
$filterPhone = trim($_GET['phone'] ?? '');
$filterStartDate = trim($_GET['start_date'] ?? '');
$filterEndDate = trim($_GET['end_date'] ?? '');
$filterUser = trim($_GET['user'] ?? '');
$search = trim($_GET['search'] ?? '');

$perPage = intval($_GET['per_page'] ?? 10);
if ($perPage < 5) $perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$whereClauses = ["1=1"];
$params = [];

if (!empty($filterCust) && $filterCust !== 'All Customers') {
    $whereClauses[] = "customer_name = ?";
    $params[] = $filterCust;
}
if (!empty($filterPhone)) {
    $whereClauses[] = "customer_phone LIKE ?";
    $params[] = "%{$filterPhone}%";
}
if (!empty($filterStartDate)) {
    $whereClauses[] = "DATE(paid_at) >= ?";
    $params[] = $filterStartDate;
}
if (!empty($filterEndDate)) {
    $whereClauses[] = "DATE(paid_at) <= ?";
    $params[] = $filterEndDate;
}
if (!empty($filterUser) && $filterUser !== 'All Users') {
    $whereClauses[] = "created_user = ?";
    $params[] = $filterUser;
}
if (!empty($search)) {
    $whereClauses[] = "(customer_name LIKE ? OR customer_phone LIKE ? OR invoice_number LIKE ? OR payment_method LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereSql = implode(" AND ", $whereClauses);

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE {$whereSql}");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Fetch customers with outstanding debt (balance > 0) applying matching filters
$debtorWhere = ["balance > 0"];
$debtorParams = [];
if (!empty($filterCust) && $filterCust !== 'All Customers') {
    $debtorWhere[] = "name = ?";
    $debtorParams[] = $filterCust;
}
if (!empty($filterPhone)) {
    $debtorWhere[] = "phone LIKE ?";
    $debtorParams[] = "%{$filterPhone}%";
}
if (!empty($search)) {
    $debtorWhere[] = "(name LIKE ? OR phone LIKE ?)";
    $debtorParams[] = "%{$search}%";
    $debtorParams[] = "%{$search}%";
}
$debtorStmt = $pdo->prepare("SELECT id, name, phone, balance FROM customers WHERE " . implode(" AND ", $debtorWhere) . " ORDER BY balance DESC");
$debtorStmt->execute($debtorParams);
$debtors = $debtorStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch data
$sql = "SELECT * FROM payments WHERE {$whereSql} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="flex-1 min-w-0 space-y-4">
    <!-- Top Header matching Image 4 -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-black text-red-600 tracking-tight">Credit Payments</h1>
            <p class="text-xs text-slate-400 font-medium mt-0.5">Manage customer debt payments and account balances</p>
        </div>
        <div>
            <a href="add_payment.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-black text-xs shadow-md transition-colors cursor-pointer">
                <span>Add Payment</span>
            </a>
        </div>
    </div>

    <?php if (!empty($successMsg)): ?>
        <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between">
            <span><?= htmlspecialchars($successMsg) ?></span>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">×</button>
        </div>
    <?php endif; ?>

    <!-- Filter Card matching Image 4 -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-4 sm:p-5">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4 items-end text-xs">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Customer</label>
                <select name="customer" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-900 focus:border-red-600 outline-none transition-all cursor-pointer">
                    <option value="All Customers">All Customers</option>
                    <?php foreach ($customersList as $cName): ?>
                        <option value="<?= htmlspecialchars($cName) ?>" <?= $filterCust === $cName ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($filterPhone) ?>" placeholder="Search Phone"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-900 placeholder:text-slate-400 focus:border-red-600 outline-none transition-all">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($filterStartDate) ?>"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-900 focus:border-red-600 outline-none transition-all">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($filterEndDate) ?>"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-900 focus:border-red-600 outline-none transition-all">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">User</label>
                <select name="user" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-900 focus:border-red-600 outline-none transition-all cursor-pointer">
                    <option value="All Users">All Users</option>
                    <?php foreach ($usersList as $uName): ?>
                        <option value="<?= htmlspecialchars($uName) ?>" <?= $filterUser === $uName ? 'selected' : '' ?>>
                            <?= htmlspecialchars($uName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-black text-xs shadow-sm transition-colors flex items-center justify-center cursor-pointer">
                    <span>Filter</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Table Card matching Image 4 -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
        <!-- Controls: Show per page & Search -->
        <div class="p-4 flex flex-col sm:flex-row items-center justify-between gap-3 border-b border-slate-100 text-xs">
            <div class="flex items-center gap-2 text-slate-600">
                <span>Show per page : Show</span>
                <form id="perPageFormPay" method="GET" class="inline-block">
                    <?php if (!empty($filterCust)): ?><input type="hidden" name="customer" value="<?= htmlspecialchars($filterCust) ?>"><?php endif; ?>
                    <?php if (!empty($filterPhone)): ?><input type="hidden" name="phone" value="<?= htmlspecialchars($filterPhone) ?>"><?php endif; ?>
                    <?php if (!empty($filterStartDate)): ?><input type="hidden" name="start_date" value="<?= htmlspecialchars($filterStartDate) ?>"><?php endif; ?>
                    <?php if (!empty($filterEndDate)): ?><input type="hidden" name="end_date" value="<?= htmlspecialchars($filterEndDate) ?>"><?php endif; ?>
                    <?php if (!empty($filterUser)): ?><input type="hidden" name="user" value="<?= htmlspecialchars($filterUser) ?>"><?php endif; ?>
                    <select name="per_page" onchange="document.getElementById('perPageFormPay').submit()" class="px-2 py-1 rounded-lg border border-slate-200 text-slate-700 font-bold focus:border-red-600 outline-none cursor-pointer">
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
                    <?php if (!empty($filterCust)): ?><input type="hidden" name="customer" value="<?= htmlspecialchars($filterCust) ?>"><?php endif; ?>
                    <?php if (!empty($filterPhone)): ?><input type="hidden" name="phone" value="<?= htmlspecialchars($filterPhone) ?>"><?php endif; ?>
                    <?php if (!empty($filterStartDate)): ?><input type="hidden" name="start_date" value="<?= htmlspecialchars($filterStartDate) ?>"><?php endif; ?>
                    <?php if (!empty($filterEndDate)): ?><input type="hidden" name="end_date" value="<?= htmlspecialchars($filterEndDate) ?>"><?php endif; ?>
                    <?php if (!empty($filterUser)): ?><input type="hidden" name="user" value="<?= htmlspecialchars($filterUser) ?>"><?php endif; ?>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder=""
                           class="px-2.5 py-1 rounded-lg border border-slate-200 text-xs text-slate-800 outline-none focus:border-red-600">
                </form>
            </div>
        </div>

        <!-- Red Table matching Image 4 -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-[#c00000] text-white font-bold text-[11px] tracking-wider select-none">
                    <tr>
                        <th class="py-3 px-3.5"># <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Customer <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Phone <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Previous <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Discount <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Amount <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Balance <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Method <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Department <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Created User <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5">Date <span class="text-white/70 text-[9px]">⇅</span></th>
                        <th class="py-3 px-3.5 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($payments) && empty($debtors)): ?>
                        <tr>
                            <td colspan="12" class="py-8 text-center text-slate-400 font-medium">No credit payments or outstanding debts found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($debtors as $deb): ?>
                        <tr class="bg-red-50/30 hover:bg-red-50/60 transition-colors">
                            <td class="py-3 px-3.5">
                                <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-black text-[10px] uppercase">DUE</span>
                            </td>
                            <td class="py-3 px-3.5 font-bold text-slate-800 uppercase"><?= htmlspecialchars($deb['name']) ?></td>
                            <td class="py-3 px-3.5 font-mono text-slate-600"><?= !empty($deb['phone']) ? htmlspecialchars($deb['phone']) : '-' ?></td>
                            <td class="py-3 px-3.5 font-medium text-slate-700">$<?= number_format($deb['balance'], 2, '.', '') ?></td>
                            <td class="py-3 px-3.5 font-medium text-slate-400">$0.00</td>
                            <td class="py-3 px-3.5 font-bold text-slate-400">$0.00</td>
                            <td class="py-3 px-3.5 font-black text-[#c00000]">$<?= number_format($deb['balance'], 2, '.', '') ?></td>
                            <td class="py-3 px-3.5">
                                <span class="px-2 py-0.5 rounded bg-red-100 text-red-700 font-bold text-[10px]">Unpaid / Dayn</span>
                            </td>
                            <td class="py-3 px-3.5 text-slate-600"><?= htmlspecialchars($currentBranch) ?></td>
                            <td class="py-3 px-3.5 text-slate-400 italic">Pending</td>
                            <td class="py-3 px-3.5 font-mono text-slate-400">—</td>
                            <td class="py-3 px-3.5 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="add_payment.php?customer_id=<?= $deb['id'] ?>" class="px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition-colors">
                                        Pay Now
                                    </a>
                                    <a href="payment_receipt.php?customer_id=<?= $deb['id'] ?>" class="px-2.5 py-1 rounded bg-[#c00000] hover:bg-red-700 text-white font-bold text-[11px] transition-colors">
                                        Invoice
                                    </a>
                                    <button type="button" onclick="openDeleteModal('payments.php?clear_debt_id=<?= $deb['id'] ?>')" class="px-2.5 py-1 rounded border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-[11px] transition-colors cursor-pointer">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php foreach ($payments as $p): 
                            $dispId = '#' . ($p['id'] + 349); // Matching #359 format in image 4
                            $userDisplay = !empty($p['created_user']) ? $p['created_user'] : 'N/A';
                            $dateDisplay = !empty($p['paid_at']) ? date('d-m-Y H:i', strtotime($p['paid_at'])) : date('d-m-Y H:i');
                            $isBalZero = floatval($p['remaining_balance']) <= 0;
                        ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-3.5 font-bold text-red-600 font-mono"><?= $dispId ?></td>
                            <td class="py-3 px-3.5 font-bold text-slate-800 uppercase"><?= htmlspecialchars($p['customer_name'] ?: 'Customer') ?></td>
                            <td class="py-3 px-3.5 font-mono text-slate-600"><?= !empty($p['customer_phone']) ? htmlspecialchars($p['customer_phone']) : '-' ?></td>
                            <td class="py-3 px-3.5 font-medium text-slate-700">$<?= number_format($p['previous_balance'], 2, '.', '') ?></td>
                            <td class="py-3 px-3.5 font-medium text-slate-500">$<?= number_format($p['discount'], 2, '.', '') ?></td>
                            <td class="py-3 px-3.5 font-bold text-[#c00000]">$<?= number_format($p['amount'], 2, '.', '') ?></td>
                            <td class="py-3 px-3.5 font-bold <?= $isBalZero ? 'text-emerald-600' : 'text-[#c00000]' ?>">
                                $<?= number_format($p['remaining_balance'], 2, '.', '') ?>
                            </td>
                            <td class="py-3 px-3.5 text-slate-700"><?= htmlspecialchars($p['payment_method'] ?: 'Cash') ?></td>
                            <td class="py-3 px-3.5 text-slate-600"><?= htmlspecialchars($p['department'] ?: 'Boorama Branch') ?></td>
                            <td class="py-3 px-3.5 text-slate-600">
                                <span class="inline-flex items-center gap-1 text-slate-700">
                                    <i data-lucide="user" class="w-3.5 h-3.5 text-cyan-600 inline"></i>
                                    <span><?= htmlspecialchars($userDisplay) ?></span>
                                </span>
                            </td>
                            <td class="py-3 px-3.5 font-mono text-slate-500"><?= $dateDisplay ?></td>
                            <td class="py-3 px-3.5 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="payment_receipt.php?id=<?= $p['id'] ?>"
                                       class="px-2.5 py-1 rounded bg-[#c00000] hover:bg-red-700 text-white font-bold text-[11px] transition-colors">
                                        Invoice
                                    </a>
                                    <button type="button"
                                            onclick="openDeleteModal('payments.php?delete_id=<?= $p['id'] ?>')"
                                            class="px-2.5 py-1 rounded border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-[11px] transition-colors cursor-pointer">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer / Pagination matching Image 4 -->
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
                <a href="?page=<?= max(1, $page - 1) ?>&per_page=<?= $perPage ?>&customer=<?= urlencode($filterCust) ?>&phone=<?= urlencode($filterPhone) ?>&start_date=<?= urlencode($filterStartDate) ?>&end_date=<?= urlencode($filterEndDate) ?>&user=<?= urlencode($filterUser) ?>&search=<?= urlencode($search) ?>" 
                   class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 <?= $page <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
                    Previous
                </a>

                <?php for ($p = 1; $p <= min(5, $totalPages); $p++): ?>
                    <a href="?page=<?= $p ?>&per_page=<?= $perPage ?>&customer=<?= urlencode($filterCust) ?>&phone=<?= urlencode($filterPhone) ?>&start_date=<?= urlencode($filterStartDate) ?>&end_date=<?= urlencode($filterEndDate) ?>&user=<?= urlencode($filterUser) ?>&search=<?= urlencode($search) ?>" 
                       class="px-3 py-1.5 rounded-lg font-bold <?= $p === $page ? 'bg-[#16a34a] text-white shadow-xs' : 'bg-slate-500 text-white hover:bg-slate-600' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($totalPages > 5): ?>
                    <span class="px-2 text-slate-400 font-bold">...</span>
                    <a href="?page=<?= $totalPages ?>&per_page=<?= $perPage ?>&customer=<?= urlencode($filterCust) ?>&phone=<?= urlencode($filterPhone) ?>&start_date=<?= urlencode($filterStartDate) ?>&end_date=<?= urlencode($filterEndDate) ?>&user=<?= urlencode($filterUser) ?>&search=<?= urlencode($search) ?>" 
                       class="px-3 py-1.5 rounded-lg font-bold <?= $totalPages === $page ? 'bg-[#16a34a] text-white shadow-xs' : 'bg-slate-500 text-white hover:bg-slate-600' ?>">
                        <?= $totalPages ?>
                    </a>
                <?php endif; ?>

                <!-- Next -->
                <a href="?page=<?= min($totalPages, $page + 1) ?>&per_page=<?= $perPage ?>&customer=<?= urlencode($filterCust) ?>&phone=<?= urlencode($filterPhone) ?>&start_date=<?= urlencode($filterStartDate) ?>&end_date=<?= urlencode($filterEndDate) ?>&user=<?= urlencode($filterUser) ?>&search=<?= urlencode($search) ?>" 
                   class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 <?= $page >= $totalPages ? 'pointer-events-none opacity-50' : '' ?>">
                    Next
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Printable Receipt Modal -->
<div id="receiptModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white rounded-2xl max-w-md w-full border border-slate-200 shadow-2xl overflow-hidden p-6 space-y-4">
        <div id="printArea" class="space-y-4 text-xs font-sans">
            <!-- Header -->
            <div class="text-center border-b pb-3 space-y-1">
                <h2 class="text-base font-black text-red-600 tracking-wider uppercase">AMAANI ELECTRONICS</h2>
                <p class="text-[10px] text-slate-500">Official Credit Payment Receipt</p>
                <div class="text-[10px] font-mono font-bold text-slate-700" id="rec_number">#REC-000</div>
            </div>

            <!-- Details Grid -->
            <div class="space-y-2 py-2 border-b border-dashed border-slate-200">
                <div class="flex justify-between">
                    <span class="text-slate-500">Customer:</span>
                    <span class="font-bold text-slate-900" id="rec_customer">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Phone:</span>
                    <span class="font-mono text-slate-700" id="rec_phone">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Date & Time:</span>
                    <span class="font-mono text-slate-700" id="rec_date">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Payment Method:</span>
                    <span class="font-bold text-slate-700" id="rec_method">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Branch:</span>
                    <span class="text-slate-700" id="rec_dept">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Cashier:</span>
                    <span class="text-slate-700" id="rec_user">-</span>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="space-y-1.5 py-2 border-b border-dashed border-slate-200">
                <div class="flex justify-between">
                    <span class="text-slate-600">Previous Balance:</span>
                    <span class="font-mono font-bold text-slate-800" id="rec_prev">$0.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">Discount:</span>
                    <span class="font-mono font-bold text-slate-500" id="rec_disc">$0.00</span>
                </div>
                <div class="flex justify-between text-sm pt-1 border-t border-slate-100">
                    <span class="font-black text-red-600 uppercase">Paid Amount:</span>
                    <span class="font-mono font-black text-red-600 text-base" id="rec_amount">$0.00</span>
                </div>
                <div class="flex justify-between text-xs pt-1">
                    <span class="font-bold text-slate-700">Remaining Balance:</span>
                    <span class="font-mono font-black text-emerald-600" id="rec_rem">$0.00</span>
                </div>
            </div>

            <div class="text-center text-[10px] text-slate-400 pt-2">
                Mahadsanid! Thank you for your business.
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t">
            <button type="button" onclick="closeReceiptModal()" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 font-bold hover:bg-slate-50 cursor-pointer">
                Close
            </button>
            <button type="button" onclick="window.print()" class="px-5 py-2 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-black flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Print Receipt</span>
            </button>
        </div>
    </div>
</div>

<script>
function openReceiptModal(p, dispId, dateStr) {
    document.getElementById('rec_number').innerText = dispId + ' (' + (p.invoice_number || 'RECEIPT') + ')';
    document.getElementById('rec_customer').innerText = p.customer_name || 'Customer';
    document.getElementById('rec_phone').innerText = p.customer_phone || '-';
    document.getElementById('rec_date').innerText = dateStr;
    document.getElementById('rec_method').innerText = p.payment_method || 'Cash On Hand';
    document.getElementById('rec_dept').innerText = p.department || 'Boorama Branch';
    document.getElementById('rec_user').innerText = p.created_user || 'Admin';
    document.getElementById('rec_prev').innerText = '$' + parseFloat(p.previous_balance || 0).toFixed(2);
    document.getElementById('rec_disc').innerText = '$' + parseFloat(p.discount || 0).toFixed(2);
    document.getElementById('rec_amount').innerText = '$' + parseFloat(p.amount || 0).toFixed(2);
    document.getElementById('rec_rem').innerText = '$' + parseFloat(p.remaining_balance || 0).toFixed(2);
    
    document.getElementById('receiptModal').classList.remove('hidden');
}

function closeReceiptModal() {
    document.getElementById('receiptModal').classList.add('hidden');
}

function openDeleteModal(deleteUrl) {
    document.getElementById('confirmDeleteLink').href = deleteUrl;
    document.getElementById('deleteConfirmModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').classList.add('hidden');
}
</script>

<!-- Delete Confirmation Modal matching Screenshot 2 -->
<div id="deleteConfirmModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-[1px] z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-7 text-center space-y-4">
        <div class="w-16 h-16 rounded-full border-4 border-amber-200 bg-amber-50 text-amber-500 flex items-center justify-center mx-auto text-3xl font-black">
            !
        </div>
        <div>
            <h3 class="text-lg font-black text-slate-800">Delete Payment?</h3>
            <p class="text-xs text-slate-500 mt-1">This action will reverse the credit record.</p>
        </div>
        <div class="flex items-center justify-center gap-3 pt-2">
            <a id="confirmDeleteLink" href="#" class="px-5 py-2.5 rounded-lg bg-[#dc2626] hover:bg-red-700 text-white font-bold text-xs shadow-sm transition-colors">
                Yes, Delete
            </a>
            <button type="button" onclick="closeDeleteModal()" class="px-5 py-2.5 rounded-lg bg-slate-500 hover:bg-slate-600 text-white font-bold text-xs shadow-sm transition-colors cursor-pointer">
                Cancel
            </button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
