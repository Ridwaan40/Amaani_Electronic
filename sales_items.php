<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// ── Filter parameters ────────────────────────────────────────────────────────
$filterProduct   = trim($_GET['product']    ?? '');
$filterRef       = trim($_GET['ref']        ?? '');
$filterStartDate = trim($_GET['start_date'] ?? '');
$filterEndDate   = trim($_GET['end_date']   ?? '');
$perPage         = intval($_GET['per_page'] ?? 25);
if ($perPage <= 0) $perPage = 25;

// ── Build query ──────────────────────────────────────────────────────────────
$where  = ["1=1"];
$params = [];

if ($currentBranch !== 'All Branches') {
    $where[]  = "(si.branch = ? OR si.branch LIKE ?)";
    $params[] = $currentBranch;
    $params[] = "%$currentBranch%";
}

if (!empty($filterProduct)) {
    $where[]  = "si.product_name LIKE ?";
    $params[] = "%$filterProduct%";
}

if (!empty($filterRef)) {
    $cleanRef = str_replace('#', '', $filterRef);
    $where[]  = "(si.transaction_id LIKE ? OR si.transaction_id LIKE ?)";
    $params[] = "%$filterRef%";
    $params[] = "%$cleanRef%";
}

if (!empty($filterStartDate)) {
    $where[]  = "si.created_at >= ?";
    $params[] = $filterStartDate . ' 00:00:00';
}

if (!empty($filterEndDate)) {
    $where[]  = "si.created_at <= ?";
    $params[] = $filterEndDate . ' 23:59:59';
}

$whereClause = implode(" AND ", $where);

$sql = "SELECT si.*,
               s.customer_name,
               s.phone          AS customer_phone,
               s.subtotal,
               s.discount,
               s.total_sale,
               s.paid_amount,
               (s.total_sale - s.paid_amount) AS balance,
               s.payment_method,
               s.notes          AS sale_notes,
               s.sale_date,
               s.return_date,
               s.status         AS sale_status
        FROM sale_items si
        LEFT JOIN sales s ON (
            s.invoice_number = si.transaction_id 
            OR CONCAT('#', s.invoice_number) = si.transaction_id 
            OR s.invoice_number = REPLACE(si.transaction_id, '#', '')
        )
        WHERE $whereClause
        ORDER BY si.id DESC
        LIMIT $perPage";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalItemsCount = count($items);

// ── Build Invoice Master Data for JavaScript ─────────────────────────────────
// Map of transaction_id => { sale info, items: [...] }
$invoiceRegistry = [];

// Pre-fill from fetched items
foreach ($items as $it) {
    $txId = $it['transaction_id'] ?? ('#' . $it['id']);
    $txKey = str_replace('#', '', $txId);

    if (!isset($invoiceRegistry[$txKey])) {
        $invoiceRegistry[$txKey] = [
            'txId'          => $txId,
            'cleanTx'       => $txKey,
            'customerName'  => $it['customer_name']  ?? 'Walk-In Customer',
            'customerPhone' => $it['customer_phone'] ?? 'N/A',
            'cashier'       => $it['created_by']      ?? 'Admin',
            'branch'        => $it['branch']          ?? 'Boorama Branch',
            'date'          => !empty($it['sale_date']) ? $it['sale_date'] : (!empty($it['created_at']) ? substr($it['created_at'], 0, 10) : date('Y-m-d')),
            'returnDate'    => $it['return_date']    ?? '',
            'paymentMethod' => $it['payment_method'] ?? 'Cash On Hand',
            'subtotal'      => floatval($it['subtotal']   ?? $it['total_price']),
            'discount'      => floatval($it['discount']   ?? 0),
            'totalSale'     => floatval($it['total_sale'] ?? $it['total_price']),
            'paidAmount'    => floatval($it['paid_amount'] ?? $it['total_price']),
            'balance'       => floatval($it['balance']    ?? 0),
            'notes'         => $it['sale_notes'] ?? '',
            'items'         => []
        ];
    }

    $invoiceRegistry[$txKey]['items'][] = [
        'id'         => $it['id'],
        'name'       => $it['product_name'] ?? 'Product',
        'unit'       => $it['unit']         ?? 'Pcs',
        'qty'        => intval($it['qty']   ?? 1),
        'soldPrice'  => floatval($it['sold_price']  ?? 0),
        'totalPrice' => floatval($it['total_price'] ?? 0),
        'saleType'   => $it['sale_type']    ?? 'Retail'
    ];
}
?>

<main class="flex-1 min-w-0 space-y-4">

    <!-- ═══════════════════════════════════════════════════════════════════════
         VIEW A: SALES ORDERS TRANSACTION LIST (Screenshots 2 & 3)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div id="sales-orders-list-view" class="space-y-4">
        <!-- ── 1. PAGE HEADER ────────────────────────────────────────────── -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-black text-[#c00000] flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-blue-100 text-[#0B2A5B] flex items-center justify-center shrink-0">
                        <i data-lucide="receipt" class="w-4 h-4 text-blue-600"></i>
                    </span>
                    <span>Sales Orders Transaction List</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Detailed ledger of itemized sales order lines, quantities sold, revenue, and invoice traceability.
                </p>
            </div>

            <a href="create_sales.php?branch=<?= urlencode($currentBranch) ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-black shadow-sm transition-all active:scale-95 shrink-0">
                <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                <span>Create New Sale</span>
            </a>
        </div>

        <!-- ── 2. FILTER SALES TRANSACTIONS CARD ──────────────────────────── -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4">
            <!-- Filter Header -->
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2 text-xs font-black text-slate-900">
                    <i data-lucide="filter" class="w-4 h-4 text-blue-600"></i>
                    <span>Filter Sales Transactions</span>
                </div>
                <a href="sales_items.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-1 text-[11px] font-bold text-slate-400 hover:text-slate-600 transition-colors">
                    <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                    <span>Clear Filters</span>
                </a>
            </div>

            <!-- Filter Form -->
            <form method="GET" action="sales_items.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end text-xs">
                <input type="hidden" name="branch" value="<?= htmlspecialchars($currentBranch) ?>">

                <!-- Filter By Product -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Filter By Product</label>
                    <div class="relative">
                        <i data-lucide="package" class="w-4 h-4 absolute left-3 top-2.5 text-slate-400"></i>
                        <input type="text" name="product" value="<?= htmlspecialchars($filterProduct) ?>" placeholder="Type product name..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 placeholder-slate-400 focus:border-[#c00000] focus:ring-1 focus:ring-[#c00000] outline-none">
                    </div>
                </div>

                <!-- Filter By Transaction Ref # -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Filter By Transaction Ref #</label>
                    <div class="relative">
                        <i data-lucide="file-text" class="w-4 h-4 absolute left-3 top-2.5 text-slate-400"></i>
                        <input type="text" name="ref" value="<?= htmlspecialchars($filterRef) ?>" placeholder="e.g. 1024" class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 placeholder-slate-400 focus:border-[#c00000] focus:ring-1 focus:ring-[#c00000] outline-none">
                    </div>
                </div>

                <!-- Start Date -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Start Date</label>
                    <div class="relative">
                        <input type="date" name="start_date" value="<?= htmlspecialchars($filterStartDate) ?>" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:border-[#c00000] focus:ring-1 focus:ring-[#c00000] outline-none cursor-pointer">
                    </div>
                </div>

                <!-- End Date -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">End Date</label>
                    <div class="relative">
                        <input type="date" name="end_date" value="<?= htmlspecialchars($filterEndDate) ?>" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:border-[#c00000] focus:ring-1 focus:ring-[#c00000] outline-none cursor-pointer">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-black text-xs shadow-xs transition-colors cursor-pointer">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        <span>Search</span>
                    </button>
                    <a href="sales_items.php?branch=<?= urlencode($currentBranch) ?>" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition-colors shrink-0" title="Reset Filters">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- ── 3. TRANSACTIONS LEDGER TABLE CARD ──────────────────────────── -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col relative border-b-4 border-b-green-500">
            <!-- Card Header -->
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2 text-xs font-black text-slate-900">
                    <i data-lucide="table" class="w-4 h-4 text-blue-600"></i>
                    <span>Transactions Ledger</span>
                </div>
                <span class="text-xs text-slate-400 font-medium">Showing latest line item records</span>
            </div>

            <!-- Show per page dropdown -->
            <div class="px-4 py-2.5 bg-white border-b border-slate-100 text-xs text-slate-600 flex items-center gap-1.5 font-medium">
                <span>Show per page : Show</span>
                <select onchange="location.href='sales_items.php?branch=<?= urlencode($currentBranch) ?>&per_page=' + this.value" class="px-2 py-0.5 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-800 outline-none cursor-pointer">
                    <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>100</option>
                </select>
                <span>records</span>
            </div>

            <!-- Table Container -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs whitespace-nowrap" id="sales-items-table">
                    <!-- Solid Red Header Row with Sorting Arrows -->
                    <thead class="bg-[#c00000] text-white uppercase font-black text-[10px] tracking-wider select-none">
                        <tr>
                            <th class="py-3 px-3 cursor-pointer hover:bg-red-700 transition-colors" onclick="sortTable(0)">
                                <div class="flex items-center gap-1">
                                    <span>#</span>
                                    <span class="text-[9px] opacity-70">⇅</span>
                                </div>
                            </th>
                            <th class="py-3 px-4 cursor-pointer hover:bg-red-700 transition-colors" onclick="sortTable(1)">
                                <div class="flex items-center gap-1">
                                    <span>PRODUCT NAME</span>
                                    <span class="text-[9px] opacity-70">⇅</span>
                                </div>
                            </th>
                            <th class="py-3 px-3 cursor-pointer hover:bg-red-700 transition-colors" onclick="sortTable(2)">
                                <div class="flex items-center gap-1">
                                    <span>UNIT</span>
                                    <span class="text-[9px] opacity-70">⇅</span>
                                </div>
                            </th>
                            <th class="py-3 px-3 text-center cursor-pointer hover:bg-red-700 transition-colors" onclick="sortTable(3)">
                                <div class="flex items-center justify-center gap-1">
                                    <span>QTY</span>
                                    <span class="text-[9px] opacity-70">⇅</span>
                                </div>
                            </th>
                            <th class="py-3 px-3 text-right cursor-pointer hover:bg-red-700 transition-colors" onclick="sortTable(4)">
                                <div class="flex items-center justify-end gap-1">
                                    <span>SOLD PRICE</span>
                                    <span class="text-[9px] opacity-70">⇅</span>
                                </div>
                            </th>
                            <th class="py-3 px-3 text-right cursor-pointer hover:bg-red-700 transition-colors" onclick="sortTable(5)">
                                <div class="flex items-center justify-end gap-1">
                                    <span>PREVIOUS PRICE</span>
                                    <span class="text-[9px] opacity-70">⇅</span>
                                </div>
                            </th>
                            <th class="py-3 px-3 text-right cursor-pointer hover:bg-red-700 transition-colors" onclick="sortTable(6)">
                                <div class="flex items-center justify-end gap-1">
                                    <span>LAST PRICE</span>
                                    <span class="text-[9px] opacity-70">⇅</span>
                                </div>
                            </th>
                            <th class="py-3 px-3 text-right cursor-pointer hover:bg-red-700 transition-colors" onclick="sortTable(7)">
                                <div class="flex items-center justify-end gap-1">
                                    <span>PREV WHOLESALE</span>
                                    <span class="text-[9px] opacity-70">⇅</span>
                                </div>
                            </th>
                            <th class="py-3 px-3 text-right cursor-pointer hover:bg-red-700 transition-colors" onclick="sortTable(8)">
                                <div class="flex items-center justify-end gap-1">
                                    <span>WHOLESALE PRICE</span>
                                    <span class="text-[9px] opacity-70">⇅</span>
                                </div>
                            </th>
                            <th class="py-3 px-3 text-center cursor-pointer hover:bg-red-700 transition-colors" onclick="sortTable(9)">
                                <div class="flex items-center justify-center gap-1">
                                    <span>SALE TYPE</span>
                                    <span class="text-[9px] opacity-70">⇅</span>
                                </div>
                            </th>
                            <th class="py-3 px-4 text-right cursor-pointer hover:bg-red-700 transition-colors" onclick="sortTable(10)">
                                <div class="flex items-center justify-end gap-1">
                                    <span>TOTAL PRICE</span>
                                    <span class="text-[9px] opacity-70">⇅</span>
                                </div>
                            </th>
                            <th class="py-3 px-4 cursor-pointer hover:bg-red-700 transition-colors" onclick="sortTable(11)">
                                <div class="flex items-center gap-1">
                                    <span>BRANCH</span>
                                    <span class="text-[9px] opacity-70">⇅</span>
                                </div>
                            </th>
                            <th class="py-3 px-3 cursor-pointer hover:bg-red-700 transition-colors" onclick="sortTable(12)">
                                <div class="flex items-center gap-1">
                                    <span>TRANSACTION ID</span>
                                    <span class="text-[9px] opacity-70">⇅</span>
                                </div>
                            </th>
                            <th class="py-3 px-3 cursor-pointer hover:bg-red-700 transition-colors" onclick="sortTable(13)">
                                <div class="flex items-center gap-1">
                                    <span>CREATED USER</span>
                                    <span class="text-[9px] opacity-70">⇅</span>
                                </div>
                            </th>
                            <th class="py-3 px-3 cursor-pointer hover:bg-red-700 transition-colors" onclick="sortTable(14)">
                                <div class="flex items-center gap-1">
                                    <span>DATE</span>
                                    <span class="text-[9px] opacity-70">⇅</span>
                                </div>
                            </th>
                            <th class="py-3 px-3 text-center">
                                <span>ACTIONS</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="16" class="py-12 text-center text-slate-400 font-bold">
                                No sales transactions matching your search criteria.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($items as $it): 
                            $id = $it['id'];
                            $name = $it['product_name'] ?? 'Product';
                            $unit = $it['unit'] ?? 'Pcs';
                            $qty = intval($it['qty'] ?? 1);
                            $soldPrice = floatval($it['sold_price'] ?? $it['unit_price'] ?? 0);
                            $prevPrice = floatval($it['previous_price'] ?? ($soldPrice * 0.9));
                            $lastPrice = floatval($it['last_price'] ?? $soldPrice);
                            $prevWholesale = floatval($it['prev_wholesale'] ?? ($soldPrice * 0.85));
                            $wholesalePrice = floatval($it['wholesale_price'] ?? ($soldPrice * 0.88));
                            $saleType = $it['sale_type'] ?? 'Retail';
                            $totalPrice = floatval($it['total_price'] ?? ($soldPrice * $qty));
                            $branch = $it['branch'] ?? 'Boorama Branch';
                            $txId = $it['transaction_id'] ?? ('#' . $id);
                            $cleanTx = str_replace('#', '', $txId);
                            $createdBy = $it['created_by'] ?? 'Ridwaan';
                            $createdAt = $it['created_at'] ?? '';
                            $displayDate = $createdAt ? date('d-m-Y H:i', strtotime($createdAt)) : date('d-m-Y');
                        ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <!-- # -->
                            <td class="py-3 px-3 font-semibold text-slate-500">
                                #<?= $id ?>
                            </td>

                            <!-- PRODUCT NAME with small red box icon -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-md bg-red-50 text-red-600 flex items-center justify-center shrink-0 border border-red-100 shadow-xs">
                                        <i data-lucide="package" class="w-3.5 h-3.5"></i>
                                    </span>
                                    <span class="font-bold text-slate-900"><?= htmlspecialchars($name) ?></span>
                                </div>
                            </td>

                            <!-- UNIT -->
                            <td class="py-3 px-3">
                                <span class="inline-block bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[11px] font-semibold border border-slate-200/60">
                                    <?= htmlspecialchars($unit) ?>
                                </span>
                            </td>

                            <!-- QTY -->
                            <td class="py-3 px-3 text-center font-extrabold text-slate-900">
                                <?= $qty ?>
                            </td>

                            <!-- SOLD PRICE (Bold Blue) -->
                            <td class="py-3 px-3 text-right font-black text-blue-600">
                                $<?= number_format($soldPrice, 2) ?>
                            </td>

                            <!-- PREVIOUS PRICE (Muted Gray) -->
                            <td class="py-3 px-3 text-right text-slate-400 font-medium">
                                $<?= number_format($prevPrice, 2) ?>
                            </td>

                            <!-- LAST PRICE -->
                            <td class="py-3 px-3 text-right text-slate-600 font-medium">
                                $<?= number_format($lastPrice, 2) ?>
                            </td>

                            <!-- PREV WHOLESALE -->
                            <td class="py-3 px-3 text-right text-slate-400 font-medium">
                                $<?= number_format($prevWholesale, 2) ?>
                            </td>

                            <!-- WHOLESALE PRICE (Orange/Amber) -->
                            <td class="py-3 px-3 text-right font-semibold text-amber-600">
                                $<?= number_format($wholesalePrice, 2) ?>
                            </td>

                            <!-- SALE TYPE (Pill) -->
                            <td class="py-3 px-3 text-center">
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold <?= $saleType === 'Wholesale' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-red-50 text-red-600 border border-red-200/60' ?>">
                                    <?= htmlspecialchars($saleType) ?>
                                </span>
                            </td>

                            <!-- TOTAL PRICE (Bold Green) -->
                            <td class="py-3 px-4 text-right font-black text-emerald-600 text-sm">
                                $<?= number_format($totalPrice, 2) ?>
                            </td>

                            <!-- BRANCH -->
                            <td class="py-3 px-4">
                                <span class="inline-block bg-slate-100 text-slate-700 px-2 py-0.5 rounded-md text-[11px] font-semibold border border-slate-200/60">
                                    <?= htmlspecialchars($branch) ?>
                                </span>
                            </td>

                            <!-- TRANSACTION ID -->
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-800 px-2 py-0.5 rounded text-[11px] font-mono font-bold border border-slate-200/60">
                                    <i data-lucide="receipt" class="w-3 h-3 text-slate-500"></i>
                                    <?= htmlspecialchars($txId) ?>
                                </span>
                            </td>

                            <!-- CREATED USER -->
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center gap-1 text-blue-600 text-[11px] font-semibold">
                                    <i data-lucide="user" class="w-3 h-3"></i>
                                    <?= htmlspecialchars($createdBy) ?>
                                </span>
                            </td>

                            <!-- DATE -->
                            <td class="py-3 px-3 text-slate-500 text-[11px]">
                                <span class="inline-flex items-center gap-1">
                                    <i data-lucide="clock" class="w-3 h-3 text-slate-400"></i>
                                    <?= htmlspecialchars($displayDate) ?>
                                </span>
                            </td>

                            <!-- ACTIONS: INVOICE BUTTON (Matching Screenshot 2) -->
                            <td class="py-3 px-3 text-center">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" 
                                            onclick="openInvoiceView('<?= htmlspecialchars($cleanTx, ENT_QUOTES) ?>')"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-blue-200 bg-blue-50/70 hover:bg-blue-100 text-blue-700 font-bold text-xs shadow-2xs transition-all active:scale-95 cursor-pointer"
                                            title="View / Print Invoice">
                                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                        <span>Invoice</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ── 4. TABLE FOOTER & PAGINATION ────────────────────────── -->
            <div class="px-4 py-3 bg-white border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                <span class="text-xs text-slate-500 font-medium">
                    Showing 1 to <?= $totalItemsCount ?> of <?= $totalItemsCount ?> line items
                </span>

                <!-- Pagination buttons -->
                <div class="flex items-center gap-1.5">
                    <button type="button" class="px-3 py-1 rounded-lg border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50 cursor-pointer transition-colors">
                        Previous
                    </button>
                    <button type="button" class="px-3 py-1 rounded-lg bg-green-600 text-white text-xs font-bold shadow-xs cursor-pointer">
                        1
                    </button>
                    <button type="button" class="px-3 py-1 rounded-lg border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50 cursor-pointer transition-colors">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- ═══════════════════════════════════════════════════════════════════════
         VIEW B: INVOICE FORM & PRINT PREVIEW (Matching Screenshot 1)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div id="sales-invoice-view" class="hidden space-y-4">
        <!-- Top Toolbar with Back and Print Invoice Buttons -->
        <div class="max-w-3xl mx-auto flex items-center justify-between gap-3 no-print">
            <button type="button" onclick="closeInvoiceView()" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-all active:scale-95 cursor-pointer">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back</span>
            </button>

            <button type="button" onclick="printInvoiceSheet()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-black text-xs shadow-sm transition-all active:scale-95 cursor-pointer">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Print Invoice</span>
            </button>
        </div>

        <!-- The Printable Invoice Card (Exact Match to Screenshot 1) -->
        <div id="invoice-printable-card" class="max-w-3xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm p-8 sm:p-10 space-y-6">
            
            <!-- 1. Header: Logo/Company Info (Left) & INVOICE Meta (Right) -->
            <div class="flex flex-col sm:flex-row items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <!-- Brand Icon -->
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-[#c00000] to-red-600 text-white flex items-center justify-center font-black text-xl shadow-md shrink-0">
                        CE
                    </div>
                    <div>
                        <h1 class="text-lg font-black text-[#c00000] tracking-wide uppercase">CAALAMI ELECTRONICS</h1>
                        <p class="text-xs text-slate-700 font-medium">Hoyga Qalabka Elektaroonigga ah &amp; Qalabka Casriga ah</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Sisibta, Borama, Somaliland</p>
                        <p class="text-[11px] text-slate-400">Tel: 634547497</p>
                    </div>
                </div>

                <div class="text-left sm:text-right w-full sm:w-auto">
                    <h2 class="text-2xl font-black text-[#c00000] tracking-widest uppercase">INVOICE</h2>
                    <div class="text-xs text-slate-600 space-y-0.5 mt-1 font-medium">
                        <div><span class="font-bold text-slate-800">Invoice #:</span> <span id="inv-num" class="font-black text-slate-900">9</span></div>
                        <div><span class="font-bold text-slate-800">Date:</span> <span id="inv-date">17/11/2026</span></div>
                        <div><span class="font-bold text-slate-800">Payment:</span> <span id="inv-payment-method">Cash On Hand</span></div>
                        <div><span class="font-bold text-slate-800">Return Date:</span> <span id="inv-return-date">24/11/2026</span></div>
                    </div>
                </div>
            </div>

            <!-- Red Dividing Line -->
            <div class="h-0.5 bg-[#c00000] w-full"></div>

            <!-- 2. Invoice To & Cashier / Staff Box -->
            <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 flex flex-col sm:flex-row items-start justify-between gap-4 text-xs">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">INVOICE TO:</span>
                    <span id="inv-customer-name" class="font-black text-slate-900 text-sm uppercase block mt-0.5">DEPIT OF WEEK</span>
                    <span id="inv-customer-phone" class="font-mono text-slate-600 text-xs block mt-0.5">0634531454</span>
                </div>

                <div class="sm:text-right">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">CASHIER / STAFF:</span>
                    <span id="inv-cashier-name" class="font-black text-slate-900 text-xs block mt-0.5">Ridwaan</span>
                    <span id="inv-branch-name" class="text-slate-500 text-[11px] block mt-0.5">Boorama Branch</span>
                </div>
            </div>

            <!-- 3. Line Items Table (Red solid header) -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#c00000] text-white uppercase font-black text-[10px] tracking-wider">
                        <tr>
                            <th class="py-2.5 px-3 w-12 text-center">#</th>
                            <th class="py-2.5 px-4">ITEM DESCRIPTION</th>
                            <th class="py-2.5 px-3 text-center">QTY</th>
                            <th class="py-2.5 px-4 text-right">PRICE</th>
                            <th class="py-2.5 px-4 text-right">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody id="inv-items-table-body" class="divide-y divide-slate-100 font-medium">
                        <!-- Populated by JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- 4. Stamp, Note & Financial Totals Breakdown -->
            <div class="flex flex-col sm:flex-row items-start justify-between gap-6 pt-2">
                <!-- Left: Stamp & Notes -->
                <div class="space-y-4 max-w-sm">
                    <!-- Status Stamp (PARTIAL or PAID) -->
                    <div id="inv-stamp-container">
                        <!-- Populated dynamically -->
                    </div>

                    <!-- Note -->
                    <div id="inv-notes-container" class="text-xs text-slate-600 font-medium bg-slate-50 p-2.5 rounded-lg border border-slate-200/60">
                        <span class="font-bold text-slate-800">Note:</span> <span id="inv-notes-text">Retail Sale (Return Date: 24/11/2026)</span>
                    </div>
                </div>

                <!-- Right: Financial Breakdown -->
                <div class="w-full sm:w-64 space-y-1.5 text-xs">
                    <div class="flex justify-between items-center text-slate-600 font-medium">
                        <span>Subtotal</span>
                        <span id="inv-subtotal" class="font-bold text-slate-900">$17.00</span>
                    </div>

                    <div id="inv-discount-row" class="flex justify-between items-center text-slate-600 font-medium hidden">
                        <span>Discount</span>
                        <span id="inv-discount" class="font-bold text-amber-600">-$0.00</span>
                    </div>

                    <!-- Red Block for Total -->
                    <div class="flex justify-between items-center bg-[#c00000] text-white px-3 py-2 rounded-lg font-black text-sm shadow-xs">
                        <span>Total</span>
                        <span id="inv-total-sale">$17.00</span>
                    </div>

                    <div class="flex justify-between items-center text-emerald-600 font-bold">
                        <span>Paid</span>
                        <span id="inv-paid-amount">$0.00</span>
                    </div>

                    <div class="flex justify-between items-center text-slate-800 font-bold">
                        <span>Balance</span>
                        <span id="inv-balance" class="font-black text-red-600 text-sm">$17.00</span>
                    </div>

                    <div class="flex justify-between items-center text-slate-500 text-[11px] font-medium pt-1 border-t border-slate-100">
                        <span>Return Date</span>
                        <span id="inv-summary-return-date" class="font-bold text-slate-800">24/11/2026</span>
                    </div>
                </div>
            </div>

            <!-- 5. Signatures (Customer & Authorized) -->
            <div class="flex items-center justify-between pt-14 border-t border-slate-100">
                <div class="text-center w-52">
                    <div class="border-t border-slate-400 pt-1 text-[10px] uppercase font-bold text-slate-500 tracking-wider">
                        CUSTOMER SIGNATURE
                    </div>
                </div>

                <div class="text-center w-52">
                    <div class="border-t border-slate-400 pt-1 text-[10px] uppercase font-bold text-slate-500 tracking-wider">
                        AUTHORIZED SIGNATURE
                    </div>
                </div>
            </div>

            <!-- 6. Footer Information Line -->
            <div class="border-t-2 border-[#c00000] pt-4 text-center text-[10px] text-slate-500 space-y-1">
                <div>
                    <span class="font-black text-[#c00000]">CAALAMI ELECTRONICS</span> • Hoyga Qalabka Elektaroonigga ah &amp; Qalabka Casriga ah
                </div>
                <div>
                    Tel: <strong>634547497</strong> / <strong>0634515815</strong> &nbsp;|&nbsp; 
                    <span class="font-bold text-emerald-600">ZAAD: 509900</span> &nbsp;|&nbsp; 
                    <span class="font-bold text-amber-600">eDahab: 721118</span>
                </div>
                <div class="text-slate-400">
                    <strong class="text-slate-700">Mahadsanid</strong> • Powered By Taam Solutions
                </div>
            </div>

        </div>
    </div>

</main>

<!-- Print Stylesheet: prints ONLY the invoice card cleanly on standard A4 page -->
<style>
@media print {
    body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    aside, header, #main-sidebar, nav, .no-print, #sales-orders-list-view {
        display: none !important;
    }
    #sales-invoice-view {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    #invoice-printable-card {
        border: none !important;
        box-shadow: none !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 15mm !important;
    }
    @page {
        size: A4 portrait;
        margin: 10mm;
    }
}
</style>

<script>
// Master Invoice Registry from PHP
const invoiceRegistry = <?= json_encode($invoiceRegistry, JSON_UNESCAPED_UNICODE) ?>;

// Open the Invoice View
function openInvoiceView(txKey) {
    const data = invoiceRegistry[txKey];
    if (!data) {
        alert("Invoice #" + txKey + " not found.");
        return;
    }

    // Populate metadata
    document.getElementById('inv-num').textContent = data.txId || ('#' + data.cleanTx);
    document.getElementById('inv-date').textContent = data.date || '—';
    document.getElementById('inv-payment-method').textContent = data.paymentMethod || 'Cash On Hand';
    document.getElementById('inv-return-date').textContent = data.returnDate || '—';

    document.getElementById('inv-customer-name').textContent = data.customerName || 'Walk-In Customer';
    document.getElementById('inv-customer-phone').textContent = data.customerPhone || 'N/A';
    document.getElementById('inv-cashier-name').textContent = data.cashier || 'Admin';
    document.getElementById('inv-branch-name').textContent = data.branch || 'Boorama Branch';

    // Populate line items
    const tbody = document.getElementById('inv-items-table-body');
    tbody.innerHTML = '';
    if (data.items && data.items.length > 0) {
        data.items.forEach((item, idx) => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50/60 transition-colors';
            tr.innerHTML = `
                <td class="py-2.5 px-3 text-center text-slate-500 font-bold">${idx + 1}</td>
                <td class="py-2.5 px-4 font-bold text-slate-900">${esc(item.name)}</td>
                <td class="py-2.5 px-3 text-center text-slate-700">${item.qty} ${esc(item.unit)}</td>
                <td class="py-2.5 px-4 text-right text-slate-800 font-bold">$${parseFloat(item.soldPrice).toFixed(2)}</td>
                <td class="py-2.5 px-4 text-right font-black text-slate-900">$${parseFloat(item.totalPrice).toFixed(2)}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Populate Stamp (PARTIAL or PAID)
    const stampContainer = document.getElementById('inv-stamp-container');
    const isPaid = (data.balance <= 0);
    if (isPaid) {
        stampContainer.innerHTML = `
            <div class="inline-flex items-center gap-1.5 transform -rotate-3 border-2 border-dashed border-emerald-500 text-emerald-600 bg-emerald-50/60 rounded-xl px-4 py-2 font-black text-xs uppercase tracking-widest shadow-2xs">
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                <span>PAID</span>
            </div>
        `;
    } else {
        stampContainer.innerHTML = `
            <div class="inline-flex items-center gap-1.5 transform -rotate-3 border-2 border-dashed border-amber-500 text-amber-600 bg-amber-50/60 rounded-xl px-4 py-2 font-black text-xs uppercase tracking-widest shadow-2xs">
                <i data-lucide="clock" class="w-4 h-4"></i>
                <span>PARTIAL</span>
            </div>
        `;
    }

    // Populate Notes
    const noteText = data.notes || (data.returnDate ? `Retail Sale (Return Date: ${data.returnDate})` : 'Sales Order Invoice');
    document.getElementById('inv-notes-text').textContent = noteText;

    // Populate Totals
    document.getElementById('inv-subtotal').textContent = '$' + parseFloat(data.subtotal).toFixed(2);
    document.getElementById('inv-total-sale').textContent = '$' + parseFloat(data.totalSale).toFixed(2);
    document.getElementById('inv-paid-amount').textContent = '$' + parseFloat(data.paidAmount).toFixed(2);
    document.getElementById('inv-balance').textContent = '$' + parseFloat(data.balance).toFixed(2);
    document.getElementById('inv-summary-return-date').textContent = data.returnDate || '—';

    // Show/hide discount row
    const discountRow = document.getElementById('inv-discount-row');
    if (data.discount > 0) {
        document.getElementById('inv-discount').textContent = '-$' + parseFloat(data.discount).toFixed(2);
        discountRow.classList.remove('hidden');
    } else {
        discountRow.classList.add('hidden');
    }

    // Toggle views
    document.getElementById('sales-orders-list-view').classList.add('hidden');
    document.getElementById('sales-invoice-view').classList.remove('hidden');

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
    lucide.createIcons();
}

// Close Invoice View & Return to Table List
function closeInvoiceView() {
    document.getElementById('sales-invoice-view').classList.add('hidden');
    document.getElementById('sales-orders-list-view').classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
    lucide.createIcons();
}

// Print Invoice
function printInvoiceSheet() {
    window.print();
}

// Client-side Table Sort helper
function sortTable(n) {
    const table = document.getElementById("sales-items-table");
    let rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    switching = true;
    dir = "asc";
    while (switching) {
        switching = false;
        rows = table.rows;
        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("TD")[n];
            y = rows[i + 1].getElementsByTagName("TD")[n];
            if (!x || !y) continue;
            let xVal = x.textContent.trim().replace('$', '').replace('#', '');
            let yVal = y.textContent.trim().replace('$', '').replace('#', '');
            let xNum = parseFloat(xVal);
            let yNum = parseFloat(yVal);

            if (!isNaN(xNum) && !isNaN(yNum)) {
                if (dir == "asc") {
                    if (xNum > yNum) { shouldSwitch = true; break; }
                } else if (dir == "desc") {
                    if (xNum < yNum) { shouldSwitch = true; break; }
                }
            } else {
                if (dir == "asc") {
                    if (xVal.toLowerCase() > yVal.toLowerCase()) { shouldSwitch = true; break; }
                } else if (dir == "desc") {
                    if (xVal.toLowerCase() < yVal.toLowerCase()) { shouldSwitch = true; break; }
                }
            }
        }
        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchcount++;
        } else {
            if (switchcount == 0 && dir == "asc") {
                dir = "desc";
                switching = true;
            }
        }
    }
}

// Helper escape function
function esc(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
