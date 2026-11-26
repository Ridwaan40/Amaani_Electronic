<?php
require_once __DIR__ . '/config/db.php';
$pdo = getDB();

$jobId = intval($_GET['id'] ?? 0);
if ($jobId <= 0) {
    header("Location: repairs.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM repair_jobs WHERE id = ?");
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo "Repair job ticket not found.";
    exit;
}

// Fetch spare parts
$pStmt = $pdo->prepare("SELECT * FROM repair_job_parts WHERE repair_id = ?");
$pStmt->execute([$jobId]);
$parts = $pStmt->fetchAll(PDO::FETCH_ASSOC);

$balanceDue = max(0, floatval($job['total_amount']) - floatval($job['paid_amount']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repair Ticket #<?= htmlspecialchars($job['job_number']) ?> - CAALAMI ELECTRONICS</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: white; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-6 flex flex-col items-center">
    
    <!-- Action Bar -->
    <div class="no-print w-full max-w-2xl mb-4 flex items-center justify-between">
        <a href="repairs.php" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 shadow-xs flex items-center gap-1.5">
            &larr; Back to Repair List
        </a>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-5 py-2 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-bold text-xs shadow-md shadow-red-900/30 flex items-center gap-1.5 cursor-pointer">
                <span>Print Ticket / Receipt</span>
            </button>
            <a href="create_repair.php" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs shadow-xs">
                + New Ticket
            </a>
        </div>
    </div>

    <!-- Printable Ticket Container -->
    <div id="printable-receipt" class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl p-6 sm:p-8 space-y-6">
        
        <!-- Header & Logo -->
        <div class="flex items-start justify-between border-b pb-4 border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-[#c00000] text-white flex items-center justify-center font-black text-lg shadow-md">
                    CE
                </div>
                <div>
                    <h1 class="text-base font-black text-[#c00000] uppercase tracking-wider">CAALAMI ELECTRONICS</h1>
                    <p class="text-xs text-slate-500 font-semibold uppercase">Supermarket &amp; Electronics &bull; Repair Center</p>
                    <p class="text-[11px] text-slate-400">Boorama Branch &bull; Tel: +252 63 4531454</p>
                </div>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 rounded-full bg-red-100 text-[#c00000] font-black text-xs uppercase tracking-wider">
                    Repair Ticket
                </span>
                <p class="text-sm font-black text-slate-900 mt-1 font-mono"><?= htmlspecialchars($job['job_number']) ?></p>
                <p class="text-[11px] text-slate-400">Date: <?= date('d M Y, h:i A', strtotime($job['created_at'])) ?></p>
            </div>
        </div>

        <!-- Customer & Device Details Grid -->
        <div class="grid grid-cols-2 gap-4 text-xs">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1">
                <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block">Customer Information</span>
                <p class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($job['customer_name']) ?></p>
                <p class="text-slate-600">Phone: <span class="font-medium"><?= htmlspecialchars($job['customer_phone'] ?: 'N/A') ?></span></p>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1">
                <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block">Device Details</span>
                <p class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($job['device_model']) ?> <?= !empty($job['brand']) ? '(' . htmlspecialchars($job['brand']) . ')' : '' ?></p>
                <p class="text-slate-600">IMEI/SN: <span class="font-mono"><?= htmlspecialchars($job['serial_imei'] ?: 'N/A') ?></span></p>
                <?php if (!empty($job['passcode'])): ?>
                    <p class="text-slate-600">Screen Lock/PIN: <span class="font-mono font-bold text-red-600"><?= htmlspecialchars($job['passcode']) ?></span></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Diagnostics & Problem Details -->
        <div class="p-3.5 rounded-xl bg-amber-50/60 border border-amber-200/80 text-xs space-y-1.5">
            <span class="text-[10px] font-black uppercase text-amber-800 tracking-wider block">Reported Problem &amp; Diagnosis</span>
            <p class="text-slate-800 font-medium"><?= nl2br(htmlspecialchars($job['reported_problem'])) ?></p>
            <?php if (!empty($job['diagnostic_notes'])): ?>
                <div class="pt-1 border-t border-amber-200/60 text-[11px] text-amber-900">
                    <strong>Inspection Notes:</strong> <?= nl2br(htmlspecialchars($job['diagnostic_notes'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Assigned Technician & Timeline -->
        <div class="grid grid-cols-3 gap-3 text-xs border border-slate-200 rounded-xl p-3 bg-white">
            <div>
                <span class="text-[10px] font-bold text-slate-400 block uppercase">Assigned Tech</span>
                <span class="font-bold text-slate-800"><?= htmlspecialchars($job['technician_name'] ?: 'Unassigned') ?></span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 block uppercase">Intake Date</span>
                <span class="font-bold text-slate-800"><?= date('d M Y', strtotime($job['intake_date'])) ?></span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 block uppercase">Status</span>
                <span class="font-black text-[#c00000]"><?= htmlspecialchars($job['status']) ?></span>
            </div>
        </div>

        <!-- Charges Breakdown Table -->
        <div class="border border-slate-200 rounded-xl overflow-hidden text-xs">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="py-2.5 px-3">Description</th>
                        <th class="py-2.5 px-3 w-16 text-center">Qty</th>
                        <th class="py-2.5 px-3 w-24 text-right">Rate</th>
                        <th class="py-2.5 px-3 w-24 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($job['service_name']) || $job['service_cost'] > 0): ?>
                        <tr>
                            <td class="py-2.5 px-3 font-bold text-slate-800">
                                Service &amp; Labor: <?= htmlspecialchars($job['service_name'] ?: 'General Repair') ?>
                            </td>
                            <td class="py-2.5 px-3 text-center">1</td>
                            <td class="py-2.5 px-3 text-right text-slate-600">$<?= number_format($job['service_cost'], 2) ?></td>
                            <td class="py-2.5 px-3 text-right font-bold text-slate-800">$<?= number_format($job['service_cost'], 2) ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($parts as $p): ?>
                        <tr>
                            <td class="py-2.5 px-3 text-slate-700">
                                Spare Part: <?= htmlspecialchars($p['part_name']) ?>
                            </td>
                            <td class="py-2.5 px-3 text-center"><?= $p['qty'] ?></td>
                            <td class="py-2.5 px-3 text-right text-slate-600">$<?= number_format($p['price'], 2) ?></td>
                            <td class="py-2.5 px-3 text-right font-bold text-slate-800">$<?= number_format($p['total'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals & Balances -->
        <div class="flex justify-end text-xs">
            <div class="w-64 space-y-1.5">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal:</span>
                    <span class="font-bold text-slate-800">$<?= number_format($job['service_cost'] + $job['parts_cost'], 2) ?></span>
                </div>
                <?php if ($job['discount'] > 0): ?>
                    <div class="flex justify-between text-emerald-600 font-bold">
                        <span>Discount:</span>
                        <span>-$<?= number_format($job['discount'], 2) ?></span>
                    </div>
                <?php endif; ?>
                <div class="flex justify-between text-sm font-black border-t pt-1.5 border-slate-200">
                    <span>Grand Total:</span>
                    <span class="text-[#c00000]">$<?= number_format($job['total_amount'], 2) ?></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Amount Paid:</span>
                    <span class="font-bold text-emerald-600">$<?= number_format($job['paid_amount'], 2) ?></span>
                </div>
                <div class="flex justify-between font-black text-xs border-t pt-1 border-slate-200">
                    <span>Balance Due:</span>
                    <span class="<?= $balanceDue > 0 ? 'text-red-600' : 'text-emerald-600' ?>">
                        <?= $balanceDue > 0 ? '$' . number_format($balanceDue, 2) : 'PAID IN FULL' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Terms & Signature -->
        <div class="pt-4 border-t border-slate-200 text-[10px] text-slate-400 space-y-4">
            <p>
                <strong>Terms &amp; Conditions:</strong> Fadlan hayso ticket-kan marka aad alaabta qaadanayso. Wax dammaanad ah lagama bixinayo xogta (data) ku jirta qalabka, fadlan samee backup. Qalabkii aan lagala bixin 30 maalmood gudahood mas'uul kama noqon doonto shirkaddu.
            </p>
            <div class="flex items-center justify-between pt-6">
                <div class="text-center w-40 border-t border-slate-300 pt-1">
                    Customer Signature
                </div>
                <div class="text-center w-40 border-t border-slate-300 pt-1">
                    Authorized Staff / Tech
                </div>
            </div>
        </div>

    </div>

</body>
</html>
