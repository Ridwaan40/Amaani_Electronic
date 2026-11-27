<?php
$currentPage = basename($_SERVER['PHP_SELF']);

// Quick helper for active state
function navActive($page, $currentPage) {
    if ($page === $currentPage) {
        return 'bg-red-600 text-white font-bold shadow-md shadow-red-900/40';
    }
    return 'text-slate-300 hover:text-white hover:bg-white/5 font-medium';
}

function navIconColor($page, $currentPage) {
    if ($page === $currentPage) {
        return 'text-white';
    }
    return 'text-slate-400 group-hover:text-white';
}

// Check if any sales transaction sub-page is active
$isSalesTransOpen = in_array($currentPage, ['create_sales.php', 'sales_items.php', 'invoices.php']);
?>

<!-- Left Navigation Sidebar -->
<aside id="main-sidebar" class="w-64 bg-[#160405] text-slate-300 min-h-screen shrink-0 hidden md:flex flex-col sticky top-0 h-screen border-r border-red-950/40 z-40 sidebar-scroll overflow-y-auto">
    <!-- Brand / Header -->
    <div class="sidebar-header-box p-4 flex items-center justify-between border-b border-white/5">
        <a href="index.php" class="flex items-center gap-2.5 group overflow-hidden">
            <div class="w-10 h-10 rounded-xl bg-white p-1 flex items-center justify-center shadow-md shadow-red-900/50 group-hover:scale-105 transition-transform shrink-0 overflow-hidden">
                <img src="assets/images/amaani-logo.jpg" alt="Amaani" class="w-full h-full object-contain">
            </div>
            <div class="sidebar-brand-text">
                <span class="font-black text-xs tracking-wider text-white uppercase block leading-tight">AMAANI</span>
                <span class="text-[9px] text-red-400 font-semibold uppercase tracking-widest block leading-tight">ELECTRONICS</span>
            </div>
        </a>
        <button type="button" onclick="toggleSidebarCollapse()" class="w-7 h-7 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 flex items-center justify-center transition-all cursor-pointer shrink-0" title="Collapse / Expand Sidebar">
            <i data-lucide="disc" id="sidebar-disc-icon" class="w-4 h-4 transition-transform duration-300"></i>
        </button>
    </div>

    <!-- Navigation List -->
    <nav class="flex-1 p-3 space-y-1 text-xs">
        <!-- Dashboard Button (Primary Red Active) -->
        <a href="index.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all group <?= navActive('index.php', $currentPage) ?>" title="Dashboard">
            <i data-lucide="layout-grid" class="w-4 h-4 <?= navIconColor('index.php', $currentPage) ?>"></i>
            <span class="tracking-tight text-xs">Dashboard</span>
        </a>

        <!-- Section: SALES -->
        <div class="sidebar-section-title pt-4 pb-1 px-3 text-[10px] font-black uppercase text-amber-500/90 tracking-wider">
            Sales
        </div>

        <!-- Sales Transactions Dropdown Parent -->
        <div class="space-y-1">
            <button type="button" onclick="toggleSalesTransactions(event)" class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all group cursor-pointer <?= $isSalesTransOpen ? 'bg-red-600 text-white font-bold shadow-md shadow-red-900/40' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                <span class="flex items-center gap-3">
                    <i data-lucide="shopping-cart" class="w-4 h-4 <?= $isSalesTransOpen ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>"></i>
                    <span>Sales Transactions</span>
                </span>
                <i data-lucide="chevron-down" id="sales-trans-chevron" class="w-3.5 h-3.5 <?= $isSalesTransOpen ? 'text-white' : 'text-slate-500 group-hover:text-slate-300 -rotate-90' ?> transition-transform duration-200"></i>
            </button>

            <!-- Sales Transactions Sub-Category Menu (Matching Image 1) -->
            <div id="sub-sales-transactions" class="<?= $isSalesTransOpen ? '' : 'hidden' ?> bg-[#0c0304]/90 rounded-2xl p-2 space-y-1 mt-1 border border-white/5">
                <!-- 1. Create Sales -->
                <a href="create_sales.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'create_sales.php' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-900/40' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'create_sales.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Create Sales</span>
                </a>

                <!-- 2. Sales Items -->
                <a href="sales_items.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'sales_items.php' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-900/40' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'sales_items.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Sales Items</span>
                </a>

                <!-- 3. Invoices -->
                <a href="invoices.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'invoices.php' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-900/40' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="flex items-center gap-3">
                        <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'invoices.php' ? 'border-white bg-white' : '' ?>"></span>
                        <span>Invoices</span>
                    </span>
                    <?php if ($overdueCount > 0): ?>
                        <span class="text-[9px] bg-red-600 text-white font-black px-1.5 py-0.2 rounded-full"><?= $overdueCount ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <!-- Quotations -->
        <a href="quotations.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center justify-between px-3 py-2 rounded-xl transition-all group <?= navActive('quotations.php', $currentPage) ?>">
            <span class="flex items-center gap-3">
                <i data-lucide="file-text" class="w-4 h-4 <?= navIconColor('quotations.php', $currentPage) ?>"></i>
                <span>Quotations</span>
            </span>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-500 group-hover:text-slate-300"></i>
        </a>

        <!-- Product Exchanges -->
        <a href="exchanges.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center justify-between px-3 py-2 rounded-xl transition-all group <?= navActive('exchanges.php', $currentPage) ?>">
            <span class="flex items-center gap-3">
                <i data-lucide="repeat" class="w-4 h-4 <?= navIconColor('exchanges.php', $currentPage) ?>"></i>
                <span>Product Exchanges</span>
            </span>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-500 group-hover:text-slate-300"></i>
        </a>

        <!-- Used Products -->
        <a href="inventory.php?branch=<?= urlencode($currentBranch) ?>&type=Used" class="flex items-center justify-between px-3 py-2 rounded-xl transition-all group text-slate-300 hover:text-white hover:bg-white/5 font-medium">
            <span class="flex items-center gap-3">
                <i data-lucide="recycle" class="w-4 h-4 text-slate-400 group-hover:text-white"></i>
                <span>Used Products</span>
            </span>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-500 group-hover:text-slate-300"></i>
        </a>

        <!-- Purchase Dropdown Parent (Matching Image 2) -->
        <?php
        $isPurchaseOpen = in_array($currentPage, ['create_purchase.php', 'purchases_list.php', 'purchase_items.php', 'purchase_returns.php', 'create_purchase_return.php']);
        ?>
        <div class="space-y-1">
            <button type="button" onclick="togglePurchaseMenu(event)" class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all group cursor-pointer <?= $isPurchaseOpen ? 'bg-red-600 text-white font-bold shadow-md shadow-red-900/40' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                <span class="flex items-center gap-3">
                    <i data-lucide="shopping-bag" class="w-4 h-4 <?= $isPurchaseOpen ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>"></i>
                    <span>Purchase</span>
                </span>
                <i data-lucide="chevron-down" id="purchase-chevron" class="w-3.5 h-3.5 <?= $isPurchaseOpen ? 'text-white' : 'text-slate-500 group-hover:text-slate-300 -rotate-90' ?> transition-transform duration-200"></i>
            </button>

            <!-- Purchase Sub-Category Menu (4 Subcategories matching Image 1 styling) -->
            <div id="sub-purchase" class="<?= $isPurchaseOpen ? '' : 'hidden' ?> bg-[#0c0304]/90 rounded-2xl p-2 space-y-1 mt-1 border border-white/5">
                <!-- 1. Create Purchase -->
                <a href="create_purchase.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'create_purchase.php' ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'create_purchase.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Create Purchase</span>
                </a>

                <!-- 2. Purchases List -->
                <a href="purchases_list.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'purchases_list.php' ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'purchases_list.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Purchases List</span>
                </a>

                <!-- 3. Purchased Items -->
                <a href="purchase_items.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'purchase_items.php' ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'purchase_items.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Purchased Items</span>
                </a>

                <!-- 4. Purchase Returns -->
                <a href="purchase_returns.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= in_array($currentPage, ['purchase_returns.php', 'create_purchase_return.php']) ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= in_array($currentPage, ['purchase_returns.php', 'create_purchase_return.php']) ? 'border-white bg-white' : '' ?>"></span>
                    <span>Purchase Returns</span>
                </a>
            </div>
        </div>

        <!-- Suppliers Dropdown Parent (Matching Image 1 & Image 3) -->
        <?php
        $isSupplierOpen = in_array($currentPage, ['suppliers.php', 'add_supplier.php']);
        ?>
        <div class="space-y-1">
            <button type="button" onclick="toggleSupplierMenu(event)" class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all group cursor-pointer <?= $isSupplierOpen ? 'bg-red-600 text-white font-bold shadow-md shadow-red-900/40' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                <span class="flex items-center gap-3">
                    <i data-lucide="truck" class="w-4 h-4 <?= $isSupplierOpen ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>"></i>
                    <span>Suppliers</span>
                </span>
                <i data-lucide="chevron-down" id="supplier-chevron" class="w-3.5 h-3.5 <?= $isSupplierOpen ? 'text-white' : 'text-slate-500 group-hover:text-slate-300 -rotate-90' ?> transition-transform duration-200"></i>
            </button>

            <!-- Suppliers Sub-Category Menu (Matching Image 1) -->
            <div id="sub-suppliers" class="<?= $isSupplierOpen ? '' : 'hidden' ?> bg-[#0c0304]/90 rounded-2xl p-2 space-y-1 mt-1 border border-white/5">
                <!-- 1. Supplier List -->
                <a href="suppliers.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'suppliers.php' ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'suppliers.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Supplier List</span>
                </a>

                <!-- 2. Add Supplier -->
                <a href="add_supplier.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'add_supplier.php' ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'add_supplier.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Add Supplier</span>
                </a>
            </div>
        </div>

        <!-- Inventory Dropdown Parent -->
        <?php
        $isInventoryOpen = in_array($currentPage, ['inventory.php', 'add_inventory.php']);
        ?>
        <div class="space-y-1">
            <button type="button" onclick="toggleInventoryMenu(event)" class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all group cursor-pointer <?= $isInventoryOpen ? 'bg-red-600 text-white font-bold shadow-md shadow-red-900/40' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                <span class="flex items-center gap-3">
                    <i data-lucide="boxes" class="w-4 h-4 <?= $isInventoryOpen ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>"></i>
                    <span>Inventory</span>
                </span>
                <i data-lucide="chevron-down" id="inventory-chevron" class="w-3.5 h-3.5 <?= $isInventoryOpen ? 'text-white' : 'text-slate-500 group-hover:text-slate-300 -rotate-90' ?> transition-transform duration-200"></i>
            </button>

            <!-- Inventory Sub-Category Menu (Matching Image 1 styling) -->
            <div id="sub-inventory" class="<?= $isInventoryOpen ? '' : 'hidden' ?> bg-[#0c0304]/90 rounded-2xl p-2 space-y-1 mt-1 border border-white/5">
                <!-- 1. Add New Inventory -->
                <a href="add_inventory.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'add_inventory.php' ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'add_inventory.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Add New Inventory</span>
                </a>

                <!-- 2. Inventory List -->
                <a href="inventory.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'inventory.php' ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'inventory.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Inventory List</span>
                </a>
            </div>
        </div>

        <!-- Category Setting Dropdown Parent (Matching user request) -->
        <?php
        $isCategorySettingOpen = in_array($currentPage, ['category_settings.php']);
        ?>
        <div class="space-y-1">
            <button type="button" onclick="toggleCategorySettingMenu(event)" class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all group cursor-pointer <?= $isCategorySettingOpen ? 'bg-red-600 text-white font-bold shadow-md shadow-red-900/40' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                <span class="flex items-center gap-3">
                    <i data-lucide="tags" class="w-4 h-4 <?= $isCategorySettingOpen ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>"></i>
                    <span>Category Setting</span>
                </span>
                <i data-lucide="chevron-down" id="category-setting-chevron" class="w-3.5 h-3.5 <?= $isCategorySettingOpen ? 'text-white' : 'text-slate-500 group-hover:text-slate-300 -rotate-90' ?> transition-transform duration-200"></i>
            </button>

            <!-- Sub Category: Categories and Units -->
            <div id="sub-category-setting" class="<?= $isCategorySettingOpen ? '' : 'hidden' ?> bg-[#0c0304]/90 rounded-2xl p-2 space-y-1 mt-1 border border-white/5">
                <a href="category_settings.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'category_settings.php' ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'category_settings.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Categories and Units</span>
                </a>
            </div>
        </div>

        <!-- Bad Inventory -->
        <a href="inventory.php?branch=<?= urlencode($currentBranch) ?>&status=low" class="flex items-center justify-between px-3 py-2 rounded-xl transition-all group text-slate-300 hover:text-white hover:bg-white/5 font-medium">
            <span class="flex items-center gap-3">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-slate-400 group-hover:text-white"></i>
                <span>Bad Inventory</span>
            </span>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-500 group-hover:text-slate-300"></i>
        </a>

        <!-- Customer Dropdown Parent -->
        <?php
        $isCustomerOpen = in_array($currentPage, ['customers.php', 'add_customer.php']);
        ?>
        <div class="space-y-1">
            <button type="button" onclick="toggleCustomerMenu(event)" class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all group cursor-pointer <?= $isCustomerOpen ? 'bg-red-600 text-white font-bold shadow-md shadow-red-900/40' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                <span class="flex items-center gap-3">
                    <i data-lucide="users" class="w-4 h-4 <?= $isCustomerOpen ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>"></i>
                    <span>Customers</span>
                </span>
                <i data-lucide="chevron-down" id="customer-chevron" class="w-3.5 h-3.5 <?= $isCustomerOpen ? 'text-white' : 'text-slate-500 group-hover:text-slate-300 -rotate-90' ?> transition-transform duration-200"></i>
            </button>

            <!-- Customer Sub-Category Menu -->
            <div id="sub-customer" class="<?= $isCustomerOpen ? '' : 'hidden' ?> bg-[#0c0304]/90 rounded-2xl p-2 space-y-1 mt-1 border border-white/5">
                <!-- 1. Customer List -->
                <a href="customers.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'customers.php' ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'customers.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Customer List</span>
                </a>

                <!-- 2. Add Customer -->
                <a href="add_customer.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'add_customer.php' ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'add_customer.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Add Customer</span>
                </a>
            </div>
        </div>

        <!-- Repairs Dropdown Parent (Matching user request) -->
        <?php
        $isRepairsOpen = in_array($currentPage, ['repairs.php', 'create_repair.php', 'repair_services.php', 'technicians.php', 'repair_ticket.php']);
        ?>
        <div class="space-y-1">
            <button type="button" onclick="toggleRepairsMenu(event)" class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all group cursor-pointer <?= $isRepairsOpen ? 'bg-red-600 text-white font-bold shadow-md shadow-red-900/40' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                <span class="flex items-center gap-3">
                    <i data-lucide="wrench" class="w-4 h-4 <?= $isRepairsOpen ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>"></i>
                    <span>Repairs</span>
                </span>
                <i data-lucide="chevron-down" id="repairs-chevron" class="w-3.5 h-3.5 <?= $isRepairsOpen ? 'text-white' : 'text-slate-500 group-hover:text-slate-300 -rotate-90' ?> transition-transform duration-200"></i>
            </button>

            <!-- Repairs Sub-Category Menu -->
            <div id="sub-repairs" class="<?= $isRepairsOpen ? '' : 'hidden' ?> bg-[#0c0304]/90 rounded-2xl p-2 space-y-1 mt-1 border border-white/5">
                <!-- 1. New Repair Job -->
                <a href="create_repair.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'create_repair.php' ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'create_repair.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>New Repair Job</span>
                </a>

                <!-- 2. Repair List -->
                <a href="repairs.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'repairs.php' ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'repairs.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Repair List</span>
                </a>

                <!-- 3. Service Catalog -->
                <a href="repair_services.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'repair_services.php' ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'repair_services.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Service Catalog</span>
                </a>

                <!-- 4. Technicians -->
                <a href="technicians.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'technicians.php' ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'technicians.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Technicians</span>
                </a>
            </div>
        </div>

        <!-- Section: FINANCIAL TRANSACTIONS -->
        <div class="sidebar-section-title pt-4 pb-1 px-3 text-[10px] font-black uppercase text-amber-500/90 tracking-wider">
            Financial Transactions
        </div>

        <!-- Payments Dropdown Parent -->
        <?php
        $isPaymentOpen = in_array($currentPage, ['payments.php', 'add_payment.php']);
        ?>
        <div class="space-y-1">
            <button type="button" onclick="togglePaymentMenu(event)" class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all group cursor-pointer <?= $isPaymentOpen ? 'bg-red-600 text-white font-bold shadow-md shadow-red-900/40' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                <span class="flex items-center gap-3">
                    <i data-lucide="credit-card" class="w-4 h-4 <?= $isPaymentOpen ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>"></i>
                    <span>Payments</span>
                </span>
                <i data-lucide="chevron-down" id="payment-chevron" class="w-3.5 h-3.5 <?= $isPaymentOpen ? 'text-white' : 'text-slate-500 group-hover:text-slate-300 -rotate-90' ?> transition-transform duration-200"></i>
            </button>

            <!-- Payments Sub-Category Menu -->
            <div id="sub-payments" class="<?= $isPaymentOpen ? '' : 'hidden' ?> bg-[#0c0304]/90 rounded-2xl p-2 space-y-1 mt-1 border border-white/5">
                <!-- 1. Payment List -->
                <a href="payments.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'payments.php' ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'payments.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Payment List</span>
                </a>

                <!-- 2. Add Payment -->
                <a href="add_payment.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $currentPage === 'add_payment.php' ? 'bg-red-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                    <span class="w-2.5 h-2.5 rounded-full border border-slate-400 shrink-0 inline-block <?= $currentPage === 'add_payment.php' ? 'border-white bg-white' : '' ?>"></span>
                    <span>Add Payment</span>
                </a>
            </div>
        </div>

        <a href="invoices.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center justify-between px-3 py-2 rounded-xl transition-all group text-slate-300 hover:text-white hover:bg-white/5 font-medium">
            <span class="flex items-center gap-3">
                <i data-lucide="wallet" class="w-4 h-4 text-slate-400 group-hover:text-white"></i>
                <span>Accounts</span>
            </span>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-500 group-hover:text-slate-300"></i>
        </a>

        <a href="invoices.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center justify-between px-3 py-2 rounded-xl transition-all group text-slate-300 hover:text-white hover:bg-white/5 font-medium">
            <span class="flex items-center gap-3">
                <i data-lucide="receipt" class="w-4 h-4 text-slate-400 group-hover:text-white"></i>
                <span>Expense</span>
            </span>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-500 group-hover:text-slate-300"></i>
        </a>

        <a href="invoices.php?branch=<?= urlencode($currentBranch) ?>&status=Due" class="flex items-center justify-between px-3 py-2 rounded-xl transition-all group text-slate-300 hover:text-white hover:bg-white/5 font-medium">
            <span class="flex items-center gap-3">
                <i data-lucide="scale" class="w-4 h-4 text-slate-400 group-hover:text-white"></i>
                <span>Liability</span>
            </span>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-500 group-hover:text-slate-300"></i>
        </a>

        <a href="sales_items.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center justify-between px-3 py-2 rounded-xl transition-all group text-slate-300 hover:text-white hover:bg-white/5 font-medium">
            <span class="flex items-center gap-3">
                <i data-lucide="landmark" class="w-4 h-4 text-slate-400 group-hover:text-white"></i>
                <span>Bank Management</span>
            </span>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-500 group-hover:text-slate-300"></i>
        </a>

        <!-- Section: REPORTS -->
        <div class="sidebar-section-title pt-4 pb-1 px-3 text-[10px] font-black uppercase text-amber-500/90 tracking-wider">
            Reports
        </div>

        <?php
        $isReportsOpen = in_array($currentPage, ['repair_report.php', 'sales_report.php']);
        $reportItems = [
            ['label' => 'Repair Report', 'href' => 'repair_report.php?branch=' . urlencode($currentBranch), 'page' => 'repair_report.php'],
            ['label' => 'Technician Costs Report', 'href' => 'technicians.php?report=costs&branch=' . urlencode($currentBranch), 'page' => ''],
            ['label' => 'Expense Reports', 'href' => '#expense-reports', 'page' => ''],
            ['label' => 'Liability Reports', 'href' => '#liability-reports', 'page' => ''],
            ['label' => 'Customer Balance Reports', 'href' => '#customer-balance-reports', 'page' => ''],
            ['label' => 'Customer History Report', 'href' => '#customer-history-report', 'page' => ''],
            ['label' => 'Payment Report', 'href' => '#payment-report', 'page' => ''],
            ['label' => 'Purchase Payment Report', 'href' => '#purchase-payment-report', 'page' => ''],
            ['label' => 'Sales Payment Report', 'href' => '#sales-payment-report', 'page' => ''],
            ['label' => 'Sales Report', 'href' => 'sales_report.php?branch=' . urlencode($currentBranch), 'page' => 'sales_report.php'],
            ['label' => 'Inventory Report', 'href' => '#inventory-report', 'page' => ''],
            ['label' => 'Purchase Report', 'href' => '#purchase-report', 'page' => ''],
            ['label' => 'Exchange Report', 'href' => '#exchange-report', 'page' => ''],
            ['label' => 'Balance Sheet', 'href' => '#balance-sheet', 'page' => ''],
            ['label' => 'Income Statement', 'href' => '#income-statement', 'page' => ''],
            ['label' => 'Bank Statement Report', 'href' => '#bank-statement-report', 'page' => ''],
            ['label' => 'Finance Activity Report', 'href' => '#finance-activity-report', 'page' => ''],
        ];
        ?>
        <div class="space-y-1">
            <button type="button" onclick="toggleReportsMenu(event)" class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all group cursor-pointer <?= $isReportsOpen ? 'bg-red-600 text-white font-bold shadow-md shadow-red-900/40' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                <span class="flex items-center gap-3">
                    <i data-lucide="trending-up" class="w-4 h-4 <?= $isReportsOpen ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>"></i>
                    <span>System Reports</span>
                </span>
                <i data-lucide="chevron-down" id="reports-chevron" class="w-3.5 h-3.5 <?= $isReportsOpen ? 'text-white' : 'text-slate-500 group-hover:text-slate-300 -rotate-90' ?> transition-transform duration-200"></i>
            </button>

            <div id="sub-reports" class="<?= $isReportsOpen ? '' : 'hidden' ?> bg-[#0c0304]/90 rounded-2xl p-2 space-y-0.5 mt-1 border border-white/5">
                <?php foreach ($reportItems as $ri):
                    $isActiveRep = (!empty($ri['page']) && $currentPage === $ri['page']);
                ?>
                    <a href="<?= $ri['href'] ?>" class="flex items-center gap-3 px-3 py-1.5 rounded-xl text-xs transition-all <?= $isActiveRep ? 'bg-red-600 text-white font-bold shadow-md shadow-red-900/40' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium' ?>">
                        <span class="w-2 h-2 rounded-full shrink-0 inline-block <?= $isActiveRep ? 'bg-amber-400 shadow-[0_0_6px_#f59e0b]' : 'border border-slate-500' ?>"></span>
                        <span class="truncate"><?= htmlspecialchars($ri['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section: ACTIVITIES -->
        <div class="sidebar-section-title pt-4 pb-1 px-3 text-[10px] font-black uppercase text-amber-500/90 tracking-wider">
            Activities
        </div>

        <a href="create_sales.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center justify-between px-3 py-2 rounded-xl transition-all group text-slate-300 hover:text-white hover:bg-white/5 font-medium">
            <span class="flex items-center gap-3">
                <i data-lucide="plus-circle" class="w-4 h-4 text-slate-400 group-hover:text-white"></i>
                <span>Quick POS Sale</span>
            </span>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-500 group-hover:text-slate-300"></i>
        </a>

        <a href="deliveries.php?branch=<?= urlencode($currentBranch) ?>" class="flex items-center justify-between px-3 py-2 rounded-xl transition-all group text-slate-300 hover:text-white hover:bg-white/5 font-medium">
            <span class="flex items-center gap-3">
                <i data-lucide="truck" class="w-4 h-4 text-slate-400 group-hover:text-white"></i>
                <span>Delivery Fleet</span>
            </span>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-500 group-hover:text-slate-300"></i>
        </a>

        <!-- Logout Link in Sidebar -->
        <div class="pt-4 pb-1">
            <a href="logout.php" onclick="return confirm('Ma hubtaa inaad ka baxdo nidaamka (Log Out)?');" class="flex items-center justify-between px-3 py-2 rounded-xl transition-all group text-red-400 hover:text-white hover:bg-red-600/25 font-bold border border-red-900/30">
                <span class="flex items-center gap-3">
                    <i data-lucide="log-out" class="w-4 h-4 text-red-500 group-hover:text-red-400"></i>
                    <span>Log Out</span>
                </span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-red-500/50"></i>
            </a>
        </div>
    </nav>
</aside>

<script>
function toggleSalesTransactions(e) {
    if (e) e.preventDefault();
    const menu = document.getElementById('sub-sales-transactions');
    const chevron = document.getElementById('sales-trans-chevron');
    if (menu) {
        menu.classList.toggle('hidden');
    }
    if (chevron) {
        chevron.classList.toggle('-rotate-90');
    }
}

function toggleInventoryMenu(e) {
    if (e) e.preventDefault();
    const menu = document.getElementById('sub-inventory');
    const chevron = document.getElementById('inventory-chevron');
    if (menu) {
        menu.classList.toggle('hidden');
    }
    if (chevron) {
        chevron.classList.toggle('-rotate-90');
    }
}

function togglePurchaseMenu(e) {
    if (e) e.preventDefault();
    const menu = document.getElementById('sub-purchase');
    const chevron = document.getElementById('purchase-chevron');
    if (menu) {
        menu.classList.toggle('hidden');
    }
    if (chevron) {
        chevron.classList.toggle('-rotate-90');
    }
}

function toggleSupplierMenu(e) {
    if (e) e.preventDefault();
    const menu = document.getElementById('sub-suppliers');
    const chevron = document.getElementById('supplier-chevron');
    if (menu) {
        menu.classList.toggle('hidden');
    }
    if (chevron) {
        chevron.classList.toggle('-rotate-90');
    }
}

function toggleCategorySettingMenu(e) {
    if (e) e.preventDefault();
    const menu = document.getElementById('sub-category-setting');
    const chevron = document.getElementById('category-setting-chevron');
    if (menu) {
        menu.classList.toggle('hidden');
    }
    if (chevron) {
        chevron.classList.toggle('-rotate-90');
    }
}

function toggleCustomerMenu(e) {
    if (e) e.preventDefault();
    const menu = document.getElementById('sub-customer');
    const chevron = document.getElementById('customer-chevron');
    if (menu) {
        menu.classList.toggle('hidden');
    }
    if (chevron) {
        chevron.classList.toggle('-rotate-90');
    }
}

function togglePaymentMenu(e) {
    if (e) e.preventDefault();
    const menu = document.getElementById('sub-payments');
    const chevron = document.getElementById('payment-chevron');
    if (menu) {
        menu.classList.toggle('hidden');
    }
    if (chevron) {
        chevron.classList.toggle('-rotate-90');
    }
}

function toggleReportsMenu(e) {
    if (e) e.preventDefault();
    const menu = document.getElementById('sub-reports');
    const chevron = document.getElementById('reports-chevron');
    if (menu) {
        menu.classList.toggle('hidden');
    }
    if (chevron) {
        chevron.classList.toggle('-rotate-90');
    }
}

function toggleRepairsMenu(e) {
    if (e) e.preventDefault();
    const menu = document.getElementById('sub-repairs');
    const chevron = document.getElementById('repairs-chevron');
    if (menu) {
        menu.classList.toggle('hidden');
    }
    if (chevron) {
        chevron.classList.toggle('-rotate-90');
    }
}

function toggleSidebarCollapse() {
    const sidebar = document.getElementById('main-sidebar');
    const discIcon = document.getElementById('sidebar-disc-icon');
    if (!sidebar) return;

    sidebar.classList.toggle('sidebar-collapsed');
    const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
    try {
        localStorage.setItem('caalami_sidebar_collapsed', isCollapsed ? '1' : '0');
    } catch(err) {}

    if (discIcon) {
        if (isCollapsed) {
            discIcon.classList.add('rotate-180', 'text-red-500');
        } else {
            discIcon.classList.remove('rotate-180', 'text-red-500');
        }
    }
}

// Restore saved collapsed state immediately
(function() {
    try {
        if (localStorage.getItem('caalami_sidebar_collapsed') === '1') {
            const sidebar = document.getElementById('main-sidebar');
            const discIcon = document.getElementById('sidebar-disc-icon');
            if (sidebar) sidebar.classList.add('sidebar-collapsed');
            if (discIcon) discIcon.classList.add('rotate-180', 'text-red-500');
        }
    } catch(err) {}
})();
// Zoom Management Functions
function updateZoomDisplay(val) {
    const el = document.getElementById('system-zoom-display');
    if (el) el.textContent = Math.round(val * 100) + '%';
}

function adjustSystemZoom(delta) {
    try {
        let current = parseFloat(localStorage.getItem('caalami_erp_zoom') || '1.08');
        current = Math.min(1.40, Math.max(0.85, current + delta));
        current = Math.round(current * 100) / 100;
        document.documentElement.style.zoom = current;
        localStorage.setItem('caalami_erp_zoom', current.toString());
        updateZoomDisplay(current);
    } catch(err) {}
}

function resetSystemZoom() {
    try {
        const defaultZoom = '1.08';
        document.documentElement.style.zoom = defaultZoom;
        localStorage.setItem('caalami_erp_zoom', defaultZoom);
        updateZoomDisplay(1.08);
    } catch(err) {}
}

// Initialize Zoom display
document.addEventListener('DOMContentLoaded', function() {
    try {
        const current = parseFloat(localStorage.getItem('caalami_erp_zoom') || '1.08');
        updateZoomDisplay(current);
    } catch(err) {}
});
</script>

<!-- Right Main Column Wrapper -->
<div class="flex-1 flex flex-col min-w-0 min-h-screen bg-[#f8fafc]">
    <!-- Top Navigation Header -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-30 shadow-xs px-4 sm:px-6 py-2.5 sm:py-3 flex items-center justify-between">
        <!-- Brand Title & Tagline -->
        <div class="flex items-center gap-3">
            <button type="button" onclick="document.getElementById('main-sidebar').classList.toggle('hidden')" class="md:hidden p-1.5 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors" title="Menu">
                <i data-lucide="menu" class="w-5 h-5"></i>
            </button>
            <div>
                <h2 class="text-sm sm:text-base font-black tracking-tight text-[#c00000] uppercase leading-tight">AMAANI ELECTRONICS</h2>
                <p class="text-[10px] sm:text-[11px] text-slate-400 font-medium leading-tight">Hoyga Qalabka Elektaroonigga ah &amp; Qalabka Casriga ah</p>
            </div>
        </div>

        <!-- Right Side Icons & Profile -->
        <div class="flex items-center gap-2 sm:gap-3">
            <!-- Zoom Controls (Quick Zoom in / out / reset) -->
            <div class="hidden sm:flex items-center bg-slate-100/90 hover:bg-slate-200/90 border border-slate-200/90 rounded-xl px-1.5 py-1 text-slate-700 text-xs font-bold transition-all shadow-2xs" title="Adjust System Zoom">
                <button type="button" onclick="adjustSystemZoom(-0.05)" class="w-6 h-6 rounded-lg hover:bg-white flex items-center justify-center text-slate-600 hover:text-red-600 transition-colors cursor-pointer" title="Zoom Out (Yaree)">
                    <i data-lucide="minus" class="w-3 h-3"></i>
                </button>
                <button type="button" onclick="resetSystemZoom()" id="system-zoom-display" class="px-2 py-0.5 text-[11px] font-extrabold text-slate-700 hover:text-[#c00000] cursor-pointer tracking-tight" title="Guji si aad dib ugu celiso (Reset to 108%)">
                    108%
                </button>
                <button type="button" onclick="adjustSystemZoom(0.05)" class="w-6 h-6 rounded-lg hover:bg-white flex items-center justify-center text-slate-600 hover:text-red-600 transition-colors cursor-pointer" title="Zoom In (Weynee)">
                    <i data-lucide="plus" class="w-3 h-3"></i>
                </button>
            </div>

            <!-- Red Delivery Icon -->
            <a href="deliveries.php" class="w-8 h-8 rounded-full bg-red-100 hover:bg-red-200 text-red-600 flex items-center justify-center transition-all hover:scale-105 shadow-xs" title="Deliveries">
                <i data-lucide="truck" class="w-4 h-4"></i>
            </a>

            <!-- Yellow Shopping Cart Icon -->
            <a href="create_sales.php" class="w-8 h-8 rounded-full bg-amber-400 hover:bg-amber-500 text-slate-900 flex items-center justify-center transition-all hover:scale-105 shadow-xs" title="POS Register">
                <i data-lucide="shopping-cart" class="w-4 h-4"></i>
            </a>

            <!-- User Profile Badge & Logout -->
            <?php
            $userName = $_SESSION['user']['name'] ?? 'Admin';
            $userRole = $_SESSION['user']['role'] ?? 'ADMIN';
            $userInitial = strtoupper(substr($userName, 0, 1));
            ?>
            <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                <div class="w-8 h-8 rounded-full ring-2 ring-red-500 overflow-hidden bg-gradient-to-tr from-red-600 to-amber-500 text-white flex items-center justify-center font-black text-xs shadow-xs">
                    <?= $userInitial ?>
                </div>
                <div class="hidden sm:block text-left">
                    <span class="block text-xs font-bold text-slate-800 leading-tight"><?= htmlspecialchars($userName) ?></span>
                    <span class="inline-block text-[9px] font-extrabold text-red-600 tracking-wider uppercase leading-none"><?= htmlspecialchars($userRole) ?></span>
                </div>
                <a href="logout.php" onclick="return confirm('Ma hubtaa inaad ka baxdo nidaamka (Log Out)?');" class="ml-1 p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Log Out">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Page Content Container -->
    <div class="flex-1 p-3 sm:p-5 lg:p-6 space-y-4 max-w-[1750px] w-full mx-auto">
