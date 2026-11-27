<?php
require_once __DIR__ . '/config/db.php';
$pdo = getDB();

$paymentId  = intval($_GET['id'] ?? 0);
$customerId = intval($_GET['customer_id'] ?? 0);

$receipt = null;
$isUnpaidStatement = false;

if ($paymentId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$paymentId]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($p) {
        $receipt = [
            'receipt_no'   => !empty($p['invoice_number']) ? $p['invoice_number'] : ('#' . ($p['id'] + 349)),
            'date'         => !empty($p['paid_at']) ? date('d/m/Y', strtotime($p['paid_at'])) : date('d/m/Y'),
            'method'       => $p['payment_method'] ?: 'Cash On Hand',
            'cust_name'    => $p['customer_name'] ?: 'Customer',
            'cust_phone'   => $p['customer_phone'] ?: '—',
            'received_by'  => $p['created_user'] ?: 'Admin',
            'branch'       => $p['department'] ?: 'Boorama Branch',
            'prev_balance' => floatval($p['previous_balance']),
            'discount'     => floatval($p['discount']),
            'amount_paid'  => floatval($p['amount']),
            'rem_balance'  => floatval($p['remaining_balance']),
        ];
    }
} elseif ($customerId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($c) {
        $isUnpaidStatement = true;
        $bal = floatval($c['balance']);
        $receipt = [
            'receipt_no'   => 'DUE-' . $c['id'],
            'date'         => date('d/m/Y'),
            'method'       => 'Unpaid / Dayn',
            'cust_name'    => $c['name'],
            'cust_phone'   => $c['phone'] ?: '—',
            'received_by'  => 'System',
            'branch'       => 'Boorama Branch',
            'prev_balance' => $bal,
            'discount'     => 0.00,
            'amount_paid'  => 0.00,
            'rem_balance'  => $bal,
        ];
    }
}

if (!$receipt) {
    header("Location: payments.php");
    exit;
}

$isPaidFull = ($receipt['rem_balance'] <= 0.001 && $receipt['amount_paid'] > 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt <?= htmlspecialchars($receipt['receipt_no']) ?> - AMAANI ELECTRONICS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .receipt-card { box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-8 flex flex-col items-center gap-4">

    <!-- Top Action Bar -->
    <div class="no-print w-full max-w-2xl bg-white rounded-xl border border-slate-200/80 px-4 py-3 flex items-center justify-between shadow-xs">
        <a href="payments.php" class="px-4 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs transition-colors">
            Back
        </a>
        <button type="button" onclick="window.print()" class="px-5 py-1.5 rounded-lg bg-[#c00000] hover:bg-red-700 text-white font-bold text-xs shadow-xs transition-colors cursor-pointer">
            Print Receipt
        </button>
    </div>

    <!-- Printable Receipt Card -->
    <div id="printable-receipt" class="receipt-card w-full max-w-2xl bg-white rounded-2xl border border-slate-200/80 shadow-lg p-7 sm:p-10 space-y-6">

        <!-- Header -->
        <div class="flex items-start justify-between pb-4 border-b-2 border-[#c00000]">
            <div class="flex items-center gap-3">
                <img src="assets/images/amaani-logo.jpg" alt="Amaani Electronics" class="h-12 w-auto object-contain">
                <div>
                    <h1 class="text-base font-black text-[#c00000] uppercase tracking-tight leading-tight">AMAANI ELECTRONICS &amp; SUPERMARKET</h1>
                    <p class="text-[11px] text-slate-500">Boorama, Somaliland</p>
                    <p class="text-[11px] text-slate-500">Tel: 634547497 / 0634515815</p>
                </div>
            </div>
            <div class="text-right">
                <h2 class="text-base font-black text-[#c00000] uppercase tracking-wider">RECEIPT</h2>
                <p class="text-[11px] text-slate-600 mt-0.5"><strong>Receipt #:</strong> <?= htmlspecialchars($receipt['receipt_no']) ?></p>
                <p class="text-[11px] text-slate-600"><strong>Date:</strong> <?= htmlspecialchars($receipt['date']) ?></p>
                <p class="text-[11px] text-slate-600"><strong>Method:</strong> <?= htmlspecialchars($receipt['method']) ?></p>
            </div>
        </div>

        <!-- Customer & Received By Box -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between text-xs">
            <div>
                <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block">CUSTOMER:</span>
                <p class="font-black text-slate-900 text-sm uppercase mt-0.5"><?= htmlspecialchars($receipt['cust_name']) ?></p>
                <p class="text-slate-500 font-mono text-[11px]"><?= htmlspecialchars($receipt['cust_phone']) ?></p>
            </div>
            <div class="text-right">
                <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block">RECEIVED BY:</span>
                <p class="font-bold text-slate-800 mt-0.5"><?= htmlspecialchars($receipt['received_by']) ?></p>
                <p class="text-slate-500 text-[11px]"><?= htmlspecialchars($receipt['branch']) ?></p>
            </div>
        </div>

        <!-- Description / Amount Table -->
        <div class="border border-slate-200 rounded-xl overflow-hidden text-xs">
            <table class="w-full text-left">
                <thead class="bg-[#c00000] text-white font-black text-[10px] uppercase tracking-wider">
                    <tr>
                        <th class="py-2.5 px-4">DESCRIPTION</th>
                        <th class="py-2.5 px-4 text-right">AMOUNT</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="py-3 px-4 font-medium text-slate-700">Previous Balance</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">$<?= number_format($receipt['prev_balance'], 2) ?></td>
                    </tr>
                    <?php if ($receipt['discount'] > 0): ?>
                    <tr>
                        <td class="py-3 px-4 font-medium text-slate-700">Discount</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-700">-$<?= number_format($receipt['discount'], 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="bg-slate-50/70">
                        <td class="py-3 px-4 font-medium text-slate-700">Payment Received</td>
                        <td class="py-3 px-4 text-right font-mono font-black text-[#c00000]">$<?= number_format($receipt['amount_paid'], 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Stamp + Summary -->
        <div class="flex items-center justify-between pt-2">
            <div>
                <?php if ($isPaidFull): ?>
                    <span class="inline-block px-5 py-1.5 rounded-lg border-2 border-emerald-600 text-emerald-700 font-black text-sm tracking-widest uppercase -rotate-6">
                        PAID
                    </span>
                <?php elseif ($receipt['rem_balance'] > 0): ?>
                    <span class="inline-block px-5 py-1.5 rounded-lg border-2 border-red-600 text-red-600 font-black text-sm tracking-widest uppercase -rotate-6">
                        DUE
                    </span>
                <?php endif; ?>
            </div>

            <div class="w-64 text-xs border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-3.5 py-2 flex justify-between bg-white">
                    <span class="text-slate-600">Previous Balance</span>
                    <span class="font-mono font-bold text-slate-800">$<?= number_format($receipt['prev_balance'], 2) ?></span>
                </div>
                <div class="px-3.5 py-2 flex justify-between bg-[#c00000] text-white font-black">
                    <span>Amount Paid</span>
                    <span class="font-mono">$<?= number_format($receipt['amount_paid'], 2) ?></span>
                </div>
                <div class="px-3.5 py-2 flex justify-between bg-white">
                    <span class="text-emerald-700 font-bold">Remaining Balance</span>
                    <span class="font-mono font-black <?= $receipt['rem_balance'] > 0 ? 'text-red-600' : 'text-emerald-600' ?>">$<?= number_format($receipt['rem_balance'], 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Signatures -->
        <div class="grid grid-cols-2 gap-12 pt-10 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">
            <div class="border-t border-slate-400 pt-2">CUSTOMER SIGNATURE</div>
            <div class="border-t border-slate-400 pt-2">AUTHORIZED SIGNATURE</div>
        </div>

        <!-- Footer -->
        <div class="pt-4 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2 text-[10px] text-slate-400">
            <span><strong class="text-[#c00000]">AMAANI ELECTRONICS</strong> &bull; Hoyga Qalabka Elektaroonigga ah &amp; Qalabka Casriga ah</span>
            <span>Tel: 634547497 / 0634515815 &bull; <strong class="text-emerald-600">ZAAD: 509900</strong> &bull; <strong class="text-amber-600">eDahab: 721118</strong></span>
        </div>
    </div>

</body>
</html>
