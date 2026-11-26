<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Filters matching Screenshot 2
$startDate   = $_GET['start_date'] ?? date('Y-m-01');
$endDate     = $_GET['end_date'] ?? date('Y-m-d');
$jobStatus   = trim($_GET['status'] ?? 'All Statuses');
$payStatus   = trim($_GET['payment'] ?? 'All Payments');
$techFilter  = intval($_GET['technician'] ?? 0);
$svcFilter   = trim($_GET['service'] ?? 'All Services');
$searchKey   = trim($_GET['search'] ?? '');

$technicians = $pdo->query("SELECT id, name FROM technicians ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$services    = $pdo->query("SELECT id, name FROM repair_services ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Build query
$where = ["1=1"];
$params = [];

if ($currentBranch !== 'All Branches') {
    $where[] = "r.branch = ?";
    $params[] = $currentBranch;
}
if (!empty($startDate)) {
    $where[] = "DATE(COALESCE(r.intake_date, DATE(r.created_at))) >= ?";
    $params[] = $startDate;
}
if (!empty($endDate)) {
    $where[] = "DATE(COALESCE(r.intake_date, DATE(r.created_at))) <= ?";
    $params[] = $endDate;
}
if ($jobStatus !== 'All Statuses' && !empty($jobStatus)) {
    $where[] = "r.status = ?";
    $params[] = $jobStatus;
}
if ($payStatus === 'Paid') {
    $where[] = "r.paid_amount >= r.total_amount";
} elseif ($payStatus === 'Partial / Due') {
    $where[] = "r.paid_amount > 0 AND r.paid_amount < r.total_amount";
} elseif ($payStatus === 'Unpaid') {
    $where[] = "r.paid_amount <= 0";
}
if ($techFilter > 0) {
    $where[] = "r.technician_id = ?";
    $params[] = $techFilter;
}
if ($svcFilter !== 'All Services' && !empty($svcFilter)) {
    $where[] = "r.service_name LIKE ?";
    $params[] = '%' . $svcFilter . '%';
}
if (!empty($searchKey)) {
    $where[] = "(r.customer_name LIKE ? OR r.customer_phone LIKE ? OR r.job_number LIKE ? OR r.device_model LIKE ? OR r.serial_imei LIKE ?)";
    $kw = '%' . $searchKey . '%';
    array_push($params, $kw, $kw, $kw, $kw, $kw);
}

$whereSql = implode(' AND ', $where);

$sql = "
    SELECT r.*,
        (SELECT COUNT(*) FROM repair_job_parts p WHERE p.repair_id = r.id) AS parts_count
    FROM repair_jobs r
    WHERE $whereSql
    ORDER BY r.intake_date DESC, r.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compute KPIs & Totals
$totalJobs     = count($jobs);
$deliveredCnt  = 0;
$readyCnt      = 0;
$fullyCleared  = 0;
$sumLabor      = 0.00;
$sumTechCost   = 0.00;
$sumParts      = 0.00;
$sumDisc       = 0.00;
$sumTotal      = 0.00;
$sumPaid       = 0.00;
$sumDue        = 0.00;

foreach ($jobs as $j) {
    if ($j['status'] === 'Delivered') $deliveredCnt++;
    if ($j['status'] === 'Ready') $readyCnt++;
    $tot  = floatval($j['total_amount']);
    $paid = floatval($j['paid_amount']);
    $due  = max(0, $tot - $paid);
    if ($due <= 0.001) $fullyCleared++;

    $sumLabor    += floatval($j['service_cost']);
    $sumTechCost += floatval($j['technician_cost']);
    $sumParts    += floatval($j['parts_cost']);
    $sumDisc     += floatval($j['discount']);
    $sumTotal    += $tot;
    $sumPaid     += $paid;
    $sumDue      += $due;
}
$netServiceProfit = max(0, $sumLabor - $sumTechCost);
?>

<main class="flex-1 min-w-0 space-y-5">

    <!-- Top Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 no-print">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center shrink-0">
                <i data-lucide="wrench" class="w-5 h-5"></i>
            </div>
            <div>
                <h1 class="text-lg font-black text-red-600 tracking-tight">Repairing &amp; Servicing Report</h1>
                <p class="text-xs text-slate-400 font-medium">Comprehensive performance report on repair jobs, labor charges, parts costs, customer dues, and technician activity</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="px-4 py-2 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-bold text-xs shadow-sm flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Print Report</span>
            </button>
            <a href="create_repair.php?branch=<?= urlencode($currentBranch) ?>" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-sm flex items-center gap-1.5">
                <span>+ New Repair Job</span>
            </a>
            <a href="repairs.php?branch=<?= urlencode($currentBranch) ?>" class="px-4 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 font-bold text-xs shadow-xs flex items-center gap-1.5">
                <i data-lucide="list" class="w-3.5 h-3.5"></i>
                <span>Repair List</span>
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <form method="GET" class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-4 space-y-3 text-xs no-print">
        <input type="hidden" name="branch" value="<?= htmlspecialchars($currentBranch) ?>">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none font-medium">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none font-medium">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Job Status</label>
                <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none font-medium bg-white">
                    <option value="All Statuses" <?= $jobStatus === 'All Statuses' ? 'selected' : '' ?>>All Statuses</option>
                    <?php foreach (['Received', 'In Progress', 'Ready', 'Delivered', 'Cancelled'] as $st): ?>
                        <option value="<?= $st ?>" <?= $jobStatus === $st ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Payment Status</label>
                <select name="payment" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none font-medium bg-white">
                    <?php foreach (['All Payments', 'Paid', 'Partial / Due', 'Unpaid'] as $ps): ?>
                        <option value="<?= $ps ?>" <?= $payStatus === $ps ? 'selected' : '' ?>><?= $ps ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Technician</label>
                <select name="technician" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none font-medium bg-white">
                    <option value="0">All Technicians</option>
                    <?php foreach ($technicians as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $techFilter == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Service Type</label>
                <select name="service" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none font-medium bg-white">
                    <option value="All Services">All Services</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= htmlspecialchars($s['name']) ?>" <?= $svcFilter === $s['name'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
            <div class="flex-1">
                <label class="block font-bold text-slate-700 mb-1">Search Keyword</label>
                <input type="text" name="search" value="<?= htmlspecialchars($searchKey) ?>"
                       placeholder="Search by customer name, phone, ticket #, device name, IMEI..."
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none">
            </div>
            <button type="submit" class="px-8 py-2 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 cursor-pointer shadow-sm">
                <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                <span>Filter</span>
            </button>
            <a href="repair_report.php?branch=<?= urlencode($currentBranch) ?>" class="px-6 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center gap-1.5">
                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                <span>Reset</span>
            </a>
        </div>
    </form>

    <div id="printable-receipt" class="space-y-5">
        <!-- 6 KPI Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs">
                <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block">TOTAL JOBS</span>
                <span class="text-2xl font-black text-red-600 mt-1 block"><?= $totalJobs ?></span>
                <span class="text-[10px] text-slate-400 font-medium mt-1 block"><?= $deliveredCnt ?> Delivered &bull; <?= $readyCnt ?> Ready</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs">
                <span class="text-[10px] font-black uppercase text-red-500 tracking-wider block">LABOR / SERVICES (PROFIT)</span>
                <span class="text-2xl font-black text-emerald-600 mt-1 block">$<?= number_format($netServiceProfit, 2) ?></span>
                <span class="text-[10px] text-slate-400 font-medium mt-1 block">Net profit (after $<?= number_format($sumTechCost, 2) ?> tech)</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs">
                <span class="text-[10px] font-black uppercase text-slate-500 tracking-wider block">SPARE PARTS</span>
                <span class="text-2xl font-black text-slate-900 mt-1 block">$<?= number_format($sumParts, 2) ?></span>
                <span class="text-[10px] text-slate-400 font-medium mt-1 block">From Inventory</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs">
                <span class="text-[10px] font-black uppercase text-red-500 tracking-wider block">NET BILLED</span>
                <span class="text-2xl font-black text-red-600 mt-1 block">$<?= number_format($sumTotal, 2) ?></span>
                <span class="text-[10px] text-slate-400 font-medium mt-1 block">After $<?= number_format($sumDisc, 2) ?> disc.</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs">
                <span class="text-[10px] font-black uppercase text-emerald-600 tracking-wider block">COLLECTED (PAID)</span>
                <span class="text-2xl font-black text-emerald-600 mt-1 block">$<?= number_format($sumPaid, 2) ?></span>
                <span class="text-[10px] text-emerald-600 font-bold mt-1 flex items-center gap-1">
                    <i data-lucide="check-circle-2" class="w-3 h-3"></i>
                    <span><?= $fullyCleared ?> fully cleared</span>
                </span>
            </div>

            <div class="bg-red-50/70 rounded-2xl border border-red-200/90 p-4 shadow-xs">
                <span class="text-[10px] font-black uppercase text-red-600 tracking-wider block">CUSTOMER DUES</span>
                <span class="text-2xl font-black text-red-600 mt-1 block">$<?= number_format($sumDue, 2) ?></span>
                <span class="text-[10px] text-red-500 font-bold mt-1 flex items-center gap-1">
                    <i data-lucide="alert-circle" class="w-3 h-3"></i>
                    <span>In Customer Balances</span>
                </span>
            </div>
        </div>

        <!-- Report Table -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs">
                <div class="flex items-center gap-2 font-black text-red-600">
                    <i data-lucide="table" class="w-4 h-4 text-amber-500"></i>
                    <span>Repair Jobs (<?= $totalJobs ?> records found)</span>
                </div>
                <span class="text-[11px] text-slate-400 font-medium">
                    Showing period: <strong class="text-slate-700"><?= date('d M Y', strtotime($startDate)) ?></strong> to <strong class="text-slate-700"><?= date('d M Y', strtotime($endDate)) ?></strong>
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 font-black text-[10px] uppercase border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-3">Ticket #</th>
                            <th class="py-3 px-3">Intake Date</th>
                            <th class="py-3 px-3">Customer</th>
                            <th class="py-3 px-3">Device Details</th>
                            <th class="py-3 px-3">Problem</th>
                            <th class="py-3 px-3">Technician</th>
                            <th class="py-3 px-3 text-right">Labor ($)</th>
                            <th class="py-3 px-3 text-right">Parts ($)</th>
                            <th class="py-3 px-3 text-right">Disc. ($)</th>
                            <th class="py-3 px-3 text-right">Total ($)</th>
                            <th class="py-3 px-3 text-right">Paid ($)</th>
                            <th class="py-3 px-3 text-right">Due ($)</th>
                            <th class="py-3 px-3 text-center">Status</th>
                            <th class="py-3 px-3 text-center no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($jobs)): ?>
                            <tr>
                                <td colspan="14" class="py-8 text-center text-slate-400 italic">No repair jobs found for the selected period/filters.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($jobs as $j):
                                $tot  = floatval($j['total_amount']);
                                $paid = floatval($j['paid_amount']);
                                $due  = max(0, $tot - $paid);
                                $isPaid = ($due <= 0.001);
                            ?>
                                <tr class="hover:bg-slate-50/70">
                                    <td class="py-3 px-3 font-black text-red-600 font-mono text-[11px]"><?= htmlspecialchars($j['job_number']) ?></td>
                                    <td class="py-3 px-3 text-slate-600"><?= date('d M Y', strtotime($j['intake_date'] ?: $j['created_at'])) ?></td>
                                    <td class="py-3 px-3">
                                        <span class="font-bold text-slate-800 block"><?= htmlspecialchars($j['customer_name']) ?></span>
                                        <span class="text-[10px] text-slate-400"><?= htmlspecialchars($j['customer_phone'] ?: '—') ?></span>
                                    </td>
                                    <td class="py-3 px-3 font-bold text-slate-700"><?= htmlspecialchars($j['device_model']) ?></td>
                                    <td class="py-3 px-3 max-w-[210px] truncate">
                                        <span class="text-slate-700 block truncate"><?= htmlspecialchars($j['reported_problem']) ?></span>
                                        <?php if (!empty($j['service_name'])): ?>
                                            <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-bold text-[9px]"><?= htmlspecialchars($j['service_name']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-3 text-slate-600"><?= htmlspecialchars($j['technician_name'] ?: 'Unassigned') ?></td>
                                    <td class="py-3 px-3 text-right font-mono text-slate-600">$<?= number_format($j['service_cost'], 2) ?></td>
                                    <td class="py-3 px-3 text-right font-mono text-slate-700">
                                        $<?= number_format($j['parts_cost'], 2) ?>
                                        <?php if (intval($j['parts_count']) > 0): ?>
                                            <span class="ml-1 px-1.5 py-0.5 rounded bg-slate-700 text-white text-[9px] font-bold"><?= $j['parts_count'] ?> parts</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono text-red-500">$<?= number_format($j['discount'], 2) ?></td>
                                    <td class="py-3 px-3 text-right font-mono font-black text-red-600">$<?= number_format($tot, 2) ?></td>
                                    <td class="py-3 px-3 text-right font-mono font-bold text-emerald-600">$<?= number_format($paid, 2) ?></td>
                                    <td class="py-3 px-3 text-right font-mono font-bold <?= $due > 0 ? 'text-red-600' : 'text-slate-600' ?>">$<?= number_format($due, 2) ?></td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="inline-block px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold text-[10px]"><?= htmlspecialchars($j['status']) ?></span>
                                        <span class="block mt-0.5 text-[10px] font-black <?= $isPaid ? 'text-emerald-600' : 'text-red-600' ?>"><?= $isPaid ? 'Paid' : 'Due' ?></span>
                                    </td>
                                    <td class="py-3 px-3 text-center no-print">
                                        <a href="repair_ticket.php?id=<?= $j['id'] ?>" target="_blank" class="inline-flex w-7 h-7 rounded-lg border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 items-center justify-center" title="Print Ticket">
                                            <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-slate-50 border-t-2 border-red-600 font-black text-xs">
                        <tr>
                            <td colspan="6" class="py-3 px-3 text-red-600 uppercase tracking-wider">REPORT TOTALS (<?= $totalJobs ?> JOBS):</td>
                            <td class="py-3 px-3 text-right font-mono text-red-600">$<?= number_format($sumLabor, 2) ?></td>
                            <td class="py-3 px-3 text-right font-mono text-slate-900">$<?= number_format($sumParts, 2) ?></td>
                            <td class="py-3 px-3 text-right font-mono text-red-600">-$<?= number_format($sumDisc, 2) ?></td>
                            <td class="py-3 px-3 text-right font-mono text-red-600">$<?= number_format($sumTotal, 2) ?></td>
                            <td class="py-3 px-3 text-right font-mono text-emerald-600">$<?= number_format($sumPaid, 2) ?></td>
                            <td class="py-3 px-3 text-right font-mono text-red-600">$<?= number_format($sumDue, 2) ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
