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

$errorMsg = '';

// Edit Mode check
$editId = intval($_GET['edit_id'] ?? 0);
$editJob = null;
$editParts = [];
if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM repair_jobs WHERE id = ?");
    $stmt->execute([$editId]);
    $editJob = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editJob) {
        $pStmt = $pdo->prepare("SELECT product_id, part_name, qty, price FROM repair_job_parts WHERE repair_id = ?");
        $pStmt->execute([$editId]);
        $editParts = $pStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Fetch all Customers, Technicians, Services, and Products
$customers = $pdo->query("SELECT id, name, phone FROM customers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$technicians = $pdo->query("SELECT id, name, specialty, phone FROM technicians WHERE status = 'Active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$services = $pdo->query("SELECT id, name, labor_cost FROM repair_services ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$inventoryProducts = $pdo->query("SELECT id, name, barcode, retail_price, stock_qty FROM products ORDER BY name ASC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_repair_job'])) {
    $customerId = intval($_POST['customer_id'] ?? 0);
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');

    $deviceModel = trim($_POST['device_model'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $serialImei = trim($_POST['serial_imei'] ?? '');
    $passcode = trim($_POST['passcode'] ?? '');
    $reportedProblem = trim($_POST['reported_problem'] ?? '');
    $diagnosticNotes = trim($_POST['diagnostic_notes'] ?? '');

    $techId = intval($_POST['technician_id'] ?? 0);
    $techName = 'Unassigned';
    if ($techId > 0) {
        $tStmt = $pdo->prepare("SELECT name FROM technicians WHERE id = ?");
        $tStmt->execute([$techId]);
        $techName = $tStmt->fetchColumn() ?: 'Unassigned';
    }

    $initialStatus = trim($_POST['initial_status'] ?? 'Received');
    $intakeDate = trim($_POST['intake_date'] ?? date('Y-m-d'));
    $expectedDate = !empty($_POST['expected_date']) ? trim($_POST['expected_date']) : null;

    $serviceId = 0;
    // Combine up to 3 service rows into one aggregated name + cost
    $serviceParts = [];
    $serviceCost = 0.00;
    if (isset($_POST['services']) && is_array($_POST['services'])) {
        foreach ($_POST['services'] as $svc) {
            $sName = trim($svc['name'] ?? '');
            $sCost = floatval($svc['cost'] ?? 0);
            if (!empty($sName) || $sCost > 0) {
                if (!empty($sName)) $serviceParts[] = $sName;
                $serviceCost += $sCost;
            }
        }
    }
    // Fallback: single hidden service_cost field
    if (empty($serviceParts) && floatval($_POST['service_cost'] ?? 0) > 0) {
        $serviceCost = floatval($_POST['service_cost']);
        $singleDesc = trim($_POST['custom_service_desc'] ?? '');
        if (!empty($singleDesc)) $serviceParts[] = $singleDesc;
    }
    $serviceName = !empty($serviceParts) ? implode(' + ', $serviceParts) : '';

    $techCost = floatval($_POST['technician_cost'] ?? 0);
    $partsCost = floatval($_POST['parts_total_cost'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $totalAmount = max(0, ($serviceCost + $partsCost) - $discount);
    $paidAmount = floatval($_POST['paid_amount'] ?? 0);
    $paymentMethod = trim($_POST['payment_method'] ?? 'Cash');
    $branch = $currentBranch !== 'All Branches' ? $currentBranch : 'Boorama Branch';
    $createdBy = $_SESSION['user']['name'] ?? 'Admin';

    if (empty($customerName) || empty($deviceModel) || empty($reportedProblem)) {
        $errorMsg = "Fadlan buuxi Macmiilka, Qalabka (Device Model), iyo Dhibaatada jirta (Problem Description).";
    } else {
        if ($editId > 0) {
            // Reverse old technician balance before applying updated technician cost
            if ($editJob) {
                $oldTechId = intval($editJob['technician_id'] ?? 0);
                $oldTechCost = floatval($editJob['technician_cost'] ?? 0);
                if ($oldTechId > 0 && $oldTechCost > 0) {
                    $pdo->prepare("UPDATE technicians SET balance = GREATEST(0, balance - ?) WHERE id = ?")->execute([$oldTechCost, $oldTechId]);
                }
            }

            // Update Existing Job
            $upd = $pdo->prepare("
                UPDATE repair_jobs SET 
                customer_id = ?, customer_name = ?, customer_phone = ?,
                device_model = ?, brand = ?, serial_imei = ?, passcode = ?,
                reported_problem = ?, diagnostic_notes = ?,
                technician_id = ?, technician_name = ?, technician_cost = ?,
                service_id = ?, service_name = ?, service_cost = ?,
                parts_cost = ?, discount = ?, total_amount = ?, paid_amount = ?,
                status = ?, payment_method = ?, intake_date = ?, expected_date = ?
                WHERE id = ?
            ");
            $upd->execute([
                $customerId ?: null, $customerName, $customerPhone,
                $deviceModel, $brand, $serialImei, $passcode,
                $reportedProblem, $diagnosticNotes,
                $techId ?: null, $techName, $techCost,
                $serviceId ?: null, $serviceName, $serviceCost,
                $partsCost, $discount, $totalAmount, $paidAmount,
                $initialStatus, $paymentMethod, $intakeDate, $expectedDate,
                $editId
            ]);
            $savedId = $editId;

            // Credit updated technician's balance
            if ($techId > 0 && $techCost > 0) {
                $pdo->prepare("UPDATE technicians SET balance = balance + ? WHERE id = ?")->execute([$techCost, $techId]);
            }
        } else {
            // Generate Job Number e.g. REP-20261124-7890
            $randomSuffix = rand(1000, 9999);
            $jobNum = 'REP-' . date('Ymd') . '-' . $randomSuffix;

            $insert = $pdo->prepare("
                INSERT INTO repair_jobs 
                (job_number, customer_id, customer_name, customer_phone, device_model, brand, serial_imei, passcode, reported_problem, diagnostic_notes, technician_id, technician_name, technician_cost, service_id, service_name, service_cost, parts_cost, discount, total_amount, paid_amount, status, payment_method, intake_date, expected_date, branch, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $jobNum, $customerId ?: null, $customerName, $customerPhone,
                $deviceModel, $brand, $serialImei, $passcode,
                $reportedProblem, $diagnosticNotes,
                $techId ?: null, $techName, $techCost,
                $serviceId ?: null, $serviceName, $serviceCost,
                $partsCost, $discount, $totalAmount, $paidAmount,
                $initialStatus, $paymentMethod, $intakeDate, $expectedDate,
                $branch, $createdBy
            ]);
            $savedId = $pdo->lastInsertId();

            // Credit technician's balance if technician cost assigned
            if ($techId > 0 && $techCost > 0) {
                $pdo->prepare("UPDATE technicians SET balance = balance + ? WHERE id = ?")->execute([$techCost, $techId]);
            }
        }

        // For edit mode: always restore old stock & clear old parts before saving updated parts
        if ($editId > 0) {
            $oldParts = $pdo->prepare("SELECT product_id, qty FROM repair_job_parts WHERE repair_id = ? AND product_id IS NOT NULL AND product_id > 0");
            $oldParts->execute([$savedId]);
            $restoreStmt = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?");
            foreach ($oldParts->fetchAll(PDO::FETCH_ASSOC) as $old) {
                $restoreStmt->execute([$old['qty'], $old['product_id']]);
            }
            $pdo->prepare("DELETE FROM repair_job_parts WHERE repair_id = ?")->execute([$savedId]);
        }

        // Save spare parts used & DEDUCT from inventory stock
        if (isset($_POST['parts']) && is_array($_POST['parts'])) {
            $partStmt = $pdo->prepare("INSERT INTO repair_job_parts (repair_id, product_id, part_name, qty, price, total) VALUES (?, ?, ?, ?, ?, ?)");
            $deductStmt = $pdo->prepare("UPDATE products SET stock_qty = GREATEST(0, stock_qty - ?) WHERE id = ?");

            foreach ($_POST['parts'] as $part) {
                $pName    = trim($part['name'] ?? '');
                $pQty     = max(1, intval($part['qty'] ?? 1));
                $pPrice   = floatval($part['price'] ?? 0);
                $pTotal   = $pQty * $pPrice;
                $pProdId  = intval($part['product_id'] ?? 0);

                if (!empty($pName)) {
                    // Insert spare part record
                    $partStmt->execute([$savedId, $pProdId ?: null, $pName, $pQty, $pPrice, $pTotal]);

                    // Deduct from inventory if product_id is set
                    if ($pProdId > 0) {
                        $deductStmt->execute([$pQty, $pProdId]);
                    }
                }
            }
        }

        safeRedirect("repair_ticket.php?id=" . $savedId);
    }
}
?>

<main class="flex-1 min-w-0 space-y-4">
    <!-- Top Header matching Screenshot 4 -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center shrink-0">
                <i data-lucide="wrench" class="w-5 h-5 text-amber-500"></i>
            </div>
            <div>
                <h1 class="text-lg font-black text-slate-800 tracking-tight">
                    <?= $editJob ? 'Edit Repair Job Ticket' : 'Create Repair Job Ticket' ?>
                </h1>
                <p class="text-xs text-slate-400 font-medium">Record device diagnostics, customer specs, technician tasking, and billing</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="technicians.php?report=costs" class="px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5">
                <i data-lucide="trending-up" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span>Technician Costs</span>
            </a>
            <a href="repairs.php" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Back to Repairs</span>
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($errorMsg)): ?>
        <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-bold flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 shrink-0"></i>
            <span><?= htmlspecialchars($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <!-- Main Repair Ticket Form matching Screenshot 4 (6 Cards in Grid) -->
    <form method="POST" action="create_repair.php<?= $editId ? '?edit_id=' . $editId : '' ?>" id="repair-form" class="space-y-4">
        <input type="hidden" name="save_repair_job" value="1">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            
            <!-- ════════ LEFT COLUMN ════════ -->
            <div class="space-y-4">

                <!-- ── 1. Customer Information ── -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
                    <div class="bg-[#c00000] text-white px-4 py-2.5 flex items-center justify-between">
                        <div class="flex items-center gap-2 font-black text-xs uppercase tracking-wide">
                            <i data-lucide="user" class="w-4 h-4"></i>
                            <span>1. Customer Information</span>
                        </div>
                        <button type="button" onclick="openNewCustomerQuickModal()" class="px-2.5 py-0.5 rounded-lg bg-white/20 hover:bg-white/30 text-white font-extrabold text-[10px] tracking-tight">
                            + New Customer
                        </button>
                    </div>
                    <div class="p-4 space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Search Customer (by Name or Phone) <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                    <i data-lucide="search" class="w-4 h-4"></i>
                                </div>
                                <select id="customer-select" onchange="onCustomerSelectChange(this)" class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs font-medium">
                                    <option value="">-- Choose Existing Customer or type below --</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>" data-phone="<?= htmlspecialchars($c['phone']) ?>" <?= ($editJob && $editJob['customer_id'] == $c['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['phone']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Customer Full Name <span class="text-red-500">*</span></label>
                                <input type="hidden" name="customer_id" id="customer-id-input" value="<?= $editJob ? $editJob['customer_id'] : '' ?>">
                                <input type="text" name="customer_name" id="customer-name-input" required 
                                       value="<?= $editJob ? htmlspecialchars($editJob['customer_name']) : '' ?>"
                                       placeholder="e.g. Ahmed Mohamed"
                                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Phone Number</label>
                                <input type="text" name="customer_phone" id="customer-phone-input" 
                                       value="<?= $editJob ? htmlspecialchars($editJob['customer_phone']) : '' ?>"
                                       placeholder="e.g. 063XXXXXXX / 25263..."
                                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── 2. Device & Problem Description ── -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
                    <div class="bg-[#c00000] text-white px-4 py-2.5 flex items-center gap-2 font-black text-xs uppercase tracking-wide">
                        <i data-lucide="smartphone" class="w-4 h-4"></i>
                        <span>2. Device &amp; Problem Description</span>
                    </div>
                    <div class="p-4 space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Device Name / Model <span class="text-red-500">*</span></label>
                            <input type="text" name="device_model" required 
                                   value="<?= $editJob ? htmlspecialchars($editJob['device_model']) : '' ?>"
                                   placeholder="e.g. iPhone 13 Pro, HP EliteBook 840, Samsung A55"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Reported Problem / Defect <span class="text-red-500">*</span></label>
                            <textarea name="reported_problem" required rows="2" 
                                      placeholder="Describe what the customer says is wrong with the device..."
                                      class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs"><?= $editJob ? htmlspecialchars($editJob['reported_problem']) : '' ?></textarea>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Diagnostic / Initial Inspection Notes</label>
                            <textarea name="diagnostic_notes" rows="2" 
                                      placeholder="Initial inspection notes by receiving staff..."
                                      class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs"><?= $editJob ? htmlspecialchars($editJob['diagnostic_notes']) : '' ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- ── 3. Technician Assignment & Timeline ── -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
                    <div class="bg-[#c00000] text-white px-4 py-2.5 flex items-center justify-between">
                        <div class="flex items-center gap-2 font-black text-xs uppercase tracking-wide">
                            <i data-lucide="user-cog" class="w-4 h-4"></i>
                            <span>3. Technician Assignment &amp; Timeline</span>
                        </div>
                        <a href="technicians.php" target="_blank" class="px-2.5 py-0.5 rounded-lg bg-white/20 hover:bg-white/30 text-white font-extrabold text-[10px] tracking-tight">
                            + New Technician
                        </a>
                    </div>
                    <div class="p-4 space-y-3 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Assigned Technician <span class="text-red-500">*</span></label>
                                <select name="technician_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs">
                                    <option value="0">-- Select Technician --</option>
                                    <?php foreach ($technicians as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= ($editJob && $editJob['technician_id'] == $t['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['specialty']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Initial Status</label>
                                <select name="initial_status" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs font-bold text-slate-700">
                                    <option value="Received" <?= ($editJob && $editJob['status'] === 'Received') ? 'selected' : '' ?>>Received (At Counter)</option>
                                    <option value="In Progress" <?= ($editJob && $editJob['status'] === 'In Progress') ? 'selected' : '' ?>>In Progress</option>
                                    <option value="Ready" <?= ($editJob && $editJob['status'] === 'Ready') ? 'selected' : '' ?>>Ready</option>
                                    <option value="Delivered" <?= ($editJob && $editJob['status'] === 'Delivered') ? 'selected' : '' ?>>Delivered</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Intake Date <span class="text-red-500">*</span></label>
                                <input type="date" name="intake_date" required 
                                       value="<?= $editJob ? $editJob['intake_date'] : date('Y-m-d') ?>"
                                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Expected Completion Date</label>
                                <input type="date" name="expected_date" 
                                       value="<?= $editJob ? $editJob['expected_date'] : date('Y-m-d', strtotime('+1 day')) ?>"
                                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs">
                            </div>
                        </div>
                    </div>
                </div>

            </div>


            <!-- ════════ RIGHT COLUMN ════════ -->
            <div class="space-y-4">

                <!-- ── 4. Repair Service & Labor (up to 3 services) ── -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
                    <div class="bg-[#c00000] text-white px-4 py-2.5 flex items-center justify-between">
                        <div class="flex items-center gap-2 font-black text-xs uppercase tracking-wide">
                            <i data-lucide="wrench" class="w-4 h-4"></i>
                            <span>4. Repair Service &amp; Labor</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-200 font-extrabold text-[9px] border border-emerald-400/30">
                                Max 3 Services
                            </span>
                            <a href="repair_services.php" target="_blank" class="px-2 py-0.5 rounded-lg bg-white/20 hover:bg-white/30 text-white font-extrabold text-[10px]">
                                + New Service
                            </a>
                        </div>
                    </div>
                    <div class="p-4 space-y-3 text-xs">

                        <!-- Info note -->
                        <div class="flex items-start gap-2 p-2.5 bg-blue-50 rounded-xl border border-blue-200/80 text-[11px] text-blue-800">
                            <i data-lucide="info" class="w-3.5 h-3.5 text-blue-500 shrink-0 mt-0.5"></i>
                            <span>Ilaa <strong>3 adeeg</strong> ayaad ku dari kartaa shaqada dayactirka kasta (tusaale: Screen Replace, Back Glass, Speaker Replace). Qiimaha adeegyada dhammaantood waa la uruursan doonaa oo wadarta loogu xisaabin doonaa.</span>
                        </div>

                        <!-- Service Rows Table -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-black border-b border-slate-200">
                                    <tr>
                                        <th class="py-2 px-3">#</th>
                                        <th class="py-2 px-3">Service / Description</th>
                                        <th class="py-2 px-3 w-28 text-right">Cost ($)</th>
                                        <th class="py-2 px-2 w-8"></th>
                                    </tr>
                                </thead>
                                <tbody id="services-tbody">
                                    <!-- Service Row 1 -->
                                    <tr class="border-b border-slate-100 service-row" id="svc-row-1">
                                        <td class="py-2 px-3 font-black text-slate-400">1</td>
                                        <td class="py-2 px-3">
                                            <div class="flex items-center gap-2">
                                                <select onchange="onServiceDropChange(this, 1)" class="w-36 shrink-0 px-2 py-1.5 rounded-lg border border-slate-200 focus:border-[#c00000] outline-none text-xs" title="Pick from catalog">
                                                    <option value="0" data-cost="0.00">-- Catalog --</option>
                                                    <?php foreach ($services as $s): ?>
                                                        <option value="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['name']) ?>" data-cost="<?= $s['labor_cost'] ?>" <?= ($editJob && $editJob['service_id'] == $s['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($s['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="text" name="services[0][name]" id="svc-name-1"
                                                       value="<?= $editJob ? htmlspecialchars($editJob['service_name']) : '' ?>"
                                                       placeholder="e.g. Screen Replacement"
                                                       class="flex-1 px-2 py-1.5 rounded-lg border border-slate-200 focus:border-[#c00000] outline-none text-xs">
                                            </div>
                                        </td>
                                        <td class="py-2 px-3">
                                            <input type="number" step="0.01" min="0" name="services[0][cost]" id="svc-cost-1"
                                                   value="<?= $editJob ? number_format($editJob['service_cost'], 2, '.', '') : '0.00' ?>"
                                                   oninput="recalculateTotals()"
                                                   class="w-full px-2 py-1.5 rounded-lg border border-slate-200 focus:border-[#c00000] outline-none text-xs font-black text-right text-slate-800">
                                        </td>
                                        <td class="py-2 px-2 text-center text-slate-300">—</td>
                                    </tr>

                                    <!-- Service Row 2 -->
                                    <tr class="border-b border-slate-100 service-row" id="svc-row-2">
                                        <td class="py-2 px-3 font-black text-slate-400">2</td>
                                        <td class="py-2 px-3">
                                            <div class="flex items-center gap-2">
                                                <select onchange="onServiceDropChange(this, 2)" class="w-36 shrink-0 px-2 py-1.5 rounded-lg border border-slate-200 focus:border-[#c00000] outline-none text-xs" title="Pick from catalog">
                                                    <option value="0" data-cost="0.00">-- Catalog --</option>
                                                    <?php foreach ($services as $s): ?>
                                                        <option value="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['name']) ?>" data-cost="<?= $s['labor_cost'] ?>">
                                                            <?= htmlspecialchars($s['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="text" name="services[1][name]" id="svc-name-2"
                                                       placeholder="e.g. Back Glass Replacement"
                                                       class="flex-1 px-2 py-1.5 rounded-lg border border-slate-200 focus:border-[#c00000] outline-none text-xs">
                                            </div>
                                        </td>
                                        <td class="py-2 px-3">
                                            <input type="number" step="0.01" min="0" name="services[1][cost]" id="svc-cost-2"
                                                   value="0.00" oninput="recalculateTotals()"
                                                   class="w-full px-2 py-1.5 rounded-lg border border-slate-200 focus:border-[#c00000] outline-none text-xs font-black text-right text-slate-800">
                                        </td>
                                        <td class="py-2 px-2 text-center">
                                            <button type="button" onclick="clearServiceRow(2)" class="text-slate-300 hover:text-red-500 transition-colors" title="Clear">
                                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Service Row 3 -->
                                    <tr class="service-row" id="svc-row-3">
                                        <td class="py-2 px-3 font-black text-slate-400">3</td>
                                        <td class="py-2 px-3">
                                            <div class="flex items-center gap-2">
                                                <select onchange="onServiceDropChange(this, 3)" class="w-36 shrink-0 px-2 py-1.5 rounded-lg border border-slate-200 focus:border-[#c00000] outline-none text-xs" title="Pick from catalog">
                                                    <option value="0" data-cost="0.00">-- Catalog --</option>
                                                    <?php foreach ($services as $s): ?>
                                                        <option value="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['name']) ?>" data-cost="<?= $s['labor_cost'] ?>">
                                                            <?= htmlspecialchars($s['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="text" name="services[2][name]" id="svc-name-3"
                                                       placeholder="e.g. Speaker Replacement"
                                                       class="flex-1 px-2 py-1.5 rounded-lg border border-slate-200 focus:border-[#c00000] outline-none text-xs">
                                            </div>
                                        </td>
                                        <td class="py-2 px-3">
                                            <input type="number" step="0.01" min="0" name="services[2][cost]" id="svc-cost-3"
                                                   value="0.00" oninput="recalculateTotals()"
                                                   class="w-full px-2 py-1.5 rounded-lg border border-slate-200 focus:border-[#c00000] outline-none text-xs font-black text-right text-slate-800">
                                        </td>
                                        <td class="py-2 px-2 text-center">
                                            <button type="button" onclick="clearServiceRow(3)" class="text-slate-300 hover:text-red-500 transition-colors" title="Clear">
                                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-slate-50 border-t border-slate-200">
                                        <td colspan="2" class="py-2 px-3 text-right font-black text-slate-700 text-[11px] uppercase tracking-wide">
                                            Total Services Cost:
                                        </td>
                                        <td class="py-2 px-3 text-right font-black text-emerald-700" id="services-total-display">$0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <!-- Hidden field to store combined service info for backend -->
                        <input type="hidden" name="service_cost" id="service-cost-input" value="<?= $editJob ? number_format($editJob['service_cost'], 2, '.', '') : '0.00' ?>">
                        <input type="hidden" name="custom_service_desc" id="custom-service-desc" value="<?= $editJob ? htmlspecialchars($editJob['service_name']) : '' ?>">
                        <input type="hidden" name="service_id" value="0">
                        <span class="text-[10px] text-emerald-600 font-bold flex items-center gap-1">
                            <i data-lucide="trending-up" class="w-3 h-3"></i>
                            Dhammaan qiimeyaasha adeegyada waxaa lagu xisaabin doonaa faa'iidada (Store Profit)
                        </span>
                    </div>
                </div>

                <!-- ── 5. Inventory Spare Parts Used ── -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
                    <div class="bg-[#c00000] text-white px-4 py-2.5 flex items-center justify-between">
                        <div class="flex items-center gap-2 font-black text-xs uppercase tracking-wide">
                            <i data-lucide="package" class="w-4 h-4"></i>
                            <span>5. Inventory Spare Parts Used</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-md bg-white/20 text-white font-extrabold text-[9px]">
                            Auto-added to Sales
                        </span>
                    </div>
                    <div class="p-4 space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Search Inventory Part (by Name or SKU)</label>
                            <div class="relative">
                                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                    <i data-lucide="search" class="w-4 h-4"></i>
                                </div>
                                <select id="product-picker" onchange="onPartPicked(this)" class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs">
                                    <option value="">Type part name, barcode, or SKU...</option>
                                    <?php foreach ($inventoryProducts as $p): ?>
                                        <option value="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>" data-price="<?= $p['retail_price'] ?>" data-stock="<?= $p['stock_qty'] ?>">
                                            <?= htmlspecialchars($p['name']) ?> - $<?= number_format($p['retail_price'], 2) ?> (Stock: <?= $p['stock_qty'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Dynamic Parts Table -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden">
                            <table class="w-full text-left text-xs" id="parts-table">
                                <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold border-b border-slate-200">
                                    <tr>
                                        <th class="py-2 px-3">Part Name</th>
                                        <th class="py-2 px-3 w-16 text-center">Qty</th>
                                        <th class="py-2 px-3 w-20 text-right">Price ($)</th>
                                        <th class="py-2 px-3 w-20 text-right">Total ($)</th>
                                        <th class="py-2 px-2 w-8 text-center"></th>
                                    </tr>
                                </thead>
                                <tbody id="parts-tbody" class="divide-y divide-slate-100">
                                    <tr id="no-parts-row">
                                        <td colspan="5" class="py-4 text-center text-slate-400 italic text-[11px]">
                                            No spare parts attached yet
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-right pt-1">
                            <input type="hidden" name="parts_total_cost" id="parts-total-cost" value="<?= $editJob ? $editJob['parts_cost'] : '0.00' ?>">
                            <span class="text-xs font-bold text-slate-600">Total Parts Cost: </span>
                            <span id="parts-total-display" class="font-black text-red-600 text-xs">$<?= $editJob ? number_format($editJob['parts_cost'], 2) : '0.00' ?></span>
                        </div>
                    </div>
                </div>

                <!-- ── 6. Billing & Customer Balance ── -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
                    <div class="bg-[#c00000] text-white px-4 py-2.5 flex items-center gap-2 font-black text-xs uppercase tracking-wide">
                        <i data-lucide="receipt" class="w-4 h-4"></i>
                        <span>6. Billing &amp; Customer Balance</span>
                    </div>
                    <div class="p-4 space-y-3 text-xs">
                        
                        <!-- Technician Cost Box (Green) -->
                        <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-200/80 flex items-center justify-between gap-3">
                            <div>
                                <span class="font-extrabold text-emerald-800 text-xs flex items-center gap-1.5">
                                    <i data-lucide="user-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>Technician Cost ($):</span>
                                </span>
                                <span class="text-[10px] text-emerald-600/90 font-medium block">Lacagta teknishaanka — kuma jirto biilka macmiilka; waxaa laga jari doonaa faa'iidada.</span>
                            </div>
                            <div class="flex items-center gap-1.5 w-32">
                                <span class="font-bold text-emerald-800">$</span>
                                <input type="number" step="0.01" min="0" name="technician_cost" id="tech-cost-input"
                                       value="<?= $editJob ? number_format($editJob['technician_cost'], 2, '.', '') : '0.00' ?>"
                                       oninput="recalculateTotals()"
                                       class="w-full px-2.5 py-1.5 rounded-lg border border-emerald-300 bg-white font-black text-emerald-700 text-right outline-none text-xs">
                            </div>
                        </div>

                        <!-- Calculation Breakdown Table -->
                        <div class="space-y-1.5 pt-1 text-slate-600 border-t border-slate-100">
                            <div class="flex items-center justify-between">
                                <span>Company Service Cost:</span>
                                <span id="summary-service-cost" class="font-bold text-slate-800">$0.00</span>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-orange-600">
                                <span>Technician Pay (laga jaraa faa'iidada):</span>
                                <span id="summary-tech-cost" class="font-bold">$0.00</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Inventory Spare Parts Total:</span>
                                <span id="summary-parts-cost" class="font-bold text-slate-800">$0.00</span>
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <span class="font-bold text-slate-700">Discount ($):</span>
                                <input type="number" step="0.01" min="0" name="discount" id="discount-input"
                                       value="<?= $editJob ? number_format($editJob['discount'], 2, '.', '') : '0.00' ?>"
                                       oninput="recalculateTotals()"
                                       class="w-24 px-2 py-1 rounded-lg border border-slate-200 text-right font-bold text-xs outline-none">
                            </div>
                        </div>

                        <!-- Grand Total Customer Bill -->
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                            <span class="font-black text-slate-800 text-xs uppercase tracking-tight">Total Customer Bill (Net Price):</span>
                            <span id="grand-total-display" class="font-black text-[#c00000] text-xl tracking-tight">$0.00</span>
                        </div>

                        <!-- Estimated Store Profit Box -->
                        <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200/80 flex items-center justify-between text-xs">
                            <span class="font-bold flex items-center gap-1.5">
                                <i data-lucide="trending-up" class="w-3.5 h-3.5 text-emerald-600"></i>
                                <span>Estimated Store Profit:</span>
                            </span>
                            <span id="store-profit-display" class="font-black text-emerald-700 text-sm">$0.00</span>
                        </div>

                        <!-- Paid Amount & Payment Method -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Amount Paid Now ($)</label>
                                <input type="number" step="0.01" min="0" name="paid_amount" id="paid-amount-input"
                                       value="<?= $editJob ? number_format($editJob['paid_amount'], 2, '.', '') : '0.00' ?>"
                                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs font-bold text-slate-900">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Payment Method</label>
                                <select name="payment_method" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs font-bold text-slate-700">
                                    <option value="Cash" <?= ($editJob && $editJob['payment_method'] === 'Cash') ? 'selected' : '' ?>>Cash</option>
                                    <option value="Zaad Services" <?= ($editJob && $editJob['payment_method'] === 'Zaad Services') ? 'selected' : '' ?>>Zaad Services</option>
                                    <option value="E-Dahab" <?= ($editJob && $editJob['payment_method'] === 'E-Dahab') ? 'selected' : '' ?>>E-Dahab</option>
                                    <option value="Bank Transfer" <?= ($editJob && $editJob['payment_method'] === 'Bank Transfer') ? 'selected' : '' ?>>Bank Transfer</option>
                                </select>
                            </div>
                        </div>

                        <!-- Action Buttons matching Screenshot 4 -->
                        <div class="pt-3 flex items-center gap-3">
                            <button type="submit" class="flex-1 py-3 px-4 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-bold text-xs tracking-wide shadow-md shadow-red-900/30 flex items-center justify-center gap-2 transition-all cursor-pointer">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                <span><?= $editJob ? 'Update Repair &amp; Print Ticket' : 'Register Repair &amp; Print Ticket' ?></span>
                            </button>
                            <a href="repairs.php" class="px-5 py-3 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors">
                                Cancel
                            </a>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </form>
</main>

<script>
let partsList = <?= json_encode(array_map(function($p) {
    return [
        'id' => $p['product_id'] ?: '',
        'name' => $p['part_name'],
        'price' => floatval($p['price']),
        'qty' => intval($p['qty'])
    ];
}, $editParts ?? [])) ?>;

function onCustomerSelectChange(select) {
    const selected = select.options[select.selectedIndex];
    if (selected && selected.value) {
        document.getElementById('customer-id-input').value = selected.value;
        document.getElementById('customer-name-input').value = selected.getAttribute('data-name') || '';
        document.getElementById('customer-phone-input').value = selected.getAttribute('data-phone') || '';
    }
}

function openNewCustomerQuickModal() {
    document.getElementById('customer-id-input').value = '';
    document.getElementById('customer-name-input').value = '';
    document.getElementById('customer-phone-input').value = '';
    document.getElementById('customer-select').value = '';
    document.getElementById('customer-name-input').focus();
}

function onServiceDropChange(select, rowNum) {
    const opt = select.options[select.selectedIndex];
    if (!opt || opt.value == 0) { clearServiceRow(rowNum); return; }
    const cost = parseFloat(opt.getAttribute('data-cost') || 0);
    const name = opt.getAttribute('data-name') || '';
    document.getElementById('svc-name-' + rowNum).value = name;
    document.getElementById('svc-cost-' + rowNum).value = cost.toFixed(2);
    recalculateTotals();
}

function clearServiceRow(rowNum) {
    const nameEl = document.getElementById('svc-name-' + rowNum);
    const costEl = document.getElementById('svc-cost-' + rowNum);
    if (nameEl) nameEl.value = '';
    if (costEl) costEl.value = '0.00';
    recalculateTotals();
}

function onPartPicked(select) {
    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) return;

    const pId = opt.value;
    const pName = opt.getAttribute('data-name');
    const pPrice = parseFloat(opt.getAttribute('data-price') || 0);

    // Check if already in list
    const existing = partsList.find(p => p.id == pId);
    if (existing) {
        existing.qty += 1;
    } else {
        partsList.push({
            id: pId,
            name: pName,
            price: pPrice,
            qty: 1
        });
    }

    select.value = '';
    renderPartsTable();
}

function removePart(index) {
    partsList.splice(index, 1);
    renderPartsTable();
}

function onPartQtyChange(index, newQty) {
    const q = parseInt(newQty) || 1;
    partsList[index].qty = Math.max(1, q);
    renderPartsTable();
}

function onPartPriceChange(index, newPrice) {
    const val = parseFloat(newPrice);
    partsList[index].price = isNaN(val) ? 0 : Math.max(0, val);
    const totalEl = document.getElementById('part-row-total-' + index);
    if (totalEl) {
        totalEl.textContent = '$' + (partsList[index].qty * partsList[index].price).toFixed(2);
    }
    recalculateTotals();
}

function renderPartsTable() {
    const tbody = document.getElementById('parts-tbody');
    tbody.innerHTML = '';

    if (partsList.length === 0) {
        tbody.innerHTML = `
            <tr id="no-parts-row">
                <td colspan="5" class="py-4 text-center text-slate-400 italic text-[11px]">
                    No spare parts attached yet
                </td>
            </tr>
        `;
    } else {
        partsList.forEach((p, idx) => {
            const total = p.qty * p.price;
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50/60';
            tr.innerHTML = `
                <td class="py-2 px-3 font-bold text-slate-800">
                    <input type="hidden" name="parts[${idx}][product_id]" value="${p.id}">
                    <input type="hidden" name="parts[${idx}][name]" value="${p.name}">
                    ${p.name}
                </td>
                <td class="py-2 px-3 text-center">
                    <input type="number" min="1" name="parts[${idx}][qty]" value="${p.qty}" 
                           onchange="onPartQtyChange(${idx}, this.value)"
                           class="w-12 px-1 py-0.5 rounded border border-slate-200 focus:border-[#c00000] outline-none text-center text-xs font-bold">
                </td>
                <td class="py-2 px-3 text-right font-medium text-slate-600">
                    <div class="inline-flex items-center gap-0.5">
                        <span class="text-slate-400 text-[11px]">$</span>
                        <input type="number" step="0.01" min="0" name="parts[${idx}][price]" value="${p.price.toFixed(2)}"
                               oninput="onPartPriceChange(${idx}, this.value)"
                               class="w-20 px-1.5 py-0.5 rounded border border-slate-200 focus:border-[#c00000] outline-none text-right text-xs font-bold text-slate-800">
                    </div>
                </td>
                <td id="part-row-total-${idx}" class="py-2 px-3 text-right font-black text-slate-800">
                    $${total.toFixed(2)}
                </td>
                <td class="py-2 px-2 text-center">
                    <button type="button" onclick="removePart(${idx})" class="text-red-500 hover:text-red-700 p-0.5">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        if (window.lucide) lucide.createIcons();
    }

    recalculateTotals();
}

function recalculateTotals() {
    // Sum all 3 service rows
    let serviceCost = 0;
    let svcNames = [];
    for (let i = 1; i <= 3; i++) {
        const costEl = document.getElementById('svc-cost-' + i);
        const nameEl = document.getElementById('svc-name-' + i);
        if (costEl) serviceCost += parseFloat(costEl.value) || 0;
        if (nameEl && nameEl.value.trim()) svcNames.push(nameEl.value.trim());
    }

    // Update hidden fields for form submission
    document.getElementById('service-cost-input').value = serviceCost.toFixed(2);
    document.getElementById('custom-service-desc').value = svcNames.join(' + ');

    const techCost = parseFloat(document.getElementById('tech-cost-input').value) || 0;
    const discount = parseFloat(document.getElementById('discount-input').value) || 0;

    let partsTotal = 0;
    partsList.forEach(p => {
        partsTotal += (p.qty * p.price);
    });

    document.getElementById('parts-total-cost').value = partsTotal.toFixed(2);
    document.getElementById('parts-total-display').textContent = '$' + partsTotal.toFixed(2);
    document.getElementById('services-total-display').textContent = '$' + serviceCost.toFixed(2);

    document.getElementById('summary-service-cost').textContent = '$' + serviceCost.toFixed(2);
    document.getElementById('summary-tech-cost').textContent = '$' + techCost.toFixed(2);
    document.getElementById('summary-parts-cost').textContent = '$' + partsTotal.toFixed(2);

    const grandTotal = Math.max(0, (serviceCost + partsTotal) - discount);
    document.getElementById('grand-total-display').textContent = '$' + grandTotal.toFixed(2);

    // Estimated store profit = Grand Total - Technician Pay - discount
    // (Technician cost is NOT billed to customer — it's deducted from store profit)
    const estimatedProfit = Math.max(0, (serviceCost + partsTotal) - discount - techCost);
    document.getElementById('store-profit-display').textContent = '$' + estimatedProfit.toFixed(2);

    // If paid amount is untouched, prefill with grandTotal
    const paidInput = document.getElementById('paid-amount-input');
    if (!paidInput.dataset.touched) {
        paidInput.value = grandTotal.toFixed(2);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const paidInput = document.getElementById('paid-amount-input');
    <?php if ($editJob): ?>
    paidInput.dataset.touched = 'true';
    <?php endif; ?>
    paidInput.addEventListener('input', function() {
        this.dataset.touched = 'true';
    });
    if (partsList.length > 0) {
        renderPartsTable();
    } else {
        recalculateTotals();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
