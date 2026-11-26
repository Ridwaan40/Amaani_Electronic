<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate   = $_GET['end_date'] ?? date('Y-m-d');

$branchCond = ($currentBranch === 'All Branches') ? '1=1' : 'si.branch = ' . $pdo->quote($currentBranch);

// 1. Detailed Sales Records
$detailStmt = $pdo->prepare("
    SELECT 
        si.*,
        COALESCE(s.sale_date, si.created_at) AS display_date,
        COALESCE(s.customer_name, 'Walk-in Customer') AS customer_name,
        COALESCE(s.phone, '—') AS customer_phone,
        COALESCE(s.payment_method, 'Cash On Hand') AS payment_method
    FROM sale_items si
    LEFT JOIN sales s ON (si.sale_id = s.id OR (si.sale_id IS NULL AND si.transaction_id = s.invoice_number))
    WHERE $branchCond
      AND DATE(COALESCE(s.sale_date, si.created_at, CURDATE())) >= ?
      AND DATE(COALESCE(s.sale_date, si.created_at, CURDATE())) <= ?
    ORDER BY display_date DESC, si.id DESC
");
$detailStmt->execute([$startDate, $endDate]);
$detailedRecords = $detailStmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Aggregated Sales Summary by Product
$summaryMap = [];
$totalQty     = 0;
$totalRevenue = 0.00;
$totalProfit  = 0.00;

foreach ($detailedRecords as $row) {
    $pName = $row['product_name'] ?: 'Unknown Product';
    $qty   = intval($row['qty'] ?: 1);
    $sold  = floatval($row['sold_price'] > 0 ? $row['sold_price'] : $row['unit_price']);
    $prev  = floatval($row['previous_price']);
    $last  = floatval($row['last_price'] > 0 ? $row['last_price'] : $row['wholesale_price']);
    $rev   = floatval($row['total_price'] > 0 ? $row['total_price'] : ($sold * $qty));
    $profUnit = $sold - $last;
    $prof  = $profUnit * $qty;

    $totalQty     += $qty;
    $totalRevenue += $rev;
    $totalProfit  += $prof;

    if (!isset($summaryMap[$pName])) {
        $summaryMap[$pName] = [
            'product_name' => $pName,
            'unit'         => $row['unit'] ?: 'Pcs',
            'qty'          => 0,
            'sum_sold'     => 0,
            'sum_prev'     => 0,
            'sum_last'     => 0,
            'revenue'      => 0,
            'profit'       => 0,
            'count'        => 0,
        ];
    }
    $summaryMap[$pName]['qty']      += $qty;
    $summaryMap[$pName]['sum_sold'] += ($sold * $qty);
    $summaryMap[$pName]['sum_prev'] += ($prev * $qty);
    $summaryMap[$pName]['sum_last'] += ($last * $qty);
    $summaryMap[$pName]['revenue']  += $rev;
    $summaryMap[$pName]['profit']   += $prof;
    $summaryMap[$pName]['count']    += 1;
}

// Sort summary by revenue DESC
$summaryList = array_values($summaryMap);
usort($summaryList, function($a, $b) { return $b['revenue'] <=> $a['revenue']; });

$totalProducts   = count($summaryList);
$overallMargin   = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

// Top 5 for Charts
$top5 = array_slice($summaryList, 0, 5);
$chartLabels  = array_map(fn($x) => $x['product_name'], $top5);
$chartRev     = array_map(fn($x) => round($x['revenue'], 2), $top5);
$chartProfit  = array_map(fn($x) => round($x['profit'], 2), $top5);
?>

<main class="flex-1 min-w-0 space-y-5">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 no-print">
        <div>
            <h1 class="text-lg font-black text-slate-800 tracking-tight">Sales Report</h1>
            <p class="text-xs text-slate-400 font-medium">Manage Your Inventory Sales Report</p>
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-2 text-xs">
            <input type="hidden" name="branch" value="<?= htmlspecialchars($currentBranch) ?>">
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white font-bold text-slate-700 outline-none focus:border-[#c00000]">
            <span class="text-slate-400 font-bold">to</span>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white font-bold text-slate-700 outline-none focus:border-[#c00000]">
            <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold flex items-center gap-1 cursor-pointer shadow-xs" title="Filter">
                <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                <span>Filter</span>
            </button>
            <button type="button" onclick="window.print()" class="px-3.5 py-1.5 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-bold flex items-center gap-1 cursor-pointer shadow-xs">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Print</span>
            </button>
        </form>
    </div>

    <div id="printable-receipt" class="space-y-5">
        <!-- 4 Solid Colored KPI Cards matching Screenshot 3 -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-xl bg-[#146ef5] text-white p-4 shadow-sm">
                <span class="text-xs font-bold opacity-90 block">Total Products</span>
                <span class="text-2xl font-black mt-1 block"><?= $totalProducts ?></span>
            </div>
            <div class="rounded-xl bg-[#007c49] text-white p-4 shadow-sm">
                <span class="text-xs font-bold opacity-90 block">Total Quantity</span>
                <span class="text-2xl font-black mt-1 block"><?= $totalQty ?></span>
            </div>
            <div class="rounded-xl bg-[#00bbe4] text-white p-4 shadow-sm">
                <span class="text-xs font-bold opacity-90 block">Total Revenue</span>
                <span class="text-2xl font-black mt-1 block">$<?= number_format($totalRevenue, 2) ?></span>
            </div>
            <div class="rounded-xl bg-[#f5b400] text-white p-4 shadow-sm">
                <span class="text-xs font-bold opacity-90 block">Total Profit</span>
                <span class="text-2xl font-black mt-1 block">$<?= number_format($totalProfit, 2) ?></span>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <!-- Bar Chart: Top Products Revenue & Profit -->
            <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <div>
                        <h3 class="text-sm font-black text-slate-800 flex items-center gap-1.5">
                            <i data-lucide="bar-chart-2" class="w-4 h-4 text-blue-600"></i>
                            <span>Top Products: Revenue &amp; Profit</span>
                        </h3>
                        <p class="text-[11px] text-slate-400">Comparison of top performing products by sales revenue and net profit</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold">
                        <?= date('M d, Y', strtotime($startDate)) ?> &ndash; <?= date('M d, Y', strtotime($endDate)) ?>
                    </span>
                </div>
                <div class="h-64">
                    <canvas id="topProductsBarChart"></canvas>
                </div>
            </div>

            <!-- Doughnut Chart: Revenue Share -->
            <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs flex flex-col">
                <div class="mb-3">
                    <h3 class="text-sm font-black text-slate-800 flex items-center gap-1.5">
                        <i data-lucide="pie-chart" class="w-4 h-4 text-emerald-600"></i>
                        <span>Revenue Share</span>
                    </h3>
                    <p class="text-[11px] text-slate-400">Revenue distribution among top products</p>
                </div>
                <div class="flex-1 flex items-center justify-center relative h-56">
                    <canvas id="revenueShareDonut"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-[10px] text-slate-400 font-bold">Total Revenue</span>
                        <span class="text-sm font-black text-red-600">$<?= number_format($totalRevenue, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Summary Table -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-black text-slate-800">Sales Summary</h3>
                <div class="flex items-center gap-3">
                    <input type="text" id="search-summary" onkeyup="filterTable('summary-tbody', this.value)" placeholder="Search summary..."
                           class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs outline-none focus:border-[#c00000] no-print">
                    <span class="text-[11px] text-slate-400 font-medium">From <?= date('M d, Y', strtotime($startDate)) ?> to <?= date('M d, Y', strtotime($endDate)) ?></span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-600 font-black text-[10px] uppercase border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3">#</th>
                            <th class="py-2.5 px-3">Product</th>
                            <th class="py-2.5 px-3">Unit</th>
                            <th class="py-2.5 px-3 text-right">Quantity</th>
                            <th class="py-2.5 px-3 text-right">Avg Sale Price</th>
                            <th class="py-2.5 px-3 text-right">Avg Previous Price</th>
                            <th class="py-2.5 px-3 text-right">Avg Last Price</th>
                            <th class="py-2.5 px-3 text-right">Total Revenue</th>
                            <th class="py-2.5 px-3 text-right">Total Profit</th>
                            <th class="py-2.5 px-3 text-center">Profit Margin</th>
                            <th class="py-2.5 px-3 text-right">Sales Count</th>
                        </tr>
                    </thead>
                    <tbody id="summary-tbody" class="divide-y divide-slate-100">
                        <?php if (empty($summaryList)): ?>
                            <tr><td colspan="11" class="py-6 text-center text-slate-400 italic">No sales summary data for this period.</td></tr>
                        <?php else: ?>
                            <?php foreach ($summaryList as $idx => $s):
                                $avgSold = $s['qty'] > 0 ? $s['sum_sold'] / $s['qty'] : 0;
                                $avgPrev = $s['qty'] > 0 ? $s['sum_prev'] / $s['qty'] : 0;
                                $avgLast = $s['qty'] > 0 ? $s['sum_last'] / $s['qty'] : 0;
                                $margin  = $s['revenue'] > 0 ? ($s['profit'] / $s['revenue']) * 100 : 0;
                            ?>
                                <tr class="hover:bg-slate-50/70">
                                    <td class="py-2.5 px-3 text-slate-500"><?= $idx + 1 ?></td>
                                    <td class="py-2.5 px-3 font-bold text-slate-800"><?= htmlspecialchars($s['product_name']) ?></td>
                                    <td class="py-2.5 px-3 text-slate-500"><?= htmlspecialchars($s['unit']) ?></td>
                                    <td class="py-2.5 px-3 text-right font-bold"><?= $s['qty'] ?></td>
                                    <td class="py-2.5 px-3 text-right font-mono">$<?= number_format($avgSold, 2) ?></td>
                                    <td class="py-2.5 px-3 text-right font-mono text-slate-500">$<?= number_format($avgPrev, 2) ?></td>
                                    <td class="py-2.5 px-3 text-right font-mono text-blue-600">$<?= number_format($avgLast, 2) ?></td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">$<?= number_format($s['revenue'], 2) ?></td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-600">$<?= number_format($s['profit'], 2) ?></td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded bg-emerald-600 text-white font-black text-[10px]"><?= number_format($margin, 2) ?>%</span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-bold"><?= $s['count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-slate-100 border-t-2 border-red-600 font-black text-xs">
                        <tr>
                            <td colspan="7" class="py-2.5 px-3 text-right text-slate-700">Grand Totals:</td>
                            <td class="py-2.5 px-3 text-right font-mono text-slate-900">$<?= number_format($totalRevenue, 2) ?></td>
                            <td class="py-2.5 px-3 text-right font-mono text-emerald-700">$<?= number_format($totalProfit, 2) ?></td>
                            <td class="py-2.5 px-3 text-center text-slate-800"><?= number_format($overallMargin, 2) ?>%</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Detailed Sales Records Table -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-black text-slate-800">Detailed Sales Records</h3>
                <div class="flex items-center gap-3">
                    <input type="text" id="search-details" onkeyup="filterTable('details-tbody', this.value)" placeholder="Search detailed records..."
                           class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs outline-none focus:border-[#c00000] no-print">
                    <span class="text-[11px] text-slate-400 font-medium">From <?= date('M d, Y', strtotime($startDate)) ?> to <?= date('M d, Y', strtotime($endDate)) ?></span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-600 font-black text-[10px] uppercase border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3">Date</th>
                            <th class="py-2.5 px-3">Item</th>
                            <th class="py-2.5 px-3">Customer</th>
                            <th class="py-2.5 px-3">Category</th>
                            <th class="py-2.5 px-3">Unit</th>
                            <th class="py-2.5 px-3 text-right">Quantity</th>
                            <th class="py-2.5 px-3 text-right">Sold Price</th>
                            <th class="py-2.5 px-3 text-right">Previous Price</th>
                            <th class="py-2.5 px-3 text-right">Last Price</th>
                            <th class="py-2.5 px-3 text-right">Profit/Unit</th>
                            <th class="py-2.5 px-3 text-right">Total Revenue</th>
                            <th class="py-2.5 px-3 text-right">Total Profit</th>
                            <th class="py-2.5 px-3 text-center">Sales ID</th>
                            <th class="py-2.5 px-3">Payment Method</th>
                        </tr>
                    </thead>
                    <tbody id="details-tbody" class="divide-y divide-slate-100">
                        <?php if (empty($detailedRecords)): ?>
                            <tr><td colspan="14" class="py-6 text-center text-slate-400 italic">No detailed sales records for this period.</td></tr>
                        <?php else: ?>
                            <?php foreach ($detailedRecords as $r):
                                $qty      = intval($r['qty'] ?: 1);
                                $sold     = floatval($r['sold_price'] > 0 ? $r['sold_price'] : $r['unit_price']);
                                $prev     = floatval($r['previous_price']);
                                $last     = floatval($r['last_price'] > 0 ? $r['last_price'] : $r['wholesale_price']);
                                $rev      = floatval($r['total_price'] > 0 ? $r['total_price'] : ($sold * $qty));
                                $profUnit = $sold - $last;
                                $totProf  = $profUnit * $qty;
                            ?>
                                <tr class="hover:bg-slate-50/70">
                                    <td class="py-2.5 px-3 text-slate-600 font-mono text-[11px]"><?= htmlspecialchars($r['display_date']) ?></td>
                                    <td class="py-2.5 px-3 font-bold text-slate-800"><?= htmlspecialchars($r['product_name']) ?></td>
                                    <td class="py-2.5 px-3">
                                        <span class="font-bold text-slate-700 block"><?= htmlspecialchars($r['customer_name']) ?></span>
                                        <span class="text-[10px] text-slate-400"><?= htmlspecialchars($r['customer_phone']) ?></span>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <span class="px-2 py-0.5 rounded border border-slate-200 bg-slate-50 text-slate-600 font-bold text-[10px]"><?= htmlspecialchars($r['sale_type'] ?: 'Retail') ?></span>
                                    </td>
                                    <td class="py-2.5 px-3 text-slate-500"><?= htmlspecialchars($r['unit'] ?: 'Pcs') ?></td>
                                    <td class="py-2.5 px-3 text-right font-bold"><?= number_format($qty, 2) ?></td>
                                    <td class="py-2.5 px-3 text-right font-mono">$<?= number_format($sold, 2) ?></td>
                                    <td class="py-2.5 px-3 text-right font-mono text-slate-500">$<?= number_format($prev, 2) ?></td>
                                    <td class="py-2.5 px-3 text-right font-mono text-blue-600">$<?= number_format($last, 2) ?></td>
                                    <td class="py-2.5 px-3 text-right font-mono text-emerald-600">$<?= number_format($profUnit, 2) ?></td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">$<?= number_format($rev, 2) ?></td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-600">$<?= number_format($totProf, 2) ?></td>
                                    <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700"><?= htmlspecialchars($r['transaction_id'] ?: ('#' . $r['sale_id'])) ?></td>
                                    <td class="py-2.5 px-3 text-slate-600"><?= htmlspecialchars($r['payment_method']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
function filterTable(tbodyId, query) {
    const q = query.toLowerCase().trim();
    const rows = document.querySelectorAll('#' + tbodyId + ' tr');
    rows.forEach(tr => {
        const text = tr.textContent.toLowerCase();
        tr.style.display = text.includes(q) ? '' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const labels = <?= json_encode($chartLabels) ?>;
    const revenues = <?= json_encode($chartRev) ?>;
    const profits = <?= json_encode($chartProfit) ?>;

    if (labels.length > 0 && window.Chart) {
        new Chart(document.getElementById('topProductsBarChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Total Revenue', data: revenues, backgroundColor: '#dc2626', borderRadius: 4 },
                    { label: 'Total Profit', data: profits, backgroundColor: '#10b981', borderRadius: 4 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', align: 'end' } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => '$' + v } }
                }
            }
        });

        new Chart(document.getElementById('revenueShareDonut'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: revenues,
                    backgroundColor: ['#dc2626', '#2563eb', '#06b6d4', '#10b981', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
