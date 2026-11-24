<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$successMsg = '';
$errorMsg = '';

// Fetch active Categories, Sub Categories, and Measurements from DB
$categoriesList = $pdo->query("SELECT name FROM categories WHERE status = 'Active' ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($categoriesList)) {
    $categoriesList = ['PHONE PARTS', 'EARPHONE', 'POWER BANK', 'LIGHT', 'FIISH', 'CHARGER', 'PHONE COVER', 'GAME PARTS', 'GLASS', 'COOL BACK', 'Mobile', 'Laptop', 'Accessories'];
}

$subCategoriesList = $pdo->query("SELECT category_name, name FROM sub_categories WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

$unitsList = $pdo->query("SELECT symbol, name FROM measurements WHERE status = 'Active' ORDER BY id ASC")->fetchAll();
if (empty($unitsList)) {
    $unitsList = [
        ['symbol' => 'Pcs', 'name' => 'Pieces'],
        ['symbol' => 'Box', 'name' => 'Box'],
        ['symbol' => 'Set', 'name' => 'Set'],
        ['symbol' => 'Meter', 'name' => 'Meter'],
        ['symbol' => 'Kg', 'name' => 'Kilogram']
    ];
}

// Fetch suppliers for datalist (deduplicated by name)
$suppliersList = [];
try {
    $rawSup = $pdo->query("SELECT name, phone FROM suppliers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $seenNames = [];
    foreach ($rawSup as $sup) {
        $key = strtolower(trim($sup['name']));
        if (!in_array($key, $seenNames) && trim($sup['name'])) {
            $suppliersList[] = $sup;
            $seenNames[] = $key;
        }
    }
} catch (Exception $e) {}

// Handle Bulk Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'bulk_register') {
        $productsJson = $_POST['queued_products_json'] ?? '[]';
        $items = json_decode($productsJson, true);
        if (!empty($items) && is_array($items)) {
            $count = 0;
            $stmt = $pdo->prepare("INSERT INTO products (code, name, category, subcategory, type, unit, stock_qty, retail_price, wholesale_price, last_price, prev_price, prev_wholesale, supplier, notes, barcode, created_user, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Admin', CURDATE())");
            foreach ($items as $item) {
                $name = trim($item['name'] ?? '');
                if (!$name) continue;
                $cleanSlug = preg_replace('/[^A-Za-z0-9]/', '', substr($name, 0, 8));
                $serialCode = ($cleanSlug ? strtoupper($cleanSlug) : 'ITEM') . '-' . rand(100, 999);
                $barcode = '20000' . rand(10000000, 99999999);
                $subCatVal = trim($item['subcategory'] ?? $item['type'] ?? 'General');
                $stmt->execute([
                    $serialCode, $name,
                    $item['category'] ?? 'General',
                    $subCatVal, $subCatVal,
                    $item['unit'] ?? 'Pcs',
                    intval($item['qty'] ?? 1),
                    floatval($item['retail_price'] ?? 0),
                    floatval($item['wholesale_price'] ?? 0),
                    floatval($item['last_price'] ?? 0),
                    floatval($item['prev_price'] ?? 0),
                    floatval($item['prev_wholesale'] ?? 0),
                    $item['supplier'] ?? '',
                    $item['notes'] ?? '',
                    $barcode
                ]);
                $count++;
            }
            $successMsg = "Successfully registered $count products into inventory!";
        } else {
            $errorMsg = "No products found in the queue to register.";
        }
    }
}
?>

<main class="space-y-5 animate-fade-in">
    <!-- Top Action Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <h1 class="text-lg font-black text-slate-900 tracking-tight">Products Management</h1>
            <p class="text-xs text-slate-500 font-medium">Add &amp; Register Multiple Products</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="setUsedProduct()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-xs transition-colors cursor-pointer">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                <span>Add Used Product</span>
            </button>
            <button type="button" onclick="openExcelModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-colors cursor-pointer">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                <span>Upload Excel Sheet</span>
            </button>
            <a href="inventory.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-colors">
                <i data-lucide="list" class="w-3.5 h-3.5"></i>
                <span>View Products List</span>
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if ($successMsg): ?>
        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold flex items-center justify-between shadow-xs">
            <span class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                <?= htmlspecialchars($successMsg) ?>
            </span>
            <a href="inventory.php" class="underline hover:text-emerald-950 font-extrabold">View in Inventory List &rarr;</a>
        </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="p-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-900 text-xs font-bold flex items-center gap-2 shadow-xs">
            <i data-lucide="alert-circle" class="w-4 h-4 text-red-600"></i>
            <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Product Details Form Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="box" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-800">Product Details</h3>
                    <p class="text-[11px] text-slate-400">Fill in details then click "Add Product to Table"</p>
                </div>
            </div>
            <button type="button" onclick="openExcelModal()" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-emerald-600 text-emerald-700 hover:bg-emerald-50 text-xs font-bold transition-colors cursor-pointer">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span>Import Excel</span>
            </button>
        </div>

        <form id="product-form" onsubmit="return false;" class="p-5 space-y-4">
            <!-- Row 1: Product Name, Category, Sub Category -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Product Name <span class="text-red-500">*</span></label>
                    <input type="text" id="p-name" placeholder="e.g. Samsung Galaxy A20 4G"
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Category <span class="text-red-500">*</span></label>
                    <select id="p-category" onchange="filterSubCategories(this.value)"
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 focus:border-red-600 outline-none transition-all cursor-pointer bg-white">
                        <?php foreach ($categoriesList as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Sub Category <span class="text-red-500">*</span></label>
                    <select id="p-subcategory" onchange="handleSubCategoryChange(this)"
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:border-red-600 outline-none transition-all cursor-pointer bg-white">
                        <option value="">Select Sub Category</option>
                    </select>
                </div>
            </div>

            <!-- Row 2: Unit, Quantity, Selling Price -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Unit <span class="text-red-500">*</span></label>
                    <select id="p-unit" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 focus:border-red-600 outline-none transition-all cursor-pointer bg-white">
                        <?php foreach ($unitsList as $u): ?>
                            <option value="<?= htmlspecialchars($u['symbol']) ?>"><?= htmlspecialchars($u['symbol']) ?> (<?= htmlspecialchars($u['name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Quantity <span class="text-red-500">*</span></label>
                    <input type="number" id="p-qty" min="1" value="1"
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-extrabold text-slate-900 focus:border-red-600 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Selling Price / Retail ($) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-slate-400 font-bold text-xs">$</span>
                        <input type="number" step="0.01" id="p-retail-price" placeholder="0.00" value="0.00"
                               class="w-full pl-7 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-black text-emerald-600 outline-none focus:border-red-600 focus:ring-1 focus:ring-red-600">
                    </div>
                </div>
            </div>

            <!-- Row 3: Wholesale, Cost, Supplier -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Wholesale Price ($)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-slate-400 font-bold text-xs">$</span>
                        <input type="number" step="0.01" id="p-wholesale-price" placeholder="0.00" value="0.00"
                               class="w-full pl-7 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-black text-blue-600 outline-none focus:border-red-600">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-800 mb-1">Avg Cost / Purchase Price ($) <span class="text-amber-600 text-[10px] font-semibold">(Qiimaha soo iibsiga)</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-slate-400 font-bold text-xs">$</span>
                        <input type="number" step="0.01" id="p-last-price" placeholder="0.00" value="0.00"
                               class="w-full pl-7 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-black text-slate-700 outline-none focus:border-red-600">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Supplier (Optional)</label>
                    <!-- Input + datalist from DB -->
                    <input type="text" id="p-supplier" list="suppliers-datalist"
                           placeholder="Search supplier..."
                           autocomplete="off"
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-red-600 transition-all">
                    <datalist id="suppliers-datalist">
                        <?php foreach ($suppliersList as $sup): ?>
                            <option value="<?= htmlspecialchars($sup['name']) ?>"><?= htmlspecialchars($sup['phone'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>

            <!-- Row 4: Notes -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Description / Notes (Optional)</label>
                <input type="text" id="p-notes" placeholder="Product details, serial, notes..."
                       class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-medium outline-none">
            </div>

            <!-- Buttons -->
            <div class="pt-2 flex flex-wrap items-center gap-2">
                <button type="button" onclick="addProductToQueue()"
                        class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-black text-xs shadow-md transition-all active:scale-95 cursor-pointer">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Add Product to Table</span>
                </button>
                <button type="button" onclick="resetForm()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors cursor-pointer">
                    <i data-lucide="eraser" class="w-3.5 h-3.5"></i>
                    <span>Clear Form</span>
                </button>
                <button type="button" onclick="setUsedProduct()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold text-xs transition-colors cursor-pointer">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                    <span>Used Product</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Products Ready to Register Queue Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col">
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <h3 class="text-sm font-black text-slate-900">Products Ready to Register</h3>
                <span id="queue-badge" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-600 text-white">0 Products</span>
            </div>
            <button type="button" onclick="openExcelModal()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-600 text-emerald-700 hover:bg-emerald-50 text-xs font-bold transition-colors cursor-pointer self-start sm:self-auto">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span>Upload Excel</span>
            </button>
        </div>

        <div class="overflow-x-auto min-h-[160px]">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-black text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-3">#</th>
                        <th class="py-3 px-3">Product Name</th>
                        <th class="py-3 px-3">Category</th>
                        <th class="py-3 px-3">Sub Category</th>
                        <th class="py-3 px-3">Unit</th>
                        <th class="py-3 px-3 text-center">Qty</th>
                        <th class="py-3 px-3 text-right font-black text-slate-800">Avg Cost ⇅ ($)</th>
                        <th class="py-3 px-3 text-right text-blue-600">Wholesale ($)</th>
                        <th class="py-3 px-3 text-right text-emerald-700 font-extrabold">Retail ($)</th>
                        <th class="py-3 px-3">Supplier</th>
                        <th class="py-3 px-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="queue-tbody" class="divide-y divide-slate-100"></tbody>
            </table>

            <div id="queue-empty" class="py-12 flex flex-col items-center justify-center text-center p-4">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                    <i data-lucide="package-open" class="w-6 h-6"></i>
                </div>
                <h4 class="text-sm font-bold text-slate-700 mb-1">No Products Added Yet</h4>
                <p class="text-xs text-slate-400 max-w-md mb-4">
                    Fill in the product details above and click <strong>"Add Product to Table"</strong> to queue it here.
                    Add as many products as you want, then click <strong>"Register Products"</strong> to save them all to inventory at once.
                </p>
                <button type="button" onclick="openExcelModal()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition-colors cursor-pointer">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                    <span>Upload Excel Sheet</span>
                </button>
            </div>
        </div>

        <div class="p-3.5 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <span class="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                <i data-lucide="info" class="w-4 h-4 text-blue-500 shrink-0"></i>
                <span>Alaabaha form-ka buuxi → <strong>"Add Product to Table"</strong> ku dhufo → dhamaan marka aad ku dartid → <strong>"Register Products"</strong> ku dhufo si oo dhan inventory loogu keydiyo.</span>
            </span>
            <form method="POST" id="bulk-form" onsubmit="submitBulkQueue(event)">
                <input type="hidden" name="action" value="bulk_register">
                <input type="hidden" name="queued_products_json" id="queued-products-json">
                <button type="submit" id="btn-register-bulk" disabled
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-[#c00000] hover:bg-red-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-black text-xs shadow-md transition-all cursor-pointer">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span id="btn-register-text">Register Products (0)</span>
                </button>
            </form>
        </div>
    </div>
</main>

<!-- ══════════ EXCEL IMPORT MODAL ══════════ -->
<div id="excel-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4"
     style="background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);" onclick="handleModalBackdrop(event)">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden" onclick="event.stopPropagation()">
        <!-- Header -->
        <div class="bg-gradient-to-r from-[#1a3a6b] to-[#0f2347] px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center">
                    <i data-lucide="file-spreadsheet" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-white">Upload Products Excel Sheet</h2>
                    <p class="text-[11px] text-blue-200">Bulk import products from .xlsx, .xls, or .csv</p>
                </div>
            </div>
            <button onclick="closeExcelModal()" class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/25 flex items-center justify-center cursor-pointer transition-colors">
                <i data-lucide="x" class="w-4 h-4 text-white"></i>
            </button>
        </div>

        <div class="p-5 space-y-4">
            <!-- Download sample template -->
            <div class="flex items-start gap-3 p-3.5 rounded-xl bg-blue-50 border border-blue-100">
                <i data-lucide="user" class="w-4 h-4 text-blue-500 shrink-0 mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-xs font-bold text-slate-700 mb-1">Need a starting template?</p>
                    <p class="text-[11px] text-slate-500 mb-2">Download our pre-formatted spreadsheet with the correct column headers and sample rows.</p>
                    <button onclick="downloadSampleTemplate()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition-colors cursor-pointer">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        Download Sample (.xlsx)
                    </button>
                </div>
            </div>

            <!-- Supported columns info -->
            <p class="text-[11px] text-slate-500 leading-relaxed">
                <span class="font-bold text-slate-700">Supported Columns:</span>
                <span class="text-[#c00000]"> Product Name *</span> · Category · Sub Category · Unit · Quantity ·
                Last Price · Wholesale Price · Retail Selling Price · Supplier · Notes
            </p>

            <!-- Drop Zone -->
            <div id="excel-drop-zone"
                 ondragover="handleDragOver(event)"
                 ondragleave="handleDragLeave(event)"
                 ondrop="handleDrop(event)"
                 onclick="document.getElementById('excel-file-input').click()"
                 class="border-2 border-dashed border-slate-300 rounded-xl p-8 flex flex-col items-center justify-center text-center transition-all cursor-pointer hover:border-emerald-500 hover:bg-emerald-50/30">
                <div id="drop-zone-icon" class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center mb-3">
                    <i data-lucide="upload-cloud" class="w-6 h-6 text-emerald-600"></i>
                </div>
                <p class="text-sm font-bold text-slate-700 mb-1" id="drop-zone-title">Click to browse or drag &amp; drop spreadsheet here</p>
                <p class="text-xs text-slate-400 mb-3">Supports .xlsx, .xls, .csv files up to 10MB</p>
                <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border-2 border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition-colors">
                    <i data-lucide="folder-open" class="w-3.5 h-3.5"></i>
                    Choose File from Computer
                </span>
            </div>

            <!-- Preview (shown after file parsed) -->
            <div id="excel-preview" class="hidden space-y-2">
                <div class="flex items-center gap-2 p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                    <i data-lucide="file-check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                    <span id="excel-file-name" class="text-xs font-bold text-emerald-800 flex-1 truncate"></span>
                    <span id="excel-row-count" class="text-xs font-black text-emerald-700 bg-emerald-200 px-2 py-0.5 rounded-full"></span>
                </div>
                <div class="overflow-x-auto rounded-xl border border-slate-200 max-h-44">
                    <table class="w-full text-[11px] text-left">
                        <thead id="excel-preview-head" class="bg-slate-100 text-slate-600 font-bold uppercase text-[10px]"></thead>
                        <tbody id="excel-preview-body" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-3">
            <button onclick="closeExcelModal()"
                    class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs transition-colors cursor-pointer">
                Cancel
            </button>
            <button id="btn-import-excel" onclick="importExcelToQueue()" disabled
                    class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-black text-xs shadow-md transition-all cursor-pointer">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span id="btn-import-text">Import 0 Products to Queue</span>
            </button>
        </div>
    </div>
</div>

<!-- Hidden file input -->
<input type="file" id="excel-file-input" accept=".xlsx,.xls,.csv" class="hidden" onchange="handleFileSelected(event)">

<!-- SheetJS — real Excel/CSV parser (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script>
let queuedProducts = [];
let parsedExcelRows = [];
const allSubCategories = <?= json_encode($subCategoriesList) ?>;

// ── Sub Category ──────────────────────────────────────────────
function filterSubCategories(catName, preselected = '') {
    const sel = document.getElementById('p-subcategory');
    if (!sel) return;
    sel.innerHTML = '<option value="">Select Sub Category</option>';
    if (!catName) return;
    const filtered = allSubCategories.filter(s => s.category_name.trim().toLowerCase() === catName.trim().toLowerCase());
    if (filtered.length > 0) {
        filtered.forEach(s => {
            const o = document.createElement('option');
            o.value = s.name; o.textContent = s.name;
            if (preselected && s.name.toLowerCase() === preselected.toLowerCase()) o.selected = true;
            sel.appendChild(o);
        });
    } else {
        const o = document.createElement('option'); o.value = 'General '+catName; o.textContent = 'General '+catName; sel.appendChild(o);
    }
    const oo = document.createElement('option'); oo.value = '__custom__'; oo.textContent = '+ Add / Other Sub Category...'; sel.appendChild(oo);
    if (!preselected && filtered.length > 0) sel.selectedIndex = 1;
}

function handleSubCategoryChange(el) {
    if (el.value === '__custom__') {
        const cat = document.getElementById('p-category').value;
        const v = prompt(`Geli magaca Sub Category-ga cusub ee (${cat}):`);
        if (v && v.trim()) {
            const o = document.createElement('option'); o.value = v.trim(); o.textContent = v.trim(); o.selected = true;
            el.insertBefore(o, el.lastElementChild);
        } else { el.selectedIndex = el.options.length > 2 ? 1 : 0; }
    }
}

function setUsedProduct() {
    const sel = document.getElementById('p-subcategory');
    if (sel) {
        let found = false;
        for (let i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value.toLowerCase().includes('used')) { sel.selectedIndex = i; found = true; break; }
        }
        if (!found) {
            const o = document.createElement('option'); o.value = 'Used Products'; o.textContent = 'Used Products'; o.selected = true;
            sel.insertBefore(o, sel.lastElementChild);
        }
    }
    document.getElementById('p-name').focus();
    showToast('Mode Set', 'Sub Category switched to Used Products', 'info');
}

function resetForm() {
    ['p-name','p-notes','p-supplier'].forEach(id => { const el = document.getElementById(id); if(el) el.value = ''; });
    ['p-qty'].forEach(id => { const el = document.getElementById(id); if(el) el.value = 1; });
    ['p-retail-price','p-wholesale-price','p-last-price'].forEach(id => { const el = document.getElementById(id); if(el) el.value = '0.00'; });
    const cat = document.getElementById('p-category');
    if (cat && cat.value) filterSubCategories(cat.value);
    document.getElementById('p-name').focus();
}

document.addEventListener('DOMContentLoaded', () => {
    const cat = document.getElementById('p-category');
    if (cat && cat.value) filterSubCategories(cat.value);
});

// ── Queue ─────────────────────────────────────────────────────
function addProductToQueue() {
    const name = document.getElementById('p-name').value.trim();
    if (!name) { showToast('Magaca ku geli', 'Product Name waa loo baahan yahay!', 'error'); document.getElementById('p-name').focus(); return; }
    const sub = document.getElementById('p-subcategory').value.trim();
    if (!sub || sub === '__custom__') { showToast('Sub Category dooro', 'Sub Category waa loo baahan yahay!', 'error'); return; }
    const retail = parseFloat(document.getElementById('p-retail-price').value) || 0;
    if (retail <= 0 && !confirm('Selling Price waa $0.00 — sidaas ayaad u rabtaa?')) { document.getElementById('p-retail-price').focus(); return; }

    queuedProducts.push({
        id: Date.now(),
        name,
        category: document.getElementById('p-category').value,
        subcategory: sub, type: sub,
        unit: document.getElementById('p-unit').value,
        qty: parseInt(document.getElementById('p-qty').value) || 1,
        retail_price: retail,
        wholesale_price: parseFloat(document.getElementById('p-wholesale-price').value) || 0,
        last_price: parseFloat(document.getElementById('p-last-price').value) || 0,
        prev_price: 0, prev_wholesale: 0,
        supplier: document.getElementById('p-supplier').value.trim(),
        notes: document.getElementById('p-notes').value.trim()
    });
    renderQueueTable();
    resetForm();
    showToast('Table ku daray ✓', `"${name}" Products Ready to Register ku daray`, 'success');
}

function removeQueuedProduct(id) {
    queuedProducts = queuedProducts.filter(p => p.id !== id);
    renderQueueTable();
}

function renderQueueTable() {
    const tbody = document.getElementById('queue-tbody');
    const empty = document.getElementById('queue-empty');
    const badge = document.getElementById('queue-badge');
    const btn   = document.getElementById('btn-register-bulk');
    const btnTx = document.getElementById('btn-register-text');
    badge.textContent = `${queuedProducts.length} Products`;
    btnTx.textContent = `Register Products (${queuedProducts.length})`;
    if (!queuedProducts.length) { tbody.innerHTML = ''; empty.classList.remove('hidden'); btn.disabled = true; return; }
    empty.classList.add('hidden'); btn.disabled = false;
    tbody.innerHTML = queuedProducts.map((p, i) => `
        <tr class="hover:bg-slate-50 transition-colors">
            <td class="py-2.5 px-3 font-mono font-bold text-slate-500">${i+1}</td>
            <td class="py-2.5 px-3 font-bold text-slate-900">${escHtml(p.name)}</td>
            <td class="py-2.5 px-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">${escHtml(p.category)}</span></td>
            <td class="py-2.5 px-3 font-semibold text-slate-700">${escHtml(p.subcategory||'-')}</td>
            <td class="py-2.5 px-3 text-slate-600">${escHtml(p.unit)}</td>
            <td class="py-2.5 px-3 text-center font-bold">${p.qty}</td>
            <td class="py-2.5 px-3 text-right font-mono text-slate-400 text-[11px]">$${(p.last_price||0).toFixed(2)}</td>
            <td class="py-2.5 px-3 text-right font-mono text-blue-500 text-[11px]">$${(p.wholesale_price||0).toFixed(2)}</td>
            <td class="py-2.5 px-3 text-right font-black text-emerald-600">$${p.retail_price.toFixed(2)}</td>
            <td class="py-2.5 px-3 text-slate-500 text-[11px]">${escHtml(p.supplier||'—')}</td>
            <td class="py-2.5 px-3 text-center">
                <button type="button" onclick="removeQueuedProduct(${p.id})" class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors cursor-pointer">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </td>
        </tr>`).join('');
    lucide.createIcons();
}

function submitBulkQueue(e) {
    if (!queuedProducts.length) { e.preventDefault(); alert('Queue waa madhan. Alaab ku dar.'); return false; }
    document.getElementById('queued-products-json').value = JSON.stringify(queuedProducts);
    return true;
}

// ── EXCEL MODAL ───────────────────────────────────────────────
function openExcelModal() {
    parsedExcelRows = [];
    document.getElementById('excel-preview').classList.add('hidden');
    document.getElementById('excel-file-input').value = '';
    document.getElementById('drop-zone-title').textContent = 'Click to browse or drag & drop spreadsheet here';
    document.getElementById('btn-import-text').textContent = 'Import 0 Products to Queue';
    document.getElementById('btn-import-excel').disabled = true;
    const dz = document.getElementById('excel-drop-zone');
    dz.className = 'border-2 border-dashed border-slate-300 rounded-xl p-8 flex flex-col items-center justify-center text-center transition-all cursor-pointer hover:border-emerald-500 hover:bg-emerald-50/30';
    const modal = document.getElementById('excel-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    lucide.createIcons();
}

function closeExcelModal() {
    const modal = document.getElementById('excel-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function handleModalBackdrop(e) { if (e.target === e.currentTarget) closeExcelModal(); }

function handleDragOver(e) { e.preventDefault(); document.getElementById('excel-drop-zone').classList.add('border-emerald-500','bg-emerald-50/60'); }
function handleDragLeave() { document.getElementById('excel-drop-zone').classList.remove('border-emerald-500','bg-emerald-50/60'); }
function handleDrop(e) { e.preventDefault(); handleDragLeave(); const f = e.dataTransfer.files[0]; if(f) processExcelFile(f); }
function handleFileSelected(e) { const f = e.target.files[0]; if(f) processExcelFile(f); }

function processExcelFile(file) {
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['xlsx','xls','csv'].includes(ext)) { showToast('Khalaad', 'Kaliya .xlsx, .xls, ama .csv la oggol yahay!', 'error'); return; }
    const reader = new FileReader();
    reader.onload = function(ev) {
        try {
            const data = new Uint8Array(ev.target.result);
            const wb   = XLSX.read(data, { type:'array' });
            const ws   = wb.Sheets[wb.SheetNames[0]];
            const rows = XLSX.utils.sheet_to_json(ws, { defval:'' });
            if (!rows.length) { showToast('Faylka madhan','Excel sheet-ku wuu madhan yahay!','error'); return; }

            // Flexible column mapping
            const col = (row, ...keys) => {
                for (const k of keys) {
                    for (const rk of Object.keys(row)) {
                        if (rk.trim().toLowerCase() === k.toLowerCase()) {
                            const v = String(row[rk]).trim();
                            if (v && v !== 'undefined') return v;
                        }
                    }
                } return '';
            };

            parsedExcelRows = rows.map((row, idx) => {
                const name = col(row,'product name','name','product','item name','item');
                if (!name) return null;
                return {
                    id: Date.now() + idx, name,
                    category:        col(row,'category','cat') || 'General',
                    subcategory:     col(row,'sub category','subcategory','type','sub cat') || 'General',
                    type:            col(row,'sub category','subcategory','type') || 'General',
                    unit:            col(row,'unit','units') || 'Pcs',
                    qty:             parseInt(col(row,'quantity','qty','stock','stock qty')) || 1,
                    last_price:      parseFloat(col(row,'last price','cost','purchase price','cost price')) || 0,
                    prev_price:      parseFloat(col(row,'previous price','prev price')) || 0,
                    wholesale_price: parseFloat(col(row,'wholesale price','wholesale','wholesale selling price')) || 0,
                    prev_wholesale:  parseFloat(col(row,'previous wholesale','prev wholesale')) || 0,
                    retail_price:    parseFloat(col(row,'retail selling price','retail price','selling price','price','retail')) || 0,
                    supplier:        col(row,'supplier','vendor'),
                    notes:           col(row,'notes','description','remark','details')
                };
            }).filter(Boolean);

            // Update modal UI
            document.getElementById('excel-file-name').textContent = file.name;
            document.getElementById('excel-row-count').textContent = `${parsedExcelRows.length} rows`;
            document.getElementById('btn-import-text').textContent = `Import ${parsedExcelRows.length} Products to Queue`;
            document.getElementById('btn-import-excel').disabled = !parsedExcelRows.length;
            document.getElementById('drop-zone-title').textContent = `✓ ${file.name} — ${parsedExcelRows.length} products ready`;
            document.getElementById('excel-drop-zone').classList.add('border-emerald-400','bg-emerald-50/40');

            // Preview table (first 5 rows)
            const cols  = ['name','category','subcategory','unit','qty','retail_price','supplier'];
            const heads = ['Product Name','Category','Sub Cat','Unit','Qty','Retail ($)','Supplier'];
            document.getElementById('excel-preview-head').innerHTML =
                '<tr>' + heads.map(h=>`<th class="py-1.5 px-2 whitespace-nowrap">${h}</th>`).join('') + '</tr>';
            document.getElementById('excel-preview-body').innerHTML =
                parsedExcelRows.slice(0,5).map(r =>
                    '<tr class="hover:bg-slate-50">' + cols.map(c =>
                        `<td class="py-1.5 px-2 whitespace-nowrap font-medium text-slate-700">${escHtml(String(r[c]??''))}</td>`
                    ).join('') + '</tr>'
                ).join('');
            document.getElementById('excel-preview').classList.remove('hidden');
            lucide.createIcons();
        } catch(err) {
            showToast('Khalad akhris','Faylka si fiican loo akhrisan waayay: '+err.message,'error');
        }
    };
    reader.readAsArrayBuffer(file);
}

function importExcelToQueue() {
    if (!parsedExcelRows.length) return;
    parsedExcelRows.forEach(item => queuedProducts.push(item));
    renderQueueTable();
    closeExcelModal();
    showToast(`${parsedExcelRows.length} alaab la soo galiyay ✓`, 'Products Ready to Register table-ga ku daray', 'success');
    parsedExcelRows = [];
}

function downloadSampleTemplate() {
    const headers = ['Product Name','Category','Sub Category','Unit','Quantity','Last Price','Previous Price','Wholesale Price','Previous Wholesale','Retail Selling Price','Supplier','Notes'];
    const sample = [
        ['Samsung Galaxy A20 4G','Mobile','Smartphones','Pcs','10','130','0','140','0','175','AXMED MAAL','Black 128GB'],
        ['iPhone 16 Pro 256GB','Mobile','Smartphones','Pcs','5','900','0','950','0','1150','STORE INVENTORY','']
    ];
    let csv = headers.join(',') + '\n';
    sample.forEach(row => { csv += row.map(v=>`"${v}"`).join(',') + '\n'; });
    const a = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(new Blob([csv],{type:'text/csv'})),
        download: 'inventory_template.csv'
    });
    a.click(); URL.revokeObjectURL(a.href);
    showToast('Template Downloaded','inventory_template.csv soo degsatay','success');
}

function escHtml(t) {
    return String(t||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
// Legacy alias
function escapeHtml(t) { return escHtml(t); }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
