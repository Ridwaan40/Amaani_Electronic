<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$successMsg = '';
$errorMsg = '';

// Handle Delete Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_product') {
        $pId = intval($_POST['product_id'] ?? 0);
        if ($pId > 0) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$pId]);
            $successMsg = "Product deleted successfully from inventory.";
        }
    } elseif ($_POST['action'] === 'edit_product') {
        $pId = intval($_POST['product_id'] ?? 0);
        $name = trim($_POST['product_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $subcategory = trim($_POST['subcategory'] ?? $_POST['type'] ?? 'General');
        $type = $subcategory;
        $unit = trim($_POST['unit'] ?? 'Pcs');
        $qty = intval($_POST['quantity'] ?? 0);
        $retailPrice = floatval($_POST['retail_price'] ?? 0);
        $wholesalePrice = floatval($_POST['wholesale_price'] ?? 0);
        $lastPrice = floatval($_POST['last_price'] ?? 0);
        $supplier = trim($_POST['supplier'] ?? '');

        if ($pId > 0 && !empty($name)) {
            $stmt = $pdo->prepare("UPDATE products SET name = ?, category = ?, subcategory = ?, type = ?, unit = ?, stock_qty = ?, retail_price = ?, wholesale_price = ?, last_price = ?, supplier = ? WHERE id = ?");
            $stmt->execute([$name, $category, $subcategory, $type, $unit, $qty, $retailPrice, $wholesalePrice, $lastPrice, $supplier, $pId]);
            $successMsg = "Product '$name' updated successfully!";
        } else {
            $errorMsg = "Failed to update product. Please check required fields.";
        }
    }
}

// Search and filter
$search = trim($_GET['q'] ?? '');
$limit = intval($_GET['limit'] ?? 25);
if ($limit <= 0) $limit = 25;

$sql = "SELECT * FROM products WHERE 1=1";
if (!empty($search)) {
    $sql .= " AND (name LIKE " . $pdo->quote("%$search%") . " OR code LIKE " . $pdo->quote("%$search%") . " OR category LIKE " . $pdo->quote("%$search%") . " OR supplier LIKE " . $pdo->quote("%$search%") . ")";
}
$sql .= " ORDER BY id DESC LIMIT $limit";
$products = $pdo->query($sql)->fetchAll();
$totalCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Fetch active Categories and Sub Categories for Edit Modal dropdown
$categoriesList = $pdo->query("SELECT name FROM categories WHERE status = 'Active' ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($categoriesList)) {
    $categoriesList = ['PHONE PARTS', 'EARPHONE', 'POWER BANK', 'LIGHT', 'FIISH', 'CHARGER', 'PHONE COVER', 'GAME PARTS', 'GLASS', 'COOL BACK', 'Mobile', 'Laptop', 'Accessories'];
}
$subCategoriesList = $pdo->query("SELECT category_name, name FROM sub_categories WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

// Calculate Average Cost dynamically from Purchases (purchase_items)
$avgCosts = [];
try {
    $avgRows = $pdo->query("SELECT LOWER(TRIM(product_name)) as p_name, SUM(total_cost) / NULLIF(SUM(qty), 0) AS avg_cost, COUNT(*) as batch_count FROM purchase_items GROUP BY LOWER(TRIM(product_name))")->fetchAll();
    foreach ($avgRows as $ar) {
        $avgCosts[$ar['p_name']] = [
            'avg_cost' => floatval($ar['avg_cost']),
            'batch_count' => intval($ar['batch_count'])
        ];
    }
} catch (Exception $e) {}
?>

<main class="space-y-4 animate-fade-in">
    <!-- Header (Matching Image 3) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <h1 class="text-lg font-black text-slate-900 tracking-tight">Inventory List</h1>
            <p class="text-xs text-slate-500 font-medium">Manage your Inventory</p>
        </div>
        <a href="add_inventory.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs shadow-md transition-all active:scale-95 cursor-pointer">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>+ Add Inventory</span>
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

    <!-- Table Toolbar (Matching Image 3) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-3 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
        <!-- Search & Filter -->
        <form method="GET" class="flex items-center gap-2 w-full sm:w-auto">
            <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                <i data-lucide="filter" class="w-4 h-4"></i>
            </div>
            <div class="relative w-full sm:w-80">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-2.5 text-slate-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Quick search inventory..." class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium focus:border-red-600 outline-none">
            </div>
            <?php if (!empty($search)): ?>
                <a href="inventory.php" class="text-xs font-bold text-slate-400 hover:text-slate-600">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Show entries selector -->
        <form method="GET" class="flex items-center gap-2 text-xs text-slate-600 self-end sm:self-auto">
            <?php if (!empty($search)): ?>
                <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
            <?php endif; ?>
            <span class="font-bold flex items-center gap-1">
                <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5 text-blue-500"></i> Show
            </span>
            <select name="limit" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-xs font-bold text-slate-700 outline-none cursor-pointer">
                <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10 entries</option>
                <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25 entries</option>
                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50 entries</option>
                <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100 entries</option>
            </select>
        </form>
    </div>

    <!-- Inventory Table (Image 3: Bright Red Header Bar) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <!-- Red Table Header as in Image 3 -->
                <thead class="bg-red-600 text-white font-extrabold text-[11px] tracking-tight">
                    <tr>
                        <th class="py-3 px-3">#</th>
                        <th class="py-3 px-3">Product ⇅</th>
                        <th class="py-3 px-3">Sku Code ⇅</th>
                        <th class="py-3 px-3">Sub Category ⇅</th>
                        <th class="py-3 px-3 text-center">Status ⇅</th>
                        <th class="py-3 px-3 text-center">Quantity ⇅</th>
                        <th class="py-3 px-3 text-right">Avg Cost ⇅</th>
                        <th class="py-3 px-3 text-right">Selling Price ⇅</th>
                        <th class="py-3 px-3 text-center">Created ⇅</th>
                        <th class="py-3 px-3 text-center">Created User ⇅</th>
                        <th class="py-3 px-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="14" class="py-12 text-center text-slate-400 font-medium">
                            <i data-lucide="package" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                            <p>No inventory records found matching your search.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($products as $idx => $p): 
                        $qty = floatval($p['stock_qty']);
                        // Status rule specified by user: haddi alaabta quantity 0 > active oo green haddi kale empty oo red ah
                        $isActive = $qty > 0;
                        $serialCode = !empty($p['code']) ? $p['code'] : 'SKU-' . $p['id'];
                        $createdDate = !empty($p['created_at']) ? $p['created_at'] : '2026-09-19';
                        $createdUser = !empty($p['created_user']) ? $p['created_user'] : 'Admin';
                        $unit = !empty($p['unit']) ? $p['unit'] : 'Pcs';
                        $subCategoryVal = !empty($p['subcategory']) ? $p['subcategory'] : (!empty($p['type']) ? $p['type'] : 'General');
                        
                        // Weighted average cost from purchase batches, fallback to recorded price
                        $prodKey = strtolower(trim($p['name']));
                        $hasAvg = isset($avgCosts[$prodKey]);
                        $computedAvgCost = $hasAvg ? $avgCosts[$prodKey]['avg_cost'] : floatval(($p['last_price'] > 0 ? $p['last_price'] : ($p['wholesale_price'] > 0 ? $p['wholesale_price'] : $p['retail_price'])) ?: 0);
                        $batchCount = $hasAvg ? $avgCosts[$prodKey]['batch_count'] : 0;
                        $retailPrice = floatval($p['retail_price'] ?? 0);

                        // JSON payload for modals
                        $prodJson = htmlspecialchars(json_encode([
                            'id' => $p['id'],
                            'name' => $p['name'],
                            'code' => $serialCode,
                            'category' => $p['category'],
                            'subcategory' => $subCategoryVal,
                            'type' => $subCategoryVal,
                            'unit' => $unit,
                            'qty' => $qty,
                            'isActive' => $isActive,
                            'avgCost' => $computedAvgCost,
                            'lastPrice' => floatval($p['last_price'] ?? 0),
                            'wholesalePrice' => floatval($p['wholesale_price'] ?? 0),
                            'batchCount' => $batchCount,
                            'retailPrice' => $retailPrice,
                            'supplier' => $p['supplier'] ?? '-',
                            'barcode' => $p['barcode'] ?? '20000' . $p['id'] . '001',
                            'created' => $createdDate,
                            'createdUser' => $createdUser,
                            'notes' => $p['notes'] ?? ''
                        ]));
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <!-- 1. NO -->
                        <td class="py-2.5 px-3 font-mono font-bold text-slate-500"><?= $idx + 1 ?></td>

                        <!-- 2. Product Name -->
                        <td class="py-2.5 px-3 font-bold text-slate-800"><?= htmlspecialchars($p['name']) ?></td>

                        <!-- 3. Sku / Serial Code -->
                        <td class="py-2.5 px-3">
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-mono text-[11px] font-semibold border border-slate-200">
                                <?= htmlspecialchars($serialCode) ?>
                            </span>
                        </td>

                        <!-- 4. Sub Category -->
                        <td class="py-2.5 px-3 text-slate-700 font-bold"><?= htmlspecialchars($subCategoryVal) ?></td>

                        <!-- 5. Status (Active Green / Empty Red) -->
                        <td class="py-2.5 px-3 text-center">
                            <?php if ($isActive): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-red-50 text-red-700 border border-red-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Empty
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- 6. Quantity -->
                        <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-800"><?= number_format($qty, 2) ?></td>

                        <!-- 7. Avg Cost -->
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-700">
                            <?= $computedAvgCost > 0 ? '$' . number_format($computedAvgCost, 2) : '-' ?>
                            <?php if ($batchCount > 1): ?>
                                <span class="block text-[9px] text-blue-600 font-sans font-semibold"><?= $batchCount ?> batches</span>
                            <?php endif; ?>
                        </td>

                        <!-- 8. Selling Price -->
                        <td class="py-2.5 px-3 text-right font-mono font-black text-emerald-600 text-sm">
                            $<?= number_format($retailPrice, 2) ?>
                        </td>

                        <!-- 9. Created -->
                        <td class="py-2.5 px-3 text-center text-slate-500 font-mono text-[11px]"><?= htmlspecialchars($createdDate) ?></td>

                        <!-- 10. Created User -->
                        <td class="py-2.5 px-3 text-center text-slate-600 font-medium">
                            <span class="inline-flex items-center gap-1 text-[11px]">
                                <i data-lucide="user" class="w-3 h-3 text-blue-500"></i>
                                <span><?= htmlspecialchars($createdUser) ?></span>
                            </span>
                        </td>

                        <!-- 14. Action (3 Actions: View Detail, Edit Product, Delete Product) -->
                        <td class="py-2.5 px-3 text-center">
                            <div class="inline-flex items-center gap-1 bg-slate-50 p-1 rounded-xl border border-slate-200">
                                <!-- Action 1: View Detail -->
                                <button type="button" onclick="openProductDetail(<?= $prodJson ?>)" class="p-1 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-white transition-colors cursor-pointer" title="View Detail">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                </button>

                                <!-- Action 2: Edit Product -->
                                <button type="button" onclick="openEditProduct(<?= $prodJson ?>)" class="p-1 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-white transition-colors cursor-pointer" title="Edit Product">
                                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                </button>

                                <!-- Action 3: Delete Product -->
                                <button type="button" onclick="confirmDeleteProduct(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>')" class="p-1 rounded-lg text-slate-500 hover:text-red-600 hover:bg-white transition-colors cursor-pointer" title="Delete Product">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="p-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
            <span>Showing <?= count($products) ?> of <?= $totalCount ?> total inventory items</span>
            <span class="font-bold">AMAANI ELECTRONICS ERP</span>
        </div>
    </div>
</main>

<!-- ========================================== -->
<!-- MODAL 1: PRODUCT DETAILS MODAL (Image 4)   -->
<!-- ========================================== -->
<div id="product-detail-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white rounded-2xl max-w-2xl w-full border border-slate-200 shadow-2xl overflow-hidden flex flex-col animate-scale-in">
        <!-- Header (Red to Purple Gradient as in Image 4) -->
        <div class="p-4 sm:p-5 text-white flex items-center justify-between" style="background: linear-gradient(135deg, #c00000 0%, #3b82f6 100%);">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/20 backdrop-blur-xs flex items-center justify-center text-white shrink-0 font-bold">
                    <i data-lucide="box" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-white leading-tight">Product Details</h3>
                    <p id="dtl-header-sku" class="text-xs text-white/80 font-mono">SKU: --</p>
                </div>
            </div>
            <button type="button" onclick="closeProductDetail()" class="w-8 h-8 rounded-lg text-white/80 hover:text-white hover:bg-white/10 flex items-center justify-center transition-colors cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="p-5 sm:p-6 space-y-4">
            <!-- Product Title & Status -->
            <div class="flex flex-wrap items-center justify-between gap-2 pb-4 border-b border-slate-100">
                <div>
                    <h2 id="dtl-name" class="text-xl font-black text-slate-900">Product Name</h2>
                </div>
                <div id="dtl-status-badge">
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active
                    </span>
                </div>
            </div>

            <!-- Two Columns (Image 4) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Left: Pricing & Inventory -->
                <div class="bg-slate-50/80 rounded-2xl p-4 border border-slate-200/80 space-y-2.5 text-xs">
                    <h4 class="font-black text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5 mb-2">
                        <span>🪙</span> Pricing &amp; Inventory
                    </h4>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Quantity in Stock:</span>
                        <span id="dtl-qty" class="font-black text-slate-800 font-mono text-sm">0.00</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Unit of Measure:</span>
                        <span id="dtl-unit" class="font-bold text-slate-700">Pcs</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Average Cost:</span>
                        <div class="text-right">
                            <span id="dtl-avg-cost" class="font-bold text-slate-800 font-mono text-sm">$0.00</span>
                            <span id="dtl-batch-count" class="block text-[10px] text-blue-600 font-semibold font-sans"></span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-1 border-t border-slate-200">
                        <span class="text-emerald-700 font-extrabold">Retail Selling Price:</span>
                        <span id="dtl-retail-price" class="font-black text-emerald-600 font-mono text-base">$0.00</span>
                    </div>
                </div>

                <!-- Right: Specifications & Source -->
                <div class="bg-slate-50/80 rounded-2xl p-4 border border-slate-200/80 space-y-2.5 text-xs">
                    <h4 class="font-black text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5 mb-2">
                        <span>ℹ️</span> Specifications &amp; Source
                    </h4>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Sub Category:</span>
                        <span id="dtl-subcategory" class="font-bold text-slate-800 font-mono">--</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">SKU Code:</span>
                        <span id="dtl-code" class="font-mono font-bold text-slate-800">--</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Barcode:</span>
                        <span id="dtl-barcode" class="font-mono font-bold text-slate-700">--</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Supplier:</span>
                        <span id="dtl-supplier" class="font-bold text-slate-700">-</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Branch / Department:</span>
                        <span class="font-bold text-slate-700">Boorama Branch</span>
                    </div>
                    <div class="flex items-center justify-between pt-1 border-t border-slate-200">
                        <span class="text-slate-500">Date Added:</span>
                        <span id="dtl-created" class="font-mono text-slate-600">--</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Registered By:</span>
                        <span id="dtl-user" class="font-bold text-slate-700">Admin</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Buttons (Image 4) -->
        <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <button type="button" id="dtl-btn-delete" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-red-200 bg-white text-red-600 hover:bg-red-50 font-bold text-xs transition-colors cursor-pointer">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                <span>Delete Product</span>
            </button>

            <div class="flex items-center gap-2">
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition-colors cursor-pointer">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>Print Barcode</span>
                </button>
                <button type="button" id="dtl-btn-edit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-colors cursor-pointer">
                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                    <span>Edit Product</span>
                </button>
                <button type="button" onclick="closeProductDetail()" class="px-4 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs transition-colors cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 2: EDIT PRODUCT MODAL                -->
<!-- ========================================== -->
<div id="edit-product-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white rounded-2xl max-w-xl w-full border border-slate-200 shadow-2xl overflow-hidden flex flex-col animate-scale-in">
        <div class="bg-slate-900 text-white px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">
                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-white">Edit Product</h3>
                    <p id="edit-subtitle" class="text-[11px] text-slate-400 font-mono">Product ID: --</p>
                </div>
            </div>
            <button type="button" onclick="closeEditProduct()" class="w-7 h-7 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 flex items-center justify-center cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form method="POST" class="p-5 space-y-4">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="product_id" id="edit-id">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Product Name</label>
                <input type="text" name="product_name" id="edit-name" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-900 outline-none focus:border-red-600">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Category</label>
                    <select name="category" id="edit-category" onchange="filterEditSubCategories(this.value)" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 outline-none bg-white cursor-pointer">
                        <?php foreach ($categoriesList as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Sub Category</label>
                    <select name="subcategory" id="edit-subcategory" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 outline-none bg-white cursor-pointer">
                        <option value="">Select Sub Category</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Unit</label>
                    <input type="text" name="unit" id="edit-unit" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Quantity</label>
                    <input type="number" name="quantity" id="edit-qty" required min="0" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-900 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Avg Cost / Purchase ($)</label>
                    <input type="number" step="0.01" name="last_price" id="edit-last-price" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 outline-none focus:border-red-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Wholesale Price ($)</label>
                    <input type="number" step="0.01" name="wholesale_price" id="edit-wholesale-price" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-blue-600 outline-none focus:border-red-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Retail Selling Price ($)</label>
                    <input type="number" step="0.01" name="retail_price" id="edit-retail-price" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-emerald-600 outline-none focus:border-red-600">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Supplier</label>
                <input type="text" name="supplier" id="edit-supplier" placeholder="Supplier name" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium outline-none">
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeEditProduct()" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs shadow-md transition-colors cursor-pointer">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Hidden Delete Form -->
<form id="delete-form" method="POST" class="hidden">
    <input type="hidden" name="action" value="delete_product">
    <input type="hidden" name="product_id" id="delete-id">
</form>

<script>
let activeProductObj = null;
const allSubCategories = <?= json_encode($subCategoriesList) ?>;

function filterEditSubCategories(catName, preselected = '') {
    const subSelect = document.getElementById('edit-subcategory');
    if (!subSelect) return;
    subSelect.innerHTML = '<option value="">Select Sub Category</option>';
    if (!catName) return;

    const filtered = allSubCategories.filter(s => s.category_name.trim().toLowerCase() === catName.trim().toLowerCase());
    if (filtered.length > 0) {
        filtered.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.name;
            opt.textContent = s.name;
            if (preselected && s.name.toLowerCase() === preselected.toLowerCase()) {
                opt.selected = true;
            }
            subSelect.appendChild(opt);
        });
    } else {
        const opt = document.createElement('option');
        opt.value = preselected || ('General ' + catName);
        opt.textContent = preselected || ('General ' + catName);
        opt.selected = true;
        subSelect.appendChild(opt);
    }

    if (!preselected && filtered.length > 0) {
        subSelect.selectedIndex = 1;
    }
}

function openProductDetail(p) {
    activeProductObj = p;
    document.getElementById('dtl-header-sku').textContent = 'SKU: ' + p.code;
    document.getElementById('dtl-name').textContent = p.name;
    document.getElementById('dtl-subcategory').textContent = p.subcategory || p.type || '--';
    document.getElementById('dtl-code').textContent = p.code;
    document.getElementById('dtl-barcode').textContent = p.barcode || '2000000000001';
    document.getElementById('dtl-qty').textContent = Number(p.qty).toFixed(2);
    document.getElementById('dtl-unit').textContent = p.unit || 'Pcs';
    document.getElementById('dtl-avg-cost').textContent = p.avgCost > 0 ? '$' + Number(p.avgCost).toFixed(2) : '-';
    const batchSpan = document.getElementById('dtl-batch-count');
    if (p.batchCount > 1) {
        batchSpan.textContent = p.batchCount + ' purchase batches (weighted avg)';
    } else if (p.batchCount === 1) {
        batchSpan.textContent = '1 purchase batch';
    } else {
        batchSpan.textContent = 'Standard / Initial cost';
    }
    document.getElementById('dtl-retail-price').textContent = '$' + Number(p.retailPrice).toFixed(2);
    document.getElementById('dtl-supplier').textContent = p.supplier || '-';
    document.getElementById('dtl-created').textContent = p.created || '2026-09-19';
    document.getElementById('dtl-user').textContent = p.createdUser || 'Admin';

    // Status badge
    const badgeContainer = document.getElementById('dtl-status-badge');
    if (p.isActive) {
        badgeContainer.innerHTML = '<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-200"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active</span>';
    } else {
        badgeContainer.innerHTML = '<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-red-50 text-red-700 border border-red-200"><span class="w-2 h-2 rounded-full bg-red-500"></span> Empty</span>';
    }

    // Bind footer actions
    document.getElementById('dtl-btn-delete').onclick = function() {
        closeProductDetail();
        confirmDeleteProduct(p.id, p.name);
    };
    document.getElementById('dtl-btn-edit').onclick = function() {
        closeProductDetail();
        openEditProduct(p);
    };

    document.getElementById('product-detail-modal').classList.remove('hidden');
}

function closeProductDetail() {
    document.getElementById('product-detail-modal').classList.add('hidden');
}

function openEditProduct(p) {
    document.getElementById('edit-id').value = p.id;
    document.getElementById('edit-subtitle').textContent = `Product ID: #${p.id} (${p.code})`;
    document.getElementById('edit-name').value = p.name;
    document.getElementById('edit-category').value = p.category || 'Mobile';
    filterEditSubCategories(p.category || 'Mobile', p.subcategory || p.type || '');
    document.getElementById('edit-unit').value = p.unit || 'Pcs';
    document.getElementById('edit-qty').value = p.qty;
    document.getElementById('edit-retail-price').value = Number(p.retailPrice || 0).toFixed(2);
    if (document.getElementById('edit-wholesale-price')) {
        document.getElementById('edit-wholesale-price').value = Number(p.wholesalePrice || 0).toFixed(2);
    }
    if (document.getElementById('edit-last-price')) {
        document.getElementById('edit-last-price').value = Number(p.lastPrice || p.avgCost || 0).toFixed(2);
    }
    document.getElementById('edit-supplier').value = p.supplier || '';

    document.getElementById('edit-product-modal').classList.remove('hidden');
}

function closeEditProduct() {
    document.getElementById('edit-product-modal').classList.add('hidden');
}

function confirmDeleteProduct(id, name) {
    if (confirm(`Are you sure you want to delete product "${name}" from inventory?`)) {
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-form').submit();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
