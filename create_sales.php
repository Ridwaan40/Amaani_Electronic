<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Fetch all products (stock >= 0) — show everything so purchased items appear immediately
$products = $pdo->query("SELECT * FROM products ORDER BY category ASC, name ASC")->fetchAll();

// Fetch customers
$customers = $pdo->query("SELECT * FROM customers ORDER BY id ASC")->fetchAll();

// Fetch branches
$branches = $pdo->query("SELECT * FROM branches ORDER BY id ASC")->fetchAll();
?>

<!-- POS Terminal Container -->
<main class="flex-1 flex flex-col min-w-0 h-full">
    <!-- Top Bar with Editable Date & POS Status -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs mb-3 px-4 py-2.5 flex items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <i data-lucide="shopping-cart" class="w-5 h-5 text-[#c00000]"></i>
            <span class="font-black text-sm text-slate-900 tracking-tight">Create Sales</span>
            <span class="text-slate-300">|</span>
            <span class="text-xs text-slate-500 font-medium">POS Terminal · Register #01</span>
        </div>
        
        <!-- Editable Date in Top Bar requested by user -->
        <div class="flex items-center gap-3 text-xs text-slate-500 font-medium">
            <label class="flex items-center gap-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-xl transition-colors cursor-pointer" title="Click to change sale date">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-[#c00000]"></i>
                <input type="date" id="top-sale-date" value="<?= date('Y-m-d') ?>" onchange="syncDate(this.value)" class="bg-transparent text-xs text-slate-800 font-bold outline-none cursor-pointer">
            </label>
            <span class="flex items-center gap-1 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-xl text-slate-600 font-semibold">
                <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                <span id="pos-live-clock"><?= date('h:i A') ?></span>
            </span>
        </div>
    </div>

    <!-- Main Two-Column POS Layout -->
    <div class="flex gap-4 flex-1 min-h-0" style="height: calc(100vh - 160px);">
        
        <!-- ── LEFT PANEL: Product Browser ─────────────────────────── -->
        <div class="flex-1 flex flex-col min-w-0 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <!-- Search & Category Filters -->
            <div class="p-3 border-b border-slate-100 space-y-2.5">
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-2.5 text-slate-400"></i>
                    <input type="text" id="pos-search" oninput="filterProducts()" placeholder="Search products by name, category, or specs..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-[#c00000] outline-none">
                </div>

                <!-- Category Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs" id="category-pills">
                    <button type="button" onclick="selectCategory('All')" class="cat-pill px-3 py-1 rounded-xl font-bold transition-all bg-[#0B2A5B] text-white">All</button>
                    <button type="button" onclick="selectCategory('Mobile')" class="cat-pill px-3 py-1 rounded-xl font-bold transition-all bg-slate-100 text-slate-600 hover:bg-slate-200">Mobiles</button>
                    <button type="button" onclick="selectCategory('Laptop')" class="cat-pill px-3 py-1 rounded-xl font-bold transition-all bg-slate-100 text-slate-600 hover:bg-slate-200">Laptops</button>
                    <button type="button" onclick="selectCategory('Accessories')" class="cat-pill px-3 py-1 rounded-xl font-bold transition-all bg-slate-100 text-slate-600 hover:bg-slate-200">Accessories</button>
                    <button type="button" onclick="selectCategory('Networking')" class="cat-pill px-3 py-1 rounded-xl font-bold transition-all bg-slate-100 text-slate-600 hover:bg-slate-200">Networking</button>
                    <button type="button" onclick="selectCategory('TV & Display')" class="cat-pill px-3 py-1 rounded-xl font-bold transition-all bg-slate-100 text-slate-600 hover:bg-slate-200">TV &amp; Screen</button>
                </div>
            </div>

            <!-- Product Cards Grid -->
            <div class="flex-1 overflow-y-auto p-3">
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2.5" id="products-grid">
                    <?php foreach ($products as $prod): ?>
                    <?php
                        $noPriceProd = floatval($prod['retail_price']) == 0;
                        $cardBorder = $noPriceProd ? 'border-amber-300 bg-amber-50/40 hover:border-amber-500' : 'border-slate-200/80 bg-slate-50/50 hover:border-[#c00000]';
                        $onClickFn = $noPriceProd
                            ? "addToCartWithPrompt(" . htmlspecialchars(json_encode([
                                'id' => $prod['id'],
                                'name' => $prod['name'],
                                'category' => $prod['category'],
                                'unit' => $prod['unit'] ?? 'Pcs',
                                'retailPrice' => 0,
                                'wholesalePrice' => floatval($prod['wholesale_price']),
                                'stock' => intval($prod['stock_qty']),
                                'lastPrice' => floatval($prod['last_price'])
                              ])) . ")"
                            : "addToCart(" . htmlspecialchars(json_encode([
                                'id' => $prod['id'],
                                'name' => $prod['name'],
                                'category' => $prod['category'],
                                'unit' => $prod['unit'] ?? 'Pcs',
                                'retailPrice' => floatval($prod['retail_price']),
                                'wholesalePrice' => floatval($prod['wholesale_price']),
                                'stock' => intval($prod['stock_qty'])
                              ])) . ")";
                    ?>
                    <div class="product-card p-3 rounded-2xl border <?= $cardBorder ?> hover:bg-white hover:shadow-md transition-all flex flex-col justify-between group cursor-pointer"
                         data-id="<?= $prod['id'] ?>"
                         data-name="<?= htmlspecialchars($prod['name']) ?>"
                         data-category="<?= htmlspecialchars($prod['category']) ?>"
                         data-retail="<?= $prod['retail_price'] ?>"
                         data-wholesale="<?= $prod['wholesale_price'] ?>"
                         onclick="<?= $onClickFn ?>">
                        
                        <div>
                            <div class="flex items-start justify-between gap-1 mb-2">
                                <span class="text-2xl"><?= $prod['image_icon'] ?: '📦' ?></span>
                                <div class="flex flex-col items-end gap-0.5">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full <?= $prod['stock_qty'] <= 3 ? 'bg-amber-100 text-amber-700' : 'bg-slate-200/80 text-slate-600' ?>">
                                        <?= $prod['stock_qty'] ?> in stock
                                    </span>
                                    <?php if ($noPriceProd): ?>
                                    <span class="text-[9px] font-black px-1.5 py-0.5 rounded-full bg-orange-100 text-orange-600">⚠ Set Price</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <h4 class="text-xs font-bold text-slate-900 group-hover:text-[#c00000] transition-colors line-clamp-2 leading-tight">
                                <?= htmlspecialchars($prod['name']) ?>
                            </h4>
                        </div>

                        <div class="mt-3 pt-2 border-t border-slate-200/60 flex items-center justify-between">
                            <div>
                                <span class="text-[9px] text-slate-400 block font-semibold"><?= $noPriceProd ? 'Cost' : 'Retail' ?></span>
                                <span class="text-xs font-black <?= $noPriceProd ? 'text-orange-600' : 'text-slate-900' ?> prod-price-display">
                                    <?= $noPriceProd ? ('$'.number_format($prod['last_price'],2).' cost') : ('$'.number_format($prod['retail_price'],2)) ?>
                                </span>
                            </div>
                            <button type="button" class="w-7 h-7 rounded-xl <?= $noPriceProd ? 'bg-orange-200 text-orange-700 group-hover:bg-orange-500 group-hover:text-white' : 'bg-slate-200 group-hover:bg-[#c00000] text-slate-700 group-hover:text-white' ?> flex items-center justify-center transition-all shadow-xs">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ── RIGHT PANEL: Checkout / Cart (Matching Screenshot) ───────── -->
        <div class="w-[450px] xl:w-[490px] shrink-0 flex flex-col bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <!-- Header -->
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="receipt" class="w-4 h-4 text-[#c00000]"></i>
                    <span class="text-sm font-black text-slate-900">Current Sale</span>
                </div>
                <div class="flex items-center gap-2">
                    <span id="cart-items-count-badge" class="bg-red-50 text-[#c00000] text-[10px] font-black px-2 py-0.5 rounded-full">
                        0 items
                    </span>
                </div>
            </div>

            <!-- Scrollable Checkout Form -->
            <div class="flex-1 overflow-y-auto p-3.5 space-y-3">
                <!-- Customer & Branch Row (Matching Screenshot) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    <!-- Customer Selection with Walk-In Badge -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-[11px] font-bold text-slate-700">Customer</label>
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-red-600 bg-red-50 border border-red-200 px-2 py-0.5 rounded-full cursor-pointer hover:bg-red-100" onclick="selectWalkInCustomer()">
                                <i data-lucide="user" class="w-3 h-3"></i> Walk-In-Customer
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            <select id="pos-customer" onchange="onCustomerChange(this)" class="flex-1 px-2.5 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-800 outline-none focus:border-[#c00000]">
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= htmlspecialchars($c['name']) ?>" data-phone="<?= htmlspecialchars($c['phone']) ?>">
                                        <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <a href="customers.php" class="w-8 h-8 rounded-xl bg-red-600 hover:bg-red-700 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-xs transition-colors" title="Add Customer">
                                +
                            </a>
                        </div>
                    </div>

                    <!-- Branch -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Branch</label>
                        <select id="pos-branch" class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-800 outline-none focus:border-[#c00000]">
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= htmlspecialchars($b['name']) ?>" <?= $b['name'] === $currentBranch ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Hidden / Sync Sale Date -->
                <input type="hidden" id="pos-form-sale-date" value="<?= date('Y-m-d') ?>">

                <!-- Sale Pricing Type Buttons (Matching Screenshot) -->
                <div>
                    <label class="text-[11px] font-bold text-slate-700 flex items-center gap-1.5 mb-1.5">
                        <i data-lucide="tag" class="w-3.5 h-3.5 text-[#c00000]"></i>
                        <span>Sale Pricing Type</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <button type="button" id="btn-retail" onclick="setSaleType('retail')" class="py-2 px-3 rounded-xl font-black text-xs bg-[#c00000] text-white shadow-xs cursor-pointer flex items-center justify-center gap-1.5 transition-all">
                            <i data-lucide="store" class="w-4 h-4"></i>
                            <span>Retail Sale</span>
                        </button>
                        <button type="button" id="btn-wholesale" onclick="setSaleType('wholesale')" class="py-2 px-3 rounded-xl font-bold text-xs bg-slate-100 text-slate-600 hover:bg-slate-200 cursor-pointer flex items-center justify-center gap-1.5 transition-all">
                            <i data-lucide="boxes" class="w-4 h-4"></i>
                            <span>Whole Sale</span>
                        </button>
                    </div>
                </div>

                <!-- Cart Items Table (Matching Screenshot Header) -->
                <div class="rounded-xl border border-slate-200/90 overflow-hidden">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-[#c00000] text-white uppercase font-black text-[10px]">
                            <tr>
                                <th class="py-2.5 px-2.5 text-center w-8">#</th>
                                <th class="py-2.5 px-3">Product</th>
                                <th class="py-2.5 px-2 text-center w-16">Qty</th>
                                <th class="py-2.5 px-3 text-right w-24" id="cart-header-price">Retail Price($)</th>
                                <th class="py-2.5 px-2 text-center w-10">Action</th>
                            </tr>
                        </thead>
                        <tbody id="cart-table-body" class="divide-y divide-slate-100 font-medium">
                            <!-- Injected dynamically -->
                        </tbody>
                    </table>

                    <!-- Empty state -->
                    <div id="cart-empty" class="p-6 text-center text-slate-400 bg-slate-50/50">
                        <i data-lucide="shopping-bag" class="w-7 h-7 mx-auto text-slate-300 mb-1"></i>
                        <span class="text-xs font-bold block text-slate-600">Cart is empty</span>
                        <span class="text-[10px] text-slate-400">Click products on the left to add</span>
                    </div>
                </div>

                <!-- ── BOTTOM SPLIT: Payment & Return Date (Left) | Summary (Right) ── -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 items-start">
                    
                    <!-- LEFT COLUMN: Payment Method + Return Date + Notes -->
                    <div class="space-y-2.5">
                        <!-- Payment Method -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Payment Method</label>
                            <select id="pos-method" class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 outline-none focus:border-[#c00000]">
                                <option value="Cash On Hand">Cash On Hand</option>
                                <option value="ZAAD (Telesom)">ZAAD (Telesom Mobile Money)</option>
                                <option value="e-Dahab (Somtel)">e-Dahab (Somtel Mobile Money)</option>
                                <option value="EVC Plus (Hormuud)">EVC Plus (Hormuud)</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Credit / Debt">Credit / Customer Debt</option>
                            </select>
                        </div>

                        <!-- ── RETURN DATE (CUSTOMER DUE) CARD (Exact match to Screenshot) ── -->
                        <div id="return-date-card" class="border border-orange-200 bg-orange-50/40 rounded-2xl p-3 space-y-2 text-xs">
                            <div class="flex items-center justify-between gap-1">
                                <label class="font-black text-[#991b1b] flex items-center gap-1.5 text-[11px]">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-[#c00000]"></i>
                                    <span>Return Date<br><span class="text-[10px] font-semibold text-slate-500">(Customer Due)</span></span>
                                </label>
                                <span id="return-date-remaining-badge" class="bg-red-100 text-red-700 text-[10px] font-black px-2 py-0.5 rounded-lg border border-red-200 shrink-0">
                                    Remaining: $0.00
                                </span>
                            </div>

                            <!-- Date Picker -->
                            <input type="date" id="pos-return-date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" onchange="updateSummaryReturnDate()" class="w-full px-3 py-1.5 rounded-xl border border-red-300 bg-white font-black text-xs text-slate-800 outline-none focus:ring-1 focus:ring-red-500 cursor-pointer">

                            <!-- Quick Jump Date Buttons -->
                            <div class="flex flex-wrap items-center gap-1 pt-0.5">
                                <button type="button" onclick="setReturnDays(3)" class="px-2 py-0.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-[10px] font-bold text-slate-700 transition-colors shadow-2xs cursor-pointer">+3 Days</button>
                                <button type="button" onclick="setReturnDays(7)" class="px-2 py-0.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-[10px] font-bold text-slate-700 transition-colors shadow-2xs cursor-pointer">+7 Days</button>
                                <button type="button" onclick="setReturnDays(15)" class="px-2 py-0.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-[10px] font-bold text-slate-700 transition-colors shadow-2xs cursor-pointer">+15 Days</button>
                                <button type="button" onclick="setReturnDays(30)" class="px-2 py-0.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-[10px] font-bold text-slate-700 transition-colors shadow-2xs cursor-pointer">+1 Month</button>
                                <button type="button" onclick="clearReturnDate()" class="px-2 py-0.5 rounded-lg border border-dashed border-slate-300 hover:bg-slate-100 text-[10px] font-bold text-slate-500 transition-colors cursor-pointer">Clear</button>
                            </div>

                            <!-- Help Info Text -->
                            <p class="text-[10px] text-slate-500 font-medium flex items-start gap-1 pt-1 leading-tight">
                                <i data-lucide="info" class="w-3.5 h-3.5 text-red-600 shrink-0 mt-0.5"></i>
                                <span>Specify agreed return date to collect the remaining balance.</span>
                            </p>
                        </div>

                        <!-- Notes (Optional) -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Notes (Optional)</label>
                            <textarea id="pos-notes" rows="2" placeholder="Add a note for this sale..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:border-[#c00000] outline-none"></textarea>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: Financial Summary Breakdown (Matching Screenshot) -->
                    <div class="bg-white rounded-2xl border border-slate-200/90 p-3.5 space-y-2 text-xs shadow-xs">
                        <!-- Subtotal -->
                        <div class="flex justify-between items-center text-slate-600 font-medium">
                            <span>Subtotal</span>
                            <span id="pos-subtotal" class="font-bold text-slate-900">$ 0.00</span>
                        </div>

                        <!-- Discount -->
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-slate-600 font-medium">Discount</span>
                            <div class="flex items-center gap-1 font-mono">
                                <span class="text-slate-400 font-bold">$</span>
                                <input type="number" id="pos-discount" value="0.00" min="0" step="0.5" oninput="calculateTotals()" class="w-20 text-right px-2 py-0.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-800 outline-none focus:border-[#c00000]">
                            </div>
                        </div>

                        <!-- Net Total -->
                        <div class="flex items-center justify-between pt-1">
                            <span class="font-extrabold text-slate-900 text-xs uppercase">Net Total</span>
                            <span id="pos-net-total" class="text-sm font-black text-[#c00000] font-mono">$ 0.00</span>
                        </div>

                        <!-- Paid -->
                        <div class="flex items-center justify-between gap-1 pt-1 whitespace-nowrap">
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-slate-700 text-xs">Paid</span>
                                <button type="button" onclick="setFullPay()" class="text-[9px] font-black bg-emerald-100 hover:bg-emerald-200 text-emerald-800 px-1.5 py-0.2 rounded cursor-pointer transition-colors" title="Bixi Dhammaan (Full Payment)">Full</button>
                            </div>
                            <div class="flex items-center gap-1 font-mono">
                                <span class="text-slate-400 font-bold">$</span>
                                <input type="number" id="pos-paid" value="0.00" min="0" step="0.5" oninput="userEditedPaid = true; calculateTotals()" class="w-20 text-right px-2 py-0.5 rounded-lg border border-red-300 text-xs font-black text-slate-900 outline-none focus:ring-1 focus:ring-red-500">
                            </div>
                        </div>

                        <!-- Balance -->
                        <div class="flex items-center justify-between pt-1">
                            <span class="font-extrabold text-slate-900">Balance</span>
                            <span id="pos-balance" class="font-black text-slate-900 text-xs font-mono">$ 0.00</span>
                        </div>

                        <!-- Red Dashed Divider -->
                        <div class="border-t border-dashed border-red-200 my-1"></div>

                        <!-- Return Date row (Matching Screenshot) -->
                        <div class="flex items-center justify-between pt-0.5 text-xs">
                            <span class="font-bold text-slate-700 flex items-center gap-1">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-red-600"></i>
                                <span>Return Date</span>
                            </span>
                            <span id="pos-summary-return-date" class="font-bold text-red-600 font-mono">29/11/2026</span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Save Sale & Save & Print Buttons -->
            <div class="p-3 border-t border-slate-100 bg-white grid grid-cols-2 gap-2">
                <button type="button" id="btn-save-only" onclick="executeSale(false)" disabled class="py-2.5 px-3 rounded-xl text-white font-black text-xs flex items-center justify-center gap-1.5 cursor-pointer transition-all disabled:opacity-40 disabled:cursor-not-allowed bg-emerald-600 hover:bg-emerald-700 shadow-md">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <span>Iib Samee (Save)</span>
                </button>
                <button type="button" id="btn-save-print" onclick="executeSale(true)" disabled class="py-2.5 px-3 rounded-xl text-white font-black text-xs flex items-center justify-center gap-1.5 cursor-pointer transition-all disabled:opacity-40 disabled:cursor-not-allowed bg-[#c00000] hover:bg-red-700 shadow-md">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Save &amp; Print</span>
                </button>
            </div>
        </div>
    </div>
</main>

<!-- ── SALE SUCCESS CONFIRMATION MODAL ── -->
<div id="sale-success-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 text-center shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
        <!-- Success Check Icon -->
        <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center mb-3 shadow-inner">
            <i data-lucide="check" class="w-8 h-8 stroke-[3]"></i>
        </div>

        <h3 class="text-lg font-black text-slate-900">Iibka Si Guul Leh Ayaa Loo Sameeyay!</h3>
        <p class="text-xs text-slate-500 mt-0.5">Xogta iibka waxaa si toos ah loogu kaydiyay database-ka.</p>

        <!-- Receipt Card Breakdown -->
        <div class="bg-slate-50 rounded-2xl p-4 my-4 border border-slate-200/80 text-left space-y-2 text-xs">
            <div class="flex justify-between items-center pb-2 border-b border-slate-200">
                <span class="text-slate-500 font-semibold">Invoice #</span>
                <span id="succ-inv-num" class="font-black text-slate-900 font-mono text-sm">#0</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500 font-semibold">Customer</span>
                <span id="succ-customer" class="font-bold text-slate-800">Walk-In Customer</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500 font-semibold">Total Sale</span>
                <span id="succ-total" class="font-black text-slate-900 font-mono text-sm">$0.00</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500 font-semibold">Paid Amount</span>
                <span id="succ-paid" class="font-bold text-emerald-600 font-mono">$0.00</span>
            </div>
            <div class="flex justify-between items-center pt-2 border-t border-slate-200">
                <span class="text-slate-500 font-semibold">Balance / Due</span>
                <span id="succ-balance" class="font-black text-[#c00000] font-mono text-sm">$0.00</span>
            </div>
            <div id="succ-return-date-row" class="flex justify-between items-center text-red-600 font-bold hidden pt-1">
                <span>📅 Return Date Due</span>
                <span id="succ-return-date" class="font-mono">--</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-3 gap-2">
            <button type="button" onclick="printSuccessInvoice()" class="py-2.5 px-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center justify-center gap-1.5 cursor-pointer transition-all shadow-sm">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Print</span>
            </button>
            <a href="invoices.php" class="py-2.5 px-2 rounded-xl border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center gap-1.5 transition-all">
                <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Invoices</span>
            </a>
            <button type="button" onclick="closeSuccessModal()" class="py-2.5 px-2 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 cursor-pointer transition-all shadow-sm">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>New Sale</span>
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     PRINTABLE INVOICE TEMPLATE (Exact match to Sales / Amaani Receipt)
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="invoice-print-container" class="hidden">
    <div id="invoice-sheet" class="p-8 max-w-2xl mx-auto bg-white text-slate-900 font-sans space-y-6">
        <!-- Header -->
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3">
                <div class="w-12 h-12 rounded-xl bg-[#c00000] text-white flex items-center justify-center font-black text-xl">
                    CE
                </div>
                <div>
                    <h1 class="text-base font-black text-[#c00000] uppercase tracking-wide">CAALAMI ELECTRONICS</h1>
                    <p class="text-xs text-slate-600 font-medium">Hoyga Qalabka Elektaroonigga ah &amp; Qalabka Casriga ah</p>
                    <p class="text-[11px] text-slate-400">Sisibta, Borama, Somaliland</p>
                    <p class="text-[11px] text-slate-400">Tel: 634547497</p>
                </div>
            </div>

            <div class="text-right">
                <h2 class="text-2xl font-black text-[#c00000] tracking-widest uppercase">INVOICE</h2>
                <div class="text-xs text-slate-600 space-y-0.5 mt-1 font-medium">
                    <div><span class="font-bold">Invoice #:</span> <span id="print-inv-num">1</span></div>
                    <div><span class="font-bold">Date:</span> <span id="print-inv-date">10/10/2026</span></div>
                    <div><span class="font-bold">Payment:</span> <span id="print-inv-method">Cash</span></div>
                    <div id="print-inv-retdate-row"><span class="font-bold text-red-600">Return Date:</span> <span id="print-inv-retdate" class="font-black text-red-600">—</span></div>
                </div>
            </div>
        </div>

        <!-- Red Horizontal Divider -->
        <div class="h-0.5 bg-[#c00000] w-full"></div>

        <!-- Invoice To & Cashier / Staff Box -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 flex items-start justify-between text-xs">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">INVOICE TO:</span>
                <span id="print-inv-customer" class="font-black text-slate-900 text-sm uppercase block mt-0.5">DAAHIR CADUUR</span>
                <span id="print-inv-phone" class="text-slate-500 text-[11px] block mt-0.5"></span>
            </div>
            <div class="text-right">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">CASHIER / STAFF:</span>
                <span id="print-inv-cashier" class="font-black text-slate-900 text-xs block mt-0.5"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Ridwaan') ?></span>
                <span id="print-inv-branch" class="text-slate-500 text-[11px] block mt-0.5">Boorama Branch</span>
            </div>
        </div>

        <!-- Items Table -->
        <table class="w-full text-left text-xs">
            <thead class="bg-[#c00000] text-white uppercase font-black text-[10px]">
                <tr>
                    <th class="py-2 px-3 w-10 text-center">#</th>
                    <th class="py-2 px-4">ITEM DESCRIPTION</th>
                    <th class="py-2 px-3 text-center">QTY</th>
                    <th class="py-2 px-4 text-right">PRICE</th>
                    <th class="py-2 px-4 text-right">TOTAL</th>
                </tr>
            </thead>
            <tbody id="print-inv-items-body" class="divide-y divide-slate-100 font-medium">
                <!-- Injected via JS -->
            </tbody>
        </table>

        <!-- Totals & Stamp -->
        <div class="flex items-start justify-between pt-2">
            <div>
                <!-- Stamp -->
                <div id="print-stamp-box">
                    <div class="inline-flex items-center gap-1.5 border-2 border-dashed border-emerald-500 text-emerald-600 bg-emerald-50/50 rounded-xl px-4 py-2 font-black text-sm uppercase tracking-widest transform -rotate-3">
                        ✓ PAID
                    </div>
                </div>

                <div id="print-inv-note" class="text-xs text-slate-500 mt-4 max-w-xs">
                    Note: Retail Sale
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="w-56 space-y-1.5 text-xs">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal</span>
                    <span id="print-subtotal" class="font-bold text-slate-900">$0.00</span>
                </div>
                <div class="flex justify-between items-center bg-[#c00000] text-white px-3 py-1.5 rounded-lg font-black text-sm">
                    <span>Total</span>
                    <span id="print-total">$0.00</span>
                </div>
                <div class="flex justify-between text-emerald-600 font-bold">
                    <span>Paid</span>
                    <span id="print-paid">$0.00</span>
                </div>
                <div class="flex justify-between text-slate-800 font-bold">
                    <span>Balance</span>
                    <span id="print-balance">$0.00</span>
                </div>
                <div id="print-retdate-summary-row" class="flex justify-between text-red-600 font-black pt-1 border-t border-slate-100">
                    <span>Return Date</span>
                    <span id="print-retdate-summary">—</span>
                </div>
            </div>
        </div>

        <!-- Signatures -->
        <div class="flex items-center justify-between pt-12 border-t border-slate-200">
            <div class="text-center w-48">
                <div class="border-t border-slate-400 pt-1 text-[10px] uppercase font-bold text-slate-500">
                    CUSTOMER SIGNATURE
                </div>
            </div>
            <div class="text-center w-48">
                <div class="border-t border-slate-400 pt-1 text-[10px] uppercase font-bold text-slate-500">
                    AUTHORIZED SIGNATURE
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-t-2 border-[#c00000] pt-3 text-center text-[10px] text-slate-500 space-y-0.5">
            <div>
                <span class="font-black text-[#c00000]">CAALAMI ELECTRONICS</span> • Hoyga Qalabka Elektaroonigga ah &amp; Qalabka Casriga ah
            </div>
            <div>
                Tel: 634547497 / 0634515815 &nbsp;|&nbsp; 
                <span class="font-bold text-emerald-600">ZAAD: 509900</span> &nbsp;|&nbsp; 
                <span class="font-bold text-amber-600">eDahab: 721118</span>
            </div>
            <div class="text-slate-400">
                Mahadsanid • Powered By Taam Solutions
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #invoice-print-container, #invoice-print-container * {
        visibility: visible !important;
    }
    #invoice-print-container {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        width: 100vw !important;
        margin: 0 !important;
        padding: 20px !important;
        background: white !important;
        display: block !important;
        z-index: 999999 !important;
    }
}
</style>

<script>
let cart = [];
let pricingType = 'retail'; // 'retail' or 'wholesale'

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function syncDate(val) {
    document.getElementById('top-sale-date').value = val;
    document.getElementById('pos-form-sale-date').value = val;
}

function setTodayDate() {
    const today = new Date().toISOString().split('T')[0];
    syncDate(today);
}

function setSaleType(type) {
    pricingType = type;
    const btnR = document.getElementById('btn-retail');
    const btnW = document.getElementById('btn-wholesale');
    const headerPrice = document.getElementById('cart-header-price');
    if (type === 'retail') {
        btnR.className = 'py-2 px-3 rounded-xl font-black text-xs bg-[#c00000] text-white shadow-xs cursor-pointer flex items-center justify-center gap-1.5 transition-all';
        btnW.className = 'py-2 px-3 rounded-xl font-bold text-xs bg-slate-100 text-slate-600 hover:bg-slate-200 cursor-pointer flex items-center justify-center gap-1.5 transition-all';
        if (headerPrice) headerPrice.textContent = 'Retail Price($)';
    } else {
        btnW.className = 'py-2 px-3 rounded-xl font-black text-xs bg-slate-800 text-white shadow-xs cursor-pointer flex items-center justify-center gap-1.5 transition-all';
        btnR.className = 'py-2 px-3 rounded-xl font-bold text-xs bg-slate-100 text-slate-600 hover:bg-slate-200 cursor-pointer flex items-center justify-center gap-1.5 transition-all';
        if (headerPrice) headerPrice.textContent = 'Wholesale Price($)';
    }

    // Update prices in cart to reflect pricing type
    cart.forEach(item => {
        item.unitPrice = (pricingType === 'wholesale') ? (item.wholesalePrice || item.retailPrice) : item.retailPrice;
    });
    renderCart();
}

function selectCategory(cat) {
    const pills = document.querySelectorAll('.cat-pill');
    pills.forEach(p => {
        if (p.textContent === cat) {
            p.className = 'cat-pill px-3 py-1 rounded-xl font-bold transition-all bg-[#0B2A5B] text-white';
        } else {
            p.className = 'cat-pill px-3 py-1 rounded-xl font-bold transition-all bg-slate-100 text-slate-600 hover:bg-slate-200';
        }
    });

    const cards = document.querySelectorAll('.product-card');
    cards.forEach(card => {
        const c = card.getAttribute('data-category');
        if (cat === 'All' || c === cat) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

function filterProducts() {
    const q = document.getElementById('pos-search').value.toLowerCase();
    const cards = document.querySelectorAll('.product-card');
    cards.forEach(card => {
        const name = card.getAttribute('data-name').toLowerCase();
        const cat = card.getAttribute('data-category').toLowerCase();
        if (name.includes(q) || cat.includes(q)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

function selectWalkInCustomer() {
    const select = document.getElementById('pos-customer');
    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].text.toLowerCase().includes('walk-in')) {
            select.selectedIndex = i;
            break;
        }
    }
}

function addToCart(prod) {
    if (prod.stock <= 0) {
        showToast('Out of Stock', prod.name + ' has no stock!', 'error');
        return;
    }
    const existing = cart.find(x => x.id === prod.id);
    const unitPrice = (pricingType === 'wholesale') ? (prod.wholesalePrice || prod.retailPrice) : prod.retailPrice;

    if (existing) {
        existing.qty += 1;
    } else {
        cart.push({
            id: prod.id,
            name: prod.name,
            unit: prod.unit || 'Pcs',
            retailPrice: prod.retailPrice,
            wholesalePrice: prod.wholesalePrice || prod.retailPrice,
            unitPrice: unitPrice,
            qty: 1
        });
    }
    renderCart();
}

// For products with retail_price = 0: prompt user to enter selling price now
function addToCartWithPrompt(prod) {
    if (prod.stock <= 0) {
        showToast('Out of Stock', prod.name + ' has no stock!', 'error');
        return;
    }
    const suggested = prod.lastPrice > 0 ? prod.lastPrice : '';
    const enteredStr = prompt(
        '⚠ ' + prod.name + '\n\nSelling price la\'aanteed (retail_price = $0).\n\nGeli qiimaha aad ku iibinayso ($):',
        suggested
    );
    if (enteredStr === null) return; // cancelled
    const entered = parseFloat(enteredStr);
    if (isNaN(entered) || entered < 0) {
        alert('Qiimaha saxda ah geli.'); return;
    }
    prod.retailPrice = entered;
    prod.wholesalePrice = prod.wholesalePrice || entered;
    addToCart(prod);
}

function setCartQty(id, qty) {
    const item = cart.find(x => x.id === id);
    if (!item) return;
    item.qty = Math.max(1, parseInt(qty) || 1);
    renderCart();
}

function setCartPrice(id, price) {
    const item = cart.find(x => x.id === id);
    if (!item) return;
    item.unitPrice = Math.max(0, parseFloat(price) || 0);
    renderCart();
}

function removeFromCart(id) {
    cart = cart.filter(x => x.id !== id);
    renderCart();
}

function clearCart() {
    cart = [];
    userEditedPaid = false;
    renderCart();
}

function setReturnDays(days) {
    const d = new Date();
    d.setDate(d.getDate() + days);
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    document.getElementById('pos-return-date').value = `${yyyy}-${mm}-${dd}`;
    updateSummaryReturnDate();
}

function clearReturnDate() {
    document.getElementById('pos-return-date').value = '';
    document.getElementById('pos-summary-return-date').textContent = '—';
}

function updateSummaryReturnDate() {
    const val = document.getElementById('pos-return-date').value;
    if (!val) {
        document.getElementById('pos-summary-return-date').textContent = '—';
        return;
    }
    const parts = val.split('-');
    if (parts.length === 3) {
        document.getElementById('pos-summary-return-date').textContent = `${parts[2]}/${parts[1]}/${parts[0]}`;
    } else {
        document.getElementById('pos-summary-return-date').textContent = val;
    }
}

function renderCart() {
    const tbody = document.getElementById('cart-table-body');
    const emptyState = document.getElementById('cart-empty');
    const badge = document.getElementById('cart-items-count-badge');
    const btnSaveOnly = document.getElementById('btn-save-only');
    const btnSavePrint = document.getElementById('btn-save-print');

    const totalCount = cart.reduce((sum, it) => sum + it.qty, 0);
    badge.textContent = `${totalCount} items`;

    if (cart.length === 0) {
        tbody.innerHTML = '';
        emptyState.classList.remove('hidden');
        if (btnSaveOnly) btnSaveOnly.disabled = true;
        if (btnSavePrint) btnSavePrint.disabled = true;
        calculateTotals();
        return;
    }

    emptyState.classList.add('hidden');
    if (btnSaveOnly) btnSaveOnly.disabled = false;
    if (btnSavePrint) btnSavePrint.disabled = false;

    let html = '';
    cart.forEach((item, idx) => {
        html += `
        <tr class="hover:bg-slate-50/60 transition-colors">
            <td class="py-2.5 px-2.5 text-center font-bold text-slate-500">${idx + 1}</td>
            <td class="py-2.5 px-3">
                <span class="font-bold text-slate-900 block flex items-center gap-1">
                    <span class="text-amber-500 text-xs">⚡</span> <span class="truncate">${escHtml(item.name)}</span>
                </span>
                <span class="text-[10px] text-slate-400 block">${escHtml(item.unit || 'Pcs')}</span>
            </td>
            <td class="py-2.5 px-2 text-center">
                <input type="number" min="1" value="${item.qty}" onchange="setCartQty(${item.id}, this.value)" class="w-12 text-center py-1 rounded-lg border border-slate-200 font-bold text-xs outline-none focus:border-[#c00000]">
            </td>
            <td class="py-2.5 px-3 text-right">
                <div class="flex items-center justify-end gap-1 font-mono">
                    <span class="text-slate-400 font-bold">$</span>
                    <input type="number" step="0.01" min="0" value="${item.unitPrice.toFixed(2)}" onchange="setCartPrice(${item.id}, this.value)" class="w-16 text-right py-1 px-1.5 rounded-lg border border-slate-200 font-bold text-xs outline-none focus:border-[#c00000]">
                </div>
            </td>
            <td class="py-2.5 px-2 text-center">
                <button type="button" onclick="removeFromCart(${item.id})" class="p-1 rounded-md bg-red-50 hover:bg-red-100 text-red-500 hover:text-red-700 transition-colors cursor-pointer" title="Remove">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;
    calculateTotals();
    lucide.createIcons();
}

let userEditedPaid = false;

function setFullPay() {
    userEditedPaid = false;
    calculateTotals();
}

function calculateTotals() {
    const subtotal = cart.reduce((sum, it) => sum + (it.unitPrice * it.qty), 0);
    const discount = parseFloat(document.getElementById('pos-discount').value) || 0;
    const netTotal = Math.max(0, subtotal - discount);

    if (!userEditedPaid) {
        document.getElementById('pos-paid').value = netTotal > 0 ? netTotal.toFixed(2) : '0.00';
    }

    const paid = parseFloat(document.getElementById('pos-paid').value) || 0;
    const remaining = Math.max(0, netTotal - paid);

    document.getElementById('pos-subtotal').textContent = `$ ${subtotal.toFixed(2)}`;
    document.getElementById('pos-net-total').textContent = `$ ${netTotal.toFixed(2)}`;

    // Update Remaining Badge on Return Date Card
    const remBadge = document.getElementById('return-date-remaining-badge');
    if (remBadge) {
        remBadge.textContent = `Remaining: $${remaining.toFixed(2)}`;
    }

    // Update Balance
    const balElem = document.getElementById('pos-balance');
    if (balElem) {
        if (paid > netTotal && netTotal > 0) {
            const change = paid - netTotal;
            balElem.textContent = `$ ${change.toFixed(2)} (Change)`;
            balElem.className = 'font-black text-emerald-600 text-xs font-mono';
        } else {
            balElem.textContent = `$ ${remaining.toFixed(2)}`;
            balElem.className = 'font-black text-slate-900 text-xs font-mono';
        }
    }

    updateSummaryReturnDate();
}

let lastCompletedSaleData = null;

function printInvoiceReceipt(data) {
    document.getElementById('print-inv-num').textContent = data.invoiceNumber || '1';
    document.getElementById('print-inv-date').textContent = data.date || '<?= date('d/m/Y') ?>';
    document.getElementById('print-inv-method').textContent = data.paymentMethod || 'Cash On Hand';
    document.getElementById('print-inv-customer').textContent = data.customerName || 'Walk-In-Customer';
    document.getElementById('print-inv-phone').textContent = data.phone ? ('Tel: ' + data.phone) : '';
    document.getElementById('print-inv-branch').textContent = data.branch || 'Boorama Branch';

    // Return date row
    const retDateRow = document.getElementById('print-inv-retdate-row');
    const retDateSumRow = document.getElementById('print-retdate-summary-row');
    if (data.returnDate && (data.totalSale - data.paidAmount > 0)) {
        const parts = data.returnDate.split('-');
        const formattedRet = (parts.length === 3) ? `${parts[2]}/${parts[1]}/${parts[0]}` : data.returnDate;
        document.getElementById('print-inv-retdate').textContent = formattedRet;
        document.getElementById('print-retdate-summary').textContent = formattedRet;
        retDateRow.style.display = '';
        retDateSumRow.style.display = '';
    } else {
        retDateRow.style.display = 'none';
        retDateSumRow.style.display = 'none';
    }

    // Items table
    const tbody = document.getElementById('print-inv-items-body');
    let itemsHtml = '';
    (data.items || []).forEach((it, idx) => {
        const total = (it.unitPrice || 0) * (it.qty || 1);
        itemsHtml += `
            <tr class="hover:bg-slate-50/50">
                <td class="py-2.5 px-3 text-center text-slate-500 font-bold">${idx + 1}</td>
                <td class="py-2.5 px-4 font-bold text-slate-900">${escHtml(it.name)}</td>
                <td class="py-2.5 px-3 text-center text-slate-700">${it.qty} ${escHtml(it.unit || 'Pcs')}</td>
                <td class="py-2.5 px-4 text-right font-mono font-bold">$${parseFloat(it.unitPrice).toFixed(2)}</td>
                <td class="py-2.5 px-4 text-right font-mono font-black text-slate-900">$${total.toFixed(2)}</td>
            </tr>`;
    });
    tbody.innerHTML = itemsHtml;

    // Stamp
    const stampBox = document.getElementById('print-stamp-box');
    const bal = data.totalSale - data.paidAmount;
    if (bal <= 0 && data.totalSale > 0) {
        stampBox.innerHTML = `
            <div class="inline-flex items-center gap-1.5 border-2 border-dashed border-emerald-500 text-emerald-600 bg-emerald-50/50 rounded-xl px-4 py-2 font-black text-sm uppercase tracking-widest transform -rotate-3">
                ✓ PAID
            </div>`;
    } else if (data.paidAmount > 0) {
        stampBox.innerHTML = `
            <div class="inline-flex items-center gap-1.5 border-2 border-dashed border-amber-500 text-amber-600 bg-amber-50/50 rounded-xl px-4 py-2 font-black text-sm uppercase tracking-widest transform -rotate-3">
                ⚠ PARTIAL
            </div>`;
    } else {
        stampBox.innerHTML = `
            <div class="inline-flex items-center gap-1.5 border-2 border-dashed border-red-500 text-red-600 bg-red-50/50 rounded-xl px-4 py-2 font-black text-sm uppercase tracking-widest transform -rotate-3">
                ✕ UNPAID / DEBT
            </div>`;
    }

    document.getElementById('print-inv-note').textContent = data.notes ? ('Note: ' + data.notes) : 'Note: Retail Sale';
    document.getElementById('print-subtotal').textContent = '$' + parseFloat(data.subtotal).toFixed(2);
    document.getElementById('print-total').textContent = '$' + parseFloat(data.totalSale).toFixed(2);
    document.getElementById('print-paid').textContent = '$' + parseFloat(data.paidAmount).toFixed(2);
    document.getElementById('print-balance').textContent = '$' + parseFloat(Math.max(0, bal)).toFixed(2);

    // Trigger Print
    const printContainer = document.getElementById('invoice-print-container');
    printContainer.classList.remove('hidden');
    window.print();
    setTimeout(() => {
        printContainer.classList.add('hidden');
    }, 1000);
}

function executeSale(autoPrint = false) {
    if (cart.length === 0) return;

    const btnOnly = document.getElementById('btn-save-only');
    const btnPrint = document.getElementById('btn-save-print');
    if (btnOnly) btnOnly.disabled = true;
    if (btnPrint) btnPrint.disabled = true;

    const activeBtn = autoPrint ? btnPrint : btnOnly;
    const oldHtml = activeBtn ? activeBtn.innerHTML : '';
    if (activeBtn) {
        activeBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>Saving...</span>';
        lucide.createIcons();
    }

    const customerSelect = document.getElementById('pos-customer');
    const customerName = customerSelect.value;
    const customerPhone = customerSelect.options[customerSelect.selectedIndex]?.getAttribute('data-phone') || '';
    const branch = document.getElementById('pos-branch').value;
    const saleDate = document.getElementById('pos-form-sale-date').value;
    const paymentMethod = document.getElementById('pos-method').value;
    const returnDate = document.getElementById('pos-return-date').value;

    const subtotal = cart.reduce((sum, it) => sum + (it.unitPrice * it.qty), 0);
    const discount = parseFloat(document.getElementById('pos-discount').value) || 0;
    const netTotal = Math.max(0, subtotal - discount);
    const paid = parseFloat(document.getElementById('pos-paid').value) || 0;
    const notes = document.getElementById('pos-notes').value;

    const savedCartCopy = JSON.parse(JSON.stringify(cart));

    const payload = {
        customer_name: customerName,
        phone: customerPhone,
        branch: branch,
        sale_date: saleDate,
        return_date: returnDate,
        payment_method: paymentMethod,
        subtotal: subtotal,
        discount: discount,
        total_sale: netTotal,
        paid_amount: paid,
        notes: notes,
        pricing_type: pricingType,
        items: cart
    };

    fetch('api/process_sale.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (btnOnly) btnOnly.disabled = false;
        if (btnPrint) btnPrint.disabled = false;
        if (activeBtn) activeBtn.innerHTML = oldHtml;
        lucide.createIcons();

        if (data.success) {
            // Decrement UI product card stock in real-time
            savedCartCopy.forEach(item => {
                const card = document.querySelector(`.product-card[data-id="${item.id}"]`);
                if (card) {
                    const badge = card.querySelector('span[class*="rounded-full"]');
                    if (badge) {
                        const cur = parseInt(badge.textContent) || 0;
                        const nextStock = Math.max(0, cur - item.qty);
                        badge.textContent = `${nextStock} in stock`;
                    }
                }
            });

            // Retain for receipt printing
            lastCompletedSaleData = {
                invoiceNumber: data.invoiceNumber,
                customerName: customerName,
                phone: customerPhone,
                branch: branch,
                date: saleDate,
                returnDate: returnDate,
                paymentMethod: paymentMethod,
                subtotal: subtotal,
                totalSale: netTotal,
                paidAmount: paid,
                notes: notes,
                items: savedCartCopy
            };

            // Show Toast
            if (typeof showToast === 'function') {
                showToast('Iibka Waa La Diiwaangeliyay', `Invoice ${data.invoiceNumber} si guul leh ayaa loo kaydiyay!`, 'success');
            }

            // Populate Success Confirmation Modal
            document.getElementById('succ-inv-num').textContent = data.invoiceNumber;
            document.getElementById('succ-customer').textContent = customerName;
            document.getElementById('succ-total').textContent = `$${netTotal.toFixed(2)}`;
            document.getElementById('succ-paid').textContent = `$${paid.toFixed(2)}`;
            const bal = Math.max(0, netTotal - paid);
            document.getElementById('succ-balance').textContent = `$${bal.toFixed(2)}`;

            const succRetRow = document.getElementById('succ-return-date-row');
            if (bal > 0 && returnDate) {
                document.getElementById('succ-return-date').textContent = returnDate;
                succRetRow.classList.remove('hidden');
            } else {
                succRetRow.classList.add('hidden');
            }

            // Clear Cart & inputs
            clearCart();
            document.getElementById('pos-paid').value = '0.00';
            document.getElementById('pos-discount').value = '0.00';
            document.getElementById('pos-notes').value = '';
            calculateTotals();

            // Open Success Modal
            const succModal = document.getElementById('sale-success-modal');
            if (succModal) succModal.classList.remove('hidden');
            lucide.createIcons();

            // If autoPrint was selected, open print dialog
            if (autoPrint) {
                printInvoiceReceipt(lastCompletedSaleData);
            }
        } else {
            alert('Cillad: ' + (data.error || 'Failed to save sale.'));
        }
    })
    .catch(err => {
        if (btnOnly) btnOnly.disabled = false;
        if (btnPrint) btnPrint.disabled = false;
        if (activeBtn) activeBtn.innerHTML = oldHtml;
        lucide.createIcons();
        alert('Server or network error saving sale: ' + err);
    });
}

function printSuccessInvoice() {
    if (lastCompletedSaleData) {
        printInvoiceReceipt(lastCompletedSaleData);
    }
}

function closeSuccessModal() {
    const succModal = document.getElementById('sale-success-modal');
    if (succModal) succModal.classList.add('hidden');
    const searchInput = document.getElementById('pos-search');
    if (searchInput) searchInput.focus();
}

// Initial calculation & date sync
document.addEventListener('DOMContentLoaded', () => {
    updateSummaryReturnDate();
    calculateTotals();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
