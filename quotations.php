<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Handle Convert to Invoice action
if (isset($_POST['convert_quote_id'])) {
    $quoteId = intval($_POST['convert_quote_id']);
    $qStmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ?");
    $qStmt->execute([$quoteId]);
    $quote = $qStmt->fetch();

    if ($quote) {
        $invNum = '#' . rand(200, 999);
        $sDate = date('Y-m-d');
        $rDate = date('Y-m-d', strtotime('+14 days'));
        
        $ins = $pdo->prepare("
            INSERT INTO sales (invoice_number, customer_name, phone, branch, sale_date, return_date, year, month, subtotal, discount, total_sale, paid_amount, payment_method, notes, items_summary, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $invNum, $quote['customer_name'], '+252 63 4001923', $quote['branch'], $sDate, $rDate, date('Y'), date('F'),
            $quote['total_amount'], 0, $quote['total_amount'], 0, 'Credit / Debt', 'Converted from Quote ' . $quote['quotation_number'],
            $quote['items_summary'], 'Due'
        ]);

        $upQ = $pdo->prepare("UPDATE quotations SET status = 'Converted' WHERE id = ?");
        $upQ->execute([$quoteId]);

        $successMsg = "Quotation {$quote['quotation_number']} successfully converted to Invoice {$invNum}!";
    }
}

// Handle New Quote creation
if (isset($_POST['create_quote'])) {
    $cName = trim($_POST['customer_name'] ?? '');
    $qBranch = trim($_POST['branch'] ?? $currentBranch);
    $qTotal = floatval($_POST['total_amount'] ?? 0);
    $qItems = trim($_POST['items_summary'] ?? '');
    $qDate = date('Y-m-d');
    $vDate = date('Y-m-d', strtotime('+14 days'));
    $qNum = 'QT-' . rand(300, 999);

    if (!empty($cName) && $qTotal > 0) {
        $stmtIns = $pdo->prepare("INSERT INTO quotations (quotation_number, customer_name, branch, quote_date, valid_until, total_amount, items_summary, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmtIns->execute([$qNum, $cName, $qBranch, $qDate, $vDate, $qTotal, $qItems]);
        $successMsg = "Quotation {$qNum} created successfully!";
    }
}

$quotes = $pdo->query("SELECT * FROM quotations ORDER BY id DESC")->fetchAll();
?>

<main class="flex-1 min-w-0 space-y-4">
    <!-- Header -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                <i data-lucide="file-text" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-base font-black text-slate-900">Commercial Quotations</h2>
                <p class="text-xs text-slate-500">Create, review proposals and convert directly into live sales invoices.</p>
            </div>
        </div>
        <button type="button" onclick="document.getElementById('new-quote-modal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#0B2A5B] hover:bg-blue-900 text-white text-xs font-bold shadow-xs cursor-pointer">
            <i data-lucide="plus" class="w-4 h-4 text-orange-400"></i>
            <span>New Quotation</span>
        </button>
    </div>

    <?php if (!empty($successMsg)): ?>
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-xs font-bold text-emerald-800 flex items-center gap-2">
        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
        <span><?= htmlspecialchars($successMsg) ?></span>
    </div>
    <?php endif; ?>

    <!-- Quotations Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-black text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-4">Quote #</th>
                        <th class="py-3 px-4">Customer Name</th>
                        <th class="py-3 px-4">Branch</th>
                        <th class="py-3 px-4">Date Issued</th>
                        <th class="py-3 px-4">Valid Until</th>
                        <th class="py-3 px-4">Summary of Items</th>
                        <th class="py-3 px-4 text-right">Estimated Total</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($quotes)): ?>
                    <tr>
                        <td colspan="9" class="py-10 text-center text-slate-400 font-bold">No quotations on file.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($quotes as $q): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 font-mono font-bold text-slate-900"><?= htmlspecialchars($q['quotation_number']) ?></td>
                        <td class="py-3 px-4 font-bold text-slate-900"><?= htmlspecialchars($q['customer_name']) ?></td>
                        <td class="py-3 px-4 text-slate-500"><?= htmlspecialchars($q['branch']) ?></td>
                        <td class="py-3 px-4 font-mono text-slate-600"><?= htmlspecialchars($q['quote_date']) ?></td>
                        <td class="py-3 px-4 font-mono text-slate-600"><?= htmlspecialchars($q['valid_until']) ?></td>
                        <td class="py-3 px-4 text-slate-700 max-w-xs truncate"><?= htmlspecialchars($q['items_summary']) ?></td>
                        <td class="py-3 px-4 text-right font-black text-slate-900">$<?= number_format($q['total_amount'], 2) ?></td>
                        <td class="py-3 px-4 text-center">
                            <?php if ($q['status'] === 'Converted'): ?>
                                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-extrabold px-2 py-0.5 rounded-full">CONVERTED</span>
                            <?php else: ?>
                                <span class="bg-blue-100 text-blue-800 text-[10px] font-extrabold px-2 py-0.5 rounded-full">PENDING</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <?php if ($q['status'] === 'Pending'): ?>
                            <form method="POST" onsubmit="return confirm('Convert this quotation to an active sales invoice?')" class="inline">
                                <input type="hidden" name="convert_quote_id" value="<?= $q['id'] ?>">
                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] shadow-xs cursor-pointer">
                                    Convert to Invoice
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="text-[11px] text-slate-400 font-semibold">Processed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- New Quote Modal -->
<div id="new-quote-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white rounded-2xl max-w-md w-full border border-slate-200 shadow-2xl overflow-hidden p-5 space-y-4">
        <div class="flex items-center justify-between border-b pb-3">
            <h3 class="text-sm font-black text-slate-900">Create New Quotation</h3>
            <button onclick="document.getElementById('new-quote-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 cursor-pointer">×</button>
        </div>
        <form method="POST" class="space-y-3 text-xs">
            <input type="hidden" name="create_quote" value="1">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Customer / Organization Name</label>
                <input type="text" name="customer_name" required placeholder="e.g. Amoud University IT Lab" class="w-full px-3 py-2 rounded-xl border border-slate-200">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Branch</label>
                <select name="branch" class="w-full px-3 py-2 rounded-xl border border-slate-200">
                    <option value="Boorama Branch">Boorama Branch</option>
                    <option value="Hargeisa Main Branch">Hargeisa Main Branch</option>
                    <option value="Berbera Port Branch">Berbera Port Branch</option>
                    <option value="Burco Central Branch">Burco Central Branch</option>
                </select>
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Items Description</label>
                <textarea name="items_summary" rows="2" required placeholder="e.g. 5x HP Laptop Core i7, 5x Bags, 5x Mouse" class="w-full px-3 py-2 rounded-xl border border-slate-200"></textarea>
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Estimated Total ($ USD)</label>
                <input type="number" step="0.01" name="total_amount" required placeholder="0.00" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-bold">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('new-quote-modal').classList.add('hidden')" class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-bold cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-1.5 rounded-xl bg-[#0B2A5B] text-white font-bold cursor-pointer">Create Quotation</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
