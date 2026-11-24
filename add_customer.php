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
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_customer'])) {
    $name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $openingBalance = floatval($_POST['opening_balance'] ?? 0);
    $address = trim($_POST['address'] ?? 'Boorama');
    $description = trim($_POST['description'] ?? '');
    $branch = $currentBranch !== 'All Branches' ? $currentBranch : 'Boorama Branch';
    $createdUser = $_SESSION['user']['name'] ?? 'Admin';

    if (empty($name)) {
        $errorMsg = 'Fadlan geli magaca macmiilka (Customer Name is required).';
    } else {
        // Generate customer code e.g. MC-001
        $nextId = $pdo->query("SELECT IFNULL(MAX(id), 0) + 1 FROM customers")->fetchColumn();
        $code = 'MC-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("
            INSERT INTO customers 
            (code, name, phone, balance, address, description, branch, created_user, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
        ");
        $stmt->execute([$code, $name, $phone, $openingBalance, $address, $description, $branch, $createdUser]);

        safeRedirect("customers.php?created=1");
    }
}
?>

<main class="flex-1 min-w-0 space-y-4">
    <!-- Top Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-black text-red-600 tracking-tight">Add Customer</h1>
            <p class="text-xs text-slate-400 font-medium mt-0.5">Register a new customer or client account</p>
        </div>
        <a href="customers.php" class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-slate-500"></i>
            <span>Back to Customers</span>
        </a>
    </div>

    <?php if (!empty($errorMsg)): ?>
        <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-bold flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-red-500"></i>
            <span><?= htmlspecialchars($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <!-- Form Container Matching Image 1 -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6">
        <form method="POST" class="space-y-5">
            <input type="hidden" name="save_customer" value="1">

            <!-- Row 1: Customer Name & Phone Number -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Customer Name <span class="text-red-500">*</span></label>
                    <input type="text" name="customer_name" required placeholder="Enter customer full name"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 placeholder:text-slate-400 focus:border-red-600 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Phone Number</label>
                    <input type="text" name="phone_number" placeholder="e.g. 063XXXXXXX"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 placeholder:text-slate-400 focus:border-red-600 outline-none transition-all">
                </div>
            </div>

            <!-- Row 2: Opening Balance & Address / Location -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Opening Balance ($)</label>
                    <input type="number" step="0.01" name="opening_balance" value="0.00"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-red-600 placeholder:text-slate-400 focus:border-red-600 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Address / Location</label>
                    <input type="text" name="address" placeholder="Enter customer address or city"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 placeholder:text-slate-400 focus:border-red-600 outline-none transition-all">
                </div>
            </div>

            <!-- Row 3: Description / Notes -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Description / Notes</label>
                <textarea name="description" rows="4" placeholder="Enter any optional customer notes"
                          class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 placeholder:text-slate-400 focus:border-red-600 outline-none transition-all resize-y"></textarea>
            </div>

            <!-- Buttons Matching Image 1 -->
            <div class="flex items-center gap-2.5 pt-2">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-black text-xs shadow-md transition-colors cursor-pointer">
                    Save Customer
                </button>
                <a href="customers.php" class="px-5 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
