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

// Handle Delete Service
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);
    $pdo->prepare("DELETE FROM repair_services WHERE id = ?")->execute([$delId]);
    safeRedirect("repair_services.php?deleted=1");
}

// Handle Add/Edit Service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_service'])) {
    $serviceId = intval($_POST['service_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $laborCost = floatval($_POST['labor_cost'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $errorMsg = "Fadlan geli magaca adeegga dayactirka (Service Name is required).";
    } else {
        if ($serviceId > 0) {
            $stmt = $pdo->prepare("UPDATE repair_services SET name = ?, labor_cost = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $laborCost, $description, $serviceId]);
            safeRedirect("repair_services.php?updated=1");
        } else {
            $stmt = $pdo->prepare("INSERT INTO repair_services (name, labor_cost, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $laborCost, $description]);
            safeRedirect("repair_services.php?created=1");
        }
    }
}

if (isset($_GET['created'])) $successMsg = "Adeegga cusub si guul leh ayaa loo diiwaangeliyay (Service added successfully).";
if (isset($_GET['updated'])) $successMsg = "Adeegga si guul leh ayaa loo cusbooneysiiyay (Service updated successfully).";
if (isset($_GET['deleted'])) $successMsg = "Adeegga waa la tirtiray (Service deleted successfully).";

// Fetch Services with repair job count
$query = "
    SELECT s.*, 
    (SELECT COUNT(*) FROM repair_jobs j WHERE j.service_id = s.id OR LOWER(j.service_name) = LOWER(s.name)) AS total_repairs
    FROM repair_services s
    ORDER BY s.id ASC
";
$services = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="flex-1 min-w-0 space-y-4">
    <!-- Top Header matching Screenshot 1 -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center shrink-0">
                <i data-lucide="settings" class="w-5 h-5 text-amber-500"></i>
            </div>
            <div>
                <h1 class="text-lg font-black text-slate-800 tracking-tight">Repair Service Catalog</h1>
                <p class="text-xs text-slate-400 font-medium">Manage predefined repair service offerings, default labor rates, and service descriptions</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="repairs.php" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Back to Repairs</span>
            </a>
            <button type="button" onclick="openAddServiceModal()" class="px-3.5 py-2 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-bold text-xs shadow-md shadow-red-900/20 transition-all flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Add Service</span>
            </button>
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

    <!-- Services Table matching Screenshot 1 -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-[#c00000] text-white uppercase text-[11px] font-black tracking-wider">
                        <th class="py-3 px-4 w-12 text-center">#</th>
                        <th class="py-3 px-4">Service Name</th>
                        <th class="py-3 px-4">Default Labor Cost</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4 text-center">Total Repairs</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 font-medium">
                                Wax adeegyo ah laguma darin weli. Guji "Add Service" si aad mid cusub ugu darto.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($services as $idx => $s): ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4 text-center font-bold text-slate-500">
                                    <?= $idx + 1 ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2 font-bold text-red-600">
                                        <i data-lucide="wrench" class="w-3.5 h-3.5 text-amber-500 shrink-0"></i>
                                        <span><?= htmlspecialchars($s['name']) ?></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-block px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-extrabold text-xs border border-emerald-200">
                                        $<?= number_format($s['labor_cost'], 2) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500">
                                    <?= !empty($s['description']) ? htmlspecialchars($s['description']) : '<span class="text-slate-300 italic">No description provided</span>' ?>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-block px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-bold text-[10px] border border-slate-200">
                                        <?= intval($s['total_repairs']) ?> Jobs
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" 
                                                onclick="openEditServiceModal(<?= htmlspecialchars(json_encode($s)) ?>)"
                                                class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] transition-colors">
                                            Edit
                                        </button>
                                        <a href="repair_services.php?delete_id=<?= $s['id'] ?>" 
                                           onclick="return confirm('Ma hubtaa inaad tirtirto adeeggan?');"
                                           class="px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 font-bold text-[10px] transition-colors">
                                            Delete
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

<!-- Modal: Add / Edit Service -->
<div id="service-modal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="bg-gradient-to-r from-[#c00000] to-red-600 text-white p-4 flex items-center justify-between">
            <h3 id="modal-service-title" class="text-sm font-black tracking-wide">Add New Repair Service</h3>
            <button type="button" onclick="closeServiceModal()" class="text-white/80 hover:text-white p-1 rounded-lg">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form method="POST" action="repair_services.php" class="p-5 space-y-4 text-xs">
            <input type="hidden" name="save_service" value="1">
            <input type="hidden" name="service_id" id="form-service-id" value="0">

            <div>
                <label class="block font-bold text-slate-700 mb-1">Service Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="form-service-name" required placeholder="e.g. Screen Replacement, Motherboard Repair"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Default Labor Cost ($) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0" name="labor_cost" id="form-service-cost" required value="0.00"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs font-bold text-emerald-700">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Description (Optional)</label>
                <textarea name="description" id="form-service-desc" rows="3" placeholder="Enter service description or warranty details..."
                          class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-[#c00000] outline-none text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeServiceModal()" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-bold text-xs shadow-md shadow-red-900/20">
                    Save Service
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddServiceModal() {
    document.getElementById('modal-service-title').textContent = 'Add New Repair Service';
    document.getElementById('form-service-id').value = '0';
    document.getElementById('form-service-name').value = '';
    document.getElementById('form-service-cost').value = '0.00';
    document.getElementById('form-service-desc').value = '';
    document.getElementById('service-modal').classList.remove('hidden');
}

function openEditServiceModal(service) {
    document.getElementById('modal-service-title').textContent = 'Edit Repair Service';
    document.getElementById('form-service-id').value = service.id;
    document.getElementById('form-service-name').value = service.name;
    document.getElementById('form-service-cost').value = parseFloat(service.labor_cost).toFixed(2);
    document.getElementById('form-service-desc').value = service.description || '';
    document.getElementById('service-modal').classList.remove('hidden');
}

function closeServiceModal() {
    document.getElementById('service-modal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
