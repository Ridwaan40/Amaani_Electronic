<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$activeTab = $_GET['tab'] ?? 'category';
if (!in_array($activeTab, ['category', 'subcategory', 'measurement'])) {
    $activeTab = 'category';
}

$successMsg = '';
$errorMsg = '';

// Handle POST actions (Create, Edit, Delete for Category, Sub Category, Measurement)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- CATEGORY ACTIONS ---
    if ($action === 'create_category') {
        $name = trim($_POST['name'] ?? '');
        $status = ($_POST['status'] ?? 'Active') === 'Disabled' ? 'Disabled' : 'Active';
        if (!empty($name)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name, status) VALUES (?, ?)");
                $stmt->execute([$name, $status]);
                $successMsg = "Category '$name' created successfully!";
            } catch (Exception $e) {
                $errorMsg = "Category name already exists or invalid: " . $e->getMessage();
            }
        } else {
            $errorMsg = "Category name cannot be empty.";
        }
    } elseif ($action === 'edit_category') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $status = ($_POST['status'] ?? 'Active') === 'Disabled' ? 'Disabled' : 'Active';
        if ($id > 0 && !empty($name)) {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $status, $id]);
            $successMsg = "Category updated successfully!";
        } else {
            $errorMsg = "Invalid Category details.";
        }
    } elseif ($action === 'delete_category') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $successMsg = "Category deleted successfully!";
        }
    }

    // --- SUB CATEGORY ACTIONS ---
    elseif ($action === 'create_subcategory') {
        $catName = trim($_POST['category_name'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $status = ($_POST['status'] ?? 'Active') === 'Disabled' ? 'Disabled' : 'Active';
        if (!empty($name) && !empty($catName)) {
            $stmt = $pdo->prepare("INSERT INTO sub_categories (category_name, name, status) VALUES (?, ?, ?)");
            $stmt->execute([$catName, $name, $status]);
            $successMsg = "Sub Category '$name' added to '$catName'!";
            $activeTab = 'subcategory';
        } else {
            $errorMsg = "Both Category and Sub Category Name are required.";
            $activeTab = 'subcategory';
        }
    } elseif ($action === 'edit_subcategory') {
        $id = intval($_POST['id'] ?? 0);
        $catName = trim($_POST['category_name'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $status = ($_POST['status'] ?? 'Active') === 'Disabled' ? 'Disabled' : 'Active';
        if ($id > 0 && !empty($name) && !empty($catName)) {
            $stmt = $pdo->prepare("UPDATE sub_categories SET category_name = ?, name = ?, status = ? WHERE id = ?");
            $stmt->execute([$catName, $name, $status, $id]);
            $successMsg = "Sub Category updated successfully!";
            $activeTab = 'subcategory';
        } else {
            $errorMsg = "Invalid Sub Category details.";
            $activeTab = 'subcategory';
        }
    } elseif ($action === 'delete_subcategory') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM sub_categories WHERE id = ?");
            $stmt->execute([$id]);
            $successMsg = "Sub Category deleted successfully!";
            $activeTab = 'subcategory';
        }
    }

    // --- MEASUREMENT / UNIT ACTIONS ---
    elseif ($action === 'create_measurement') {
        $name = trim($_POST['name'] ?? '');
        $symbol = trim($_POST['symbol'] ?? '');
        $status = ($_POST['status'] ?? 'Active') === 'Disabled' ? 'Disabled' : 'Active';
        if (!empty($name) && !empty($symbol)) {
            $stmt = $pdo->prepare("INSERT INTO measurements (name, symbol, status) VALUES (?, ?, ?)");
            $stmt->execute([$name, $symbol, $status]);
            $successMsg = "Measurement '$name ($symbol)' added successfully!";
            $activeTab = 'measurement';
        } else {
            $errorMsg = "Both Unit Name and Symbol are required.";
            $activeTab = 'measurement';
        }
    } elseif ($action === 'edit_measurement') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $symbol = trim($_POST['symbol'] ?? '');
        $status = ($_POST['status'] ?? 'Active') === 'Disabled' ? 'Disabled' : 'Active';
        if ($id > 0 && !empty($name) && !empty($symbol)) {
            $stmt = $pdo->prepare("UPDATE measurements SET name = ?, symbol = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $symbol, $status, $id]);
            $successMsg = "Measurement updated successfully!";
            $activeTab = 'measurement';
        } else {
            $errorMsg = "Invalid measurement details.";
            $activeTab = 'measurement';
        }
    } elseif ($action === 'delete_measurement') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM measurements WHERE id = ?");
            $stmt->execute([$id]);
            $successMsg = "Measurement deleted successfully!";
            $activeTab = 'measurement';
        }
    }
}

// Fetch all categories for subcategory dropdown
$allCategories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Data fetching with pagination for current tab
$searchQ = trim($_GET['search'] ?? '');
$limit = intval($_GET['limit'] ?? 10);
if ($limit <= 0) $limit = 10;
$page = max(1, intval($_GET['p'] ?? 1));
$offset = ($page - 1) * $limit;

if ($activeTab === 'category') {
    $where = "WHERE 1=1";
    if ($searchQ) $where .= " AND name LIKE " . $pdo->quote("%$searchQ%");
    $totalRows = $pdo->query("SELECT COUNT(*) FROM categories $where")->fetchColumn();
    $listData = $pdo->query("SELECT * FROM categories $where ORDER BY id ASC LIMIT $limit OFFSET $offset")->fetchAll();
} elseif ($activeTab === 'subcategory') {
    $where = "WHERE 1=1";
    if ($searchQ) $where .= " AND (name LIKE " . $pdo->quote("%$searchQ%") . " OR category_name LIKE " . $pdo->quote("%$searchQ%") . ")";
    $totalRows = $pdo->query("SELECT COUNT(*) FROM sub_categories $where")->fetchColumn();
    $listData = $pdo->query("SELECT * FROM sub_categories $where ORDER BY id ASC LIMIT $limit OFFSET $offset")->fetchAll();
} else {
    $where = "WHERE 1=1";
    if ($searchQ) $where .= " AND (name LIKE " . $pdo->quote("%$searchQ%") . " OR symbol LIKE " . $pdo->quote("%$searchQ%") . ")";
    $totalRows = $pdo->query("SELECT COUNT(*) FROM measurements $where")->fetchColumn();
    $listData = $pdo->query("SELECT * FROM measurements $where ORDER BY id ASC LIMIT $limit OFFSET $offset")->fetchAll();
}
$totalPages = ceil($totalRows / $limit);
?>

<main class="space-y-4 animate-fade-in">
    <!-- Top Navigation Tabs (Exact match with uploaded Image) -->
    <div class="flex items-center gap-2 bg-white p-2.5 rounded-2xl border border-slate-200/80 shadow-xs">
        <!-- 1. Category Tab -->
        <a href="category_settings.php?tab=category" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all <?= $activeTab === 'category' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
            <i data-lucide="folder" class="w-4 h-4 <?= $activeTab === 'category' ? 'text-white' : 'text-blue-600' ?>"></i>
            <span>Category</span>
        </a>

        <!-- 2. Sub Category Tab -->
        <a href="category_settings.php?tab=subcategory" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all <?= $activeTab === 'subcategory' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
            <i data-lucide="layers" class="w-4 h-4 <?= $activeTab === 'subcategory' ? 'text-white' : 'text-blue-600' ?>"></i>
            <span>Sub Category</span>
        </a>

        <!-- 3. Measurement Tab -->
        <a href="category_settings.php?tab=measurement" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all <?= $activeTab === 'measurement' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
            <i data-lucide="ruler" class="w-4 h-4 <?= $activeTab === 'measurement' ? 'text-white' : 'text-blue-600' ?>"></i>
            <span>Measurement</span>
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

    <!-- Two-Column Layout (Matching Image) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        
        <!-- LEFT COLUMN: Create Form (lg:col-span-4) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
            <?php if ($activeTab === 'category'): ?>
                <!-- Category Form -->
                <div class="flex items-center gap-2 text-blue-600 font-black text-sm mb-4">
                    <span class="text-blue-600 text-base font-bold">+</span>
                    <span>Create Category</span>
                </div>

                <form method="POST" id="form-category" class="space-y-4 text-xs">
                    <input type="hidden" name="action" value="create_category">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="cat-name-input" required placeholder="Enter name" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 rounded-xl border border-slate-200 p-1 bg-slate-50 gap-1">
                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="Active" checked class="peer sr-only">
                                <div class="py-2 text-center rounded-lg text-xs font-extrabold transition-all peer-checked:bg-white peer-checked:text-emerald-700 peer-checked:border peer-checked:border-emerald-300 peer-checked:shadow-xs text-slate-500">
                                    ✓ Enabled
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="Disabled" class="peer sr-only">
                                <div class="py-2 text-center rounded-lg text-xs font-extrabold transition-all peer-checked:bg-white peer-checked:text-rose-600 peer-checked:border peer-checked:border-rose-300 peer-checked:shadow-xs text-slate-500">
                                    ✕ Disabled
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="reset" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs inline-flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 text-slate-400"></i>
                            <span>Reset</span>
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs inline-flex items-center gap-1.5 shadow-md shadow-blue-600/25 transition-all cursor-pointer">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Save</span>
                        </button>
                    </div>
                </form>

            <?php elseif ($activeTab === 'subcategory'): ?>
                <!-- Sub Category Form -->
                <div class="flex items-center gap-2 text-blue-600 font-black text-sm mb-4">
                    <span class="text-blue-600 text-base font-bold">+</span>
                    <span>Create Sub Category</span>
                </div>

                <form method="POST" id="form-subcategory" class="space-y-4 text-xs">
                    <input type="hidden" name="action" value="create_subcategory">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select name="category_name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 outline-none focus:border-blue-600 bg-white cursor-pointer">
                            <option value="">Select Category</option>
                            <?php foreach ($allCategories as $c): ?>
                                <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">
                            Sub Category Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" required placeholder="Enter sub category name" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 rounded-xl border border-slate-200 p-1 bg-slate-50 gap-1">
                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="Active" checked class="peer sr-only">
                                <div class="py-2 text-center rounded-lg text-xs font-extrabold transition-all peer-checked:bg-white peer-checked:text-emerald-700 peer-checked:border peer-checked:border-emerald-300 peer-checked:shadow-xs text-slate-500">
                                    ✓ Enabled
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="Disabled" class="peer sr-only">
                                <div class="py-2 text-center rounded-lg text-xs font-extrabold transition-all peer-checked:bg-white peer-checked:text-rose-600 peer-checked:border peer-checked:border-rose-300 peer-checked:shadow-xs text-slate-500">
                                    ✕ Disabled
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="reset" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs inline-flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 text-slate-400"></i>
                            <span>Reset</span>
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs inline-flex items-center gap-1.5 shadow-md shadow-blue-600/25 transition-all cursor-pointer">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Save</span>
                        </button>
                    </div>
                </form>

            <?php else: ?>
                <!-- Measurement / Unit Form -->
                <div class="flex items-center gap-2 text-blue-600 font-black text-sm mb-4">
                    <span class="text-blue-600 text-base font-bold">+</span>
                    <span>Create Measurement</span>
                </div>

                <form method="POST" id="form-measurement" class="space-y-4 text-xs">
                    <input type="hidden" name="action" value="create_measurement">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">
                            Unit Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" required placeholder="e.g. Pieces, Box, Meter" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">
                            Symbol / Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="symbol" required placeholder="e.g. Pcs, Box, M, Kg" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 rounded-xl border border-slate-200 p-1 bg-slate-50 gap-1">
                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="Active" checked class="peer sr-only">
                                <div class="py-2 text-center rounded-lg text-xs font-extrabold transition-all peer-checked:bg-white peer-checked:text-emerald-700 peer-checked:border peer-checked:border-emerald-300 peer-checked:shadow-xs text-slate-500">
                                    ✓ Enabled
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="Disabled" class="peer sr-only">
                                <div class="py-2 text-center rounded-lg text-xs font-extrabold transition-all peer-checked:bg-white peer-checked:text-rose-600 peer-checked:border peer-checked:border-rose-300 peer-checked:shadow-xs text-slate-500">
                                    ✕ Disabled
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="reset" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs inline-flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 text-slate-400"></i>
                            <span>Reset</span>
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs inline-flex items-center gap-1.5 shadow-md shadow-blue-600/25 transition-all cursor-pointer">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Save</span>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- RIGHT COLUMN: Table List (lg:col-span-8 - Exact match with Image) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <!-- Header Toolbar -->
            <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <i data-lucide="layout-grid" class="w-4 h-4 text-slate-500"></i>
                    <h3 class="text-sm font-black text-slate-800">
                        <?= $activeTab === 'category' ? 'Category List' : ($activeTab === 'subcategory' ? 'Sub Category List' : 'Measurement List') ?>
                    </h3>
                </div>

                <div class="flex items-center gap-3 text-xs">
                    <!-- Show entries dropdown -->
                    <div class="flex items-center gap-1.5 text-slate-600">
                        <span>Show</span>
                        <select onchange="location.href='category_settings.php?tab=<?= $activeTab ?>&limit=' + this.value + '&search=<?= urlencode($searchQ) ?>'" class="bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 font-bold text-slate-700 outline-none">
                            <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                            <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                        </select>
                        <span>entries</span>
                    </div>

                    <!-- Search Input -->
                    <form method="GET" class="flex items-center gap-1.5">
                        <input type="hidden" name="tab" value="<?= $activeTab ?>">
                        <input type="hidden" name="limit" value="<?= $limit ?>">
                        <span class="text-slate-500 font-bold">Search:</span>
                        <input type="text" name="search" value="<?= htmlspecialchars($searchQ) ?>" class="px-2.5 py-1 rounded-lg border border-slate-200 text-xs outline-none focus:border-blue-600 w-36 sm:w-48">
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-slate-50/80 text-slate-600 font-extrabold text-[11px] border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-4 w-12"># ⇅</th>
                            <?php if ($activeTab === 'category'): ?>
                                <th class="py-3 px-4">Name ⇅</th>
                                <th class="py-3 px-4">Status ⇅</th>
                                <th class="py-3 px-4 text-center w-24">Action ⇅</th>
                            <?php elseif ($activeTab === 'subcategory'): ?>
                                <th class="py-3 px-4">Sub Category Name ⇅</th>
                                <th class="py-3 px-4">Category ⇅</th>
                                <th class="py-3 px-4">Status ⇅</th>
                                <th class="py-3 px-4 text-center w-24">Action ⇅</th>
                            <?php else: ?>
                                <th class="py-3 px-4">Unit Name ⇅</th>
                                <th class="py-3 px-4">Symbol ⇅</th>
                                <th class="py-3 px-4">Status ⇅</th>
                                <th class="py-3 px-4 text-center w-24">Action ⇅</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($listData)): ?>
                            <tr>
                                <td colspan="5" class="py-10 text-center text-slate-400 font-medium">
                                    No records found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($listData as $idx => $row): 
                                $rowNo = $offset + $idx + 1;
                                $isActive = ($row['status'] ?? 'Active') === 'Active';
                                $itemJson = htmlspecialchars(json_encode($row));
                            ?>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 font-mono font-medium text-slate-500"><?= $rowNo ?></td>

                                <?php if ($activeTab === 'category'): ?>
                                    <td class="py-3 px-4 font-black text-slate-800 tracking-tight uppercase"><?= htmlspecialchars($row['name']) ?></td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-extrabold <?= $isActive ? 'bg-emerald-700 text-white' : 'bg-rose-100 text-rose-700' ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <!-- Actions: Green Edit, Red Trash (Image) -->
                                    <td class="py-3 px-4 text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button type="button" onclick="openEditModal('category', <?= $itemJson ?>)" class="w-7 h-7 rounded-md bg-emerald-700 hover:bg-emerald-800 text-white flex items-center justify-center transition-transform hover:scale-105 cursor-pointer shadow-xs" title="Edit">
                                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                            </button>
                                            <button type="button" onclick="confirmDelete('delete_category', <?= $row['id'] ?>, '<?= addslashes($row['name']) ?>')" class="w-7 h-7 rounded-md bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center transition-transform hover:scale-105 cursor-pointer shadow-xs" title="Delete">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </div>
                                    </td>

                                <?php elseif ($activeTab === 'subcategory'): ?>
                                    <td class="py-3 px-4 font-black text-slate-800 tracking-tight"><?= htmlspecialchars($row['name']) ?></td>
                                    <td class="py-3 px-4 font-bold text-blue-600 uppercase"><?= htmlspecialchars($row['category_name']) ?></td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-extrabold <?= $isActive ? 'bg-emerald-700 text-white' : 'bg-rose-100 text-rose-700' ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button type="button" onclick="openEditModal('subcategory', <?= $itemJson ?>)" class="w-7 h-7 rounded-md bg-emerald-700 hover:bg-emerald-800 text-white flex items-center justify-center transition-transform hover:scale-105 cursor-pointer shadow-xs" title="Edit">
                                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                            </button>
                                            <button type="button" onclick="confirmDelete('delete_subcategory', <?= $row['id'] ?>, '<?= addslashes($row['name']) ?>')" class="w-7 h-7 rounded-md bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center transition-transform hover:scale-105 cursor-pointer shadow-xs" title="Delete">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </div>
                                    </td>

                                <?php else: ?>
                                    <td class="py-3 px-4 font-black text-slate-800 tracking-tight"><?= htmlspecialchars($row['name']) ?></td>
                                    <td class="py-3 px-4 font-mono font-bold text-slate-700"><?= htmlspecialchars($row['symbol']) ?></td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-extrabold <?= $isActive ? 'bg-emerald-700 text-white' : 'bg-rose-100 text-rose-700' ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button type="button" onclick="openEditModal('measurement', <?= $itemJson ?>)" class="w-7 h-7 rounded-md bg-emerald-700 hover:bg-emerald-800 text-white flex items-center justify-center transition-transform hover:scale-105 cursor-pointer shadow-xs" title="Edit">
                                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                            </button>
                                            <button type="button" onclick="confirmDelete('delete_measurement', <?= $row['id'] ?>, '<?= addslashes($row['name']) ?>')" class="w-7 h-7 rounded-md bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center transition-transform hover:scale-105 cursor-pointer shadow-xs" title="Delete">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer: Pagination (Matching Image) -->
            <div class="p-3.5 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <span class="text-slate-500 font-medium">
                    Showing <?= min($offset + 1, $totalRows) ?> to <?= min($offset + $limit, $totalRows) ?> of <?= $totalRows ?> entries
                </span>

                <div class="inline-flex items-center gap-1">
                    <!-- Previous -->
                    <?php if ($page > 1): ?>
                        <a href="category_settings.php?tab=<?= $activeTab ?>&p=<?= $page - 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($searchQ) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold transition-colors">
                            Previous
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-1.5 rounded-lg border border-slate-100 text-slate-300 font-bold cursor-not-allowed">
                            Previous
                        </span>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <?php for ($i = 1; $i <= max(1, $totalPages); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="w-8 h-8 rounded-lg bg-blue-600 text-white font-extrabold flex items-center justify-center shadow-xs">
                                <?= $i ?>
                            </span>
                        <?php else: ?>
                            <a href="category_settings.php?tab=<?= $activeTab ?>&p=<?= $i ?>&limit=<?= $limit ?>&search=<?= urlencode($searchQ) ?>" class="w-8 h-8 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold flex items-center justify-center transition-colors">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Next -->
                    <?php if ($page < $totalPages): ?>
                        <a href="category_settings.php?tab=<?= $activeTab ?>&p=<?= $page + 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($searchQ) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold transition-colors">
                            Next
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-1.5 rounded-lg border border-slate-100 text-slate-300 font-bold cursor-not-allowed">
                            Next
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- EDIT MODAL -->
<div id="edit-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full border border-slate-200 shadow-2xl overflow-hidden animate-scale-up">
        <div class="bg-blue-600 px-5 py-3.5 text-white flex items-center justify-between">
            <h3 class="font-black text-sm" id="edit-modal-title">Edit Record</h3>
            <button type="button" onclick="closeEditModal()" class="text-white/80 hover:text-white font-bold cursor-pointer">✕</button>
        </div>
        <form method="POST" id="edit-modal-form" class="p-5 space-y-4 text-xs">
            <input type="hidden" name="action" id="edit-action">
            <input type="hidden" name="id" id="edit-id">

            <!-- Category selector (for subcategory only) -->
            <div id="edit-category-group" class="hidden">
                <label class="block font-bold text-slate-700 mb-1">Category</label>
                <select name="category_name" id="edit-category-name" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-bold outline-none focus:border-blue-600 bg-white">
                    <?php foreach ($allCategories as $c): ?>
                        <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Name input -->
            <div>
                <label class="block font-bold text-slate-700 mb-1" id="edit-name-label">Name</label>
                <input type="text" name="name" id="edit-name" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-blue-600">
            </div>

            <!-- Symbol input (for measurement only) -->
            <div id="edit-symbol-group" class="hidden">
                <label class="block font-bold text-slate-700 mb-1">Symbol / Code</label>
                <input type="text" name="symbol" id="edit-symbol" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-blue-600">
            </div>

            <!-- Status Radio -->
            <div>
                <label class="block font-bold text-slate-700 mb-1">Status</label>
                <div class="grid grid-cols-2 rounded-xl border border-slate-200 p-1 bg-slate-50 gap-1">
                    <label class="cursor-pointer">
                        <input type="radio" name="status" id="edit-status-active" value="Active" class="peer sr-only">
                        <div class="py-1.5 text-center rounded-lg text-xs font-bold peer-checked:bg-white peer-checked:text-emerald-700 peer-checked:border peer-checked:border-emerald-300 text-slate-500">
                            ✓ Enabled
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="status" id="edit-status-disabled" value="Disabled" class="peer sr-only">
                        <div class="py-1.5 text-center rounded-lg text-xs font-bold peer-checked:bg-white peer-checked:text-rose-600 peer-checked:border peer-checked:border-rose-300 text-slate-500">
                            ✕ Disabled
                        </div>
                    </label>
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black shadow-md cursor-pointer">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE FORM (HIDDEN) -->
<form method="POST" id="delete-form" class="hidden">
    <input type="hidden" name="action" id="delete-action">
    <input type="hidden" name="id" id="delete-id">
</form>

<script>
function openEditModal(type, data) {
    document.getElementById('edit-id').value = data.id;
    document.getElementById('edit-name').value = data.name;
    
    if (data.status === 'Disabled') {
        document.getElementById('edit-status-disabled').checked = true;
    } else {
        document.getElementById('edit-status-active').checked = true;
    }

    const catGroup = document.getElementById('edit-category-group');
    const symGroup = document.getElementById('edit-symbol-group');
    const title = document.getElementById('edit-modal-title');
    const nameLabel = document.getElementById('edit-name-label');

    if (type === 'category') {
        document.getElementById('edit-action').value = 'edit_category';
        title.innerText = 'Edit Category: ' + data.name;
        nameLabel.innerText = 'Category Name';
        catGroup.classList.add('hidden');
        symGroup.classList.add('hidden');
    } else if (type === 'subcategory') {
        document.getElementById('edit-action').value = 'edit_subcategory';
        title.innerText = 'Edit Sub Category: ' + data.name;
        nameLabel.innerText = 'Sub Category Name';
        catGroup.classList.remove('hidden');
        symGroup.classList.add('hidden');
        document.getElementById('edit-category-name').value = data.category_name || '';
    } else if (type === 'measurement') {
        document.getElementById('edit-action').value = 'edit_measurement';
        title.innerText = 'Edit Measurement: ' + data.name;
        nameLabel.innerText = 'Unit Name';
        catGroup.classList.add('hidden');
        symGroup.classList.remove('hidden');
        document.getElementById('edit-symbol').value = data.symbol || '';
    }

    document.getElementById('edit-modal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.add('hidden');
}

function confirmDelete(action, id, name) {
    if (confirm("Are you sure you want to delete '" + name + "'?")) {
        document.getElementById('delete-action').value = action;
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-form').submit();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
