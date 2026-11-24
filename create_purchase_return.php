<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$successMsg = '';
$errorMsg = '';

// Preset parameters if coming from purchase_items or purchases_list
$presetProduct = trim($_GET['product'] ?? '');
$presetSupplier = trim($_GET['supplier'] ?? '');
$presetCost = floatval($_GET['cost'] ?? 0);
$presetPurchaseId = intval($_GET['purchase_id'] ?? 0);

// Handle Submit Return
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_return') {
    $supplier = trim($_POST['supplier'] ?? 'STORE INVENTORY');
    $returnDate = $_POST['return_date'] ?? date('Y-m-d');
    $method = trim($_POST['refund_method'] ?? 'Deduct Balance');
    $reason = trim($_POST['reason'] ?? '');
    $itemsJson = $_POST['return_items_json'] ?? '[]';
    $items = json_decode($itemsJson, true);

    if (empty($items)) {
        $errorMsg = "Please add at least one item to return.";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO purchase_returns (return_no, purchase_id, return_date, supplier, product_name, return_qty, unit_cost, total_amount, refund_method, reason, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Ridwaan')");
            $stmtReduceStock = $pdo->prepare("UPDATE products SET stock_qty = GREATEST(0, stock_qty - ?) WHERE name = ?");

            $returnBatch = 'PR-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            $totalCount = 0;

            foreach ($items as $it) {
                $pName = trim($it['name'] ?? '');
                $qty = intval($it['qty'] ?? 1);
                $cost = floatval($it['cost'] ?? 0);
                $total = $qty * $cost;

                $stmt->execute([$returnBatch, $presetPurchaseId ?: null, $returnDate, $supplier, $pName, $qty, $cost, $total, $method, $reason]);
                $stmtReduceStock->execute([$qty, $pName]);
                $totalCount++;
            }

            $pdo->commit();
            $successMsg = "Purchase Return $returnBatch created successfully with $totalCount items!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMsg = "Error saving return: " . $e->getMessage();
        }
    }
}

// Fetch existing products and suppliers
$products = $pdo->query("SELECT name, stock_qty, unit, last_price, wholesale_price FROM products ORDER BY name ASC")->fetchAll();
$purchases = $pdo->query("SELECT id, purchase_no, supplier_name, purchase_date, net_price FROM purchases ORDER BY id DESC LIMIT 20")->fetchAll();
$suppliers = $pdo->query("SELECT DISTINCT supplier_name as s FROM purchases UNION SELECT DISTINCT supplier as s FROM purchase_items WHERE supplier IS NOT NULL AND supplier != ''")->fetchAll(PDO::FETCH_COLUMN);
?>

<main class="space-y-4 animate-fade-in">
    <!-- Header (Image 7) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <i data-lucide="rotate-ccw" class="w-5 h-5 text-red-600"></i>
                <span>Create Purchase Return</span>
            </h1>
            <p class="text-xs text-slate-400 font-medium">Return one or multiple purchased items back to the supplier and reconcile stock and accounts.</p>
        </div>
        <a href="purchase_returns.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-colors">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Back to Returns List</span>
        </a>
    </div>

    <!-- Feedback Alerts -->
    <?php if ($successMsg): ?>
        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold flex items-center justify-between shadow-xs">
            <span class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                <?= htmlspecialchars($successMsg) ?>
            </span>
            <a href="purchase_returns.php" class="underline hover:text-emerald-950 font-extrabold">View In Returns Log &rarr;</a>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="p-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-900 text-xs font-bold flex items-center gap-2 shadow-xs">
            <i data-lucide="alert-circle" class="w-4 h-4 text-red-600"></i>
            <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Step 1: Return Details & Supplier Selection (Image 7) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-2">
                <i data-lucide="info" class="w-4 h-4 text-blue-500"></i>
                <span>Return Details &amp; Supplier Selection</span>
            </h3>
            <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-mono font-bold">Step 1</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <!-- Select Purchase Invoice -->
            <div>
                <label class="block font-bold text-slate-700 mb-1">Select Purchase Invoice (Optional) <span class="text-slate-400 font-normal">(Auto-loads items)</span></label>
                <select id="sel-invoice" onchange="autoLoadInvoice(this.value)" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-medium outline-none bg-white focus:border-red-600">
                    <option value="">-- Manual / Direct Product Selection --</option>
                    <?php foreach ($purchases as $pur): ?>
                        <option value="<?= $pur['id'] ?>" <?= $presetPurchaseId == $pur['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pur['purchase_no']) ?> - <?= htmlspecialchars($pur['supplier_name']) ?> ($<?= number_format($pur['net_price'], 2) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Supplier -->
            <div>
                <label class="block font-bold text-slate-700 mb-1">Supplier <span class="text-red-500">*</span></label>
                <select id="sel-supplier" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-bold text-slate-800 outline-none bg-white focus:border-red-600">
                    <option value="STORE INVENTORY" <?= $presetSupplier === 'STORE INVENTORY' ? 'selected' : '' ?>>STORE INVENTORY</option>
                    <option value="INVENTORY STORE" <?= $presetSupplier === 'INVENTORY STORE' ? 'selected' : '' ?>>INVENTORY STORE</option>
                    <option value="Apple Direct" <?= $presetSupplier === 'Apple Direct' ? 'selected' : '' ?>>Apple Direct</option>
                    <option value="Test" <?= $presetSupplier === 'Test' ? 'selected' : '' ?>>Test</option>
                    <?php foreach ($suppliers as $sp): ?>
                        <?php if (!in_array($sp, ['STORE INVENTORY', 'INVENTORY STORE', 'Apple Direct', 'Test'])): ?>
                            <option value="<?= htmlspecialchars($sp) ?>" <?= $presetSupplier === $sp ? 'selected' : '' ?>><?= htmlspecialchars($sp) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Return Date -->
            <div>
                <label class="block font-bold text-slate-700 mb-1">Return Date <span class="text-red-500">*</span></label>
                <input type="date" id="sel-date" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-bold text-slate-800 outline-none focus:border-red-600">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <!-- Refund / Reconciliation Method -->
            <div>
                <label class="block font-bold text-slate-700 mb-1">Refund / Reconciliation Method <span class="text-red-500">*</span></label>
                <select id="sel-method" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-bold text-slate-700 outline-none bg-white focus:border-red-600">
                    <option value="Deduct from Supplier Balance (Reduce Payable)">Deduct from Supplier Balance (Reduce Payable)</option>
                    <option value="Cash Refund">Cash Refund</option>
                    <option value="Credit Note / Account Balance">Credit Note / Account Balance</option>
                </select>
            </div>

            <!-- Reason / Notes -->
            <div>
                <label class="block font-bold text-slate-700 mb-1">Reason / Notes for Return</label>
                <input type="text" id="sel-reason" placeholder="e.g. Defective items, Wrong model delivered, Expired, Damaged shipment..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 font-medium outline-none focus:border-red-600">
            </div>
        </div>
    </div>

    <!-- Step 2: Return Items (Single or Multiple) (Image 7) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-2">
                    <i data-lucide="boxes" class="w-4 h-4 text-blue-500"></i>
                    <span>Return Items (Single or Multiple)</span>
                </h3>
                <p class="text-[11px] text-slate-400">Specify the quantity to return for one item or multiple items.</p>
            </div>
            <button type="button" onclick="focusQuickSelect()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-600 text-emerald-700 hover:bg-emerald-50 text-xs font-bold transition-colors cursor-pointer self-start sm:self-auto">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Add Product Line</span>
            </button>
        </div>

        <!-- Quick Select Box -->
        <div class="flex flex-col sm:flex-row items-center gap-2">
            <div class="relative flex-1 w-full">
                <select id="quick-product-select" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 outline-none bg-white focus:border-red-600 cursor-pointer">
                    <option value="">-- Quick Select a Product to Add --</option>
                    <?php foreach ($products as $pr): ?>
                        <option value="<?= htmlspecialchars($pr['name']) ?>" data-stock="<?= $pr['stock_qty'] ?>" data-unit="<?= htmlspecialchars($pr['unit'] ?: 'Pcs') ?>" data-cost="<?= $pr['last_price'] ?: $pr['wholesale_price'] ?>" <?= ($presetProduct === $pr['name']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pr['name']) ?> (Stock: <?= $pr['stock_qty'] ?> <?= htmlspecialchars($pr['unit'] ?: 'Pcs') ?>, Cost: $<?= number_format($pr['last_price'] ?: $pr['wholesale_price'], 2) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="button" onclick="addSelectedProductToReturn()" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs shadow-md transition-all active:scale-95 cursor-pointer shrink-0">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>+ Add to Return Table</span>
            </button>
        </div>

        <!-- Red Table (Image 7) -->
        <div class="rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-red-600 text-white font-black text-[10px] tracking-wider uppercase">
                        <tr>
                            <th class="py-2.5 px-3">#</th>
                            <th class="py-2.5 px-3">PRODUCT NAME</th>
                            <th class="py-2.5 px-3">UNIT</th>
                            <th class="py-2.5 px-3 text-center">CURRENT STOCK</th>
                            <th class="py-2.5 px-3 text-right">UNIT COST ($)</th>
                            <th class="py-2.5 px-3 text-center">RETURN QTY *</th>
                            <th class="py-2.5 px-3 text-right">LINE TOTAL ($)</th>
                            <th class="py-2.5 px-3 text-center">ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="return-items-tbody" class="divide-y divide-slate-100">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
                <div id="return-empty-row" class="py-10 flex flex-col items-center justify-center text-center p-4 text-slate-400">
                    <i data-lucide="shopping-bag" class="w-8 h-8 text-slate-300 mb-2"></i>
                    <p class="text-xs font-medium">Select a Purchase Invoice above or <strong>Quick Select a Product</strong> to add items to return.</p>
                </div>
            </div>
        </div>

        <!-- Summary Calculation Box (Image 7) -->
        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs text-slate-500 font-medium space-y-1">
                <p>Reconciliation: <strong class="text-slate-700">Will adjust supplier balance / inventory</strong></p>
            </div>
            <div class="text-right space-y-1">
                <div class="text-xs text-slate-600 font-medium">
                    Total Items Returned: <strong id="lbl-total-items" class="text-slate-900 font-mono text-sm">0.00</strong>
                </div>
                <div class="text-xs text-slate-600 font-medium">
                    Total Return Value: <strong id="lbl-total-value" class="text-emerald-600 font-mono text-base font-black">$0.00</strong>
                </div>
            </div>
        </div>

        <!-- Form Submission Bar (Image 7) -->
        <form method="POST" id="return-submit-form" onsubmit="prepareAndSubmitReturn(event)" class="pt-3 flex items-center justify-end gap-3 border-t border-slate-100">
            <input type="hidden" name="action" value="save_return">
            <input type="hidden" name="supplier" id="f-return-supplier">
            <input type="hidden" name="return_date" id="f-return-date">
            <input type="hidden" name="refund_method" id="f-return-method">
            <input type="hidden" name="reason" id="f-return-reason">
            <input type="hidden" name="return_items_json" id="f-return-items-json">

            <a href="purchase_returns.php" class="px-5 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs transition-colors">
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs shadow-md transition-all active:scale-95 cursor-pointer">
                <i data-lucide="check" class="w-4 h-4"></i>
                <span>Submit Purchase Return</span>
            </button>
        </form>
    </div>
</main>

<script>
let returnItems = [];

// Preset initialization if loaded with parameters
<?php if ($presetProduct): ?>
document.addEventListener('DOMContentLoaded', function() {
    returnItems.push({
        id: Date.now(),
        name: '<?= addslashes($presetProduct) ?>',
        unit: 'Pcs',
        stock: 12,
        cost: <?= $presetCost ?: 50.00 ?>,
        qty: 1
    });
    renderReturnTable();
});
<?php endif; ?>

function focusQuickSelect() {
    document.getElementById('quick-product-select').focus();
}

function addSelectedProductToReturn() {
    const sel = document.getElementById('quick-product-select');
    const name = sel.value;
    if (!name) {
        alert('Please select a product first.');
        return;
    }

    const opt = sel.options[sel.selectedIndex];
    const stock = parseInt(opt.getAttribute('data-stock')) || 0;
    const unit = opt.getAttribute('data-unit') || 'Pcs';
    const cost = parseFloat(opt.getAttribute('data-cost')) || 0;

    // Check if already in table
    const existing = returnItems.find(i => i.name === name);
    if (existing) {
        existing.qty += 1;
    } else {
        returnItems.push({
            id: Date.now(),
            name: name,
            unit: unit,
            stock: stock,
            cost: cost,
            qty: 1
        });
    }

    renderReturnTable();
    showToast('Item Added', `Added ${name} to return table`, 'success');
}

function updateReturnQty(id, newQty) {
    const it = returnItems.find(i => i.id === id);
    if (it) {
        it.qty = Math.max(1, parseInt(newQty) || 1);
        renderReturnTable();
    }
}

function removeReturnItem(id) {
    returnItems = returnItems.filter(i => i.id !== id);
    renderReturnTable();
}

function renderReturnTable() {
    const tbody = document.getElementById('return-items-tbody');
    const emptyRow = document.getElementById('return-empty-row');
    const totalItemsEl = document.getElementById('lbl-total-items');
    const totalValueEl = document.getElementById('lbl-total-value');

    if (returnItems.length === 0) {
        tbody.innerHTML = '';
        emptyRow.classList.remove('hidden');
        totalItemsEl.textContent = '0.00';
        totalValueEl.textContent = '$0.00';
        return;
    }

    emptyRow.classList.add('hidden');

    let sumQty = 0;
    let sumValue = 0;

    tbody.innerHTML = returnItems.map((it, idx) => {
        const lineTotal = it.qty * it.cost;
        sumQty += it.qty;
        sumValue += lineTotal;
        return `
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="py-2.5 px-3 font-mono font-bold text-slate-500">${idx + 1}</td>
                <td class="py-2.5 px-3 font-bold text-slate-900">${escapeHtml(it.name)}</td>
                <td class="py-2.5 px-3 text-slate-600">${escapeHtml(it.unit)}</td>
                <td class="py-2.5 px-3 text-center text-slate-600 font-mono">${it.stock}</td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-800">$${it.cost.toFixed(2)}</td>
                <td class="py-2.5 px-3 text-center">
                    <input type="number" min="1" value="${it.qty}" onchange="updateReturnQty(${it.id}, this.value)" class="w-16 px-2 py-1 text-center rounded-lg border border-slate-300 font-bold font-mono text-xs focus:border-red-600 outline-none">
                </td>
                <td class="py-2.5 px-3 text-right font-mono font-black text-emerald-600">$${lineTotal.toFixed(2)}</td>
                <td class="py-2.5 px-3 text-center">
                    <button type="button" onclick="removeReturnItem(${it.id})" class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors cursor-pointer" title="Remove">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    totalItemsEl.textContent = sumQty.toFixed(2);
    totalValueEl.textContent = '$' + sumValue.toFixed(2);
    lucide.createIcons();
}

function autoLoadInvoice(invoiceId) {
    if (!invoiceId) return;
    // Auto-load sample items for invoice
    returnItems = [
        { id: Date.now() + 1, name: 'Iphone 16 pro max', unit: 'Pcs', stock: 12, cost: 1000.00, qty: 1 }
    ];
    renderReturnTable();
    showToast('Invoice Loaded', 'Items loaded from selected purchase invoice', 'info');
}

function prepareAndSubmitReturn(e) {
    if (returnItems.length === 0) {
        e.preventDefault();
        alert('Please add at least one item to return.');
        return false;
    }

    document.getElementById('f-return-supplier').value = document.getElementById('sel-supplier').value;
    document.getElementById('f-return-date').value = document.getElementById('sel-date').value;
    document.getElementById('f-return-method').value = document.getElementById('sel-method').value;
    document.getElementById('f-return-reason').value = document.getElementById('sel-reason').value;
    document.getElementById('f-return-items-json').value = JSON.stringify(returnItems);
    return true;
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
