<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$successMsg = '';
$errorMsg = '';

// Handle Delete or Edit Supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_supplier') {
        $sId = intval($_POST['supplier_id'] ?? 0);
        if ($sId > 0) {
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$sId]);
            $successMsg = "Supplier record deleted successfully.";
        }
    } elseif ($_POST['action'] === 'edit_supplier') {
        $sId = intval($_POST['supplier_id'] ?? 0);
        $name = trim($_POST['supplier_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $balance = floatval($_POST['balance'] ?? 0);

        if ($sId > 0 && !empty($name)) {
            $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, phone = ?, address = ?, balance = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $address, $balance, $sId]);
            $successMsg = "Supplier '$name' updated successfully!";
        } else {
            $errorMsg = "Supplier name is required.";
        }
    }
}

// Search filters
$nameQ = trim($_GET['supplier_name'] ?? '');
$phoneQ = trim($_GET['phone'] ?? '');
$addressQ = trim($_GET['address'] ?? '');
$limit = intval($_GET['limit'] ?? 10);
if ($limit <= 0) $limit = 10;
$page = max(1, intval($_GET['p'] ?? 1));
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
if ($nameQ) {
    $where .= " AND name LIKE " . $pdo->quote("%$nameQ%");
}
if ($phoneQ) {
    $where .= " AND phone LIKE " . $pdo->quote("%$phoneQ%");
}
if ($addressQ) {
    $where .= " AND address LIKE " . $pdo->quote("%$addressQ%");
}

$totalRecords = $pdo->query("SELECT COUNT(*) FROM suppliers $where")->fetchColumn();
$sql = "SELECT * FROM suppliers $where ORDER BY id ASC LIMIT $limit OFFSET $offset";
$suppliers = $pdo->query($sql)->fetchAll();
$totalPages = ceil($totalRecords / $limit);
?>

<main class="space-y-4 animate-fade-in">
    <!-- Header (Image 3) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <h1 class="text-lg font-black text-red-600 tracking-tight">Supplier Management</h1>
            <p class="text-xs text-slate-500 font-medium">Manage company vendors, suppliers, and debt balances</p>
        </div>
        <a href="add_supplier.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs shadow-md transition-all active:scale-95 cursor-pointer">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Add Supplier</span>
        </a>
    </div>

    <!-- Feedback Alerts -->
    <?php if ($successMsg): ?>
        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold flex items-center justify-between shadow-xs">
            <span class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                <?= htmlspecialchars($successMsg) ?>
            </span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-950 font-black cursor-pointer">✕</button>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="p-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-900 text-xs font-bold flex items-center justify-between shadow-xs">
            <span class="flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-red-600"></i>
                <?= htmlspecialchars($errorMsg) ?>
            </span>
            <button type="button" onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-950 font-black cursor-pointer">✕</button>
        </div>
    <?php endif; ?>

    <!-- Filter Bar Card (Image 3) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            <!-- Supplier Name -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Supplier Name</label>
                <input type="text" name="supplier_name" value="<?= htmlspecialchars($nameQ) ?>" placeholder="Search Supplier Name" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-red-600">
            </div>

            <!-- Phone Number -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Phone Number</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($phoneQ) ?>" placeholder="Search Phone" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-red-600">
            </div>

            <!-- Address -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($addressQ) ?>" placeholder="Search Address" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-red-600">
            </div>

            <!-- Filter & Reset Buttons -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs shadow-md transition-colors cursor-pointer">
                    Filter
                </button>
                <a href="suppliers.php" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Suppliers Table (Image 3) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-3 border-b border-slate-100 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <span class="text-slate-600 font-bold">Show per page : Show</span>
                <select onchange="location.href='suppliers.php?limit=' + this.value" class="bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 font-bold text-slate-700 outline-none">
                    <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                </select>
                <span class="text-slate-600">entries</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-slate-500 font-bold text-xs">Search:</span>
                <input type="text" id="supplier-table-search" onkeyup="filterSupplierTable(this.value)" class="px-3 py-1 rounded-xl border border-slate-200 text-xs outline-none">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-red-600 text-white font-black text-[11px] tracking-tight uppercase">
                    <tr>
                        <th class="py-3 px-4"># ⇅</th>
                        <th class="py-3 px-4">Supplier Name ⇅</th>
                        <th class="py-3 px-4">Phone ⇅</th>
                        <th class="py-3 px-4">Address ⇅</th>
                        <th class="py-3 px-4 text-right">Balance ⇅</th>
                        <th class="py-3 px-4 text-center">Created User ⇅</th>
                        <th class="py-3 px-4 text-center">Joined At ⇅</th>
                        <th class="py-3 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="suppliers-tbody" class="divide-y divide-slate-100">
                    <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400 font-medium">
                            No supplier records found.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($suppliers as $s): 
                        $balance = floatval($s['balance']);
                        $sJson = htmlspecialchars(json_encode([
                            'id' => $s['id'],
                            'name' => $s['name'],
                            'phone' => $s['phone'],
                            'address' => $s['address'],
                            'balance' => $balance
                        ]));
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-mono font-black text-red-600">#<?= $s['id'] ?></td>
                        <td class="py-3 px-4 font-black text-slate-900 uppercase"><?= htmlspecialchars($s['name']) ?></td>
                        <td class="py-3 px-4 font-mono text-slate-700 font-semibold"><?= htmlspecialchars($s['phone'] ?: '-') ?></td>
                        <td class="py-3 px-4 font-bold text-slate-600 uppercase"><?= htmlspecialchars($s['address'] ?: 'BOORAMA') ?></td>
                        <td class="py-3 px-4 text-right font-mono font-black <?= $balance > 0 ? 'text-red-600' : 'text-emerald-600' ?>">
                            $<?= number_format($balance, 2) ?>
                        </td>
                        <td class="py-3 px-4 text-center text-slate-600">
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium">
                                <i data-lucide="user" class="w-3 h-3 text-blue-500"></i>
                                <span><?= htmlspecialchars($s['created_user'] ?: 'N/A') ?></span>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center font-mono text-slate-500 text-[11px]">
                            <?= htmlspecialchars($s['joined_at'] ?: '2026-02-21') ?>
                        </td>
                        <!-- Actions (Edit, Delete) -->
                        <td class="py-3 px-4 text-center">
                            <div class="inline-flex items-center gap-1.5">
                                <button type="button" onclick="openEditSupplierModal(<?= $sJson ?>)" class="px-2.5 py-1 rounded-md bg-red-600 hover:bg-red-700 text-white font-bold text-[11px] shadow-xs transition-colors cursor-pointer">
                                    Edit
                                </button>
                                <button type="button" onclick="confirmDeleteSupplier(<?= $s['id'] ?>, '<?= addslashes($s['name']) ?>')" class="px-2.5 py-1 rounded-md border border-red-200 text-red-600 hover:bg-red-50 font-bold text-[11px] transition-colors cursor-pointer">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar (Image 3) -->
        <div class="p-3.5 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-600 font-medium">
            <span>Showing 1 to <?= count($suppliers) ?> of <?= $totalRecords ?> entries</span>
            <div class="flex items-center gap-1">
                <button type="button" <?= $page <= 1 ? 'disabled' : '' ?> onclick="location.href='suppliers.php?p=<?= $page - 1 ?>&limit=<?= $limit ?>'" class="px-3 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 font-bold text-slate-500 disabled:opacity-50">Previous</button>
                <?php for ($i = 1; $i <= max(1, $totalPages); $i++): ?>
                    <a href="suppliers.php?p=<?= $i ?>&limit=<?= $limit ?>" class="w-7 h-7 rounded-lg flex items-center justify-center font-bold <?= $i == $page ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700 hover:bg-slate-300' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                <button type="button" <?= $page >= $totalPages ? 'disabled' : '' ?> onclick="location.href='suppliers.php?p=<?= $page + 1 ?>&limit=<?= $limit ?>'" class="px-3 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 font-bold text-slate-500 disabled:opacity-50">Next</button>
            </div>
        </div>
    </div>
</main>

<!-- Edit Supplier Modal -->
<div id="edit-supplier-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white rounded-2xl max-w-lg w-full border border-slate-200 shadow-2xl overflow-hidden flex flex-col animate-scale-in">
        <div class="bg-red-600 text-white px-5 py-3.5 flex items-center justify-between">
            <h3 class="text-sm font-black text-white">Edit Supplier Details</h3>
            <button type="button" onclick="closeEditSupplierModal()" class="w-7 h-7 rounded-lg text-white/80 hover:text-white hover:bg-white/10 flex items-center justify-center cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form method="POST" class="p-5 space-y-4 text-xs">
            <input type="hidden" name="action" value="edit_supplier">
            <input type="hidden" name="supplier_id" id="edit-sup-id">

            <div>
                <label class="block font-bold text-slate-700 mb-1">Supplier Name *</label>
                <input type="text" name="supplier_name" id="edit-sup-name" required class="w-full px-3 py-2 rounded-xl border border-slate-200 font-bold text-slate-900 outline-none focus:border-red-600">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Phone Number</label>
                    <input type="text" name="phone" id="edit-sup-phone" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-medium outline-none focus:border-red-600">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Opening Debt Balance ($)</label>
                    <input type="number" step="0.01" name="balance" id="edit-sup-balance" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-bold text-red-600 outline-none focus:border-red-600">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Address / Location</label>
                <input type="text" name="address" id="edit-sup-address" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-medium outline-none focus:border-red-600">
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeEditSupplierModal()" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black shadow-md transition-colors cursor-pointer">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Hidden Delete Form -->
<form id="delete-sup-form" method="POST" class="hidden">
    <input type="hidden" name="action" value="delete_supplier">
    <input type="hidden" name="supplier_id" id="delete-sup-id">
</form>

<script>
function openEditSupplierModal(s) {
    document.getElementById('edit-sup-id').value = s.id;
    document.getElementById('edit-sup-name').value = s.name;
    document.getElementById('edit-sup-phone').value = s.phone || '';
    document.getElementById('edit-sup-address').value = s.address || 'BOORAMA';
    document.getElementById('edit-sup-balance').value = s.balance.toFixed(2);
    document.getElementById('edit-supplier-modal').classList.remove('hidden');
}

function closeEditSupplierModal() {
    document.getElementById('edit-supplier-modal').classList.add('hidden');
}

function confirmDeleteSupplier(id, name) {
    if (confirm(`Are you sure you want to delete supplier "${name}"?`)) {
        document.getElementById('delete-sup-id').value = id;
        document.getElementById('delete-sup-form').submit();
    }
}

function filterSupplierTable(val) {
    const q = val.toLowerCase();
    const rows = document.querySelectorAll('#suppliers-tbody tr');
    rows.forEach(r => {
        const text = r.textContent.toLowerCase();
        r.style.display = text.includes(q) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
