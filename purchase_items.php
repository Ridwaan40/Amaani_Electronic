<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Filter inputs
$productQ = trim($_GET['product'] ?? '');
$supplierQ = trim($_GET['supplier'] ?? 'All');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$limit = intval($_GET['limit'] ?? 10);
if ($limit <= 0) $limit = 10;

$where = "WHERE 1=1";
if ($productQ) {
    $where .= " AND product_name LIKE " . $pdo->quote("%$productQ%");
}
if ($supplierQ !== 'All' && !empty($supplierQ)) {
    $where .= " AND supplier = " . $pdo->quote($supplierQ);
}
if ($startDate) {
    $where .= " AND purchased_date >= " . $pdo->quote($startDate . ' 00:00:00');
}
if ($endDate) {
    $where .= " AND purchased_date <= " . $pdo->quote($endDate . ' 23:59:59');
}

$sql = "SELECT * FROM purchase_items $where ORDER BY id DESC LIMIT $limit";
$items = $pdo->query($sql)->fetchAll();
$totalItems = $pdo->query("SELECT COUNT(*) FROM purchase_items $where")->fetchColumn();

// Fetch distinct suppliers for filter dropdown
$suppliers = $pdo->query("SELECT DISTINCT supplier FROM purchase_items WHERE supplier IS NOT NULL AND supplier != ''")->fetchAll(PDO::FETCH_COLUMN);
?>

<main class="space-y-4 animate-fade-in">
    <!-- Header (Image 4) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                <i data-lucide="bar-chart-2" class="w-5 h-5"></i>
            </div>
            <div>
                <h1 class="text-lg font-black text-slate-900 tracking-tight">Purchases &amp; Stock Inflows</h1>
                <p class="text-xs text-slate-500 font-medium">Manage product purchases, supplier cost allocations, and inventory stock inflows.</p>
            </div>
        </div>
        <a href="create_purchase.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs shadow-md transition-all active:scale-95 cursor-pointer">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>+ Add Purchase</span>
        </a>
    </div>

    <!-- Filter Bar Card (Image 4) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <!-- Filter By Product -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Filter By Product</label>
                <div class="relative">
                    <i data-lucide="package" class="w-3.5 h-3.5 absolute left-3 top-2.5 text-slate-400"></i>
                    <input type="text" name="product" value="<?= htmlspecialchars($productQ) ?>" placeholder="Type product name..." class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-red-600">
                </div>
            </div>

            <!-- Filter By Supplier -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Filter By Supplier</label>
                <div class="relative">
                    <i data-lucide="truck" class="w-3.5 h-3.5 absolute left-3 top-2.5 text-slate-400"></i>
                    <select name="supplier" class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 outline-none bg-white">
                        <option value="All">All Suppliers</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>" <?= $supplierQ === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Start Date -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium outline-none">
            </div>

            <!-- End Date -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium outline-none">
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs shadow-md transition-colors cursor-pointer">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>Search</span>
                </button>
                <a href="purchase_items.php" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-600 transition-colors shadow-xs" title="Reset">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Purchases Ledger Card (Image 4) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-3 border-b border-slate-100 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-md bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                    <i data-lucide="menu" class="w-3.5 h-3.5"></i>
                </div>
                <h3 class="font-black text-slate-900">Purchases Ledger</h3>
            </div>
            <span class="text-[11px] text-slate-400">Showing all stock replenishment records</span>
        </div>

        <div class="p-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <span class="text-slate-600 font-bold">Show per page : Show</span>
                <select onchange="location.href='purchase_items.php?limit=' + this.value" class="bg-white border border-slate-200 rounded-lg px-2 py-1 font-bold text-slate-700 outline-none">
                    <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                </select>
                <span class="text-slate-600">entries</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-red-600 text-white font-black text-[11px] tracking-tight uppercase">
                    <tr>
                        <th class="py-3 px-3"># ⇅</th>
                        <th class="py-3 px-3">PRODUCT NAME ⇅</th>
                        <th class="py-3 px-3 text-center">QUANTITY ⇅</th>
                        <th class="py-3 px-3 text-right">UNIT COST ⇅</th>
                        <th class="py-3 px-3 text-right">ADD COST ⇅</th>
                        <th class="py-3 px-3 text-right">TOTAL COST ⇅</th>
                        <th class="py-3 px-3 text-center">REMAINING ⇅</th>
                        <th class="py-3 px-3">SUPPLIER ⇅</th>
                        <th class="py-3 px-3 text-center">CREATED USER ⇅</th>
                        <th class="py-3 px-3 text-center">PURCHASED DATE ⇅</th>
                        <th class="py-3 px-3 text-center">ACTION</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="11" class="py-12 text-center text-slate-400 font-medium">
                            No purchased items records found.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($items as $it): 
                        $itId = '#' . $it['id'];
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-3 font-mono font-black text-red-600"><?= htmlspecialchars($itId) ?></td>
                        <td class="py-3 px-3">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                                    <i data-lucide="package" class="w-3.5 h-3.5"></i>
                                </div>
                                <span class="font-bold text-slate-900"><?= htmlspecialchars($it['product_name']) ?></span>
                            </div>
                        </td>
                        <td class="py-3 px-3 text-center">
                            <span class="px-2.5 py-1 rounded-full text-xs font-mono font-black bg-slate-100 text-slate-800">
                                <?= $it['qty'] ?>
                            </span>
                        </td>
                        <td class="py-3 px-3 text-right font-mono font-bold text-slate-800">$<?= number_format($it['last_price'], 2) ?></td>
                        <td class="py-3 px-3 text-right font-mono text-slate-400">$<?= number_format($it['add_cost'], 2) ?></td>
                        <td class="py-3 px-3 text-right font-mono font-black text-red-600">$<?= number_format($it['total_cost'], 2) ?></td>
                        <td class="py-3 px-3 text-center text-slate-500 font-mono"><?= $it['remaining_qty'] ?: '-' ?></td>
                        <td class="py-3 px-3 font-medium text-slate-700"><?= htmlspecialchars($it['supplier'] ?: 'INVENTORY STORE') ?></td>
                        <td class="py-3 px-3 text-center text-slate-600">
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium">
                                <i data-lucide="user" class="w-3 h-3 text-red-500"></i>
                                <span><?= htmlspecialchars($it['created_user']) ?></span>
                            </span>
                        </td>
                        <td class="py-3 px-3 text-center font-mono text-slate-500 text-[11px]">
                            <span class="inline-flex items-center gap-1">
                                <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
                                <span><?= htmlspecialchars($it['purchased_date']) ?></span>
                            </span>
                        </td>
                        <!-- Action (Image 4: Return button) -->
                        <td class="py-3 px-3 text-center">
                            <a href="create_purchase_return.php?product=<?= urlencode($it['product_name']) ?>&supplier=<?= urlencode($it['supplier']) ?>&cost=<?= $it['last_price'] ?>" class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] shadow-xs transition-colors">
                                <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                <span>Return</span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="p-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
            <span>Showing <?= count($items) ?> of <?= $totalItems ?> entries</span>
            <div class="flex items-center gap-1">
                <button type="button" class="px-3 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 font-bold text-slate-500" disabled>Previous</button>
                <span class="w-7 h-7 rounded-lg bg-red-600 text-white flex items-center justify-center font-bold">1</span>
                <button type="button" class="px-3 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 font-bold text-slate-500" disabled>Next</button>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
