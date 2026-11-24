<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$deliveries = $pdo->query("SELECT * FROM deliveries ORDER BY id DESC")->fetchAll();
?>

<main class="flex-1 min-w-0 space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-100 text-[#0B2A5B] flex items-center justify-center font-bold">
                <i data-lucide="truck" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-base font-black text-slate-900">Dispatch &amp; Delivery Fleet Tracker</h2>
                <p class="text-xs text-slate-500">Live order fulfillment status, driver dispatches &amp; destination telemetry.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <?php foreach ($deliveries as $del): ?>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs space-y-2.5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black font-mono text-[#0B2A5B]"><?= htmlspecialchars($del['code']) ?></span>
                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full <?= $del['status'] === 'In Transit' ? 'bg-blue-100 text-blue-700' : ($del['status'] === 'Dispatched' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700') ?>">
                    <?= htmlspecialchars($del['status']) ?>
                </span>
            </div>

            <div>
                <h4 class="text-xs font-bold text-slate-900"><?= htmlspecialchars($del['customer']) ?></h4>
                <p class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                    <i data-lucide="map-pin" class="w-3 h-3 text-slate-400"></i>
                    <span><?= htmlspecialchars($del['address']) ?></span>
                </p>
            </div>

            <div class="text-[11px] bg-slate-50 p-2.5 rounded-xl border border-slate-100 text-slate-700 font-medium">
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Items Pack</span>
                <?= htmlspecialchars($del['items']) ?>
            </div>

            <div class="flex items-center justify-between pt-1 text-[11px] text-slate-500 border-t border-slate-100">
                <div class="flex items-center gap-1">
                    <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span><?= htmlspecialchars($del['driver']) ?></span>
                </div>
                <div class="flex items-center gap-1 font-bold text-slate-800">
                    <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>ETA: <?= htmlspecialchars($del['eta']) ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
