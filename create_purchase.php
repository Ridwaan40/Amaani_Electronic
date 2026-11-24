<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$successMsg = '';
$errorMsg = '';

// Handle Quick Add Supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_add_supplier') {
    $sName = trim($_POST['supplier_name'] ?? '');
    $sPhone = trim($_POST['phone'] ?? '');
    $sAddress = trim($_POST['address'] ?? 'BOORAMA');
    $sBalance = floatval($_POST['opening_balance'] ?? 0);
    if (!empty($sName)) {
        try {
            $stmtSup = $pdo->prepare("INSERT INTO suppliers (name, phone, address, balance, created_user, joined_at) VALUES (?, ?, ?, ?, 'Admin', CURDATE())");
            $stmtSup->execute([$sName, $sPhone, $sAddress, $sBalance]);
            $successMsg = "Supplier '$sName' registered successfully!";
        } catch (Exception $e) {
            $errorMsg = "Error adding supplier: " . $e->getMessage();
        }
    }
}

// Handle Create Purchase Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_purchase') {
    $supplier = trim($_POST['supplier'] ?? 'INVENTORY STORE');
    $phone = trim($_POST['phone'] ?? '');
    $warehouse = trim($_POST['warehouse'] ?? 'Boorama Branch');
    $dueDate = $_POST['due_date'] ?? date('Y-m-d');
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $netPrice = floatval($_POST['net_price'] ?? 0);
    $additionals = floatval($_POST['additional_cost'] ?? 0);
    $paidAmount = floatval($_POST['paid_amount'] ?? 0);
    $balance = floatval($_POST['balance'] ?? ($netPrice - $paidAmount));
    $paymentMethod = trim($_POST['payment_method'] ?? 'ZAAD');
    $type = ($paidAmount >= $netPrice && $netPrice > 0) ? 'Cash' : (($paidAmount > 0) ? 'Cash & Credit' : 'Credit');
    $itemsJson = $_POST['items_json'] ?? '[]';
    $items = json_decode($itemsJson, true);

    if (empty($supplier)) {
        $supplier = 'INVENTORY STORE';
    }

    $purchaseNo = '#' . rand(1180, 9999);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO purchases (purchase_no, supplier_name, phone, warehouse, purchase_date, due_date, type, subtotal, discount, net_price, additionals, paid_amount, balance, payment_method, created_user) VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Ridwaan')");
        $stmt->execute([$purchaseNo, $supplier, $phone, $warehouse, $dueDate, $type, $subtotal, $discount, $netPrice, $additionals, $paidAmount, $balance, $paymentMethod]);
        $purchaseId = $pdo->lastInsertId();

        if (!empty($items) && is_array($items)) {
            $stmtItem = $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_name, category, qty, unit, last_price, add_cost, wholesale_price, retail_price, total_cost, remaining_qty, supplier, created_user, purchased_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Ridwaan', NOW())");
            
            // Check if product exists in products table, if so increase stock; if not, create it
            $stmtCheckProd = $pdo->prepare("SELECT id, stock_qty, retail_price FROM products WHERE name = ? LIMIT 1");
            $stmtUpdateProd = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ?, last_price = ?, wholesale_price = ?, category = ?, subcategory = ?, type = ? WHERE id = ?");
            // retail_price = last_price so new products appear in Sales immediately (user sets final price in Inventory later)
            $stmtInsertProd = $pdo->prepare("INSERT INTO products (code, name, category, subcategory, type, unit, stock_qty, last_price, wholesale_price, retail_price, supplier, created_user, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Ridwaan', CURDATE())");

            foreach ($items as $it) {
                $pName = trim($it['name'] ?? '');
                if (!$pName) continue;
                $pCat = $it['category'] ?? 'Mobile';
                $pSubcat = trim($it['subcategory'] ?? 'General');
                $pQty = intval($it['qty'] ?? 1);
                $pUnit = $it['unit'] ?? 'Pcs';
                $pCost = floatval($it['cost'] ?? $it['last_price'] ?? 0);
                $pTotal = floatval($it['total'] ?? ($pQty * $pCost));

                // Check existing product to preserve its already defined selling price
                $stmtCheckProd->execute([$pName]);
                $existing = $stmtCheckProd->fetch();
                $existingRetail = $existing ? floatval($existing['retail_price']) : 0.00;

                // Save item in purchase_items
                $stmtItem->execute([$purchaseId, $pName, $pCat, $pQty, $pUnit, $pCost, $additionals, $pCost, $existingRetail, $pTotal, $pQty, $supplier]);

                // Update product stock and last cost without altering selling price
                if ($existing) {
                    $stmtUpdateProd->execute([$pQty, $pCost, $pCost, $pCat, $pSubcat, $pSubcat, $existing['id']]);
                } else {
                    $cleanSlug = preg_replace('/[^A-Za-z0-9]/', '', substr($pName, 0, 8));
                    $code = ($cleanSlug ? strtoupper($cleanSlug) : 'ITEM') . '-' . rand(100, 999);
                    // retail_price = pCost so the product is immediately visible in Sales (user adjusts in Inventory later)
                    $stmtInsertProd->execute([$code, $pName, $pCat, $pSubcat, $pSubcat, $pUnit, $pQty, $pCost, $pCost, $pCost, $supplier]);
                }
            }
            // If purchase has an unpaid balance (debt), update supplier balance in suppliers ledger
            if ($balance > 0 && !empty($supplier)) {
                $stmtUpdSup = $pdo->prepare("UPDATE suppliers SET balance = balance + ? WHERE name = ? LIMIT 1");
                $stmtUpdSup->execute([$balance, $supplier]);
            }
        }

        $pdo->commit();
        $successMsg = "Purchase $purchaseNo created successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $errorMsg = "Error creating purchase: " . $e->getMessage();
    }
}

// 1. Fetch Registered Suppliers
$suppliersList = [];
try {
    $suppliersList = $pdo->query("SELECT id, name, phone, balance, address FROM suppliers ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {}

// 2. Fetch Dynamic Weighted Average Cost from purchase_items (like inventory.php)
$avgCosts = [];
try {
    $avgStmt = $pdo->query("SELECT LOWER(TRIM(product_name)) as p_key, SUM(total_cost) / SUM(qty) as avg_cost, COUNT(*) as batch_count FROM purchase_items GROUP BY LOWER(TRIM(product_name))");
    while ($r = $avgStmt->fetch()) {
        $avgCosts[$r['p_key']] = [
            'avg_cost' => floatval($r['avg_cost']),
            'batch_count' => intval($r['batch_count'])
        ];
    }
} catch (Exception $e) {}

// 2. Fetch Active Categories & Subcategories
$categoriesList = [];
$subCategoriesList = [];
try {
    $categoriesList = $pdo->query("SELECT name FROM categories WHERE status = 'Active' ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
    $subCategoriesList = $pdo->query("SELECT category_name, name FROM sub_categories WHERE status = 'Active' ORDER BY name ASC")->fetchAll();
    $subCatCats = $pdo->query("SELECT DISTINCT category_name FROM sub_categories WHERE status = 'Active'")->fetchAll(PDO::FETCH_COLUMN);
    $categoriesList = array_values(array_unique(array_merge($categoriesList, $subCatCats)));
    sort($categoriesList);
} catch (Exception $e) {}
if (empty($categoriesList)) {
    $categoriesList = ['PHONE PARTS', 'BATTERY', 'CHARGER', 'CABLE', 'DISPLAY', 'EARPHONE', 'POWER BANK', 'PHONE COVER', 'GLASS', 'Mobile', 'Laptop', 'Accessories'];
}

// 3. Fetch Existing Products for Autocomplete
$existingProductsRaw = $pdo->query("SELECT id, name, category, subcategory, type, stock_qty, unit, last_price, retail_price FROM products ORDER BY name ASC")->fetchAll();
$existingProducts = [];
foreach ($existingProductsRaw as $ep) {
    $pKey = strtolower(trim($ep['name']));
    $computedAvgCost = isset($avgCosts[$pKey]) && $avgCosts[$pKey]['avg_cost'] > 0
        ? $avgCosts[$pKey]['avg_cost']
        : floatval($ep['last_price'] ?? 0);
    $batchCount = isset($avgCosts[$pKey]) ? $avgCosts[$pKey]['batch_count'] : 0;
    
    $existingProducts[] = [
        'id' => $ep['id'],
        'name' => $ep['name'],
        'category' => $ep['category'] ?: 'Mobile',
        'subcategory' => $ep['subcategory'] ?: ($ep['type'] ?: 'General'),
        'stock_qty' => floatval($ep['stock_qty'] ?? 0),
        'unit' => $ep['unit'] ?: 'Pcs',
        'avg_cost' => $computedAvgCost,
        'batch_count' => $batchCount,
        'retail_price' => floatval($ep['retail_price'] ?? 0)
    ];
}
?>

<main class="space-y-4 animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight">Create Purchase POS</h1>
            <p class="text-xs text-slate-400 font-medium">Purchase &amp; Stock Inflow Transaction</p>
        </div>
        <a href="purchases_list.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-colors">
            <i data-lucide="list" class="w-3.5 h-3.5"></i>
            <span>View Purchases List</span>
        </a>
    </div>

    <!-- Feedback Alerts -->
    <?php if ($successMsg): ?>
        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold flex items-center justify-between shadow-xs">
            <span class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                <?= htmlspecialchars($successMsg) ?>
            </span>
            <a href="purchases_list.php" class="underline hover:text-emerald-950 font-extrabold">View In Purchases List &rarr;</a>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="p-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-900 text-xs font-bold flex items-center gap-2 shadow-xs">
            <i data-lucide="alert-circle" class="w-4 h-4 text-red-600"></i>
            <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Main Purchase Form Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-4">
        <!-- Top Controls: Supplier, Due Date, Warehouse -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Supplier -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Supplier *</label>
                <div class="flex items-center gap-1.5">
                    <div class="relative flex-1">
                        <i data-lucide="truck" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5"></i>
                        <input type="text" id="top-supplier" list="suppliers-datalist" oninput="handleSupplierChange(this.value)" onchange="handleSupplierChange(this.value)" value="<?= htmlspecialchars($suppliersList[0]['name'] ?? 'INVENTORY STORE') ?>" placeholder="Search or select Supplier..." class="w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-900 outline-none focus:border-red-600">
                        <datalist id="suppliers-datalist">
                            <?php foreach ($suppliersList as $sup): ?>
                                <option value="<?= htmlspecialchars($sup['name']) ?>">Phone: <?= htmlspecialchars($sup['phone']) ?> • Debt: $<?= number_format($sup['balance'], 2) ?></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <button type="button" onclick="openQuickSupplierModal()" class="w-8 h-8 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-xs transition-colors cursor-pointer" title="Add New Supplier">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                    </button>
                </div>
                <!-- Supplier Info Strip -->
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-500 font-medium">
                    <span>Phone: <strong id="top-sup-phone" class="text-slate-700 font-mono">-</strong></span>
                    <span>Debt Balance: <strong id="top-sup-balance" class="text-red-600 font-mono font-bold">$0.00</strong></span>
                </div>
            </div>

            <!-- Due Date -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Due Date</label>
                <div class="relative">
                    <input type="date" id="top-due-date" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-red-600">
                </div>
            </div>

            <!-- Select Warehouse -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Select Warehouse</label>
                <select id="top-warehouse" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 outline-none focus:border-red-600 bg-white cursor-pointer">
                    <option value="Boorama Branch">Boorama Branch</option>
                    <option value="Hargeisa Main Store">Hargeisa Main Store</option>
                    <option value="Burao Store">Burao Store</option>
                </select>
            </div>
        </div>

        <!-- Product Search Bar -->
        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Product Name</label>
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
                    <input type="text" id="search-product-input" oninput="handleSearchInput(this.value)" onchange="handleProductSelect(this.value)" onkeydown="handleSearchKeyDown(event)" placeholder="Search product name or select from list..." list="products-datalist" class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-red-600">
                    <datalist id="products-datalist">
                        <?php foreach ($existingProducts as $ep): ?>
                            <option value="<?= htmlspecialchars($ep['name']) ?>">Avg Cost: $<?= number_format($ep['avg_cost'], 2) ?> • Stock: <?= $ep['stock_qty'] ?> <?= htmlspecialchars($ep['unit']) ?></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <button type="button" onclick="triggerOpenModal()" class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs shadow-md transition-all active:scale-95 cursor-pointer shrink-0">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>+ Add Product</span>
                </button>
            </div>
        </div>

        <!-- Red Products Table -->
        <div class="rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-red-600 text-white font-black text-[10px] tracking-wider uppercase">
                        <tr>
                            <th class="py-2.5 px-3">Product Name</th>
                            <th class="py-2.5 px-3">Category</th>
                            <th class="py-2.5 px-3 text-center">Current Stock</th>
                            <th class="py-2.5 px-3 text-center">Purchase Qty</th>
                            <th class="py-2.5 px-3">Unit</th>
                            <th class="py-2.5 px-3 text-right">Unit Cost ($)</th>
                            <th class="py-2.5 px-3 text-right">Total Purchase ($)</th>
                            <th class="py-2.5 px-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="purchase-items-tbody" class="divide-y divide-slate-100">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
                <div id="table-empty-row" class="py-8 text-center text-slate-400 font-medium">
                    No products added.
                </div>
            </div>
        </div>

        <!-- Totals & Payment Details -->
        <form method="POST" id="main-purchase-form" onsubmit="prepareAndSubmitPurchase(event)" class="space-y-4 pt-2">
            <input type="hidden" name="action" value="save_purchase">
            <input type="hidden" name="supplier" id="f-supplier">
            <input type="hidden" name="phone" id="f-phone">
            <input type="hidden" name="due_date" id="f-due-date">
            <input type="hidden" name="warehouse" id="f-warehouse">
            <input type="hidden" name="items_json" id="f-items-json">

            <!-- Row 1: SubTotal, Discount, Net Price -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">SubTotal</label>
                    <input type="number" step="0.01" name="subtotal" id="p-subtotal" readonly value="0.00" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-extrabold text-slate-900 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Discount</label>
                    <input type="number" step="0.01" name="discount" id="p-discount" oninput="recalcTotals()" value="0.00" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 outline-none focus:border-red-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Net Price</label>
                    <input type="number" step="0.01" name="net_price" id="p-net-price" readonly value="0.00" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-black text-slate-900 outline-none">
                </div>
            </div>

            <!-- Row 2: Additional Cost, Paid Amount, Balance, Payment Method -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Additional Cost</label>
                    <input type="number" step="0.01" name="additional_cost" id="p-additional-cost" oninput="recalcTotals()" value="0.00" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 outline-none focus:border-red-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Paid Amount</label>
                    <input type="number" step="0.01" name="paid_amount" id="p-paid-amount" oninput="recalcTotals()" value="0.00" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-extrabold text-emerald-600 outline-none focus:border-red-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Balance</label>
                    <input type="number" step="0.01" name="balance" id="p-balance" readonly value="0.00" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-black text-red-600 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Payment Method</label>
                    <select name="payment_method" id="p-payment-method" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 outline-none focus:border-red-600 cursor-pointer bg-white">
                        <option value="ZAAD">ZAAD (Telesom)</option>
                        <option value="e-Dahab">e-Dahab (Somtel)</option>
                        <option value="Cash On Hand">Cash On Hand</option>
                        <option value="EVC Plus">EVC Plus</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Supplier Credit">Supplier Credit</option>
                    </select>
                </div>
            </div>

            <!-- Bottom Submit & Cancel Buttons -->
            <div class="pt-3 flex items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs shadow-md transition-all active:scale-95 cursor-pointer">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                    <span>Create Purchase</span>
                </button>
                <a href="purchases_list.php" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</main>

<!-- ========================================== -->
<!-- MODAL: ADD PRODUCT TO PURCHASE             -->
<!-- ========================================== -->
<div id="add-product-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white rounded-2xl max-w-xl w-full border border-slate-200 shadow-2xl overflow-hidden flex flex-col animate-scale-in">
        <!-- Red Modal Header -->
        <div class="bg-red-600 text-white px-5 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="package-plus" class="w-5 h-5"></i>
                <h3 class="text-sm font-black text-white">Add Product to Purchase</h3>
            </div>
            <button type="button" onclick="closeAddProductModal()" class="w-7 h-7 rounded-lg text-white/80 hover:text-white hover:bg-white/10 flex items-center justify-center cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form id="modal-product-form" onsubmit="submitModalProduct(event)" class="p-5 space-y-3.5 text-xs">
            <!-- Product Info Header Strip (Inventory-style: Avg Cost, Selling Price, Current Stock) -->
            <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-200 font-mono text-[11px] text-slate-600">
                <div class="flex items-center gap-3">
                    <span>Average Cost: <strong id="m-avg-cost-label" class="text-slate-900 font-bold">$0.00</strong> <span id="m-batch-badge" class="text-[10px] text-blue-600 font-sans"></span></span>
                    <span>Selling Price: <strong id="m-retail-price-label" class="text-emerald-700 font-black">$0.00</strong></span>
                </div>
                <div>
                    <span>Current Stock: <strong id="m-stock-label" class="text-slate-900 font-bold">0 Pcs</strong></span>
                </div>
            </div>

            <!-- Product Name, Category & Sub Category -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                <div class="sm:col-span-1">
                    <label class="block font-bold text-slate-700 mb-1">Product Name *</label>
                    <input type="text" id="m-name" required placeholder="Enter product name" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-bold text-slate-900 outline-none focus:border-red-600">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Category</label>
                    <select id="m-category" onchange="filterModalSubCategories(this.value)" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-bold text-slate-700 outline-none bg-white cursor-pointer">
                        <?php foreach ($categoriesList as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Sub Category</label>
                    <select id="m-subcategory" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-bold text-slate-700 outline-none bg-white cursor-pointer">
                        <option value="">Select Sub Category</option>
                    </select>
                </div>
            </div>

            <!-- Quantity, Unit, Purchase Unit Cost -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Purchase Qty *</label>
                    <input type="number" id="m-qty" required min="1" value="1" oninput="calcModalTotal()" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-bold text-slate-900 outline-none focus:border-red-600">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Unit *</label>
                    <select id="m-unit" class="w-full px-2.5 py-2 rounded-xl border border-slate-200 font-bold text-slate-700 outline-none bg-white cursor-pointer">
                        <option value="Pcs">Pcs</option>
                        <option value="Box">Box</option>
                        <option value="Set">Set</option>
                        <option value="Meter">Meter</option>
                        <option value="Roll">Roll</option>
                        <option value="Pack">Pack</option>
                        <option value="Carton">Carton</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Purchase Unit Cost ($) *</label>
                    <input type="number" step="0.01" id="m-cost-price" required value="0.00" oninput="calcModalTotal()" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-black text-slate-900 outline-none focus:border-red-600">
                </div>
            </div>

            <!-- Notes / Details -->
            <div>
                <label class="block font-bold text-slate-700 mb-1">Notes / Details (Optional)</label>
                <input type="text" id="m-notes" placeholder="Optional notes or details..." class="w-full px-3 py-2 rounded-xl border border-slate-200 font-medium outline-none">
            </div>

            <!-- Total Purchase Preview -->
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-xs font-black text-slate-800">Total Purchase: </span>
                    <span id="m-total-preview" class="text-base font-black text-red-600 font-mono">$0.00</span>
                </div>
                <span class="text-[10px] text-slate-400 font-medium">(Total = Qty &times; Purchase Unit Cost)</span>
            </div>

            <!-- Modal Action Buttons -->
            <div class="pt-2 flex items-center justify-start gap-2">
                <button type="button" onclick="closeAddProductModal()" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-extrabold shadow-md transition-colors cursor-pointer flex items-center gap-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Add to Purchase</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: QUICK ADD SUPPLIER                  -->
<!-- ========================================== -->
<div id="quick-supplier-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white rounded-2xl max-w-md w-full border border-slate-200 shadow-2xl overflow-hidden flex flex-col animate-scale-in">
        <div class="bg-emerald-600 text-white px-5 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="truck" class="w-5 h-5"></i>
                <h3 class="text-sm font-black text-white">Add New Supplier</h3>
            </div>
            <button type="button" onclick="closeQuickSupplierModal()" class="w-7 h-7 rounded-lg text-white/80 hover:text-white hover:bg-white/10 flex items-center justify-center cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form method="POST" class="p-5 space-y-3.5 text-xs">
            <input type="hidden" name="action" value="quick_add_supplier">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Supplier / Vendor Name *</label>
                <input type="text" name="supplier_name" required placeholder="Enter supplier name or business" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-bold text-slate-900 outline-none focus:border-emerald-600">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Phone Number</label>
                <input type="text" name="phone" placeholder="e.g. 063XXXXXXX" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-medium outline-none focus:border-emerald-600">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Location / Address</label>
                <input type="text" name="address" placeholder="e.g. Boorama, Somaliland" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-medium outline-none focus:border-emerald-600">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Opening Debt Balance ($)</label>
                <input type="number" step="0.01" name="opening_balance" value="0.00" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-bold text-red-600 outline-none focus:border-emerald-600">
            </div>
            <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeQuickSupplierModal()" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold shadow-md cursor-pointer flex items-center gap-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Save Supplier</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let purchaseItems = [];

// Autocomplete match data
const existingProductsMap = <?= json_encode($existingProducts) ?>;
const allSubCategories = <?= json_encode($subCategoriesList) ?>;
const suppliersListMap = <?= json_encode($suppliersList) ?>;

function handleSupplierChange(val) {
    if (!val) return;
    const clean = val.trim().toLowerCase();
    const found = suppliersListMap.find(s => s.name && s.name.trim().toLowerCase() === clean);
    if (found) {
        document.getElementById('top-sup-phone').textContent = found.phone || '-';
        document.getElementById('top-sup-balance').textContent = '$' + Number(found.balance || 0).toFixed(2);
        const fPhone = document.getElementById('f-phone');
        if (fPhone) fPhone.value = found.phone || '';
    } else {
        document.getElementById('top-sup-phone').textContent = '-';
        document.getElementById('top-sup-balance').textContent = '$0.00';
    }
}

function openQuickSupplierModal() {
    document.getElementById('quick-supplier-modal').classList.remove('hidden');
}

function closeQuickSupplierModal() {
    document.getElementById('quick-supplier-modal').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    const sInput = document.getElementById('top-supplier');
    if (sInput && sInput.value) {
        handleSupplierChange(sInput.value);
    }
});

function filterModalSubCategories(catName, preselected = '') {
    const subSelect = document.getElementById('m-subcategory');
    if (!subSelect) return;
    subSelect.innerHTML = '<option value="">Select Sub Category</option>';
    if (!catName) return;

    const cleanCat = catName.trim().toLowerCase();
    const filtered = allSubCategories.filter(s => s.category_name && s.category_name.trim().toLowerCase() === cleanCat);
    if (filtered.length > 0) {
        filtered.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.name;
            opt.textContent = s.name;
            if (preselected && s.name.trim().toLowerCase() === preselected.trim().toLowerCase()) {
                opt.selected = true;
            }
            subSelect.appendChild(opt);
        });
        if (!preselected || subSelect.selectedIndex <= 0) {
            subSelect.selectedIndex = 1;
        }
    } else {
        const opt = document.createElement('option');
        const fallbackName = preselected || ('General ' + catName);
        opt.value = fallbackName;
        opt.textContent = fallbackName;
        opt.selected = true;
        subSelect.appendChild(opt);
    }
}

// When user selects or types in search bar
function handleSearchInput(val) {
    const query = val.trim().toLowerCase();
    if (!query) return;
    const exactMatch = existingProductsMap.find(p => p.name.toLowerCase() === query);
    if (exactMatch) {
        openAddProductModal(exactMatch);
    }
}

function handleProductSelect(val) {
    const query = val.trim().toLowerCase();
    if (!query) return;
    const found = existingProductsMap.find(p => p.name.toLowerCase() === query);
    if (found) {
        openAddProductModal(found);
    }
}

function handleSearchKeyDown(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const query = e.target.value.trim().toLowerCase();
        const found = existingProductsMap.find(p => p.name.toLowerCase() === query);
        if (found) {
            openAddProductModal(found);
        } else {
            openAddProductModal({ name: e.target.value.trim() });
        }
    }
}

function triggerOpenModal() {
    const query = document.getElementById('search-product-input').value.trim();
    if (query) {
        const found = existingProductsMap.find(p => p.name.toLowerCase() === query.toLowerCase());
        openAddProductModal(found || { name: query });
    } else {
        openAddProductModal(null);
    }
}

function openAddProductModal(preset = null) {
    document.getElementById('modal-product-form').reset();
    document.getElementById('m-qty').value = 1;
    document.getElementById('m-cost-price').value = '0.00';

    if (preset && preset.id) {
        // Existing Product
        document.getElementById('m-name').value = preset.name || '';
        
        // Match category case-insensitively
        const catSelect = document.getElementById('m-category');
        let chosenCat = preset.category || 'PHONE PARTS';
        for (let i = 0; i < catSelect.options.length; i++) {
            if (catSelect.options[i].value.trim().toLowerCase() === chosenCat.trim().toLowerCase()) {
                catSelect.selectedIndex = i;
                chosenCat = catSelect.options[i].value;
                break;
            }
        }
        filterModalSubCategories(chosenCat, preset.subcategory || '');
        document.getElementById('m-unit').value = preset.unit || 'Pcs';
        
        // Show Average Cost & Inventory Selling Price as reference
        const avg = Number(preset.avg_cost || 0);
        document.getElementById('m-avg-cost-label').textContent = '$' + avg.toFixed(2);
        
        const badgeSpan = document.getElementById('m-batch-badge');
        if (preset.batch_count > 1) {
            badgeSpan.textContent = `(${preset.batch_count} batches)`;
        } else if (preset.batch_count === 1) {
            badgeSpan.textContent = `(1 batch)`;
        } else {
            badgeSpan.textContent = `(Standard)`;
        }

        document.getElementById('m-retail-price-label').textContent = '$' + Number(preset.retail_price || 0).toFixed(2);
        document.getElementById('m-stock-label').textContent = (preset.stock_qty || 0) + ' ' + (preset.unit || 'Pcs');

        // Suggest current average cost as default purchase cost
        document.getElementById('m-cost-price').value = avg > 0 ? avg.toFixed(2) : '0.00';
    } else {
        // New or custom product
        const nameVal = preset ? (preset.name || '') : document.getElementById('search-product-input').value.trim();
        document.getElementById('m-name').value = nameVal;
        
        const catSelect = document.getElementById('m-category');
        const defaultCat = catSelect.value || (catSelect.options[0] ? catSelect.options[0].value : 'PHONE PARTS');
        filterModalSubCategories(defaultCat);

        document.getElementById('m-avg-cost-label').textContent = '$0.00';
        document.getElementById('m-batch-badge').textContent = '(New)';
        document.getElementById('m-retail-price-label').textContent = '$0.00';
        document.getElementById('m-stock-label').textContent = '0 Pcs';
    }

    calcModalTotal();
    document.getElementById('add-product-modal').classList.remove('hidden');
    document.getElementById('m-qty').focus();
}

function closeAddProductModal() {
    document.getElementById('add-product-modal').classList.add('hidden');
}

function calcModalTotal() {
    const qty = parseFloat(document.getElementById('m-qty').value) || 0;
    const cost = parseFloat(document.getElementById('m-cost-price').value) || 0;
    const total = qty * cost;
    document.getElementById('m-total-preview').textContent = '$' + total.toFixed(2);
}

function submitModalProduct(e) {
    e.preventDefault();
    const name = document.getElementById('m-name').value.trim();
    if (!name) return;

    const qty = parseInt(document.getElementById('m-qty').value) || 1;
    const cost = parseFloat(document.getElementById('m-cost-price').value) || 0;
    const total = qty * cost;

    // Look up current stock and existing retail price
    const found = existingProductsMap.find(p => p.name.toLowerCase() === name.toLowerCase());
    const currentStock = found ? found.stock_qty : 0;
    const retailPrice = found ? (found.retail_price || 0) : 0;

    purchaseItems.push({
        id: Date.now(),
        name: name,
        category: document.getElementById('m-category').value,
        subcategory: document.getElementById('m-subcategory').value || 'General',
        qty: qty,
        unit: document.getElementById('m-unit').value,
        cost: cost,
        retail_price: retailPrice,
        total: total,
        current_stock: currentStock
    });

    renderPurchaseTable();
    closeAddProductModal();
    document.getElementById('search-product-input').value = '';
    recalcTotals();
}

function removePurchaseItem(id) {
    purchaseItems = purchaseItems.filter(i => i.id !== id);
    renderPurchaseTable();
    recalcTotals();
}

function renderPurchaseTable() {
    const tbody = document.getElementById('purchase-items-tbody');
    const emptyRow = document.getElementById('table-empty-row');

    if (purchaseItems.length === 0) {
        tbody.innerHTML = '';
        emptyRow.classList.remove('hidden');
        return;
    }

    emptyRow.classList.add('hidden');
    tbody.innerHTML = purchaseItems.map((item) => `
        <tr class="hover:bg-slate-50 transition-colors">
            <td class="py-2.5 px-3 font-bold text-slate-900">${escapeHtml(item.name)}</td>
            <td class="py-2.5 px-3">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                    ${escapeHtml(item.category)}
                </span>
            </td>
            <td class="py-2.5 px-3 text-center text-slate-500 font-mono">${item.current_stock}</td>
            <td class="py-2.5 px-3 text-center font-bold text-slate-900 font-mono">${item.qty}</td>
            <td class="py-2.5 px-3 text-slate-600">${escapeHtml(item.unit)}</td>
            <td class="py-2.5 px-3 text-right text-slate-800 font-mono font-bold">$${item.cost.toFixed(2)}</td>
            <td class="py-2.5 px-3 text-right font-black text-red-600 font-mono text-sm">$${item.total.toFixed(2)}</td>
            <td class="py-2.5 px-3 text-center">
                <button type="button" onclick="removePurchaseItem(${item.id})" class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors cursor-pointer" title="Remove">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </td>
        </tr>
    `).join('');

    lucide.createIcons();
}

function recalcTotals() {
    const subtotal = purchaseItems.reduce((acc, it) => acc + it.total, 0);
    const discount = parseFloat(document.getElementById('p-discount').value) || 0;
    const additional = parseFloat(document.getElementById('p-additional-cost').value) || 0;
    const netPrice = Math.max(0, subtotal - discount + additional);

    document.getElementById('p-subtotal').value = subtotal.toFixed(2);
    document.getElementById('p-net-price').value = netPrice.toFixed(2);

    const paid = parseFloat(document.getElementById('p-paid-amount').value) || 0;
    const balance = Math.max(0, netPrice - paid);
    document.getElementById('p-balance').value = balance.toFixed(2);
}

function prepareAndSubmitPurchase(e) {
    if (purchaseItems.length === 0) {
        e.preventDefault();
        alert('Please add at least one product to the purchase.');
        return false;
    }

    const supName = document.getElementById('top-supplier').value.trim() || 'INVENTORY STORE';
    document.getElementById('f-supplier').value = supName;
    document.getElementById('f-due-date').value = document.getElementById('top-due-date').value;
    document.getElementById('f-warehouse').value = document.getElementById('top-warehouse').value;
    document.getElementById('f-items-json').value = JSON.stringify(purchaseItems);
    
    // Set supplier phone if matched
    const matchedSup = suppliersListMap.find(s => s.name && s.name.trim().toLowerCase() === supName.toLowerCase());
    if (matchedSup && matchedSup.phone) {
        document.getElementById('f-phone').value = matchedSup.phone;
    }
    return true;
}

function quickAddSupplier() {
    openQuickSupplierModal();
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
