<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Handle New Exchange
if (isset($_POST['record_exchange'])) {
    $cName = trim($_POST['customer_name'] ?? '');
    $retItem = trim($_POST['returned_item'] ?? '');
    $newItem = trim($_POST['new_item'] ?? '');
    $diff = floatval($_POST['price_diff'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $excNum = 'EXC-' . rand(200, 999);
    $excDate = date('Y-m-d');

    if (!empty($cName) && !empty($retItem) && !empty($newItem)) {
        $ins = $pdo->prepare("INSERT INTO product_exchanges (exchange_number, customer_name, returned_item, new_item, price_diff, exchange_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$excNum, $cName, $retItem, $newItem, $diff, $excDate, $notes]);
        $successMsg = "Exchange {$excNum} registered successfully!";
    }
}

$exchanges = $pdo->query("SELECT * FROM product_exchanges ORDER BY id DESC")->fetchAll();
?>

<main class="flex-1 min-w-0 space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                <i data-lucide="repeat" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-base font-black text-slate-900">Product Exchanges &amp; Warranty Returns</h2>
                <p class="text-xs text-slate-500">Record customer electronics device swaps, warranty claims &amp; price upgrades.</p>
            </div>
        </div>
        <button type="button" onclick="document.getElementById('new-exc-modal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#c00000] hover:bg-red-700 text-white text-xs font-bold shadow-xs cursor-pointer">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Register Exchange</span>
        </button>
    </div>

    <?php if (!empty($successMsg)): ?>
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-xs font-bold text-emerald-800 flex items-center gap-2">
        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
        <span><?= htmlspecialchars($successMsg) ?></span>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-black text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-4">Exchange #</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Customer</th>
                        <th class="py-3 px-4">Returned Item</th>
                        <th class="py-3 px-4">Replacement / New Device</th>
                        <th class="py-3 px-4 text-right">Price Difference</th>
                        <th class="py-3 px-4">Technician Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($exchanges)): ?>
                    <tr>
                        <td colspan="7" class="py-10 text-center text-slate-400 font-bold">No product exchanges recorded.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($exchanges as $exc): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 font-mono font-bold text-[#0B2A5B]"><?= htmlspecialchars($exc['exchange_number']) ?></td>
                        <td class="py-3 px-4 font-mono text-slate-600"><?= htmlspecialchars($exc['exchange_date']) ?></td>
                        <td class="py-3 px-4 font-bold text-slate-900"><?= htmlspecialchars($exc['customer_name']) ?></td>
                        <td class="py-3 px-4 text-red-600 font-medium"><?= htmlspecialchars($exc['returned_item']) ?></td>
                        <td class="py-3 px-4 text-emerald-700 font-bold"><?= htmlspecialchars($exc['new_item']) ?></td>
                        <td class="py-3 px-4 text-right font-black text-slate-900">
                            <?= $exc['price_diff'] > 0 ? '+$' . number_format($exc['price_diff'], 2) : '$0.00' ?>
                        </td>
                        <td class="py-3 px-4 text-slate-500"><?= htmlspecialchars($exc['notes']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal -->
<div id="new-exc-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white rounded-2xl max-w-md w-full border border-slate-200 shadow-2xl overflow-hidden p-5 space-y-4">
        <div class="flex items-center justify-between border-b pb-3">
            <h3 class="text-sm font-black text-slate-900">Register Device Exchange</h3>
            <button onclick="document.getElementById('new-exc-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 cursor-pointer">×</button>
        </div>
        <form method="POST" class="space-y-3 text-xs">
            <input type="hidden" name="record_exchange" value="1">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Customer Name</label>
                <input type="text" name="customer_name" required placeholder="e.g. WARSAME ELECTRONIC" class="w-full px-3 py-2 rounded-xl border border-slate-200">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Returned / Defective Device</label>
                <input type="text" name="returned_item" required placeholder="e.g. iPhone 13 (Faulty display)" class="w-full px-3 py-2 rounded-xl border border-slate-200">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Replacement / New Device Issued</label>
                <input type="text" name="new_item" required placeholder="e.g. iPhone 13 128GB Blue (New replacement)" class="w-full px-3 py-2 rounded-xl border border-slate-200">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Price Difference Charged ($ USD)</label>
                <input type="number" step="0.01" name="price_diff" value="0.00" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-bold">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Notes / Diagnosis</label>
                <input type="text" name="notes" placeholder="e.g. Verified by head technician Abdiwasac" class="w-full px-3 py-2 rounded-xl border border-slate-200">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('new-exc-modal').classList.add('hidden')" class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-bold cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-1.5 rounded-xl bg-[#c00000] text-white font-bold cursor-pointer">Confirm Exchange</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
