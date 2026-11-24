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
$allCustomers = $pdo->query("SELECT id, name, phone, balance, address FROM customers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment'])) {
    $customerId = intval($_POST['customer_id'] ?? 0);
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $transType = trim($_POST['transaction_type'] ?? 'Return Credits');
    $previousBalance = floatval($_POST['previous_balance'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $paidAmount = floatval($_POST['paid_amount'] ?? 0);
    $remainingBalance = floatval($_POST['remaining_balance'] ?? 0);
    $payDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
    $payMethod = trim($_POST['payment_method'] ?? 'Zaad Services');
    $department = $currentBranch !== 'All Branches' ? $currentBranch : 'Boorama Branch';
    $createdUser = $_SESSION['user']['name'] ?? 'Ridwaan';

    // If customer selected from list
    if ($customerId > 0) {
        $cStmt = $pdo->prepare("SELECT name, phone, balance FROM customers WHERE id = ?");
        $cStmt->execute([$customerId]);
        $cRow = $cStmt->fetch(PDO::FETCH_ASSOC);
        if ($cRow) {
            $customerName = $cRow['name'];
            if (empty($customerPhone)) $customerPhone = $cRow['phone'];
            $previousBalance = floatval($cRow['balance']);
        }
    }

    if (empty($customerName)) {
        $errorMsg = 'Fadlan dooro ama qor macmiilka (Customer Name is required).';
    } elseif ($paidAmount <= 0 && $discount <= 0) {
        $errorMsg = 'Fadlan geli lacagta la bixiyay (Paid Amount must be greater than 0).';
    } else {
        $calculatedRem = max(0, $previousBalance - ($paidAmount + $discount));
        if ($remainingBalance < 0) $remainingBalance = $calculatedRem;

        // Generate Invoice/Receipt Number e.g. REC-360
        $nextId = $pdo->query("SELECT IFNULL(MAX(id), 0) + 1 FROM payments")->fetchColumn();
        $invoiceNum = 'REC-' . (350 + $nextId);

        // Insert into payments
        $insert = $pdo->prepare("
            INSERT INTO payments 
            (customer_name, customer_phone, transaction_type, previous_balance, discount, amount, remaining_balance, payment_method, department, created_user, paid_at, invoice_number)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $paidTimestamp = $payDate . ' ' . date('H:i:s');
        $insert->execute([
            $customerName,
            $customerPhone,
            $transType,
            $previousBalance,
            $discount,
            $paidAmount,
            $calculatedRem,
            $payMethod,
            $department,
            $createdUser,
            $paidTimestamp,
            $invoiceNum
        ]);

        // Update customer's balance in customers table
        if ($customerId > 0) {
            $updCust = $pdo->prepare("UPDATE customers SET balance = ? WHERE id = ?");
            $updCust->execute([$calculatedRem, $customerId]);
        } else {
            $updCust = $pdo->prepare("UPDATE customers SET balance = ? WHERE name = ?");
            $updCust->execute([$calculatedRem, $customerName]);
        }

        safeRedirect("payments.php?success=1");
    }
}
?>

<main class="flex-1 min-w-0 space-y-4">
    <!-- Top Header matching Image 2 -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-black text-red-600 tracking-tight">Add Credit Payment</h1>
            <p class="text-xs text-slate-400 font-medium mt-0.5">Record customer debt settlement</p>
        </div>
        <a href="payments.php" class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-slate-500"></i>
            <span>Back to List</span>
        </a>
    </div>

    <?php if (!empty($errorMsg)): ?>
        <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-bold flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-red-500"></i>
            <span><?= htmlspecialchars($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <!-- Form Container matching Image 2 -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6">
        <form method="POST" id="paymentForm" class="space-y-5">
            <input type="hidden" name="save_payment" value="1">
            <input type="hidden" name="customer_id" id="selected_customer_id" value="">
            <input type="hidden" name="customer_phone" id="selected_customer_phone" value="">

            <!-- Row 1: Customer Name & Transaction Type -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Customer Name <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input list="customerList" id="customer_name_input" name="customer_name" required 
                               placeholder="Search Customer..." autocomplete="off"
                               oninput="onCustomerInput(this.value)"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 placeholder:text-slate-400 focus:border-red-600 outline-none transition-all">
                        <datalist id="customerList">
                            <?php foreach ($allCustomers as $c): ?>
                                <option value="<?= htmlspecialchars($c['name']) ?>" data-id="<?= $c['id'] ?>" data-balance="<?= $c['balance'] ?>" data-phone="<?= htmlspecialchars($c['phone']) ?>">
                                    <?= htmlspecialchars($c['name']) ?> (Dayn: $<?= number_format($c['balance'], 2) ?>) - <?= htmlspecialchars($c['phone']) ?>
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Transaction Type</label>
                    <input type="text" name="transaction_type" value="Return Credits" readonly
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/70 text-xs text-slate-800 font-medium outline-none">
                </div>
            </div>

            <!-- Row 2: Previous Balance & Discount -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Previous Balance</label>
                    <input type="number" step="0.01" name="previous_balance" id="previous_balance" value="0.00" readonly
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/70 text-xs font-bold text-slate-800 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Discount</label>
                    <input type="number" step="0.01" name="discount" id="discount" value="0" oninput="calculateBalance()"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 placeholder:text-slate-400 focus:border-red-600 outline-none transition-all">
                </div>
            </div>

            <!-- Row 3: Paid Amount & Remaining Balance -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Paid Amount <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="paid_amount" id="paid_amount" value="0.00" required oninput="calculateBalance()"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-red-600 focus:border-red-600 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Remaining Balance</label>
                    <input type="number" step="0.01" name="remaining_balance" id="remaining_balance" value="0.00" readonly
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/70 text-xs font-bold text-slate-800 outline-none">
                </div>
            </div>

            <!-- Row 4: Date & Payment Method -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Date</label>
                    <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 focus:border-red-600 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Payment Method</label>
                    <select name="payment_method" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 focus:border-red-600 outline-none transition-all cursor-pointer">
                        <option value="">Select Payment Method</option>
                        <option value="Zaad Services" selected>Zaad Services</option>
                        <option value="Cash On Hand">Cash On Hand</option>
                        <option value="E-Dahab">E-Dahab</option>
                        <option value="EVC Plus">EVC Plus</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>
            </div>

            <!-- Buttons matching Image 2 -->
            <div class="flex items-center gap-2.5 pt-2">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-black text-xs shadow-md transition-colors cursor-pointer">
                    Save Payment
                </button>
                <a href="payments.php" class="px-5 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</main>

<script>
const customerData = <?= json_encode($allCustomers) ?>;

function onCustomerInput(val) {
    const cust = customerData.find(c => c.name.toLowerCase() === val.trim().toLowerCase());
    if (cust) {
        document.getElementById('selected_customer_id').value = cust.id;
        document.getElementById('selected_customer_phone').value = cust.phone || '';
        document.getElementById('previous_balance').value = parseFloat(cust.balance || 0).toFixed(2);
    } else {
        document.getElementById('selected_customer_id').value = '';
    }
    calculateBalance();
}

function calculateBalance() {
    const prev = parseFloat(document.getElementById('previous_balance').value) || 0;
    const disc = parseFloat(document.getElementById('discount').value) || 0;
    const paid = parseFloat(document.getElementById('paid_amount').value) || 0;
    
    const rem = Math.max(0, prev - (paid + disc));
    document.getElementById('remaining_balance').value = rem.toFixed(2);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
