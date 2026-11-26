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

// Handle Delete Technician
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);
    $pdo->prepare("DELETE FROM technicians WHERE id = ?")->execute([$delId]);
    safeRedirect("technicians.php?deleted=1");
}

// Handle Add / Edit Technician
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_technician'])) {
    $techId = intval($_POST['technician_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $specialty = trim($_POST['specialty'] ?? 'General Technician');
    $balance = floatval($_POST['balance'] ?? 0);
    $status = trim($_POST['status'] ?? 'Active');

    if (empty($name)) {
        $errorMsg = "Fadlan geli magaca farsamayaqaanka (Technician Name is required).";
    } else {
        if ($techId > 0) {
            $stmt = $pdo->prepare("UPDATE technicians SET name = ?, phone = ?, specialty = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $specialty, $status, $techId]);
            safeRedirect("technicians.php?updated=1");
        } else {
            $stmt = $pdo->prepare("INSERT INTO technicians (name, phone, specialty, balance, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $specialty, $balance, $status]);
            safeRedirect("technicians.php?created=1");
        }
    }
}

// Handle Pay / Settle Technician Balance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settle_balance'])) {
    $techId = intval($_POST['settle_tech_id'] ?? 0);
    $settleAmount = floatval($_POST['settle_amount'] ?? 0);

    if ($techId > 0 && $settleAmount > 0) {
        $stmt = $pdo->prepare("UPDATE technicians SET balance = GREATEST(0, balance - ?) WHERE id = ?");
        $stmt->execute([$settleAmount, $techId]);
        safeRedirect("technicians.php?settled=1");
    }
}

if (isset($_GET['created'])) $successMsg = "Farsamayaqaanka cusub si guul leh ayaa loo diiwaangeliyay (Technician registered successfully).";
if (isset($_GET['updated'])) $successMsg = "Xogta farsamayaqaanka waa la cusbooneysiiyay (Technician updated successfully).";
if (isset($_GET['deleted'])) $successMsg = "Farsamayaqaanka waa la tirtiray (Technician deleted successfully).";
if (isset($_GET['settled'])) $successMsg = "Lacag-bixinta farsamayaqaanka si guul leh ayaa loo diiwaangeliyay (Payout recorded successfully).";

// Summary Stats
$totalTechs = $pdo->query("SELECT COUNT(*) FROM technicians WHERE status = 'Active'")->fetchColumn();
$totalBalances = $pdo->query("SELECT IFNULL(SUM(balance), 0) FROM technicians")->fetchColumn();
$totalRepairsHandled = $pdo->query("SELECT COUNT(*) FROM repair_jobs WHERE technician_id IS NOT NULL AND technician_id > 0")->fetchColumn();

// Fetch Technicians with assigned job counts
$query = "
    SELECT t.*, 
    (SELECT COUNT(*) FROM repair_jobs j WHERE j.technician_id = t.id) AS assigned_jobs
    FROM technicians t
    ORDER BY t.id ASC
";
$technicians = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="flex-1 min-w-0 space-y-4">
    <!-- Top Header matching Screenshot 2 -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center shrink-0">
                <i data-lucide="users" class="w-5 h-5 text-amber-500"></i>
            </div>
            <div>
                <h1 class="text-lg font-black text-slate-800 tracking-tight">Technicians Management</h1>
                <p class="text-xs text-slate-400 font-medium">Manage repair technicians, contact numbers, job assignments, and outstanding balances</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="openAddTechModal()" class="px-3.5 py-2 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-bold text-xs shadow-md shadow-red-900/20 transition-all flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                <span>Add Technician</span>
            </button>
            <a href="technicians.php?report=costs" class="px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5">
                <i data-lucide="file-text" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span>Technician Costs Report</span>
            </a>
            <a href="repairs.php" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Repair Jobs</span>
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
    <?php if (!empty($errorMsg)): ?>
        <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-bold flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 shrink-0"></i>
            <span><?= htmlspecialchars($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <!-- 3 Stat Cards matching Screenshot 2 -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- 1. Total Technicians -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-5 relative overflow-hidden border-l-4 border-l-red-600">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase text-slate-400 tracking-wider block">Total Technicians</span>
                    <span class="text-2xl font-black text-slate-800 mt-1 block"><?= number_format($totalTechs) ?></span>
                    <span class="text-[11px] text-slate-400 font-medium block mt-1">Active repair specialists</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                    <i data-lucide="wrench" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <!-- 2. Total Technician Balances -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-5 relative overflow-hidden border-l-4 border-l-emerald-500">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase text-slate-400 tracking-wider block">Total Technician Balances</span>
                    <span class="text-2xl font-black text-emerald-600 mt-1 block">$<?= number_format($totalBalances, 2) ?></span>
                    <span class="text-[11px] text-slate-400 font-medium block mt-1">Total earnings currently accumulated</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="wallet" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <!-- 3. Total Repairs Handled -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-5 relative overflow-hidden border-l-4 border-l-amber-500">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase text-slate-400 tracking-wider block">Total Repairs Handled</span>
                    <span class="text-2xl font-black text-slate-800 mt-1 block"><?= number_format($totalRepairsHandled) ?></span>
                    <span class="text-[11px] text-slate-400 font-medium block mt-1">Assigned device repair tickets</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="check-check" class="w-5 h-5"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Registered Technicians Table matching Screenshot 2 -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center gap-2">
            <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i>
            <div>
                <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider">Registered Technicians</h3>
                <p class="text-[11px] text-slate-400 font-medium">All technicians available for repair assignment</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase text-[10px] font-black tracking-wider border-b border-slate-200/80">
                        <th class="py-3 px-4 w-12 text-center">#</th>
                        <th class="py-3 px-4">Technician Name</th>
                        <th class="py-3 px-4">Phone Number</th>
                        <th class="py-3 px-4 text-center">Assigned Jobs</th>
                        <th class="py-3 px-4 text-center">Accumulated Balance ($)</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($technicians)): ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 font-medium">
                                Wax farsamayaqaan ah laguma darin weli. Guji "Add Technician" si aad mid cusub ugu darto.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($technicians as $idx => $t): ?>
                            <?php $initial = strtoupper(substr($t['name'], 0, 1)); ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 text-center font-bold text-slate-400">
                                    <?= $idx + 1 ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-red-100 text-red-600 font-black text-xs flex items-center justify-center shrink-0">
                                            <?= $initial ?>
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-800 block"><?= htmlspecialchars($t['name']) ?></span>
                                            <span class="inline-block text-[9px] font-semibold text-slate-400 bg-slate-100 px-1.5 py-0.2 rounded-md">
                                                User Account #<?= $t['id'] ?> &bull; <?= htmlspecialchars($t['specialty']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="phone" class="w-3 h-3 text-slate-400"></i>
                                        <span><?= !empty($t['phone']) ? htmlspecialchars($t['phone']) : '-' ?></span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <a href="repairs.php?technician=<?= $t['id'] ?>" class="inline-block px-2.5 py-1 rounded-full bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-[10px] border border-blue-200 transition-colors">
                                        <?= intval($t['assigned_jobs']) ?> Jobs
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-block px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-black text-xs border border-emerald-200">
                                        $<?= number_format($t['balance'], 2) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <?php if ($t['status'] === 'Active'): ?>
                                        <span class="inline-block px-2 py-0.5 rounded-full bg-emerald-600 text-white font-extrabold text-[10px] shadow-2xs">
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-block px-2 py-0.5 rounded-full bg-slate-200 text-slate-600 font-extrabold text-[10px]">
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Edit -->
                                        <button type="button" 
                                                onclick="openEditTechModal(<?= htmlspecialchars(json_encode($t)) ?>)"
                                                class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors" title="Edit">
                                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <!-- Settle / Payout Balance -->
                                        <button type="button" 
                                                onclick="openSettleModal(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['name'])) ?>', <?= $t['balance'] ?>)"
                                                class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 flex items-center justify-center transition-colors" title="Pay / Settle Balance">
                                            <i data-lucide="banknote" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <!-- View Jobs -->
                                        <a href="repairs.php?technician=<?= $t['id'] ?>" 
                                           class="w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 flex items-center justify-center transition-colors" title="View Assigned Jobs">
                                            <i data-lucide="clipboard-list" class="w-3.5 h-3.5"></i>
                                        </a>
                                        <!-- Delete -->
                                        <a href="technicians.php?delete_id=<?= $t['id'] ?>" 
                                           onclick="return confirm('Ma hubtaa inaad tirtirto farsamayaqaankan?');"
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
    </div>
</main>

<!-- Modal: Add / Edit Technician -->
<div id="tech-modal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="bg-gradient-to-r from-[#c00000] to-red-600 text-white p-4 flex items-center justify-between">
            <h3 id="modal-tech-title" class="text-sm font-black tracking-wide">Add New Technician</h3>
            <button type="button" onclick="closeTechModal()" class="text-white/80 hover:text-white p-1 rounded-lg">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form method="POST" action="technicians.php" class="p-5 space-y-4 text-xs">
            <input type="hidden" name="save_technician" value="1">
            <input type="hidden" name="technician_id" id="form-tech-id" value="0">

            <div>
                <label class="block font-bold text-slate-700 mb-1">Technician Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="form-tech-name" required placeholder="e.g. Ahmed Nur Farah"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Phone Number</label>
                <input type="text" name="phone" id="form-tech-phone" placeholder="e.g. 063XXXXXXX / 064434"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Specialty / Role</label>
                <input type="text" name="specialty" id="form-tech-specialty" placeholder="e.g. Mobile & Laptop Hardware, Electronics"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs">
            </div>

            <div class="grid grid-cols-2 gap-3" id="balance-group">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Opening Balance ($)</label>
                    <input type="number" step="0.01" name="balance" id="form-tech-balance" value="0.00"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs font-bold text-emerald-700">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Status</label>
                    <select name="status" id="form-tech-status" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs font-bold">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeTechModal()" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-bold text-xs shadow-md shadow-red-900/20">
                    Save Technician
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Settle Technician Balance -->
<div id="settle-modal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-sm overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="bg-emerald-600 text-white p-4 flex items-center justify-between">
            <h3 class="text-sm font-black tracking-wide">Pay Technician Balance</h3>
            <button type="button" onclick="closeSettleModal()" class="text-white/80 hover:text-white p-1 rounded-lg">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form method="POST" action="technicians.php" class="p-5 space-y-4 text-xs">
            <input type="hidden" name="settle_balance" value="1">
            <input type="hidden" name="settle_tech_id" id="settle-tech-id" value="0">

            <div>
                <span class="text-slate-500 font-medium block">Technician:</span>
                <span id="settle-tech-name" class="font-bold text-slate-800 text-sm block mt-0.5"></span>
            </div>

            <div>
                <span class="text-slate-500 font-medium block">Current Accumulated Balance:</span>
                <span id="settle-tech-balance" class="font-black text-emerald-600 text-lg block mt-0.5"></span>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Payout Amount ($) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0.01" name="settle_amount" id="settle-amount" required
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-emerald-600 outline-none text-xs font-black text-emerald-700 text-base">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeSettleModal()" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-900/20">
                    Confirm Payout
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddTechModal() {
    document.getElementById('modal-tech-title').textContent = 'Add New Technician';
    document.getElementById('form-tech-id').value = '0';
    document.getElementById('form-tech-name').value = '';
    document.getElementById('form-tech-phone').value = '';
    document.getElementById('form-tech-specialty').value = 'General Technician';
    document.getElementById('form-tech-balance').value = '0.00';
    document.getElementById('form-tech-status').value = 'Active';
    document.getElementById('tech-modal').classList.remove('hidden');
}

function openEditTechModal(tech) {
    document.getElementById('modal-tech-title').textContent = 'Edit Technician';
    document.getElementById('form-tech-id').value = tech.id;
    document.getElementById('form-tech-name').value = tech.name;
    document.getElementById('form-tech-phone').value = tech.phone || '';
    document.getElementById('form-tech-specialty').value = tech.specialty || '';
    document.getElementById('form-tech-balance').value = parseFloat(tech.balance).toFixed(2);
    document.getElementById('form-tech-status').value = tech.status;
    document.getElementById('tech-modal').classList.remove('hidden');
}

function closeTechModal() {
    document.getElementById('tech-modal').classList.add('hidden');
}

function openSettleModal(id, name, balance) {
    document.getElementById('settle-tech-id').value = id;
    document.getElementById('settle-tech-name').textContent = name;
    document.getElementById('settle-tech-balance').textContent = '$' + parseFloat(balance).toFixed(2);
    document.getElementById('settle-amount').value = parseFloat(balance).toFixed(2);
    document.getElementById('settle-modal').classList.remove('hidden');
}

function closeSettleModal() {
    document.getElementById('settle-modal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
