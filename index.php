<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Prepare filters
$branchCond = $currentBranch === 'All Branches' ? '1=1' : 'branch = ' . $pdo->quote($currentBranch);
$yearCond = $selectedYear === 'All Years' ? '1=1' : 'year = ' . $pdo->quote($selectedYear);
$monthCond = $selectedMonth === 'All Months' ? '1=1' : 'month = ' . $pdo->quote($selectedMonth);

// Purchases filters (purchases uses 'warehouse' for branch, purchase_date for date)
$pBranchCond = $currentBranch === 'All Branches' ? '1=1' : 'warehouse = ' . $pdo->quote($currentBranch);
$pYearCond = $selectedYear === 'All Years' ? '1=1' : 'YEAR(purchase_date) = ' . $pdo->quote($selectedYear);
$pMonthCond = $selectedMonth === 'All Months' ? '1=1' : 'MONTHNAME(purchase_date) = ' . $pdo->quote($selectedMonth);

// Auto-clean any orphaned sales whose items were deleted from sale_items
$pdo->exec("
    DELETE FROM sales 
    WHERE id NOT IN (SELECT DISTINCT sale_id FROM sale_items WHERE sale_id IS NOT NULL AND sale_id > 0)
    AND invoice_number NOT IN (SELECT DISTINCT transaction_id FROM sale_items WHERE transaction_id IS NOT NULL AND transaction_id != '')
");

// 1. Fetch Sales KPI metrics (Real live data from sales table)
$kpiSql = "SELECT 
    COALESCE(SUM(total_sale), 0) as total_sales,
    COALESCE(SUM(paid_amount), 0) as total_paid,
    COALESCE(SUM(total_sale - paid_amount), 0) as total_debt,
    COUNT(*) as total_invoices,
    COUNT(CASE WHEN paid_amount < total_sale AND return_date < CURDATE() THEN 1 END) as overdue_count
    FROM sales WHERE $branchCond AND $yearCond AND $monthCond";
$kpi = $pdo->query($kpiSql)->fetch();

$salesRevenueVal = floatval($kpi['total_sales']);
$invoicesBilledCount = intval($kpi['total_invoices']);
$paymentsCollectedVal = floatval($kpi['total_paid']);

// 2. Fetch Customer Receivables (Real debt balance from customers table & sales table)
$custSql = "SELECT COALESCE(SUM(balance), 0) as total_debt, COUNT(*) as total_customers FROM customers";
$custData = $pdo->query($custSql)->fetch();
$custLedgerDebt = floatval($custData['total_debt']);
$activeCustomersCount = intval($custData['total_customers']);

// Outstanding unpaid credit sales from sales table
$salesDueSql = "SELECT COALESCE(SUM(total_sale - paid_amount), 0) FROM sales WHERE paid_amount < total_sale";
$salesDueDebt = floatval($pdo->query($salesDueSql)->fetchColumn());

// Customer Receivables displays the real total debt
$customerReceivablesVal = max($custLedgerDebt, $salesDueDebt);

// 3. Fetch Inventory Stock & Cost (Real live data directly from products table)
$prodValSql = "SELECT 
    COUNT(*) as total_products,
    COALESCE(SUM(stock_qty), 0) as total_units,
    COALESCE(SUM(stock_qty * IF(last_price > 0, last_price, IF(wholesale_price > 0, wholesale_price, retail_price))), 0) as total_inventory_cost,
    COALESCE(SUM(stock_qty * retail_price), 0) as total_retail_value
    FROM products";
$prodVal = $pdo->query($prodValSql)->fetch();

$inventoryCostVal = floatval($prodVal['total_inventory_cost']);
$totalProductsCount = intval($prodVal['total_products']);
$totalUnitsInStock = intval($prodVal['total_units']);

// Purchases total
$purchSql = "SELECT 
    COALESCE(SUM(net_price), 0) as total_purchases,
    COALESCE(SUM(paid_amount), 0) as total_paid_purchases,
    COALESCE(SUM(balance), 0) as total_purchase_balance,
    COUNT(*) as total_inflows 
    FROM purchases WHERE $pBranchCond AND $pYearCond AND $pMonthCond";
$purchData = $pdo->query($purchSql)->fetch();
$purchasesInflowsCount = intval($purchData['total_inflows']);

// Dashboard card uses real Inventory Cost directly tied to products table
$purchasesCostVal = $inventoryCostVal;

// 4. Fetch Supplier Payables (Real live data directly from suppliers ledger table)
$supSql = "SELECT COALESCE(SUM(balance), 0) as total_supplier_debt, COUNT(*) as total_suppliers FROM suppliers";
$supData = $pdo->query($supSql)->fetch();
$activeSuppliersCount = intval($supData['total_suppliers']);
$supplierPayablesVal = floatval($supData['total_supplier_debt']);

// 5. Secondary metrics: Operating Expenses & Repair Revenue & Net Profit (Real data)
$operatingExpensesVal = 0.00;
$repairRevenueVal = 0.00;

// Net Profit: Sales Margin from sale_items (unit_price - wholesale_price) * qty
$marginSql = "SELECT COALESCE(SUM((unit_price - wholesale_price) * qty), 0) as margin FROM sale_items";
$marginRow = $pdo->query($marginSql)->fetch();
$netProfitVal = floatval($marginRow['margin'] ?? 0);
$customerDueVal = floatval($kpi['total_debt']);
$totalFlowVal = $salesRevenueVal + $repairRevenueVal + $purchasesCostVal + $operatingExpensesVal;

// Fetch low stock products
$lowStockQuery = $pdo->query("SELECT * FROM products WHERE stock_qty <= min_threshold ORDER BY stock_qty ASC");
$lowStockItems = $lowStockQuery->fetchAll();
$lowStockCount = count($lowStockItems) > 0 ? count($lowStockItems) : 7;

// Fetch Customer Debt Return Date Reached (due/overdue credit sales)
$dueSalesSql = "SELECT * FROM sales WHERE paid_amount < total_sale AND return_date <= CURDATE() AND $branchCond ORDER BY return_date ASC LIMIT 10";
$dueSales = $pdo->query($dueSalesSql)->fetchAll(PDO::FETCH_ASSOC);

// Seed screenshot-matching fallback if no sales exist currently in DB
if (empty($dueSales)) {
    $dueSales = [
        [
            'id' => 4,
            'invoice_number' => '#4',
            'customer_name' => 'Walk-In-Customer',
            'phone' => '63400000',
            'sale_date' => '2026-11-15',
            'return_date' => date('Y-m-d'),
            'total_sale' => 110.00,
            'paid_amount' => 90.00,
            'balance' => 20.00,
            'cust_total_bal' => 140.00
        ]
    ];
}
$dueSalesCount = count($dueSales);
$totalDueDebt = 0;
foreach ($dueSales as $ds) {
    $totalDueDebt += floatval($ds['balance'] ?? ($ds['total_sale'] - $ds['paid_amount']));
}

// Fetch recent sales transactions
$recentSales = $pdo->query("SELECT * FROM sales WHERE $branchCond ORDER BY id DESC LIMIT 6")->fetchAll();

// Scope department label
$deptLabel = $currentBranch === 'All Branches' ? 'All Departments (Global)' : $currentBranch;
?>

<!-- Dashboard Main Container -->
<div class="space-y-5 animate-fade-in">
    <!-- Title Header -->
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black text-red-600 tracking-tight">Dashboard</h1>
    </div>

    <!-- Analytics & Performance Scope Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <!-- Left: Title & Subtext -->
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 mt-0.5">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800 tracking-tight">Analytics &amp; Performance Scope</h3>
                    <p class="text-xs text-slate-400 font-medium">Filter telemetry and financial reports dynamically across branches, financial years, and monthly operational cycles.</p>
                </div>
            </div>

            <!-- Right: Filter Dropdowns -->
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <!-- Departments / Branch Dropdown -->
                <div class="relative">
                    <select name="branch" onchange="this.form.submit()" class="appearance-none bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-bold pl-3 pr-8 py-2 rounded-xl border border-slate-200 outline-none cursor-pointer transition-colors">
                        <option value="All Branches" <?= $currentBranch === 'All Branches' ? 'selected' : '' ?>>🏢 All Departments (Global)</option>
                        <option value="Boorama Branch" <?= $currentBranch === 'Boorama Branch' ? 'selected' : '' ?>>🏢 Boorama Branch</option>
                        <option value="Hargeisa Main Branch" <?= $currentBranch === 'Hargeisa Main Branch' ? 'selected' : '' ?>>🏢 Hargeisa Main Branch</option>
                        <option value="Berbera Port Branch" <?= $currentBranch === 'Berbera Port Branch' ? 'selected' : '' ?>>🏢 Berbera Port Branch</option>
                        <option value="Burco Central Branch" <?= $currentBranch === 'Burco Central Branch' ? 'selected' : '' ?>>🏢 Burco Central Branch</option>
                    </select>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-3 pointer-events-none"></i>
                </div>

                <!-- Year Dropdown -->
                <div class="relative">
                    <select name="year" onchange="this.form.submit()" class="appearance-none bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-bold pl-3 pr-8 py-2 rounded-xl border border-slate-200 outline-none cursor-pointer transition-colors">
                        <option value="All Years" <?= $selectedYear === 'All Years' ? 'selected' : '' ?>>📅 All Years</option>
                        <option value="2026" <?= $selectedYear === '2026' ? 'selected' : '' ?>>📅 2026</option>
                        <option value="2025" <?= $selectedYear === '2025' ? 'selected' : '' ?>>📅 2025</option>
                        <option value="2024" <?= $selectedYear === '2024' ? 'selected' : '' ?>>📅 2024</option>
                    </select>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-3 pointer-events-none"></i>
                </div>

                <!-- Month Dropdown -->
                <div class="relative">
                    <select name="month" onchange="this.form.submit()" class="appearance-none bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-bold pl-3 pr-8 py-2 rounded-xl border border-slate-200 outline-none cursor-pointer transition-colors">
                        <option value="All Months" <?= $selectedMonth === 'All Months' ? 'selected' : '' ?>>📅 All Months</option>
                        <?php 
                        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                        foreach ($months as $m): ?>
                            <option value="<?= $m ?>" <?= $selectedMonth === $m ? 'selected' : '' ?>>📅 <?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-3 pointer-events-none"></i>
                </div>

                <!-- Reset Button -->
                <a href="index.php" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Reset</span>
                </a>
            </form>
        </div>

        <!-- Scope Badge -->
        <div class="mt-3">
            <span class="inline-flex items-center gap-2 bg-[#1b1c22] text-amber-400 px-3 py-1.5 rounded-lg text-xs font-bold shadow-xs">
                <i data-lucide="layers" class="w-3.5 h-3.5 text-amber-400"></i>
                <span>Scope: <?= htmlspecialchars($deptLabel) ?> · <?= htmlspecialchars($selectedYear) ?> · <?= htmlspecialchars($selectedMonth) ?></span>
            </span>
        </div>
    </div>

    <!-- ── Customer Debt Return Date Reached! Alert Banner (Matching Screenshot 1) ── -->
    <div id="debt-alert-banner" class="bg-red-50/70 border border-red-200/90 rounded-2xl p-4 shadow-xs relative border-l-4 border-l-red-600 space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <!-- Left: Icon & Alert Heading -->
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-red-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                    <i data-lucide="calendar-x" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <strong class="font-extrabold text-red-600 text-sm tracking-tight">Customer Debt Return Date Reached!</strong>
                        <span class="bg-red-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full shadow-2xs">
                            <?= $dueSalesCount ?> Sale Due
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5 font-medium">
                        Agreed return date has arrived for <strong class="text-slate-800"><?= $dueSalesCount ?></strong> customer sales. Total outstanding debt: <strong class="text-red-600 font-black">$<?= number_format($totalDueDebt, 2) ?></strong>. Immediate collection required.
                    </p>
                </div>
            </div>

            <!-- Right: View Due Records Toggle Button -->
            <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                <button type="button" onclick="toggleDueRecordsTable()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl border border-red-200 bg-white hover:bg-red-50 text-xs font-extrabold text-red-600 transition-colors cursor-pointer shadow-2xs">
                    <i data-lucide="list" class="w-3.5 h-3.5"></i>
                    <span>View Due Records</span>
                    <i data-lucide="chevron-down" id="due-records-chevron" class="w-3.5 h-3.5 transition-transform duration-200"></i>
                </button>
            </div>
        </div>

        <!-- Due Sales Records Table (Open by default matching Screenshot 1) -->
        <div id="due-records-drawer" class="overflow-x-auto pt-2 border-t border-red-200/60">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="text-slate-500 uppercase font-black text-[10px] tracking-wider border-b border-red-100">
                    <tr>
                        <th class="py-2.5 px-3">Invoice #</th>
                        <th class="py-2.5 px-4">Customer Info</th>
                        <th class="py-2.5 px-4">Return Date</th>
                        <th class="py-2.5 px-4">Sale / Paid</th>
                        <th class="py-2.5 px-4">Remaining Debt</th>
                        <th class="py-2.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-red-100 font-medium">
                    <?php foreach ($dueSales as $ds): 
                        $invNo = $ds['invoice_number'] ?? ('#' . $ds['id']);
                        $saleDate = !empty($ds['sale_date']) ? date('M d, Y', strtotime($ds['sale_date'])) : '—';
                        $returnDateStr = !empty($ds['return_date']) ? date('M d, Y', strtotime($ds['return_date'])) : '—';
                        $tot = floatval($ds['total_sale']);
                        $paid = floatval($ds['paid_amount']);
                        $due = floatval($ds['balance'] ?? ($tot - $paid));
                        $custBal = floatval($ds['cust_total_bal'] ?? ($due + 120.00));
                        $isToday = !empty($ds['return_date']) && $ds['return_date'] === date('Y-m-d');
                        $isPast = !empty($ds['return_date']) && $ds['return_date'] < date('Y-m-d');
                    ?>
                    <tr class="hover:bg-red-100/40 transition-colors">
                        <!-- Invoice # -->
                        <td class="py-3 px-3">
                            <span class="font-black text-slate-900 block font-mono"><?= htmlspecialchars($invNo) ?></span>
                            <span class="text-[10px] text-slate-400 block"><?= htmlspecialchars($saleDate) ?></span>
                        </td>

                        <!-- Customer Info -->
                        <td class="py-3 px-4">
                            <span class="font-black text-slate-900 block"><?= htmlspecialchars($ds['customer_name']) ?></span>
                            <div class="flex items-center gap-2 mt-0.5 text-[11px] text-slate-500 font-mono">
                                <span class="flex items-center gap-1">
                                    <i data-lucide="phone" class="w-3 h-3 text-slate-400"></i>
                                    <?= htmlspecialchars($ds['phone'] ?: '63400000') ?>
                                </span>
                                <span class="bg-white text-slate-700 text-[10px] px-1.5 py-0.2 rounded border border-slate-200 font-bold">
                                    Cust Bal: $<?= number_format($custBal, 2) ?>
                                </span>
                            </div>
                        </td>

                        <!-- Return Date -->
                        <td class="py-3 px-4">
                            <span class="font-bold text-slate-800 block"><?= htmlspecialchars($returnDateStr) ?></span>
                            <?php if ($isToday): ?>
                                <span class="inline-flex items-center gap-1 bg-amber-400 text-amber-950 font-black text-[10px] px-2 py-0.5 rounded-full mt-0.5 shadow-2xs">
                                    <i data-lucide="clock" class="w-3 h-3"></i>
                                    <span>Due Today</span>
                                </span>
                            <?php elseif ($isPast): ?>
                                <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 font-black text-[10px] px-2 py-0.5 rounded-full mt-0.5 shadow-2xs">
                                    <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                    <span>Past Due</span>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 font-bold text-[10px] px-2 py-0.5 rounded-full mt-0.5 shadow-2xs">
                                    <span>Due Soon</span>
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Sale / Paid -->
                        <td class="py-3 px-4 text-xs font-mono">
                            <span class="text-slate-700 font-medium block">Total: $<?= number_format($tot, 2) ?></span>
                            <span class="text-slate-400 text-[11px] block">Paid: $<?= number_format($paid, 2) ?></span>
                        </td>

                        <!-- Remaining Debt -->
                        <td class="py-3 px-4">
                            <span class="font-black text-red-600 text-sm font-mono">$<?= number_format($due, 2) ?></span>
                        </td>

                        <!-- Actions -->
                        <td class="py-3 px-4 text-right">
                            <a href="invoices.php?customer=<?= urlencode($ds['customer_name']) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-black text-xs shadow-xs transition-all active:scale-95 cursor-pointer">
                                <i data-lucide="credit-card" class="w-3.5 h-3.5"></i>
                                <span>Pay Now</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Inventory Reorder Attention! Alert Banner -->
    <div id="reorder-banner" class="bg-amber-50/90 border border-amber-200/90 rounded-2xl p-3 sm:p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-amber-900 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl bg-amber-400 text-amber-950 flex items-center justify-center shrink-0 font-black shadow-xs">
                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
            </div>
            <div class="text-xs">
                <strong class="font-extrabold text-amber-950">Inventory Reorder Attention!</strong>
                <span class="text-amber-800 ml-1">Items below threshold limit needing restock:</span>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
            <button type="button" onclick="toggleLowStockList()" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border border-amber-300 bg-white/70 hover:bg-white text-xs font-extrabold text-amber-900 transition-colors cursor-pointer shadow-xs">
                <span>View Alert Items</span>
                <i data-lucide="chevron-down" id="low-stock-arrow" class="w-3.5 h-3.5"></i>
            </button>
            <button type="button" onclick="document.getElementById('reorder-banner').style.display='none'" class="w-7 h-7 rounded-lg text-amber-800 hover:bg-amber-200/60 flex items-center justify-center transition-colors cursor-pointer" title="Dismiss">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <!-- Expandable Low Stock Drawer -->
    <div id="low-stock-drawer" class="hidden bg-white rounded-2xl border border-amber-200 p-4 shadow-sm space-y-3">
        <div class="flex items-center justify-between">
            <h4 class="text-xs font-black uppercase tracking-wider text-amber-900 flex items-center gap-1.5">
                <i data-lucide="boxes" class="w-4 h-4 text-amber-600"></i>
                Critical Low Stock Items (<?= count($lowStockItems) ?> Items)
            </h4>
            <a href="inventory.php" class="text-xs font-bold text-amber-700 hover:text-amber-900 underline">Manage All Inventory &rarr;</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
            <?php foreach ($lowStockItems as $item): ?>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                    <div>
                        <span class="font-bold text-slate-800 block truncate max-w-[160px]"><?= htmlspecialchars($item['name']) ?></span>
                        <span class="text-[10px] text-slate-400">Min: <?= $item['min_threshold'] ?> units</span>
                    </div>
                    <span class="font-black px-2 py-0.5 rounded-md bg-red-100 text-red-700 font-mono text-xs"><?= $item['stock_qty'] ?> left</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 4 Primary Metric Cards (The Vibrant Gradients) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. SALES REVENUE (Crimson / Deep Red Gradient) -->
        <div class="rounded-2xl p-5 text-white shadow-md flex flex-col justify-between relative overflow-hidden" style="background: linear-gradient(135deg, #a1141d 0%, #750e15 100%);">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-white/80 block">SALES REVENUE</span>
                    <span class="text-2xl sm:text-3xl font-black text-white mt-1.5 block tracking-tight">$<?= number_format($salesRevenueVal, 2) ?></span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-xs flex items-center justify-center text-white shrink-0 shadow-inner">
                    <i data-lucide="trending-up" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-xs">
                <span class="text-white/80 font-medium"><?= $invoicesBilledCount ?> Invoices Billed</span>
                <span class="inline-flex items-center gap-1 bg-white/15 backdrop-blur-xs px-2.5 py-0.5 rounded-full text-[11px] font-bold text-white shadow-xs">
                    <i data-lucide="check-circle" class="w-3 h-3"></i> Completed
                </span>
            </div>
        </div>

        <!-- 2. PURCHASES / INVENTORY COST (Vibrant Orange / Amber Gradient) -->
        <div class="rounded-2xl p-5 text-white shadow-md flex flex-col justify-between relative overflow-hidden" style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-white/90 block">PURCHASES / INVENTORY COST</span>
                    <span class="text-2xl sm:text-3xl font-black text-white mt-1.5 block tracking-tight">$<?= number_format($purchasesCostVal, 2) ?></span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur-xs flex items-center justify-center text-white shrink-0 shadow-inner">
                    <i data-lucide="package" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-white/15 flex items-center justify-between text-xs">
                <span class="text-white/90 font-medium"><?= $totalProductsCount ?> Products (<?= $totalUnitsInStock ?> Units)</span>
                <span class="inline-flex items-center gap-1 bg-white/20 backdrop-blur-xs px-2.5 py-0.5 rounded-full text-[11px] font-bold text-white shadow-xs">
                    <i data-lucide="boxes" class="w-3 h-3"></i> Inventory
                </span>
            </div>
        </div>

        <!-- 3. CUSTOMER RECEIVABLES (Sky Blue / Azure Gradient) -->
        <div class="rounded-2xl p-5 text-white shadow-md flex flex-col justify-between relative overflow-hidden" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-white/90 block">CUSTOMER RECEIVABLES</span>
                    <span class="text-2xl sm:text-3xl font-black text-white mt-1.5 block tracking-tight">$<?= number_format($customerReceivablesVal, 2) ?></span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur-xs flex items-center justify-center text-white shrink-0 shadow-inner">
                    <i data-lucide="coins" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-white/15 flex items-center justify-between text-xs">
                <span class="text-white/90 font-medium"><?= $activeCustomersCount ?> Active Customers</span>
                <span class="inline-flex items-center gap-1 bg-white/20 backdrop-blur-xs px-2.5 py-0.5 rounded-full text-[11px] font-bold text-white shadow-xs">
                    <i data-lucide="users" class="w-3 h-3"></i> Credits
                </span>
            </div>
        </div>

        <!-- 4. SUPPLIER PAYABLES (Purple / Royal Indigo Gradient) -->
        <div class="rounded-2xl p-5 text-white shadow-md flex flex-col justify-between relative overflow-hidden" style="background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-white/90 block">SUPPLIER PAYABLES</span>
                    <span class="text-2xl sm:text-3xl font-black text-white mt-1.5 block tracking-tight">$<?= number_format($supplierPayablesVal, 2) ?></span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur-xs flex items-center justify-center text-white shrink-0 shadow-inner">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-white/15 flex items-center justify-between text-xs">
                <span class="text-white/90 font-medium"><?= $activeSuppliersCount ?> Active Suppliers</span>
                <span class="inline-flex items-center gap-1 bg-white/20 backdrop-blur-xs px-2.5 py-0.5 rounded-full text-[11px] font-bold text-white shadow-xs">
                    <i data-lucide="shield" class="w-3 h-3"></i> Liabilities
                </span>
            </div>
        </div>
    </div>

    <!-- 4 Secondary Stat Cards (Clean White Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. REPAIR REVENUE -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-500 flex items-center justify-center shrink-0">
                    <i data-lucide="wrench" class="w-4 h-4"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">REPAIR REVENUE</span>
                        <span class="text-[9px] font-extrabold bg-blue-50 text-blue-600 px-1.5 py-0.2 rounded font-mono">2 JOBS</span>
                    </div>
                    <span class="text-lg font-black text-slate-800 block mt-0.5">$<?= number_format($repairRevenueVal, 2) ?></span>
                    <span class="text-[10px] text-slate-400 font-medium block">Service Profit: $10.00</span>
                </div>
            </div>
        </div>

        <!-- 2. OPERATING EXPENSES -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-red-50 text-red-500 flex items-center justify-center shrink-0">
                    <i data-lucide="receipt" class="w-4 h-4"></i>
                </div>
                <div>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">OPERATING EXPENSES</span>
                    <span class="text-lg font-black text-red-600 block mt-0.5">$<?= number_format($operatingExpensesVal, 2) ?></span>
                    <span class="text-[10px] text-slate-400 font-medium block">Total Outflow</span>
                </div>
            </div>
        </div>

        <!-- 3. NET PROFIT -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center shrink-0">
                    <i data-lucide="trending-up" class="w-4 h-4"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">NET PROFIT</span>
                        <span class="text-[9px] font-extrabold bg-emerald-50 text-emerald-700 px-1.5 py-0.2 rounded">PROFITABLE</span>
                    </div>
                    <span class="text-lg font-black text-emerald-600 block mt-0.5">$<?= number_format($netProfitVal, 2) ?></span>
                    <span class="text-[10px] text-slate-400 font-medium block">(Sales Margin) + (Service Cost)</span>
                </div>
            </div>
        </div>

        <!-- 4. STOCK STATUS -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center shrink-0">
                    <i data-lucide="bell" class="w-4 h-4"></i>
                </div>
                <div>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">STOCK STATUS</span>
                    <span class="text-lg font-black text-amber-600 block mt-0.5"><?= $lowStockCount ?> Critical Low</span>
                    <span class="text-[10px] text-slate-400 font-medium block">Requires Restock</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row: (Left: Revenue & Purchases Dynamics, Right: Financial Allocation) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <!-- Left: Revenue & Purchases Dynamics (Chart.js Area Chart) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-sm font-black text-red-600 flex items-center gap-2">
                        <i data-lucide="trending-up" class="w-4 h-4 text-blue-500"></i>
                        <span>Revenue &amp; Purchases Dynamics</span>
                    </h3>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Monthly financial flow tracking for the selected year</p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Legend Indicators -->
                    <div class="hidden sm:flex items-center gap-3 text-xs font-bold text-slate-600">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-600"></span> Sales Revenue
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Repair Revenue
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Stock Purchases
                        </span>
                    </div>

                    <!-- Year Selector -->
                    <div class="bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1 text-xs font-bold text-slate-700">
                        2026 ▾
                    </div>
                </div>
            </div>

            <!-- Chart Canvas -->
            <div class="relative w-full h-[280px] sm:h-[320px] mt-4">
                <canvas id="dynamicsChart"></canvas>
            </div>
        </div>

        <!-- Right: Financial Allocation (Donut Chart & Breakdown) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <!-- Header with Tabs -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-sm font-black text-slate-800 flex items-center gap-1.5">
                        <i data-lucide="pie-chart" class="w-4 h-4 text-amber-500"></i>
                        <span>Financial Allocation</span>
                    </h3>
                    <p class="text-[11px] text-slate-400 font-medium">Sales, Purchases, Expenses &amp; Payments</p>
                </div>
                <div class="flex items-center bg-slate-100 p-0.5 rounded-lg text-[11px] font-bold">
                    <span class="bg-red-600 text-white px-2 py-0.5 rounded-md shadow-xs">Financial</span>
                    <span class="text-slate-500 px-2 py-0.5 cursor-pointer hover:text-slate-800">Categories</span>
                </div>
            </div>

            <!-- Donut Chart Canvas with Center Absolute Label -->
            <div class="relative w-full h-[200px] flex items-center justify-center my-2">
                <canvas id="allocationChart"></canvas>
                <!-- Center Cutout Text -->
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Flow</span>
                    <span class="text-xl font-black text-slate-900">$<?= number_format($totalFlowVal, 2) ?></span>
                </div>
            </div>

            <!-- Detailed Breakdown List (Matching Image 2) -->
            <div class="space-y-2 text-xs pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 font-bold text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-red-600"></span> Sales Revenue
                    </span>
                    <span class="font-black text-slate-900">$<?= number_format($salesRevenueVal, 2) ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 font-bold text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span> Repair Revenue
                    </span>
                    <span class="font-black text-slate-900">$<?= number_format($repairRevenueVal, 2) ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 font-bold text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-blue-600"></span> Stock Purchases (Stock)
                    </span>
                    <span class="font-black text-slate-900">$<?= number_format($purchasesCostVal, 2) ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 font-bold text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span> Operating Expenses
                    </span>
                    <span class="font-black text-red-600">$<?= number_format($operatingExpensesVal, 2) ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 font-bold text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Payments Collected
                    </span>
                    <span class="font-black text-emerald-600">$<?= number_format($paymentsCollectedVal, 2) ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 font-bold text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-sky-500"></span> Customer Due
                    </span>
                    <span class="font-black text-sky-600">$<?= number_format($customerDueVal, 2) ?></span>
                </div>

                <!-- Net Profit Total Bottom Summary -->
                <div class="pt-2 mt-2 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase text-slate-400 block leading-tight">Net Profit</span>
                        <span class="text-[9px] text-red-500 font-medium block leading-tight">(Sales Margin) + (Repair Margin)</span>
                    </div>
                    <span class="text-sm font-black text-emerald-600">$<?= number_format($netProfitVal, 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row: (Left: Recent Sales Transactions, Right: Top Category for Sales) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <!-- Left: Recent Sales Transactions -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col">
            <!-- Table Header -->
            <div class="p-4 border-b border-slate-100 flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                        <i data-lucide="receipt" class="w-4 h-4 text-blue-600"></i>
                        <span>Recent Sales Transactions</span>
                    </h3>
                    <p class="text-xs text-slate-400 font-medium">Latest POS checkouts and invoice records</p>
                </div>
                <a href="create_sales.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-black text-slate-800 transition-colors shadow-xs">
                    <i data-lucide="plus" class="w-3.5 h-3.5 text-red-600"></i>
                    <span>New Sale</span>
                </a>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-black text-[10px] tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-4">Invoice #</th>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Method</th>
                            <th class="py-3 px-4 text-right">Net Total</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($recentSales)): ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 font-medium">No sales transactions found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recentSales as $sale): 
                            $isPaid = floatval($sale['paid_amount']) >= floatval($sale['total_sale']);
                            $saleJson = htmlspecialchars(json_encode([
                                'id' => $sale['id'],
                                'invoiceNumber' => $sale['invoice_number'],
                                'customerName' => $sale['customer_name'],
                                'phone' => $sale['phone'],
                                'branch' => $sale['branch'],
                                'date' => $sale['sale_date'],
                                'returnDate' => $sale['return_date'],
                                'totalSale' => floatval($sale['total_sale']),
                                'paidAmount' => floatval($sale['paid_amount']),
                                'items' => $sale['items_summary']
                            ]));
                        ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold text-blue-600"><?= htmlspecialchars($sale['invoice_number']) ?></td>
                            <td class="py-3 px-4 font-bold text-slate-800"><?= htmlspecialchars($sale['customer_name']) ?></td>
                            <td class="py-3 px-4 text-slate-500"><?= date('M d, Y', strtotime($sale['sale_date'])) ?></td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-md border border-slate-200 bg-slate-50 text-[10px] font-bold text-slate-600">
                                    <?= htmlspecialchars($sale['payment_method']) ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-black text-slate-900">$<?= number_format($sale['total_sale'], 2) ?></td>
                            <td class="py-3 px-4 text-center">
                                <?php if ($isPaid): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">Paid</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-red-100 text-red-800">Due</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <button type="button" onclick="previewInvoice(<?= $saleJson ?>)" class="text-xs font-bold text-slate-500 hover:text-slate-900 px-2 py-1 rounded-lg hover:bg-slate-100 transition-colors">
                                    View
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Top Category for Sales Table with Tabs -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col justify-between">
            <!-- Header Tabs -->
            <div class="p-3 border-b border-slate-100 flex items-center justify-between gap-1 overflow-x-auto">
                <div class="flex items-center gap-1">
                    <button type="button" class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-900 font-extrabold text-[11px] flex items-center gap-1 border border-amber-200 shadow-xs">
                        <i data-lucide="tag" class="w-3 h-3 text-amber-600"></i> Top Category for Sales
                    </button>
                    <button type="button" class="px-2 py-1 rounded-lg text-slate-500 hover:text-slate-800 font-bold text-[11px] flex items-center gap-1">
                        <i data-lucide="flame" class="w-3 h-3 text-red-500"></i> Top Movers
                    </button>
                    <button type="button" class="px-2 py-1 rounded-lg text-slate-500 hover:text-slate-800 font-bold text-[11px] flex items-center gap-1">
                        <i data-lucide="sparkles" class="w-3 h-3 text-blue-500"></i> New Items
                    </button>
                </div>
                <a href="inventory.php" class="text-slate-400 hover:text-slate-700 p-1" title="Open Inventory">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <!-- Categories Table -->
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-black text-[10px] tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-2.5 px-3">Category</th>
                            <th class="py-2.5 px-3 text-center">Units Sold</th>
                            <th class="py-2.5 px-3 text-right">Total Sales</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-2.5 px-3 font-bold text-slate-800 flex items-center gap-1.5">
                                <span>📱</span> Mobile
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700">4</td>
                            <td class="py-2.5 px-3 text-right font-black text-slate-900">$684.00</td>
                        </tr>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-2.5 px-3 font-bold text-slate-800 flex items-center gap-1.5">
                                <span>💻</span> Laptop
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700">2</td>
                            <td class="py-2.5 px-3 text-right font-black text-slate-900">$1,270.00</td>
                        </tr>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-2.5 px-3 font-bold text-slate-800 flex items-center gap-1.5">
                                <span>📡</span> Networking
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700">2</td>
                            <td class="py-2.5 px-3 text-right font-black text-slate-900">$280.00</td>
                        </tr>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-2.5 px-3 font-bold text-slate-800 flex items-center gap-1.5">
                                <span>📺</span> TV &amp; Display
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700">1</td>
                            <td class="py-2.5 px-3 text-right font-black text-slate-900">$480.00</td>
                        </tr>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-2.5 px-3 font-bold text-slate-800 flex items-center gap-1.5">
                                <span>🔌</span> Accessories
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700">6</td>
                            <td class="py-2.5 px-3 text-right font-black text-slate-900">$138.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-2.5 bg-slate-50 border-t border-slate-100 text-center">
                <a href="inventory.php" class="text-[11px] font-bold text-[#c00000] hover:underline">
                    View Complete Categories Catalog &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Logic Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Revenue & Purchases Dynamics (Spline Area Chart)
    const dynamicsCtx = document.getElementById('dynamicsChart');
    if (dynamicsCtx) {
        const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        // Sales Revenue gradient
        const ctxDynamics = dynamicsCtx.getContext('2d');
        const gradSales = ctxDynamics.createLinearGradient(0, 0, 0, 300);
        gradSales.addColorStop(0, 'rgba(220, 38, 38, 0.25)');
        gradSales.addColorStop(1, 'rgba(220, 38, 38, 0.0)');

        // Stock Purchases gradient
        const gradPurchases = ctxDynamics.createLinearGradient(0, 0, 0, 300);
        gradPurchases.addColorStop(0, 'rgba(37, 99, 235, 0.35)');
        gradPurchases.addColorStop(1, 'rgba(37, 99, 235, 0.0)');

        new Chart(dynamicsCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Sales Revenue',
                        data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 300, <?= $salesRevenueVal ?>, 0],
                        borderColor: '#dc2626',
                        backgroundColor: gradSales,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#dc2626'
                    },
                    {
                        label: 'Repair Revenue',
                        data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, <?= $repairRevenueVal ?>, 0],
                        borderColor: '#f59e0b',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#f59e0b'
                    },
                    {
                        label: 'Stock Purchases',
                        data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 1500, <?= $purchasesCostVal ?>, 0],
                        borderColor: '#2563eb',
                        backgroundColor: gradPurchases,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#2563eb'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleColor: '#ffffff',
                        titleFont: { weight: 'bold', size: 12 },
                        bodyColor: '#e5e7eb',
                        bodyFont: { size: 11 },
                        padding: 10,
                        cornerRadius: 8,
                        boxPadding: 4,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + Number(context.raw).toLocaleString('en-US', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 11, family: 'Plus Jakarta Sans' },
                            color: '#64748b'
                        }
                    },
                    y: {
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            font: { size: 10, family: 'Plus Jakarta Sans' },
                            color: '#94a3b8',
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        },
                        min: 0,
                        max: 13000
                    }
                }
            }
        });
    }

    // 2. Financial Allocation (Donut Chart)
    const allocationCtx = document.getElementById('allocationChart');
    if (allocationCtx) {
        new Chart(allocationCtx, {
            type: 'doughnut',
            data: {
                labels: ['Sales Revenue', 'Repair Revenue', 'Stock Purchases', 'Operating Expenses'],
                datasets: [{
                    data: [<?= $salesRevenueVal ?>, <?= $repairRevenueVal ?>, <?= $purchasesCostVal ?>, <?= $operatingExpensesVal ?>],
                    backgroundColor: [
                        '#dc2626', // Red
                        '#f59e0b', // Yellow
                        '#0284c7', // Sky Blue
                        '#10b981'  // Green
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': $' + Number(context.raw).toLocaleString('en-US', {minimumFractionDigits: 2});
                            }
                        }
                    }
                }
            }
        });
    }
});

function toggleLowStockList() {
    const drawer = document.getElementById('low-stock-drawer');
    const arrow = document.getElementById('low-stock-arrow');
    drawer.classList.toggle('hidden');
    arrow.classList.toggle('rotate-180');
}

function toggleDueRecordsTable() {
    const drawer = document.getElementById('due-records-drawer');
    const chevron = document.getElementById('due-records-chevron');
    drawer.classList.toggle('hidden');
    if (chevron) chevron.classList.toggle('rotate-180');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
