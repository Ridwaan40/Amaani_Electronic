<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

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

$successMsg = '';
$errorMsg = '';

// Handle Delete Repair Job
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);

    // Reverse technician balance & customer debt if assigned
    $jStmt = $pdo->prepare("SELECT technician_id, technician_cost, customer_id, customer_name, total_amount, paid_amount FROM repair_jobs WHERE id = ?");
    $jStmt->execute([$delId]);
    $delJob = $jStmt->fetch(PDO::FETCH_ASSOC);
    if ($delJob && intval($delJob['technician_id']) > 0 && floatval($delJob['technician_cost']) > 0) {
        $pdo->prepare("UPDATE technicians SET balance = GREATEST(0, balance - ?) WHERE id = ?")
            ->execute([floatval($delJob['technician_cost']), intval($delJob['technician_id'])]);
    }
    if ($delJob) {
        $delDue = max(0, floatval($delJob['total_amount']) - floatval($delJob['paid_amount']));
        if ($delDue > 0) {
            if (intval($delJob['customer_id']) > 0) {
                $pdo->prepare("UPDATE customers SET balance = GREATEST(0, balance - ?) WHERE id = ?")
                    ->execute([$delDue, intval($delJob['customer_id'])]);
            } elseif (!empty($delJob['customer_name'])) {
                $pdo->prepare("UPDATE customers SET balance = GREATEST(0, balance - ?) WHERE name = ?")
                    ->execute([$delDue, $delJob['customer_name']]);
            }
        }
    }

    // Restore spare parts stock back to inventory
    $pStmt = $pdo->prepare("SELECT product_id, qty FROM repair_job_parts WHERE repair_id = ? AND product_id IS NOT NULL AND product_id > 0");
    $pStmt->execute([$delId]);
    $restoreStmt = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?");
    foreach ($pStmt->fetchAll(PDO::FETCH_ASSOC) as $oldPart) {
        $restoreStmt->execute([$oldPart['qty'], $oldPart['product_id']]);
    }

    $pdo->prepare("DELETE FROM repair_job_parts WHERE repair_id = ?")->execute([$delId]);
    $pdo->prepare("DELETE FROM repair_jobs WHERE id = ?")->execute([$delId]);
    safeRedirect("repairs.php?deleted=1");
}

// Handle Quick Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_job_status'])) {
    $jobId = intval($_POST['job_id'] ?? 0);
    $newStatus = trim($_POST['new_status'] ?? '');
    $allowed = ['Received', 'In Progress', 'Ready', 'Delivered', 'Cancelled'];
    if ($jobId > 0 && in_array($newStatus, $allowed)) {
        $stmt = $pdo->prepare("UPDATE repair_jobs SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $jobId]);
        safeRedirect("repairs.php?status_updated=1");
    }
}

if (isset($_GET['created'])) $successMsg = "Job-ka cusub ee dayactirka si guul leh ayaa loo diiwaangeliyay (Repair ticket created successfully).";
if (isset($_GET['deleted'])) $successMsg = "Ticket-ka dayactirka waa la tirtiray (Repair job deleted successfully).";
if (isset($_GET['status_updated'])) $successMsg = "Xaaladda dayactirka si guul leh ayaa loo cusbooneysiiyay (Status updated successfully).";

// Filtering inputs
$filterStatus = trim($_GET['status'] ?? 'All Statuses');
$filterTech = intval($_GET['technician'] ?? 0);
$filterStart = trim($_GET['start_date'] ?? '');
$filterEnd = trim($_GET['end_date'] ?? '');
$searchTerm = trim($_GET['search'] ?? '');

// Base SQL
$whereClauses = ["1=1"];
$params = [];

if ($filterStatus !== 'All Statuses' && !empty($filterStatus)) {
    $whereClauses[] = "status = ?";
    $params[] = $filterStatus;
}
if ($filterTech > 0) {
    $whereClauses[] = "technician_id = ?";
    $params[] = $filterTech;
}
if (!empty($filterStart)) {
    $whereClauses[] = "intake_date >= ?";
    $params[] = $filterStart;
}
if (!empty($filterEnd)) {
    $whereClauses[] = "intake_date <= ?";
    $params[] = $filterEnd;
}
if (!empty($searchTerm)) {
    $whereClauses[] = "(job_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ? OR device_model LIKE ? OR reported_problem LIKE ?)";
    $like = "%$searchTerm%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(' AND ', $whereClauses);

// Stat card totals
$totalJobs = $pdo->query("SELECT COUNT(*) FROM repair_jobs")->fetchColumn();
$totalReceived = $pdo->query("SELECT COUNT(*) FROM repair_jobs WHERE status = 'Received'")->fetchColumn();
$totalInProgress = $pdo->query("SELECT COUNT(*) FROM repair_jobs WHERE status = 'In Progress'")->fetchColumn();
$totalReady = $pdo->query("SELECT COUNT(*) FROM repair_jobs WHERE status = 'Ready'")->fetchColumn();
$totalDelivered = $pdo->query("SELECT COUNT(*) FROM repair_jobs WHERE status = 'Delivered'")->fetchColumn();
$totalCustomerDues = $pdo->query("SELECT IFNULL(SUM(GREATEST(0, total_amount - paid_amount)), 0) FROM repair_jobs")->fetchColumn();

// Fetch technicians for filter
$allTechnicians = $pdo->query("SELECT id, name FROM technicians ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM repair_jobs WHERE $whereSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();

$perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$totalPages = ceil($totalRows / $perPage);
$offset = ($page - 1) * $perPage;

// Fetch jobs with spare parts count
$query = "
    SELECT j.*, 
    (SELECT COUNT(*) FROM repair_job_parts p WHERE p.repair_id = j.id) AS parts_count
    FROM repair_jobs j
    WHERE $whereSql
    ORDER BY j.id DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$repairJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="flex-1 min-w-0 space-y-4">
    <!-- Top Header matching Screenshot 3 -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center shrink-0">
                <i data-lucide="wrench" class="w-5 h-5 text-amber-500"></i>
            </div>
            <div>
                <h1 class="text-lg font-black text-slate-800 tracking-tight">Repair &amp; Servicing Management</h1>
                <p class="text-xs text-slate-400 font-medium">Track device repairs, diagnostics, spare parts consumption, technician assignments, and job workflows</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="technicians.php" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs shadow-2xs transition-colors flex items-center gap-1.5">
                <i data-lucide="users" class="w-3.5 h-3.5 text-blue-600"></i>
                <span>Technicians</span>
            </a>
            <a href="technicians.php?report=costs" class="px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 font-bold text-xs shadow-2xs transition-colors flex items-center gap-1.5">
                <i data-lucide="trending-up" class="w-3.5 h-3.5 text-amber-600"></i>
                <span>Technician Costs</span>
            </a>
            <a href="repair_services.php" class="px-3 py-1.5 rounded-xl border border-red-200 bg-white hover:bg-red-50 text-red-600 font-bold text-xs shadow-2xs transition-colors flex items-center gap-1.5">
                <i data-lucide="settings" class="w-3.5 h-3.5 text-red-500"></i>
                <span>Repair Services</span>
            </a>
            <a href="create_repair.php" class="px-3.5 py-1.5 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-bold text-xs shadow-md shadow-red-900/20 transition-all flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>New Repair Job</span>
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($successMsg)): ?>
        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
            <span><?= htmlspecialchars($successMsg) ?></span>
        </div>
    <?php endif; ?>

    <!-- 6 Stat Cards matching Screenshot 3 -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- 1. TOTAL JOBS -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-3.5">
            <span class="text-[10px] font-extrabold uppercase text-slate-400 tracking-wider block">Total Jobs</span>
            <span class="text-xl font-black text-slate-800 mt-0.5 block"><?= number_format($totalJobs) ?></span>
            <span class="text-[10px] text-slate-400 font-medium block mt-0.5">All recorded repairs</span>
        </div>

        <!-- 2. RECEIVED -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-3.5">
            <span class="text-[10px] font-extrabold uppercase text-amber-500 tracking-wider block">Received</span>
            <span class="text-xl font-black text-slate-800 mt-0.5 block"><?= number_format($totalReceived) ?></span>
            <span class="text-[10px] text-slate-400 font-medium block mt-0.5">Awaiting diagnostic</span>
        </div>

        <!-- 3. IN PROGRESS -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-3.5">
            <span class="text-[10px] font-extrabold uppercase text-blue-500 tracking-wider block">In Progress</span>
            <span class="text-xl font-black text-slate-800 mt-0.5 block"><?= number_format($totalInProgress) ?></span>
            <span class="text-[10px] text-slate-400 font-medium block mt-0.5">Diagnosing &amp; Repairing</span>
        </div>

        <!-- 4. READY FOR PICKUP -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-3.5">
            <span class="text-[10px] font-extrabold uppercase text-emerald-600 tracking-wider block">Ready For Pickup</span>
            <span class="text-xl font-black text-slate-800 mt-0.5 block"><?= number_format($totalReady) ?></span>
            <span class="text-[10px] text-slate-400 font-medium block mt-0.5">Repairs completed</span>
        </div>

        <!-- 5. DELIVERED -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-3.5">
            <span class="text-[10px] font-extrabold uppercase text-slate-500 tracking-wider block">Delivered</span>
            <span class="text-xl font-black text-slate-800 mt-0.5 block"><?= number_format($totalDelivered) ?></span>
            <span class="text-[10px] text-slate-400 font-medium block mt-0.5">Handed to customer</span>
        </div>

        <!-- 6. CUSTOMER DUES -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-3.5">
            <span class="text-[10px] font-extrabold uppercase text-red-500 tracking-wider block">Customer Dues</span>
            <span class="text-xl font-black text-slate-800 mt-0.5 block">$<?= number_format($totalCustomerDues, 2) ?></span>
            <span class="text-[10px] text-red-500 font-medium block mt-0.5 flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span> Unpaid balance
            </span>
        </div>
    </div>

    <!-- Filter Bar matching Screenshot 3 -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-4">
        <form method="GET" action="repairs.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end text-xs">
            <!-- 1. Status Workflow -->
            <div>
                <label class="block font-bold text-slate-700 mb-1">Status Workflow</label>
                <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs">
                    <option value="All Statuses" <?= $filterStatus === 'All Statuses' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="Received" <?= $filterStatus === 'Received' ? 'selected' : '' ?>>Received</option>
                    <option value="In Progress" <?= $filterStatus === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Ready" <?= $filterStatus === 'Ready' ? 'selected' : '' ?>>Ready</option>
                    <option value="Delivered" <?= $filterStatus === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="Cancelled" <?= $filterStatus === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <!-- 2. Technician -->
            <div>
                <label class="block font-bold text-slate-700 mb-1">Technician</label>
                <select name="technician" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs">
                    <option value="0">All Technicians</option>
                    <?php foreach ($allTechnicians as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $filterTech == $t['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 3. Start Date -->
            <div>
                <label class="block font-bold text-slate-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($filterStart) ?>"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs">
            </div>

            <!-- 4. End Date -->
            <div>
                <label class="block font-bold text-slate-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($filterEnd) ?>"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs">
            </div>

            <!-- 5. Action Buttons -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 px-4 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-bold text-xs shadow-md shadow-red-900/20 transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Filter</span>
                </button>
                <a href="repairs.php" class="py-2 px-3.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors flex items-center justify-center gap-1">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- Table Container matching Screenshot 3 -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
        <!-- Sub-header with search -->
        <div class="p-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs">
            <div class="text-slate-500 font-medium">
                Show per page: <span class="font-bold text-slate-700">10 entries</span>
            </div>
            <form method="GET" action="repairs.php" class="flex items-center gap-2">
                <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
                <input type="hidden" name="technician" value="<?= $filterTech ?>">
                <label class="font-medium text-slate-500">Search:</label>
                <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Search job, customer, device..."
                       class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:border-[#c00000] outline-none">
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-[#c00000] text-white uppercase text-[10px] font-black tracking-wider">
                        <th class="py-3 px-3 w-10 text-center">#</th>
                        <th class="py-3 px-3">Job #</th>
                        <th class="py-3 px-3">Customer</th>
                        <th class="py-3 px-3">Device &amp; Model</th>
                        <th class="py-3 px-3">Problem Description</th>
                        <th class="py-3 px-3">Technician</th>
                        <th class="py-3 px-3 text-center">Status</th>
                        <th class="py-3 px-3 text-center">Total ($)</th>
                        <th class="py-3 px-3 text-center">Balance Due</th>
                        <th class="py-3 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($repairJobs)): ?>
                        <tr>
                            <td colspan="10" class="py-10 text-center text-slate-400 font-medium">
                                Wax dayactir ah laguma helin xogtaan. Guji "New Repair Job" si aad u diiwaangeliso shaqo cusub.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($repairJobs as $idx => $job): ?>
                            <?php 
                            $balanceDue = max(0, floatval($job['total_amount']) - floatval($job['paid_amount']));
                            $status = $job['status'];
                            ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-3 text-center font-bold text-slate-400">
                                    <?= $offset + $idx + 1 ?>
                                </td>

                                <!-- Job # -->
                                <td class="py-3.5 px-3">
                                    <span class="font-bold text-red-600 block tracking-tight"><?= htmlspecialchars($job['job_number']) ?></span>
                                    <span class="text-[10px] text-slate-400 block font-mono">Rec: <?= date('d-m-Y', strtotime($job['intake_date'] ?? $job['created_at'])) ?></span>
                                </td>

                                <!-- Customer -->
                                <td class="py-3.5 px-3">
                                    <span class="font-bold text-slate-800 block"><?= htmlspecialchars($job['customer_name']) ?></span>
                                    <?php if (!empty($job['customer_phone'])): ?>
                                        <span class="text-[10px] text-slate-400 flex items-center gap-1">
                                            <i data-lucide="phone" class="w-3 h-3 text-slate-400"></i>
                                            <?= htmlspecialchars($job['customer_phone']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Device & Model -->
                                <td class="py-3.5 px-3">
                                    <span class="font-bold text-slate-700 block"><?= htmlspecialchars($job['device_model']) ?></span>
                                    <span class="text-[10px] text-slate-400 block">
                                        <?= !empty($job['brand']) ? '[' . htmlspecialchars($job['brand']) . ']' : '' ?>
                                        <?= !empty($job['serial_imei']) ? '(IMEI/SN: ' . htmlspecialchars($job['serial_imei']) . ')' : '' ?>
                                    </span>
                                </td>

                                <!-- Problem Description -->
                                <td class="py-3.5 px-3 max-w-[240px]">
                                    <p class="text-[11px] text-slate-600 line-clamp-2"><?= htmlspecialchars($job['reported_problem']) ?></p>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <?php if ($job['parts_count'] > 0): ?>
                                            <span class="inline-block px-1.5 py-0.2 rounded-md bg-blue-50 text-blue-700 font-bold text-[9px] border border-blue-200">
                                                <?= $job['parts_count'] ?> parts used
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($job['service_name'])): ?>
                                            <span class="inline-block px-1.5 py-0.2 rounded-md bg-amber-50 text-amber-700 font-bold text-[9px] border border-amber-200">
                                                <?= htmlspecialchars($job['service_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Technician -->
                                <td class="py-3.5 px-3">
                                    <?php if (!empty($job['technician_name']) && $job['technician_name'] !== 'Unassigned'): ?>
                                        <div class="flex items-center gap-1 text-slate-700 font-bold">
                                            <i data-lucide="user-check" class="w-3.5 h-3.5 text-blue-600 shrink-0"></i>
                                            <span><?= htmlspecialchars($job['technician_name']) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 font-bold text-[10px] border border-slate-200">
                                            Unassigned
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status (Dropdown to update status easily) -->
                                <td class="py-3.5 px-3 text-center">
                                    <form method="POST" action="repairs.php">
                                        <input type="hidden" name="update_job_status" value="1">
                                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                        <select name="new_status" onchange="this.form.submit()"
                                                class="px-2 py-1 rounded-full text-[10px] font-black cursor-pointer outline-none border transition-all
                                                <?= $status === 'Ready' ? 'bg-emerald-600 text-white border-emerald-600' : '' ?>
                                                <?= $status === 'Received' ? 'bg-amber-400 text-slate-900 border-amber-400' : '' ?>
                                                <?= $status === 'In Progress' ? 'bg-blue-600 text-white border-blue-600' : '' ?>
                                                <?= $status === 'Delivered' ? 'bg-slate-700 text-white border-slate-700' : '' ?>
                                                <?= $status === 'Cancelled' ? 'bg-red-600 text-white border-red-600' : '' ?>
                                                ">
                                            <option value="Received" <?= $status === 'Received' ? 'selected' : '' ?>>Received ▾</option>
                                            <option value="In Progress" <?= $status === 'In Progress' ? 'selected' : '' ?>>In Progress ▾</option>
                                            <option value="Ready" <?= $status === 'Ready' ? 'selected' : '' ?>>Ready ▾</option>
                                            <option value="Delivered" <?= $status === 'Delivered' ? 'selected' : '' ?>>Delivered ▾</option>
                                            <option value="Cancelled" <?= $status === 'Cancelled' ? 'selected' : '' ?>>Cancelled ▾</option>
                                        </select>
                                    </form>
                                </td>

                                <!-- Total ($) -->
                                <td class="py-3.5 px-3 text-center font-black text-red-600">
                                    $<?= number_format($job['total_amount'], 2) ?>
                                </td>

                                <!-- Balance Due -->
                                <td class="py-3.5 px-3 text-center">
                                    <?php if ($balanceDue <= 0): ?>
                                        <span class="inline-flex items-center gap-1 text-emerald-600 font-extrabold text-[11px]">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                            <span>Paid</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-block px-2 py-0.5 rounded-lg bg-red-50 text-red-600 font-black text-[10px] border border-red-200">
                                            $<?= number_format($balanceDue, 2) ?> Due
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- View / Print Ticket -->
                                        <a href="repair_ticket.php?id=<?= $job['id'] ?>" target="_blank"
                                           class="w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 flex items-center justify-center transition-colors" title="Print Job Ticket">
                                            <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                        </a>
                                        <!-- Edit -->
                                        <a href="create_repair.php?edit_id=<?= $job['id'] ?>" 
                                           class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 flex items-center justify-center transition-colors" title="Edit Repair">
                                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                        </a>
                                        <!-- Delete -->
                                        <a href="repairs.php?delete_id=<?= $job['id'] ?>" 
                                           onclick="return confirm('Ma hubtaa inaad tirtirto ticket-kan dayactirka ah?');"
                                           class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 flex items-center justify-center transition-colors" title="Delete">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="p-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-400 font-medium">
                    Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalRows) ?> of <?= $totalRows ?> entries
                </span>
                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&status=<?= urlencode($filterStatus) ?>&technician=<?= $filterTech ?>" 
                           class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold">Previous</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&status=<?= urlencode($filterStatus) ?>&technician=<?= $filterTech ?>" 
                           class="px-2.5 py-1 rounded-lg border font-bold <?= $i == $page ? 'bg-emerald-600 text-white border-emerald-600' : 'border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&status=<?= urlencode($filterStatus) ?>&technician=<?= $filterTech ?>" 
                           class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
