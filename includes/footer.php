    </div><!-- End Page Content Container -->
</div><!-- End Right Main Column Wrapper -->
</div><!-- End Outer flex w-full -->

<!-- Global Toast Notification Container -->
<div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"></div>

<!-- ========================================== -->
<!-- 1. PAYMENT MODAL (DEBT SETTLEMENT)         -->
<!-- ========================================== -->
<div id="payment-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white rounded-2xl max-w-lg w-full border border-slate-200 shadow-2xl overflow-hidden flex flex-col animate-scale-in">
        <!-- Header -->
        <div class="bg-[#0B2A5B] text-white px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-orange-500/20 text-orange-400 flex items-center justify-center">
                    <i data-lucide="coins" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Process Customer Debt Settlement</h3>
                    <p id="pm-invoice-subtitle" class="text-xs text-slate-300 font-mono">Invoice # --</p>
                </div>
            </div>
            <button type="button" onclick="closePaymentModal()" class="p-1 rounded-lg text-slate-300 hover:text-white hover:bg-white/10 transition-colors cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Invoice Summary Box -->
        <div class="bg-slate-50 border-b border-slate-200 px-5 py-3">
            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="bg-white p-2 rounded-xl border border-slate-200">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Sale</span>
                    <span id="pm-total-sale" class="text-sm font-extrabold text-slate-800">$0.00</span>
                </div>
                <div class="bg-white p-2 rounded-xl border border-slate-200">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Already Paid</span>
                    <span id="pm-already-paid" class="text-sm font-extrabold text-emerald-600">$0.00</span>
                </div>
                <div class="bg-red-50 p-2 rounded-xl border border-red-200">
                    <span class="text-[10px] uppercase font-bold text-red-500 block">Remaining Due</span>
                    <span id="pm-remaining-due" class="text-sm font-black text-red-600">$0.00</span>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form id="payment-form" onsubmit="submitPayment(event)" class="p-5 space-y-4">
            <input type="hidden" id="pm-sale-id" name="sale_id">
            <input type="hidden" id="pm-invoice-number" name="invoice_number">

            <!-- Amount Input -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-bold text-slate-700">Settlement Amount ($ USD)</label>
                    <button type="button" onclick="pmPayFull()" class="text-[11px] font-bold text-orange-600 hover:text-orange-700 bg-orange-50 hover:bg-orange-100 px-2 py-0.5 rounded-md transition-colors cursor-pointer">
                        Pay in Full
                    </button>
                </div>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm">$</span>
                    <input type="number" id="pm-amount" name="amount" step="0.01" min="0.01" required class="w-full pl-8 pr-3 py-2 text-sm font-extrabold text-slate-900 bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#0B2A5B] outline-none">
                </div>
            </div>

            <!-- Payment Method Grid -->
            <div>
                <label class="text-xs font-bold text-slate-700 block mb-1.5">Select Payment Gateway / Method</label>
                <div class="grid grid-cols-2 gap-2" id="pm-method-grid">
                    <button type="button" onclick="pmSelectMethod('ZAAD (Telesom)')" class="pm-method-btn flex items-center gap-2 p-2 rounded-xl border border-slate-200 bg-blue-50/70 ring-2 ring-[#0B2A5B] text-slate-800 text-left cursor-pointer">
                        <i data-lucide="smartphone" class="w-4 h-4 text-[#0B2A5B]"></i>
                        <div>
                            <span class="block text-xs font-bold">ZAAD Service</span>
                            <span class="block text-[9px] text-slate-400">Telesom Mobile Money</span>
                        </div>
                    </button>
                    <button type="button" onclick="pmSelectMethod('e-Dahab (Somtel)')" class="pm-method-btn flex items-center gap-2 p-2 rounded-xl border border-slate-200 bg-white text-slate-800 text-left cursor-pointer">
                        <i data-lucide="smartphone" class="w-4 h-4 text-slate-400"></i>
                        <div>
                            <span class="block text-xs font-bold">e-Dahab</span>
                            <span class="block text-[9px] text-slate-400">Somtel Mobile Money</span>
                        </div>
                    </button>
                    <button type="button" onclick="pmSelectMethod('Cash On Hand')" class="pm-method-btn flex items-center gap-2 p-2 rounded-xl border border-slate-200 bg-white text-slate-800 text-left cursor-pointer">
                        <i data-lucide="banknote" class="w-4 h-4 text-slate-400"></i>
                        <div>
                            <span class="block text-xs font-bold">Cash (USD)</span>
                            <span class="block text-[9px] text-slate-400">Physical Counter Cash</span>
                        </div>
                    </button>
                    <button type="button" onclick="pmSelectMethod('Bank Transfer')" class="pm-method-btn flex items-center gap-2 p-2 rounded-xl border border-slate-200 bg-white text-slate-800 text-left cursor-pointer">
                        <i data-lucide="credit-card" class="w-4 h-4 text-slate-400"></i>
                        <div>
                            <span class="block text-xs font-bold">Bank Transfer</span>
                            <span class="block text-[9px] text-slate-400">Premier / Dahabshiil</span>
                        </div>
                    </button>
                </div>
                <input type="hidden" id="pm-selected-method" name="payment_method" value="ZAAD (Telesom)">
            </div>

            <!-- Reference & Notes -->
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-[11px] font-bold text-slate-700 block mb-1">Receipt Reference</label>
                    <input type="text" id="pm-reference" name="reference_code" class="w-full px-2.5 py-1.5 text-xs font-mono bg-slate-50 border border-slate-200 rounded-xl focus:bg-white outline-none">
                </div>
                <div>
                    <label class="text-[11px] font-bold text-slate-700 block mb-1">Ledger Note</label>
                    <input type="text" id="pm-notes" name="notes" value="Settlement collected at Boorama counter." class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white outline-none">
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="pt-2 flex items-center justify-between gap-3">
                <div class="flex items-center gap-1.5 text-[10px] text-slate-400 font-medium">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                    <span>Instant ledger reconciliation</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="closePaymentModal()" class="px-3 py-1.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="pm-submit-btn" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-[#0B2A5B] hover:bg-blue-900 rounded-xl shadow-md transition-all cursor-pointer">
                        <i data-lucide="check" class="w-3.5 h-3.5 text-orange-400"></i>
                        <span>Confirm Settlement</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- 2. INVOICE DETAIL & RECEIPT MODAL          -->
<!-- ========================================== -->
<div id="invoice-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white rounded-2xl max-w-lg w-full border border-slate-200 shadow-2xl overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="bg-[#0B2A5B] text-white px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="receipt" class="w-4 h-4 text-orange-400"></i>
                <h3 id="inv-modal-title" class="text-base font-bold text-white">Invoice Details</h3>
            </div>
            <button onclick="closeInvoiceModal()" class="text-slate-300 hover:text-white cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div id="inv-modal-body" class="p-5 space-y-4 text-xs overflow-y-auto max-h-[70vh]">
            <!-- Content will be injected by openInvoiceDetail -->
        </div>

        <!-- Footer -->
        <div class="bg-slate-50 px-5 py-3 border-t border-slate-200 flex items-center justify-between">
            <button onclick="printCurrentInvoice()" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 font-bold text-slate-700 cursor-pointer text-xs">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Print Official Receipt</span>
            </button>
            <button onclick="closeInvoiceModal()" class="px-4 py-1.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold cursor-pointer text-xs">
                Close
            </button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- PRINTABLE RECEIPT TEMPLATE (FOR PRINTING)  -->
<!-- ========================================== -->
<div id="printable-receipt" class="hidden p-6 max-w-md mx-auto text-black">
    <div class="text-center pb-3 border-b border-black">
        <h2 class="text-lg font-black tracking-tight uppercase">CAALAMI ELECTRONICS</h2>
        <p class="text-xs">Hoyga Qalabka Elektaroonigga ah &amp; Casriga ah</p>
        <p class="text-[10px]">Boorama Main Branch · Tel: +252 63 4452100</p>
    </div>
    <div id="receipt-print-content" class="py-3 text-xs space-y-2"></div>
    <div class="pt-3 border-t border-black text-center text-[10px]">
        <p class="font-bold">Mahadsanid! Waad ku mahadsan tahay ganacsigaaga.</p>
        <p>1-Year Official Distributor Warranty Included.</p>
    </div>
</div>

<script>
// Initialize Lucide Icons
lucide.createIcons();

// Active Payment Modal State
let currentInvoiceForPayment = null;

function openPaymentModal(inv) {
    currentInvoiceForPayment = inv;
    const remaining = Math.max(0, inv.totalSale - inv.paidAmount);
    
    document.getElementById('pm-sale-id').value = inv.id;
    document.getElementById('pm-invoice-number').value = inv.invoiceNumber;
    document.getElementById('pm-invoice-subtitle').textContent = `Invoice ${inv.invoiceNumber} · ${inv.customerName}`;
    document.getElementById('pm-total-sale').textContent = `$${parseFloat(inv.totalSale).toFixed(2)}`;
    document.getElementById('pm-already-paid').textContent = `$${parseFloat(inv.paidAmount).toFixed(2)}`;
    document.getElementById('pm-remaining-due').textContent = `$${remaining.toFixed(2)}`;
    
    document.getElementById('pm-amount').value = remaining.toFixed(2);
    document.getElementById('pm-amount').max = remaining;
    document.getElementById('pm-reference').value = 'REF-' + Math.floor(100000 + Math.random() * 900000);
    
    document.getElementById('payment-modal').classList.remove('hidden');
    lucide.createIcons();
}

function closePaymentModal() {
    document.getElementById('payment-modal').classList.add('hidden');
    currentInvoiceForPayment = null;
}

function pmPayFull() {
    if (!currentInvoiceForPayment) return;
    const remaining = Math.max(0, currentInvoiceForPayment.totalSale - currentInvoiceForPayment.paidAmount);
    document.getElementById('pm-amount').value = remaining.toFixed(2);
}

function pmSelectMethod(methodName) {
    document.getElementById('pm-selected-method').value = methodName;
    const btns = document.querySelectorAll('.pm-method-btn');
    btns.forEach(btn => {
        if (btn.textContent.includes(methodName.split(' ')[0])) {
            btn.classList.add('bg-blue-50/70', 'ring-2', 'ring-[#0B2A5B]');
            btn.classList.remove('bg-white');
        } else {
            btn.classList.remove('bg-blue-50/70', 'ring-2', 'ring-[#0B2A5B]');
            btn.classList.add('bg-white');
        }
    });
}

function submitPayment(e) {
    e.preventDefault();
    const btn = document.getElementById('pm-submit-btn');
    btn.disabled = true;
    btn.innerHTML = 'Recording...';

    const formData = new FormData(document.getElementById('payment-form'));

    fetch('api/process_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = 'Confirm Settlement';
        if (data.success) {
            showToast('Settlement Recorded', `Payment of $${data.amount} recorded for ${data.invoiceNumber}.`, 'success');
            closePaymentModal();
            setTimeout(() => window.location.reload(), 800);
        } else {
            alert('Error: ' + (data.error || 'Failed to process payment.'));
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = 'Confirm Settlement';
        alert('Network or server error.');
    });
}

// Invoice Detail Modal
function openInvoiceDetail(inv) {
    const remaining = Math.max(0, inv.totalSale - inv.paidAmount);
    document.getElementById('inv-modal-title').textContent = `Invoice ${inv.invoiceNumber}`;
    
    const html = `
        <div class="grid grid-cols-2 gap-3 bg-slate-50 p-3 rounded-xl border border-slate-200">
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Customer</span>
                <span class="font-extrabold text-slate-900 block uppercase">${inv.customerName}</span>
                <span class="text-slate-500 font-mono text-[11px] block">${inv.phone || 'N/A'}</span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Branch & Date</span>
                <span class="font-bold text-slate-800 block">${inv.branch}</span>
                <span class="text-slate-500 text-[11px] block">Date: ${inv.date}</span>
            </div>
        </div>

        <div>
            <h4 class="font-bold text-slate-800 mb-1.5">Billed Items</h4>
            <div class="border border-slate-200 rounded-xl p-3 bg-white">
                <div class="flex justify-between font-semibold text-slate-800">
                    <span>${inv.items || 'Electronics hardware pack'}</span>
                    <span>$${parseFloat(inv.totalSale).toFixed(2)}</span>
                </div>
            </div>
        </div>

        <div class="border border-slate-200 rounded-xl p-3 bg-slate-50 space-y-1.5">
            <div class="flex justify-between text-slate-600">
                <span>Total Sale</span>
                <span class="font-bold text-slate-900">$${parseFloat(inv.totalSale).toFixed(2)}</span>
            </div>
            <div class="flex justify-between text-slate-600">
                <span>Amount Paid</span>
                <span class="font-bold text-emerald-600">$${parseFloat(inv.paidAmount).toFixed(2)}</span>
            </div>
            <div class="border-t border-slate-200 pt-1.5 flex justify-between font-bold">
                <span>Remaining Debt</span>
                <span class="text-red-600 font-black text-sm">$${remaining.toFixed(2)}</span>
            </div>
            <div class="flex justify-between text-[11px] text-slate-500 pt-1">
                <span>Settlement Due Date</span>
                <span class="font-semibold text-red-600">${inv.returnDate}</span>
            </div>
        </div>
    `;

    document.getElementById('inv-modal-body').innerHTML = html;
    
    // Also prepare printable receipt
    document.getElementById('receipt-print-content').innerHTML = `
        <div class="flex justify-between">
            <span>Invoice: <strong>${inv.invoiceNumber}</strong></span>
            <span>Date: ${inv.date}</span>
        </div>
        <div>Customer: <strong>${inv.customerName}</strong></div>
        <hr class="border-black border-dashed my-2">
        <div class="flex justify-between font-bold">
            <span>${inv.items || 'Standard Electronics Package'}</span>
            <span>$${parseFloat(inv.totalSale).toFixed(2)}</span>
        </div>
        <hr class="border-black border-dashed my-2">
        <div class="flex justify-between">
            <span>Paid Amount:</span>
            <span>$${parseFloat(inv.paidAmount).toFixed(2)}</span>
        </div>
        <div class="flex justify-between font-bold">
            <span>Remaining Due:</span>
            <span>$${remaining.toFixed(2)}</span>
        </div>
    `;

    document.getElementById('invoice-modal').classList.remove('hidden');
    lucide.createIcons();
}

function closeInvoiceModal() {
    document.getElementById('invoice-modal').classList.add('hidden');
}

function printCurrentInvoice() {
    window.print();
}

// Toast notification helper
function showToast(title, message, type = 'info') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `p-3 rounded-xl border shadow-xl flex items-start gap-2.5 transition-all pointer-events-auto transform translate-y-2 opacity-0 ${
        type === 'success' 
            ? 'bg-emerald-50 border-emerald-200 text-emerald-900' 
            : 'bg-blue-50 border-blue-200 text-blue-900'
    }`;
    toast.innerHTML = `
        <div class="w-6 h-6 rounded-lg ${type === 'success' ? 'bg-emerald-500 text-white' : 'bg-[#0B2A5B] text-white'} flex items-center justify-center shrink-0 text-xs font-bold">✓</div>
        <div class="flex-1 min-w-0">
            <h5 class="text-xs font-bold leading-tight">${title}</h5>
            <p class="text-[11px] text-slate-600 mt-0.5 leading-tight">${message}</p>
        </div>
    `;
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    }, 10);
    setTimeout(() => {
        toast.classList.add('opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}
</script>
</body>
</html>
