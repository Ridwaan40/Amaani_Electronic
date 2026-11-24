<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$successMsg = '';
$errorMsg = '';

// Handle Save Supplier Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_supplier') {
    $name = trim($_POST['supplier_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $balance = floatval($_POST['balance'] ?? 0);
    $address = trim($_POST['address'] ?? '');

    if (empty($name)) {
        $errorMsg = "Supplier Name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO suppliers (name, phone, address, balance, created_user, joined_at) VALUES (?, ?, ?, ?, 'Admin', CURDATE())");
            $stmt->execute([$name, $phone, $address ?: 'BOORAMA', $balance]);
            $successMsg = "Supplier '$name' registered successfully!";
        } catch (Exception $e) {
            $errorMsg = "Error saving supplier: " . $e->getMessage();
        }
    }
}
?>

<main class="space-y-4 animate-fade-in">
    <!-- Header (Image 2) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <h1 class="text-lg font-black text-red-600 tracking-tight">Add Supplier</h1>
            <p class="text-xs text-slate-500 font-medium">Register a new vendor or product supplier</p>
        </div>
        <a href="suppliers.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-colors">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Back to Suppliers</span>
        </a>
    </div>

    <!-- Feedback Alerts -->
    <?php if ($successMsg): ?>
        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold flex items-center justify-between shadow-xs">
            <span class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                <?= htmlspecialchars($successMsg) ?>
            </span>
            <a href="suppliers.php" class="underline hover:text-emerald-950 font-extrabold">View In Supplier List &rarr;</a>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="p-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-900 text-xs font-bold flex items-center gap-2 shadow-xs">
            <i data-lucide="alert-circle" class="w-4 h-4 text-red-600"></i>
            <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Add Supplier Form Card (Image 2) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
        <form method="POST" class="space-y-5 text-xs">
            <input type="hidden" name="action" value="save_supplier">

            <!-- Row 1: Supplier Name & Phone Number -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block font-bold text-slate-800 mb-1.5">Supplier Name</label>
                    <input type="text" name="supplier_name" required placeholder="Enter supplier or company name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-red-600 focus:ring-1 focus:ring-red-600">
                </div>

                <div>
                    <label class="block font-bold text-slate-800 mb-1.5">Phone Number</label>
                    <input type="text" name="phone" placeholder="e.g. 063XXXXXXX" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-red-600 focus:ring-1 focus:ring-red-600">
                </div>
            </div>

            <!-- Row 2: Opening Debt Balance & Address / Location -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <label class="font-bold text-slate-800">Opening Debt Balance ($)</label>
                        <span class="text-[9px] font-bold px-2 py-0.2 rounded-md bg-rose-100 text-rose-700">Charges Short Term Liability</span>
                    </div>
                    <input type="number" step="0.01" name="balance" value="0.00" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-red-600 outline-none focus:border-red-600 focus:ring-1 focus:ring-red-600">
                    <p class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>If this supplier has an opening debt balance, it will automatically charge and increase Short Term Liability.</span>
                    </p>
                </div>

                <div>
                    <label class="block font-bold text-slate-800 mb-1.5">Address / Location</label>
                    <input type="text" name="address" placeholder="e.g. Sisibta, Borama" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-red-600 focus:ring-1 focus:ring-red-600">
                </div>
            </div>

            <!-- Action Buttons (Image 2) -->
            <div class="pt-3 flex items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs shadow-md transition-all active:scale-95 cursor-pointer">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Save Supplier</span>
                </button>
                <a href="suppliers.php" class="px-5 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
