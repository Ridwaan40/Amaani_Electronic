<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// ── Filter parameters ────────────────────────────────────────────────────────
$filterCustomer  = trim($_GET['customer']   ?? '');
$filterPhone     = trim($_GET['phone']      ?? '');
$filterProduct   = trim($_GET['product']    ?? '');
$filterMethod    = trim($_GET['method']     ?? '');
$filterCashier   = trim($_GET['cashier']    ?? '');
$filterStartDate = trim($_GET['start_date'] ?? '');
$filterEndDate   = trim($_GET['end_date']   ?? '');
$perPage         = intval($_GET['per_page'] ?? 25);
$viewMode        = trim($_GET['view']       ?? 'item'); // 'item' | 'invoice'
if ($perPage <= 0) $perPage = 25;

// ── Build query ──────────────────────────────────────────────────────────────
$where  = ["1=1", "si.sale_type != 'Returned'"];
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
if (!empty($filterCustomer)) {
    $where[]  = "s.customer_name LIKE ?";
    $params[] = "%$filterCustomer%";
}
if (!empty($filterPhone)) {
    $where[]  = "s.phone LIKE ?";
    $params[] = "%$filterPhone%";
}
if (!empty($filterMethod)) {
    $where[]  = "s.payment_method = ?";
    $params[] = $filterMethod;
}
if (!empty($filterCashier)) {
    $where[]  = "si.created_by = ?";
    $params[] = $filterCashier;
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

// Cashiers list for filter dropdown
$cashierRows = $pdo->query("SELECT DISTINCT created_by FROM sale_items WHERE created_by IS NOT NULL AND created_by <> '' ORDER BY created_by")->fetchAll(PDO::FETCH_COLUMN);

$totalItemsCount = count($items);

// ── Build Transaction Master Map for JavaScript ──────────────────────────────
$transMap = [];
foreach ($items as $it) {
    $txId = $it['transaction_id'] ?? ('#' . $it['id']);
    $txKey = str_replace('#', '', $txId);

    if (!isset($transMap[$txKey])) {
        $transMap[$txKey] = [
            'txId'          => $txId,
            'cleanTx'       => $txKey,
            'customerName'  => $it['customer_name']  ?? 'Walk-In Customer',
            'customerPhone' => $it['customer_phone'] ?? 'N/A',
            'cashier'       => $it['created_by']      ?? 'Admin',
            'branch'        => $it['branch']          ?? 'Boorama Branch',
            'date'          => !empty($it['sale_date']) ? $it['sale_date'] : (!empty($it['created_at']) ? substr($it['created_at'], 0, 10) : date('Y-m-d')),
            'createdAt'     => $it['created_at']      ?? date('Y-m-d H:i:s'),
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

    $transMap[$txKey]['items'][] = [
        'id'         => $it['id'],
        'name'       => $it['product_name'] ?? 'Product',
        'unit'       => $it['unit']         ?? 'Pcs',
        'qty'        => intval($it['qty']   ?? 1),
        'soldPrice'  => floatval($it['sold_price']  ?? 0),
        'totalPrice' => floatval($it['total_price'] ?? 0),
        'saleType'   => $it['sale_type']    ?? 'Retail',
        'txId'       => $txId
    ];
}
?>

<main class="flex-1 min-w-0 space-y-4">
    <!-- ── 1. PAGE HEADER ────────────────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-[#c00000] flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
                    <i data-lucide="receipt" class="w-4 h-4 text-blue-600"></i>
                </span>
                <span>Sales Payment Transaction List</span>
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                Detailed ledger of customer checkout payments, credit settlements, refunds, and invoice traceability.
            </p>
        </div>

        <a href="create_sales.php?branch=<?= urlencode($currentBranch) ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-black shadow-sm transition-all active:scale-95 shrink-0 cursor-pointer">
            <i data-lucide="shopping-cart" class="w-4 h-4"></i>
            <span>Create New Sale</span>
        </a>
    </div>

    <!-- ── 2. FILTER SALES TRANSACTIONS CARD (Matching Screenshot 2) ─── -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2 text-xs font-black text-slate-900">
                <i data-lucide="filter" class="w-4 h-4 text-blue-600"></i>
                <span>Filter Sales Transactions</span>
            </div>
            <a href="invoices.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-1 text-[11px] font-bold text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                <span>Clear Filters</span>
            </a>
        </div>

        <form method="GET" action="invoices.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end text-xs">
            <input type="hidden" name="branch" value="<?= htmlspecialchars($currentBranch) ?>">

            <!-- Filter By Customer -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">FILTER BY CUSTOMER</label>
                <div class="relative">
                    <i data-lucide="user" class="w-4 h-4 absolute left-3 top-2.5 text-slate-400"></i>
                    <input type="text" name="customer" value="<?= htmlspecialchars($filterCustomer) ?>" placeholder="Customer name..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 placeholder-slate-400 focus:border-[#c00000] focus:ring-1 focus:ring-[#c00000] outline-none">
                </div>
            </div>

            <!-- Filter By Phone -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">FILTER BY PHONE</label>
                <div class="relative">
                    <i data-lucide="phone" class="w-4 h-4 absolute left-3 top-2.5 text-slate-400"></i>
                    <input type="text" name="phone" value="<?= htmlspecialchars($filterPhone) ?>" placeholder="Phone number..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 placeholder-slate-400 focus:border-[#c00000] focus:ring-1 focus:ring-[#c00000] outline-none">
                </div>
            </div>

            <!-- Filter By Product -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">FILTER BY PRODUCT</label>
                <div class="relative">
                    <i data-lucide="package" class="w-4 h-4 absolute left-3 top-2.5 text-slate-400"></i>
                    <input type="text" name="product" value="<?= htmlspecialchars($filterProduct) ?>" placeholder="Product name or keyword..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 placeholder-slate-400 focus:border-[#c00000] focus:ring-1 focus:ring-[#c00000] outline-none">
                </div>
            </div>

            <!-- Payment Method -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">PAYMENT METHOD</label>
                <div class="relative">
                    <i data-lucide="credit-card" class="w-4 h-4 absolute left-3 top-2.5 text-slate-400"></i>
                    <select name="method" class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:border-[#c00000] focus:ring-1 focus:ring-[#c00000] outline-none appearance-none cursor-pointer">
                        <option value="">All Methods</option>
                        <?php foreach (['Cash', 'Cash On Hand', 'ZAAD', 'eDahab', 'Cheque', 'Credit'] as $m): ?>
                        <option value="<?= $m ?>" <?= $filterMethod === $m ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Cashier / Staff -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">CASHIER / STAFF</label>
                <div class="relative">
                    <i data-lucide="user-check" class="w-4 h-4 absolute left-3 top-2.5 text-slate-400"></i>
                    <select name="cashier" class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:border-[#c00000] focus:ring-1 focus:ring-[#c00000] outline-none appearance-none cursor-pointer">
                        <option value="">All Cashiers</option>
                        <?php foreach ($cashierRows as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $filterCashier === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Start Date -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">START DATE</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($filterStartDate) ?>" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:border-[#c00000] focus:ring-1 focus:ring-[#c00000] outline-none cursor-pointer">
            </div>

            <!-- End Date -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">END DATE</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($filterEndDate) ?>" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:border-[#c00000] focus:ring-1 focus:ring-[#c00000] outline-none cursor-pointer">
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-black text-xs shadow-xs transition-colors cursor-pointer">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>Search</span>
                </button>
                <a href="invoices.php?branch=<?= urlencode($currentBranch) ?>" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition-colors shrink-0 cursor-pointer" title="Reset Filters">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- ── 3. SOLD ITEMS & TRANSACTIONS LEDGER TABLE CARD ─────────────── -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col">
        <!-- Card Header -->
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 text-xs font-black text-slate-900">
                    <i data-lucide="table" class="w-4 h-4 text-blue-600"></i>
                    <span>Sold Items &amp; Transactions Ledger</span>
                </div>
                <p class="text-[11px] text-slate-400 font-medium mt-0.5">
                    Displaying every sold product on its own row. Click transaction # to inspect invoice details.
                </p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <!-- Per Item Sold / Per Invoice toggle -->
                <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#c00000] text-white text-xs font-bold shadow-xs">
                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                    <span>Per Item Sold</span>
                </button>
                <a href="sales_items.php?branch=<?= urlencode($currentBranch) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-xs font-bold transition-colors">
                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                    <span>Per Invoice</span>
                </a>

                <!-- Quick Search Input -->
                <input type="text" id="ledgerQuickSearch" placeholder="Quick search records..." oninput="filterLedgerTable(this.value)" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-xs text-slate-800 placeholder-slate-400 outline-none focus:bg-white focus:border-[#c00000] w-44">
            </div>
        </div>

        <!-- Show per page dropdown -->
        <div class="px-4 py-2 bg-white border-b border-slate-100 text-xs text-slate-600 flex items-center gap-1.5 font-medium">
            <span>Show per page : Show</span>
            <select onchange="location.href='invoices.php?branch=<?= urlencode($currentBranch) ?>&per_page=' + this.value" class="px-2 py-0.5 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-800 outline-none cursor-pointer">
                <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10</option>
                <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25</option>
                <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>100</option>
            </select>
            <span>records</span>
        </div>

        <!-- Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap" id="invoices-ledger-table">
                <!-- Red text sortable headers (Matching Screenshot 2) -->
                <thead class="bg-white text-[#c00000] uppercase font-black text-[10px] tracking-wider border-b border-slate-200 select-none">
                    <tr>
                        <th class="py-3 px-3 cursor-pointer hover:bg-slate-50" onclick="sortInvoicesTable(0)">REF ⇅</th>
                        <th class="py-3 px-4 cursor-pointer hover:bg-slate-50" onclick="sortInvoicesTable(1)">CUSTOMER NAME ⇅</th>
                        <th class="py-3 px-3 cursor-pointer hover:bg-slate-50" onclick="sortInvoicesTable(2)">PHONE ⇅</th>
                        <th class="py-3 px-4 cursor-pointer hover:bg-slate-50" onclick="sortInvoicesTable(3)">PRODUCT SOLD ⇅</th>
                        <th class="py-3 px-3 text-center cursor-pointer hover:bg-slate-50" onclick="sortInvoicesTable(4)">QTY ⇅</th>
                        <th class="py-3 px-3 text-right cursor-pointer hover:bg-slate-50" onclick="sortInvoicesTable(5)">UNIT PRICE ⇅</th>
                        <th class="py-3 px-4 text-right cursor-pointer hover:bg-slate-50" onclick="sortInvoicesTable(6)">ITEM TOTAL ⇅</th>
                        <th class="py-3 px-3 cursor-pointer hover:bg-slate-50" onclick="sortInvoicesTable(7)">METHOD ⇅</th>
                        <th class="py-3 px-4 cursor-pointer hover:bg-slate-50" onclick="sortInvoicesTable(8)">CASHIER ⇅</th>
                        <th class="py-3 px-3 cursor-pointer hover:bg-slate-50" onclick="sortInvoicesTable(9)">DATE ⇅</th>
                        <th class="py-3 px-4 text-center">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium" id="invoices-tbody">
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="11" class="py-12 text-center text-slate-400 font-bold">
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
                        $totalPrice = floatval($it['total_price'] ?? ($soldPrice * $qty));
                        $txId = $it['transaction_id'] ?? ('#' . $id);
                        $cleanTx = str_replace('#', '', $txId);
                        $createdBy = $it['created_by'] ?? 'Ridwaan';
                        $createdAt = $it['created_at'] ?? '';
                        $displayDate = $createdAt ? date('d-m-Y H:i', strtotime($createdAt)) : date('d-m-Y');
                        $custName = $it['customer_name'] ?? 'Walk-In-Customer';
                        $custPhone = $it['customer_phone'] ?? 'N/A';
                        $payMethod = $it['payment_method'] ?? 'Cash On Hand';
                        $notes = $it['sale_notes'] ?? '';
                    ?>
                    <tr class="hover:bg-slate-50/80 transition-colors" id="sale-row-<?= $id ?>">
                        <!-- REF -->
                        <td class="py-3 px-3 font-semibold">
                            <span class="inline-block bg-slate-100 text-slate-800 px-2 py-0.5 rounded text-[11px] font-mono font-bold border border-slate-200 cursor-pointer hover:bg-red-50 hover:text-red-700" onclick="openSoldProductsModal('<?= htmlspecialchars($cleanTx, ENT_QUOTES) ?>')">
                                <?= htmlspecialchars($txId) ?>
                            </span>
                        </td>

                        <!-- CUSTOMER NAME -->
                        <td class="py-3 px-4 font-bold text-slate-900">
                            <?= htmlspecialchars($custName) ?>
                        </td>

                        <!-- PHONE -->
                        <td class="py-3 px-3 font-mono text-slate-600 text-[11px]">
                            <?php if ($custPhone && $custPhone !== 'N/A'): ?>
                                <span class="flex items-center gap-1"><i data-lucide="phone" class="w-3 h-3 text-slate-400"></i><?= htmlspecialchars($custPhone) ?></span>
                            <?php else: ?>
                                <span class="text-slate-400">N/A</span>
                            <?php endif; ?>
                        </td>

                        <!-- PRODUCT SOLD -->
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100 shadow-2xs">
                                    <i data-lucide="package" class="w-3.5 h-3.5"></i>
                                </span>
                                <div>
                                    <span class="font-bold text-slate-900 block"><?= htmlspecialchars($name) ?></span>
                                    <?php if (!empty($notes)): ?>
                                        <span class="text-[10px] text-slate-400 block"><?= htmlspecialchars(substr($notes, 0, 30)) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>

                        <!-- QTY -->
                        <td class="py-3 px-3 text-center">
                            <span class="inline-block bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-[11px] font-bold border border-slate-200">
                                <?= $qty ?> <?= htmlspecialchars($unit) ?>
                            </span>
                        </td>

                        <!-- UNIT PRICE -->
                        <td class="py-3 px-3 text-right font-bold text-slate-800">
                            $<?= number_format($soldPrice, 2) ?>
                        </td>

                        <!-- ITEM TOTAL -->
                        <td class="py-3 px-4 text-right font-black text-blue-600">
                            $<?= number_format($totalPrice, 2) ?>
                        </td>

                        <!-- METHOD -->
                        <td class="py-3 px-3">
                            <span class="inline-block bg-slate-100 text-slate-700 px-2 py-0.5 rounded-full text-[11px] font-semibold border border-slate-200">
                                <?= htmlspecialchars($payMethod) ?>
                            </span>
                        </td>

                        <!-- CASHIER -->
                        <td class="py-3 px-4 text-slate-700 text-[11px]">
                            <span class="inline-flex items-center gap-1 font-semibold text-blue-600">
                                <i data-lucide="user-check" class="w-3 h-3"></i>
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

                        <!-- ACTIONS: 5 ICONS (Matching Screenshot 2) -->
                        <td class="py-3 px-4 text-center">
                            <div class="inline-flex items-center gap-1">
                                <!-- 1. View Sold Product -->
                                <button type="button" onclick="openSoldProductsModal('<?= htmlspecialchars($cleanTx, ENT_QUOTES) ?>')" class="p-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200 transition-all active:scale-95 cursor-pointer" title="View Sold Products">
                                    <i data-lucide="package-search" class="w-3.5 h-3.5"></i>
                                </button>

                                <!-- 2. View / Print Invoice -->
                                <button type="button" onclick="printInvoiceDirect('<?= htmlspecialchars($cleanTx, ENT_QUOTES) ?>')" class="p-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 transition-all active:scale-95 cursor-pointer" title="Print Invoice">
                                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                </button>

                                <!-- 3. Edit -->
                                <button type="button" onclick="openEditSaleModal(<?= $id ?>, '<?= htmlspecialchars(addslashes($name), ENT_QUOTES) ?>', <?= $qty ?>, <?= $soldPrice ?>)" class="p-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 border border-amber-200 transition-all active:scale-95 cursor-pointer" title="Edit Sale Item">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                </button>

                                <!-- 4. Return Sales Items -->
                                <button type="button" onclick="returnSaleItemRow(<?= $id ?>, '<?= htmlspecialchars(addslashes($name), ENT_QUOTES) ?>', <?= $qty ?>)" class="p-1.5 rounded-lg bg-orange-50 hover:bg-orange-100 text-orange-600 border border-orange-200 transition-all active:scale-95 cursor-pointer" title="Return Sales Item">
                                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                </button>

                                <!-- 5. Delete -->
                                <button type="button" onclick="deleteSaleItemRow(<?= $id ?>, '<?= htmlspecialchars(addslashes($name), ENT_QUOTES) ?>')" class="p-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 transition-all active:scale-95 cursor-pointer" title="Delete">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
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
                Showing 1 to <?= $totalItemsCount ?> of <?= $totalItemsCount ?> records
            </span>

            <div class="flex items-center gap-1.5">
                <button type="button" class="px-3 py-1 rounded-lg border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50 cursor-pointer">
                    Previous
                </button>
                <button type="button" class="px-3 py-1 rounded-lg bg-green-600 text-white text-xs font-bold shadow-xs cursor-pointer">
                    1
                </button>
                <button type="button" class="px-3 py-1 rounded-lg border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50 cursor-pointer">
                    Next
                </button>
            </div>
        </div>
    </div>
</main>


<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL 1: SOLD PRODUCTS MODAL (Exact match to Screenshot 3)
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="sold-products-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" style="background:rgba(15,23,42,0.65); backdrop-filter:blur(2px)">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden animate-scale-in">
        <!-- Red Header -->
        <div class="bg-[#c00000] px-5 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-md bg-white/20 text-white flex items-center justify-center font-bold text-xs">
                    M
                </span>
                <h2 id="sp-modal-title" class="text-sm font-black text-white">Sold Products • Transaction #1</h2>
            </div>
            <button type="button" onclick="closeSoldProductsModal()" class="text-white/80 hover:text-white p-1 rounded-lg transition-colors cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- 4 Info Pills Row -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 p-4 border-b border-slate-100 bg-slate-50/50">
            <!-- Customer -->
            <div class="bg-white rounded-xl p-2.5 border border-slate-200 shadow-2xs">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block flex items-center gap-1 mb-0.5">
                    <i data-lucide="user" class="w-3 h-3 text-slate-400"></i> CUSTOMER
                </span>
                <span id="sp-cust-name" class="font-black text-slate-900 text-xs uppercase block truncate">DAAHIR CADUUR</span>
            </div>

            <!-- Phone -->
            <div class="bg-white rounded-xl p-2.5 border border-slate-200 shadow-2xs">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block flex items-center gap-1 mb-0.5">
                    <i data-lucide="phone" class="w-3 h-3 text-slate-400"></i> PHONE
                </span>
                <span id="sp-cust-phone" class="font-mono text-slate-700 text-xs block">N/A</span>
            </div>

            <!-- Date & Time -->
            <div class="bg-white rounded-xl p-2.5 border border-slate-200 shadow-2xs">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block flex items-center gap-1 mb-0.5">
                    <i data-lucide="clock" class="w-3 h-3 text-slate-400"></i> DATE &amp; TIME
                </span>
                <span id="sp-date-time" class="font-bold text-slate-700 text-xs block">10 Oct, 2026 08:35 AM</span>
            </div>

            <!-- Total Items -->
            <div class="bg-white rounded-xl p-2.5 border border-slate-200 shadow-2xs">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block flex items-center gap-1 mb-0.5">
                    <i data-lucide="package" class="w-3 h-3 text-slate-400"></i> TOTAL ITEMS
                </span>
                <span id="sp-total-items-badge" class="font-black text-blue-600 text-xs block">1 items (1 pcs)</span>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="overflow-x-auto px-4 py-3">
            <table class="w-full text-left text-xs">
                <thead class="text-slate-400 uppercase font-black text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="pb-2 px-2 w-8">#</th>
                        <th class="pb-2 px-2">Product Description</th>
                        <th class="pb-2 px-2 text-center">Quantity Sold</th>
                        <th class="pb-2 px-2 text-right">Unit Price</th>
                        <th class="pb-2 px-2 text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody id="sp-items-tbody" class="divide-y divide-slate-100 font-medium">
                    <!-- Populated dynamically -->
                </tbody>
            </table>
        </div>

        <!-- Financial Summary Bar (5 Columns) -->
        <div class="grid grid-cols-5 gap-2 px-4 py-3 bg-slate-50 border-t border-slate-200 text-center text-xs">
            <div class="bg-white p-2 rounded-xl border border-slate-200 shadow-2xs">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">SUBTOTAL</span>
                <span id="sp-sum-subtotal" class="font-black text-slate-800 text-xs block mt-0.5">$14.00</span>
            </div>
            <div class="bg-white p-2 rounded-xl border border-slate-200 shadow-2xs">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">DISCOUNT</span>
                <span id="sp-sum-discount" class="font-black text-emerald-600 text-xs block mt-0.5">$0.00</span>
            </div>
            <div class="bg-white p-2 rounded-xl border border-slate-200 shadow-2xs">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">NET AMOUNT</span>
                <span id="sp-sum-net" class="font-black text-blue-600 text-xs block mt-0.5">$14.00</span>
            </div>
            <div class="bg-white p-2 rounded-xl border border-slate-200 shadow-2xs">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">PAID</span>
                <span id="sp-sum-paid" class="font-black text-emerald-600 text-xs block mt-0.5">$14.00</span>
            </div>
            <div class="bg-white p-2 rounded-xl border border-slate-200 shadow-2xs">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">BALANCE</span>
                <span id="sp-sum-balance" class="font-black text-slate-700 text-xs block mt-0.5">$0.00</span>
            </div>
        </div>

        <!-- Footer Buttons -->
        <div class="px-5 py-3 border-t border-slate-200 flex items-center justify-between bg-white">
            <button type="button" id="sp-print-btn" onclick="printInvoiceFromCurrentModal()" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs shadow-xs transition-all active:scale-95 cursor-pointer">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Print / View Invoice</span>
            </button>

            <button type="button" onclick="closeSoldProductsModal()" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs transition-colors cursor-pointer">
                Close
            </button>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL 2: EDIT SALE ITEM MODAL
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="edit-sale-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" style="background:rgba(15,23,42,0.65); backdrop-filter:blur(2px)">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden animate-scale-in">
        <div class="bg-amber-500 text-white px-5 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="pencil" class="w-4 h-4"></i>
                <h3 class="text-sm font-black">Edit Sale Item</h3>
            </div>
            <button type="button" onclick="closeEditSaleModal()" class="text-white/80 hover:text-white cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form id="edit-sale-form" onsubmit="submitEditSaleForm(event)" class="p-5 space-y-3 text-xs">
            <input type="hidden" id="edit-item-id">

            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Product Name</label>
                <input type="text" id="edit-product-name" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 outline-none focus:border-amber-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Quantity</label>
                    <input type="number" id="edit-qty" min="1" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Unit Price ($)</label>
                    <input type="number" step="0.01" min="0" id="edit-price" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 outline-none focus:border-amber-500">
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="closeEditSaleModal()" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs shadow-xs cursor-pointer">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════════════════
     PRINTABLE INVOICE TEMPLATE (Exact match to Screenshot 1)
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="invoice-print-container" class="hidden">
    <div id="invoice-sheet" class="p-8 max-w-2xl mx-auto bg-white text-slate-900 font-sans space-y-6">
        <!-- Header -->
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3">
                <img src="assets/images/amaani-logo.jpg" alt="Amaani Electronics" class="h-12 w-auto object-contain">
                <div>
                    <h1 class="text-base font-black text-[#c00000] uppercase tracking-wide">AMAANI ELECTRONICS</h1>
                    <p class="text-xs text-slate-600 font-medium">Hoyga Qalabka Elektaroonigga ah &amp; Qalabka Casriga ah</p>
                    <p class="text-[11px] text-slate-400">Sisibta, Borama, Somaliland</p>
                    <p class="text-[11px] text-slate-400">Tel: 634547497</p>
                </div>
            </div>

            <div class="text-right">
                <h2 class="text-2xl font-black text-[#c00000] tracking-widest uppercase">INVOICE</h2>
                <div class="text-xs text-slate-600 space-y-0.5 mt-1 font-medium">
                    <div><span class="font-bold">Invoice #:</span> <span id="print-inv-num">1</span></div>
                    <div><span class="font-bold">Date:</span> <span id="print-inv-date">10/10/2026</span></div>
                    <div><span class="font-bold">Payment:</span> <span id="print-inv-method">Cash</span></div>
                </div>
            </div>
        </div>

        <!-- Red Horizontal Divider -->
        <div class="h-0.5 bg-[#c00000] w-full"></div>

        <!-- Invoice To & Cashier / Staff Box -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 flex items-start justify-between text-xs">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">INVOICE TO:</span>
                <span id="print-inv-customer" class="font-black text-slate-900 text-sm uppercase block mt-0.5">DAAHIR CADUUR</span>
            </div>
            <div class="text-right">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">CASHIER / STAFF:</span>
                <span id="print-inv-cashier" class="font-black text-slate-900 text-xs block mt-0.5">A/rahman Ismail Ali</span>
                <span id="print-inv-branch" class="text-slate-500 text-[11px] block mt-0.5">Boorama Branch</span>
            </div>
        </div>

        <!-- Table -->
        <table class="w-full text-left text-xs">
            <thead class="bg-[#c00000] text-white uppercase font-black text-[10px]">
                <tr>
                    <th class="py-2 px-3 w-10 text-center">#</th>
                    <th class="py-2 px-4">ITEM DESCRIPTION</th>
                    <th class="py-2 px-3 text-center">QTY</th>
                    <th class="py-2 px-4 text-right">PRICE</th>
                    <th class="py-2 px-4 text-right">TOTAL</th>
                </tr>
            </thead>
            <tbody id="print-inv-items-body" class="divide-y divide-slate-100 font-medium">
                <!-- Injected via JS -->
            </tbody>
        </table>

        <!-- Totals & Stamp -->
        <div class="flex items-start justify-between pt-2">
            <div>
                <!-- Stamp -->
                <div id="print-stamp-box">
                    <div class="inline-flex items-center gap-1.5 border-2 border-dashed border-emerald-500 text-emerald-600 bg-emerald-50/50 rounded-xl px-4 py-2 font-black text-sm uppercase tracking-widest transform -rotate-3">
                        ✓ PAID
                    </div>
                </div>

                <div id="print-inv-note" class="text-xs text-slate-500 mt-4 max-w-xs">
                    Note: Repair Ticket #REP-20261010-22B3 - Spare parts used for des
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="w-56 space-y-1.5 text-xs">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal</span>
                    <span id="print-subtotal" class="font-bold text-slate-900">$14.00</span>
                </div>
                <div class="flex justify-between items-center bg-[#c00000] text-white px-3 py-1.5 rounded-lg font-black text-sm">
                    <span>Total</span>
                    <span id="print-total">$14.00</span>
                </div>
                <div class="flex justify-between text-emerald-600 font-bold">
                    <span>Paid</span>
                    <span id="print-paid">$14.00</span>
                </div>
                <div class="flex justify-between text-slate-800 font-bold">
                    <span>Balance</span>
                    <span id="print-balance">$0.00</span>
                </div>
            </div>
        </div>

        <!-- Signatures -->
        <div class="flex items-center justify-between pt-12 border-t border-slate-200">
            <div class="text-center w-48">
                <div class="border-t border-slate-400 pt-1 text-[10px] uppercase font-bold text-slate-500">
                    CUSTOMER SIGNATURE
                </div>
            </div>
            <div class="text-center w-48">
                <div class="border-t border-slate-400 pt-1 text-[10px] uppercase font-bold text-slate-500">
                    AUTHORIZED SIGNATURE
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-t-2 border-[#c00000] pt-3 text-center text-[10px] text-slate-500 space-y-0.5">
            <div>
                <span class="font-black text-[#c00000]">AMAANI ELECTRONICS</span> • Hoyga Qalabka Elektaroonigga ah &amp; Qalabka Casriga ah
            </div>
            <div>
                Tel: 634547497 / 0634515815 &nbsp;|&nbsp; 
                <span class="font-bold text-emerald-600">ZAAD: 509900</span> &nbsp;|&nbsp; 
                <span class="font-bold text-amber-600">eDahab: 721118</span>
            </div>
            <div class="text-slate-400">
                Mahadsanid • Powered By Taam Solutions
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #invoice-print-container, #invoice-print-container * {
        visibility: visible !important;
    }
    #invoice-print-container {
        display: block !important;
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
    }
    @page {
        size: A4 portrait;
        margin: 10mm;
    }
}
</style>

<script>
// Transaction registry passed from PHP
const transMap = <?= json_encode($transMap, JSON_UNESCAPED_UNICODE) ?>;
let activeTxKey = null;

// ── 1. MODAL: SOLD PRODUCTS (Screenshot 3) ──────────────────────────────────
function openSoldProductsModal(txKey) {
    activeTxKey = txKey;
    const data = transMap[txKey];
    if (!data) {
        alert("Transaction record #" + txKey + " not found.");
        return;
    }

    // Set Modal Header & Pills
    document.getElementById('sp-modal-title').textContent = "Sold Products • Transaction " + data.txId;
    document.getElementById('sp-cust-name').textContent = data.customerName || 'Walk-In Customer';
    document.getElementById('sp-cust-phone').textContent = data.customerPhone || 'N/A';
    
    // Format Date & Time
    let dateStr = data.createdAt || data.date;
    try {
        const d = new Date(dateStr);
        if (!isNaN(d.getTime())) {
            dateStr = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        }
    } catch(e) {}
    document.getElementById('sp-date-time').textContent = dateStr;

    // Total Items count
    let totalPcs = 0;
    const tbody = document.getElementById('sp-items-tbody');
    tbody.innerHTML = '';

    if (data.items && data.items.length > 0) {
        data.items.forEach((item, idx) => {
            totalPcs += item.qty;
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50/70 transition-colors';
            tr.innerHTML = `
                <td class="py-2.5 px-2 text-slate-500 font-bold">${idx + 1}</td>
                <td class="py-2.5 px-2">
                    <span class="font-bold text-slate-900 block">${esc(item.name)}</span>
                    <span class="text-[10px] text-slate-400 block">Item Ref: ${esc(item.txId)}</span>
                </td>
                <td class="py-2.5 px-2 text-center">
                    <span class="inline-block bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-[11px] font-bold border border-slate-200">
                        ${item.qty} ${esc(item.unit)}
                    </span>
                </td>
                <td class="py-2.5 px-2 text-right font-bold text-slate-700">$${parseFloat(item.soldPrice).toFixed(2)}</td>
                <td class="py-2.5 px-2 text-right font-black text-blue-600">$${parseFloat(item.totalPrice).toFixed(2)}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    document.getElementById('sp-total-items-badge').textContent = `${data.items.length} items (${totalPcs} pcs)`;

    // Summary Totals
    document.getElementById('sp-sum-subtotal').textContent = '$' + parseFloat(data.subtotal).toFixed(2);
    document.getElementById('sp-sum-discount').textContent = '$' + parseFloat(data.discount).toFixed(2);
    document.getElementById('sp-sum-net').textContent = '$' + parseFloat(data.totalSale).toFixed(2);
    document.getElementById('sp-sum-paid').textContent = '$' + parseFloat(data.paidAmount).toFixed(2);
    document.getElementById('sp-sum-balance').textContent = '$' + parseFloat(data.balance).toFixed(2);

    // Unhide modal
    document.getElementById('sold-products-modal').classList.remove('hidden');
    lucide.createIcons();
}

function closeSoldProductsModal() {
    document.getElementById('sold-products-modal').classList.add('hidden');
}

function printInvoiceFromCurrentModal() {
    if (!activeTxKey) return;
    closeSoldProductsModal();
    printInvoiceDirect(activeTxKey);
}


// ── 2. VIEW / PRINT INVOICE (Screenshot 1) ──────────────────────────────────
function printInvoiceDirect(txKey) {
    const data = transMap[txKey];
    if (!data) {
        alert("Transaction record #" + txKey + " not found.");
        return;
    }

    document.getElementById('print-inv-num').textContent = data.cleanTx;
    document.getElementById('print-inv-date').textContent = data.date;
    document.getElementById('print-inv-method').textContent = data.paymentMethod || 'Cash';
    document.getElementById('print-inv-customer').textContent = data.customerName || 'DAAHIR CADUUR';
    document.getElementById('print-inv-cashier').textContent = data.cashier || 'Admin';
    document.getElementById('print-inv-branch').textContent = data.branch || 'Boorama Branch';

    // Populate items
    const tbody = document.getElementById('print-inv-items-body');
    tbody.innerHTML = '';
    if (data.items && data.items.length > 0) {
        data.items.forEach((item, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="py-2 px-3 text-center text-slate-500 font-bold">${idx + 1}</td>
                <td class="py-2 px-4 font-bold text-slate-900">${esc(item.name)}</td>
                <td class="py-2 px-3 text-center">${item.qty} ${esc(item.unit)}</td>
                <td class="py-2 px-4 text-right font-bold">$${parseFloat(item.soldPrice).toFixed(2)}</td>
                <td class="py-2 px-4 text-right font-black">$${parseFloat(item.totalPrice).toFixed(2)}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Stamp
    const stampBox = document.getElementById('print-stamp-box');
    if (data.balance <= 0) {
        stampBox.innerHTML = `
            <div class="inline-flex items-center gap-1.5 border-2 border-dashed border-emerald-500 text-emerald-600 bg-emerald-50/50 rounded-xl px-4 py-2 font-black text-sm uppercase tracking-widest transform -rotate-3">
                ✓ PAID
            </div>
        `;
    } else {
        stampBox.innerHTML = `
            <div class="inline-flex items-center gap-1.5 border-2 border-dashed border-amber-500 text-amber-600 bg-amber-50/50 rounded-xl px-4 py-2 font-black text-sm uppercase tracking-widest transform -rotate-3">
                🕒 PARTIAL
            </div>
        `;
    }

    // Note
    document.getElementById('print-inv-note').textContent = data.notes ? ('Note: ' + data.notes) : 'Note: Sales Transaction Receipt';

    // Totals
    document.getElementById('print-subtotal').textContent = '$' + parseFloat(data.subtotal).toFixed(2);
    document.getElementById('print-total').textContent = '$' + parseFloat(data.totalSale).toFixed(2);
    document.getElementById('print-paid').textContent = '$' + parseFloat(data.paidAmount).toFixed(2);
    document.getElementById('print-balance').textContent = '$' + parseFloat(data.balance).toFixed(2);

    // Show printable sheet and trigger print
    const printContainer = document.getElementById('invoice-print-container');
    printContainer.classList.remove('hidden');
    window.print();
    setTimeout(() => {
        printContainer.classList.add('hidden');
    }, 1000);
}


// ── 3. EDIT SALE ITEM ───────────────────────────────────────────────────────
function openEditSaleModal(id, name, qty, price) {
    document.getElementById('edit-item-id').value = id;
    document.getElementById('edit-product-name').value = name;
    document.getElementById('edit-qty').value = qty;
    document.getElementById('edit-price').value = price;

    document.getElementById('edit-sale-modal').classList.remove('hidden');
    lucide.createIcons();
}

function closeEditSaleModal() {
    document.getElementById('edit-sale-modal').classList.add('hidden');
}

function submitEditSaleForm(e) {
    e.preventDefault();
    const id = document.getElementById('edit-item-id').value;
    const productName = document.getElementById('edit-product-name').value.trim();
    const qty = parseInt(document.getElementById('edit-qty').value);
    const soldPrice = parseFloat(document.getElementById('edit-price').value);

    fetch('api/edit_sale_item.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, product_name: productName, qty, sold_price: soldPrice })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            closeEditSaleModal();
            location.reload();
        } else {
            alert('Error updating sale item: ' + (res.error || 'Unknown error'));
        }
    })
    .catch(() => alert('Network error while updating sale item.'));
}


// ── 4. RETURN SALE ITEM ─────────────────────────────────────────────────────
function returnSaleItemRow(id, name, qty) {
    if (!confirm(`Return ${qty} qty of "${name}"?\nThis will restore the product stock into inventory.`)) {
        return;
    }

    fetch('api/return_sale_item.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, qty })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            // Remove row from DOM immediately — no page reload needed
            const row = document.getElementById('sale-row-' + id);
            if (row) {
                row.style.transition = 'opacity 0.3s';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }
            // Show brief toast/alert
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-5 right-5 z-50 bg-emerald-600 text-white text-sm font-bold px-5 py-3 rounded-xl shadow-lg';
            toast.textContent = `✅ Returned ${res.qty_returned} qty of "${res.product}". Stock restored.`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3500);
        } else {
            alert('Error returning item: ' + (res.error || 'Failed to return.'));
        }
    })
    .catch(() => alert('Network error while returning item.'));
}


// ── 5. DELETE SALE ITEM ─────────────────────────────────────────────────────
function deleteSaleItemRow(id, name) {
    if (!confirm(`Are you sure you want to permanently delete "${name}" from this transaction?\nThis cannot be undone.`)) {
        return;
    }

    fetch('api/delete_sale_item.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            location.reload();
        } else {
            alert('Error deleting item: ' + (res.error || 'Failed to delete.'));
        }
    })
    .catch(() => alert('Network error while deleting item.'));
}


// ── Client-side Quick Search Filter ─────────────────────────────────────────
function filterLedgerTable(query) {
    const q = query.toLowerCase();
    const rows = document.querySelectorAll('#invoices-tbody tr');
    rows.forEach(tr => {
        const text = tr.textContent.toLowerCase();
        tr.style.display = text.includes(q) ? '' : 'none';
    });
}

// ── Client-side Table Sort ──────────────────────────────────────────────────
function sortInvoicesTable(colIdx) {
    const table = document.getElementById("invoices-ledger-table");
    let rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    switching = true;
    dir = "asc";
    while (switching) {
        switching = false;
        rows = table.rows;
        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("TD")[colIdx];
            y = rows[i + 1].getElementsByTagName("TD")[colIdx];
            if (!x || !y) continue;
            let xVal = x.textContent.trim().replace('$', '').replace('#', '');
            let yVal = y.textContent.trim().replace('$', '').replace('#', '');
            let xNum = parseFloat(xVal);
            let yNum = parseFloat(yVal);

            if (!isNaN(xNum) && !isNaN(yNum)) {
                if (dir == "asc" && xNum > yNum) { shouldSwitch = true; break; }
                if (dir == "desc" && xNum < yNum) { shouldSwitch = true; break; }
            } else {
                if (dir == "asc" && xVal.toLowerCase() > yVal.toLowerCase()) { shouldSwitch = true; break; }
                if (dir == "desc" && xVal.toLowerCase() < yVal.toLowerCase()) { shouldSwitch = true; break; }
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

function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
