<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$successMsg = '';
$errorMsg = '';

// Handle Delete Purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_purchase') {
        $pId = intval($_POST['purchase_id'] ?? 0);
        if ($pId > 0) {
            $stmt = $pdo->prepare("DELETE FROM purchases WHERE id = ?");
            $stmt->execute([$pId]);
            $successMsg = "Purchase transaction deleted successfully.";
        }
    }
}

// Search and filter parameters
$supplierQ = trim($_GET['supplier'] ?? '');
$phoneQ = trim($_GET['phone'] ?? '');
$typeQ = trim($_GET['type'] ?? 'All');
$methodQ = trim($_GET['method'] ?? 'All');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$limit = intval($_GET['limit'] ?? 10);
if ($limit <= 0) $limit = 10;

$where = "WHERE 1=1";
if ($supplierQ) {
    $where .= " AND supplier_name LIKE " . $pdo->quote("%$supplierQ%");
}
if ($phoneQ) {
    $where .= " AND phone LIKE " . $pdo->quote("%$phoneQ%");
}
if ($typeQ !== 'All' && !empty($typeQ)) {
    $where .= " AND type = " . $pdo->quote($typeQ);
}
if ($methodQ !== 'All' && !empty($methodQ)) {
    $where .= " AND payment_method = " . $pdo->quote($methodQ);
}
if ($startDate) {
    $where .= " AND purchase_date >= " . $pdo->quote($startDate);
}
if ($endDate) {
    $where .= " AND purchase_date <= " . $pdo->quote($endDate);
}

$sql = "SELECT * FROM purchases $where ORDER BY id DESC LIMIT $limit";
$purchases = $pdo->query($sql)->fetchAll();
$totalRecords = $pdo->query("SELECT COUNT(*) FROM purchases $where")->fetchColumn();

// Load all purchase items grouped by purchase_id for receipt printing
$allPurchaseItems = [];
$piRows = $pdo->query("SELECT * FROM purchase_items ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($piRows as $pi) {
    $allPurchaseItems[$pi['purchase_id']][] = [
        'name'  => $pi['product_name'] ?: 'N/A',
        'qty'   => floatval($pi['qty']),
        'cost'  => floatval($pi['last_price'] ?? $pi['wholesale_price'] ?? 0),
        'total' => floatval($pi['total_cost'] ?? 0)
    ];
}
?>

<main class="space-y-4 animate-fade-in">
    <!-- Header (Image 5) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <h1 class="text-lg font-black text-slate-900 tracking-tight">Purchases Payment Transactions</h1>
            <p class="text-xs text-slate-500 font-medium">Manage and monitor purchase transaction records and supplier payments</p>
        </div>
        <a href="create_purchase.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs shadow-md transition-all active:scale-95 cursor-pointer">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>New Purchase</span>
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

    <!-- Filter Form Card (Image 5) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs">
        <form method="GET" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Supplier Name -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Supplier Name</label>
                    <input type="text" name="supplier" value="<?= htmlspecialchars($supplierQ) ?>" placeholder="Search Supplier" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-red-600">
                </div>

                <!-- Phone Number -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Phone Number</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($phoneQ) ?>" placeholder="Search Phone" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium outline-none focus:border-red-600">
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Type</label>
                    <select name="type" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 outline-none bg-white">
                        <option value="All" <?= $typeQ === 'All' ? 'selected' : '' ?>>All Types</option>
                        <option value="Cash" <?= $typeQ === 'Cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="Credit" <?= $typeQ === 'Credit' ? 'selected' : '' ?>>Credit</option>
                        <option value="Cash & Credit" <?= $typeQ === 'Cash & Credit' ? 'selected' : '' ?>>Cash &amp; Credit</option>
                    </select>
                </div>

                <!-- Payment Method -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Payment Method</label>
                    <select name="method" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 outline-none bg-white">
                        <option value="All" <?= $methodQ === 'All' ? 'selected' : '' ?>>All Methods</option>
                        <option value="ZAAD" <?= $methodQ === 'ZAAD' ? 'selected' : '' ?>>ZAAD</option>
                        <option value="e-Dahab" <?= $methodQ === 'e-Dahab' ? 'selected' : '' ?>>e-Dahab</option>
                        <option value="Cash On Hand" <?= $methodQ === 'Cash On Hand' ? 'selected' : '' ?>>Cash On Hand</option>
                        <option value="Bank Transfer" <?= $methodQ === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                    </select>
                </div>

                <!-- Start Date -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                <!-- End Date -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">End Date</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium outline-none">
                </div>

                <!-- Filter & Reset Buttons -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs shadow-md transition-colors cursor-pointer">
                        Filter
                    </button>
                    <a href="purchases_list.php" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Purchases Table (Image 5) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-3 border-b border-slate-100 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <span class="text-slate-600 font-bold">Show per page : Show</span>
                <select onchange="location.href='purchases_list.php?limit=' + this.value" class="bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 font-bold text-slate-700 outline-none">
                    <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                </select>
                <span class="text-slate-600">entries</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-slate-500 font-bold text-xs">Search:</span>
                <input type="text" id="table-filter-input" onkeyup="filterTable(this.value)" class="px-3 py-1 rounded-xl border border-slate-200 text-xs outline-none">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-red-600 text-white font-black text-[11px] tracking-tight">
                    <tr>
                        <th class="py-3 px-3"># ⇅</th>
                        <th class="py-3 px-3">Supplier ⇅</th>
                        <th class="py-3 px-3">Phone ⇅</th>
                        <th class="py-3 px-3">Type ⇅</th>
                        <th class="py-3 px-3 text-right">Sub Total ⇅</th>
                        <th class="py-3 px-3 text-right">Discount ⇅</th>
                        <th class="py-3 px-3 text-right">Net Price ⇅</th>
                        <th class="py-3 px-3 text-right">Additionals ⇅</th>
                        <th class="py-3 px-3 text-right">Paid Amount ⇅</th>
                        <th class="py-3 px-3 text-right">Balance ⇅</th>
                        <th class="py-3 px-3 text-center">Paid Date ⇅</th>
                        <th class="py-3 px-3 text-center">Method ⇅</th>
                        <th class="py-3 px-3 text-center">Created User ⇅</th>
                        <th class="py-3 px-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="purchases-tbody" class="divide-y divide-slate-100">
                    <?php if (empty($purchases)): ?>
                    <tr>
                        <td colspan="14" class="py-12 text-center text-slate-400 font-medium">
                            No purchase transaction records found.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($purchases as $p): 
                        $purchaseNo = !empty($p['purchase_no']) ? $p['purchase_no'] : '#' . $p['id'];
                        $pData = [
                            'id'            => $p['id'],
                            'purchase_no'   => $purchaseNo,
                            'supplier_name' => $p['supplier_name'] ?: 'INVENTORY STORE',
                            'phone'         => $p['phone'] ?: '0634531454',
                            'purchase_date' => $p['purchase_date'] ?: date('Y-m-d'),
                            'subtotal'      => floatval($p['subtotal']),
                            'discount'      => floatval($p['discount']),
                            'net_price'     => floatval($p['net_price']),
                            'additionals'   => floatval($p['additionals']),
                            'paid_amount'   => floatval($p['paid_amount']),
                            'balance'       => floatval($p['balance']),
                            'items'         => $allPurchaseItems[$p['id']] ?? []
                        ];
                        $pJson = htmlspecialchars(json_encode($pData, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-3 font-mono font-black text-slate-900"><?= htmlspecialchars($purchaseNo) ?></td>
                        <td class="py-3 px-3 font-black text-slate-900 uppercase"><?= htmlspecialchars($p['supplier_name']) ?></td>
                        <td class="py-3 px-3 font-mono text-slate-600 font-medium"><?= htmlspecialchars($p['phone'] ?: '0634531454') ?></td>
                        <td class="py-3 px-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-red-50 text-red-700 border border-red-200">
                                <?= htmlspecialchars($p['type']) ?>
                            </span>
                        </td>
                        <td class="py-3 px-3 text-right font-black text-slate-900 font-mono">$<?= number_format($p['subtotal'], 2) ?></td>
                        <td class="py-3 px-3 text-right text-slate-400 font-mono">$<?= number_format($p['discount'], 2) ?></td>
                        <td class="py-3 px-3 text-right font-black text-slate-900 font-mono">$<?= number_format($p['net_price'], 2) ?></td>
                        <td class="py-3 px-3 text-right text-slate-400 font-mono">$<?= number_format($p['additionals'], 2) ?></td>
                        <td class="py-3 px-3 text-right font-black text-red-600 font-mono">$<?= number_format($p['paid_amount'], 2) ?></td>
                        <td class="py-3 px-3 text-right font-black text-red-600 font-mono">$<?= number_format($p['balance'], 2) ?></td>
                        <td class="py-3 px-3 text-center text-slate-600 font-mono text-[11px]"><?= htmlspecialchars($p['purchase_date']) ?></td>
                        <td class="py-3 px-3 text-center font-bold text-slate-700"><?= htmlspecialchars($p['payment_method']) ?></td>
                        <td class="py-3 px-3 text-center text-slate-600 font-medium">
                            <span class="inline-flex items-center gap-1 text-[11px]">
                                <i data-lucide="user" class="w-3 h-3 text-blue-500"></i>
                                <span><?= htmlspecialchars($p['created_user']) ?></span>
                            </span>
                        </td>
                        <!-- Actions (Image 5: Edit, Return, Print, Delete) -->
                        <td class="py-3 px-3 text-center">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="create_purchase.php?edit_id=<?= $p['id'] ?>" class="px-2 py-1 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold shadow-xs transition-colors flex items-center gap-1">
                                    <i data-lucide="edit" class="w-3 h-3"></i>
                                    <span>Edit</span>
                                </a>
                                <a href="create_purchase_return.php?purchase_id=<?= $p['id'] ?>" class="px-2 py-1 rounded-md bg-amber-500 hover:bg-amber-600 text-white text-[11px] font-bold shadow-xs transition-colors flex items-center gap-1">
                                    <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                    <span>Return</span>
                                </a>
                                <button type="button" onclick='openPurchaseReceipt(<?= $pJson ?>)' class="px-2 py-1 rounded-md bg-red-600 hover:bg-red-700 text-white text-[11px] font-bold shadow-xs transition-colors flex items-center gap-1 cursor-pointer" title="Print Purchases Receipt">
                                    <i data-lucide="printer" class="w-3 h-3"></i>
                                    <span>Print</span>
                                </button>
                                <button type="button" onclick="confirmDeletePurchase(<?= $p['id'] ?>)" class="px-2 py-1 rounded-md border border-slate-300 hover:bg-red-50 hover:text-red-600 text-slate-600 text-[11px] font-bold transition-colors cursor-pointer">
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

        <!-- Pagination Bar (Image 5) -->
        <div class="p-3.5 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-600 font-medium">
            <span>Showing 1 to <?= count($purchases) ?> of <?= $totalRecords ?> entries</span>
            <div class="flex items-center gap-1">
                <button type="button" class="px-3 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 font-bold text-slate-500 disabled:opacity-50" disabled>Previous</button>
                <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold">1</span>
                <button type="button" class="px-3 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 font-bold text-slate-500 disabled:opacity-50" disabled>Next</button>
            </div>
        </div>
    </div>
</main>

<!-- Delete Form -->
<form id="delete-purchase-form" method="POST" class="hidden">
    <input type="hidden" name="action" value="delete_purchase">
    <input type="hidden" name="purchase_id" id="delete-p-id">
</form>

<!-- ═══════════════════════════════════════════════════════════════════════════
     PURCHASES RECEIPT MODAL & PRINT CONTAINER (Exact match to Screenshot 2)
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="purchase-receipt-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs no-print">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto animate-scale-in">
        <!-- Top Action Bar (Buttons to Print or Close) -->
        <div class="p-3 bg-slate-100 border-b border-slate-200 flex items-center justify-between no-print">
            <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                <i data-lucide="receipt" class="w-4 h-4 text-red-600"></i> Purchases Receipt Preview
            </span>
            <div class="flex items-center gap-2">
                <button type="button" onclick="printReceiptNow()" class="px-4 py-1.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-black shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>Print Receipt</span>
                </button>
                <button type="button" onclick="closePurchaseReceipt()" class="px-3 py-1.5 rounded-xl border border-slate-300 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors cursor-pointer">
                    Close
                </button>
            </div>
        </div>

        <!-- Printable Receipt Body (Exact match to Screenshot 2) -->
        <div id="purchase-receipt-sheet" class="p-8 bg-white text-slate-900 font-sans space-y-4">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <img src="assets/images/amaani-logo.jpg" alt="Amaani Electronics" class="h-12 w-auto object-contain shrink-0">
                    <div>
                        <h1 class="text-base font-black text-[#c00000] uppercase tracking-wide">AMAANI ELECTRONICS</h1>
                        <p class="text-[11px] text-slate-600 font-medium">Hoyga Qalabka Elektaroonigga ah &amp; Qalabka Casriga ah</p>
                        <p class="text-[10px] text-slate-400">Sisibta, Borama, Somaliland</p>
                        <p class="text-[10px] text-slate-400">Tel: 634547497 / 0634515815 | Zaad: 509900 | eDahab: 721118</p>
                    </div>
                </div>

                <div class="text-right">
                    <h2 class="text-xl font-black text-slate-900 uppercase tracking-wider">INVOICE</h2>
                    <div class="text-xs text-slate-700 font-black" id="pr-invoice-num">#4</div>
                    <div class="text-[11px] text-slate-500 mt-1">Date: <span id="pr-invoice-date" class="font-bold text-slate-800">2026-11-17</span></div>
                </div>
            </div>

            <!-- Title Bar enclosed by double border lines -->
            <div class="border-t-2 border-b-2 border-slate-400 py-1 text-center font-black tracking-widest text-xs uppercase text-slate-900">
                PURCHASES RECEIPT
            </div>

            <!-- Info Box: Invoice To & Phone No -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs space-y-0.5">
                <div><span class="font-bold text-slate-700">Invoice To :</span> <span id="pr-supplier-name" class="font-black text-slate-900 uppercase">INVENTORY STORE</span></div>
                <div><span class="font-bold text-slate-700">Phone No :</span> <span id="pr-supplier-phone" class="font-mono text-slate-800">0634531454</span></div>
            </div>

            <!-- Table with dark header -->
            <table class="w-full text-left text-xs border border-slate-200">
                <thead class="bg-[#2f3237] text-white uppercase font-black text-[10px]">
                    <tr>
                        <th class="py-2.5 px-3">ITEM DESCRIPTION</th>
                        <th class="py-2.5 px-3 text-center w-24">QTY</th>
                        <th class="py-2.5 px-3 text-right w-28">UNIT COST</th>
                        <th class="py-2.5 px-3 text-right w-28">TOTAL COST</th>
                    </tr>
                </thead>
                <tbody id="pr-items-tbody" class="divide-y divide-slate-200 font-medium text-slate-800">
                    <!-- Populated dynamically -->
                </tbody>
            </table>

            <!-- Totals Summary -->
            <div class="flex justify-end pt-2">
                <div class="w-64 space-y-1 text-xs">
                    <div class="flex justify-between text-slate-600"><span>Sub Total:</span><span id="pr-subtotal" class="font-bold text-slate-900 font-mono">$0.00</span></div>
                    <div class="flex justify-between text-slate-600"><span>Discount:</span><span id="pr-discount" class="font-bold text-amber-600 font-mono">-$0.00</span></div>
                    <div class="flex justify-between items-center bg-[#2f3237] text-white px-2.5 py-1.5 rounded-lg font-black text-xs"><span>Total Cost:</span><span id="pr-total" class="font-mono">$0.00</span></div>
                    <div class="flex justify-between text-emerald-600 font-bold"><span>Paid Amount:</span><span id="pr-paid" class="font-mono">$0.00</span></div>
                    <div class="flex justify-between text-red-600 font-black"><span>Balance Due:</span><span id="pr-balance" class="font-mono">$0.00</span></div>
                </div>
            </div>

            <!-- Signatures -->
            <div class="flex items-center justify-between pt-10 border-t border-slate-200 text-center">
                <div class="w-48">
                    <div class="border-t border-slate-400 pt-1 text-[10px] uppercase font-bold text-slate-500">SUPPLIER SIGNATURE</div>
                </div>
                <div class="w-48">
                    <div class="border-t border-slate-400 pt-1 text-[10px] uppercase font-bold text-slate-500">AUTHORIZED SIGNATURE</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body {
        background: #fff !important;
    }
    aside, header, #main-sidebar, nav, .no-print, main {
        display: none !important;
    }
    #purchase-receipt-modal, #purchase-receipt-sheet, #purchase-receipt-sheet * {
        visibility: visible !important;
        display: block !important;
    }
    #purchase-receipt-sheet table {
        display: table !important;
        width: 100% !important;
    }
    #purchase-receipt-sheet thead {
        display: table-header-group !important;
    }
    #purchase-receipt-sheet tbody {
        display: table-row-group !important;
    }
    #purchase-receipt-sheet tr {
        display: table-row !important;
    }
    #purchase-receipt-sheet th, #purchase-receipt-sheet td {
        display: table-cell !important;
    }
    #purchase-receipt-sheet .flex {
        display: flex !important;
    }
    #purchase-receipt-modal {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        background: transparent !important;
        padding: 0 !important;
        overflow: visible !important;
    }
    #purchase-receipt-sheet {
        padding: 10mm !important;
        box-shadow: none !important;
        border: none !important;
        max-width: 100% !important;
    }
    @page {
        size: A4 portrait;
        margin: 10mm;
    }
}
</style>

<script>
function confirmDeletePurchase(id) {
    if (confirm('Are you sure you want to delete this purchase transaction?')) {
        document.getElementById('delete-p-id').value = id;
        document.getElementById('delete-purchase-form').submit();
    }
}

function filterTable(val) {
    const q = val.toLowerCase();
    const rows = document.querySelectorAll('#purchases-tbody tr');
    rows.forEach(r => {
        const text = r.textContent.toLowerCase();
        r.style.display = text.includes(q) ? '' : 'none';
    });
}

function openPurchaseReceipt(p) {
    document.getElementById('pr-invoice-num').textContent = p.purchase_no || ('#' + p.id);
    document.getElementById('pr-invoice-date').textContent = p.purchase_date || '—';
    document.getElementById('pr-supplier-name').textContent = p.supplier_name || 'INVENTORY STORE';
    document.getElementById('pr-supplier-phone').textContent = p.phone || '0634531454';

    const tbody = document.getElementById('pr-items-tbody');
    tbody.innerHTML = '';

    if (p.items && p.items.length > 0) {
        p.items.forEach(it => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="py-2 px-3 text-slate-800">${escHtml(it.name || 'N/A')}</td>
                <td class="py-2 px-3 text-center font-mono">${parseFloat(it.qty).toFixed(2)}</td>
                <td class="py-2 px-3 text-right font-mono">$${parseFloat(it.cost).toFixed(2)}</td>
                <td class="py-2 px-3 text-right font-mono font-bold">$${parseFloat(it.total).toFixed(2)}</td>
            `;
            tbody.appendChild(tr);
        });
    } else {
        // Fallback row if no specific items linked
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="py-2 px-3 text-slate-800">Inventory Stock Package</td>
            <td class="py-2 px-3 text-center font-mono">1.00</td>
            <td class="py-2 px-3 text-right font-mono">$${parseFloat(p.net_price).toFixed(2)}</td>
            <td class="py-2 px-3 text-right font-mono font-bold">$${parseFloat(p.net_price).toFixed(2)}</td>
        `;
        tbody.appendChild(tr);
    }

    document.getElementById('pr-subtotal').textContent = '$' + parseFloat(p.subtotal).toFixed(2);
    document.getElementById('pr-discount').textContent = '-$' + parseFloat(p.discount).toFixed(2);
    document.getElementById('pr-total').textContent = '$' + parseFloat(p.net_price).toFixed(2);
    document.getElementById('pr-paid').textContent = '$' + parseFloat(p.paid_amount).toFixed(2);
    document.getElementById('pr-balance').textContent = '$' + parseFloat(p.balance).toFixed(2);

    document.getElementById('purchase-receipt-modal').classList.remove('hidden');
    lucide.createIcons();
}

function closePurchaseReceipt() {
    document.getElementById('purchase-receipt-modal').classList.add('hidden');
}

function printReceiptNow() {
    window.print();
}

function escHtml(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
