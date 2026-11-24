<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $cashier = !empty($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'Ridwaan';
    $customerName = !empty($input['customer_name']) ? trim($input['customer_name']) : 'Walk-In Customer';
    $phone = !empty($input['phone']) ? trim($input['phone']) : '+252 63 4001923';
    $branch = !empty($input['branch']) ? trim($input['branch']) : 'Boorama Branch';
    
    // Editable Sale Date requested by user
    $saleDate = !empty($input['sale_date']) ? trim($input['sale_date']) : date('Y-m-d');
    $saleTime = strtotime($saleDate);
    $returnDate = !empty($input['return_date']) ? trim($input['return_date']) : date('Y-m-d', strtotime('+14 days', $saleTime));
    $year = date('Y', $saleTime);
    $month = date('F', $saleTime);

    $subtotal = floatval($input['subtotal'] ?? 0);
    $discount = floatval($input['discount'] ?? 0);
    $totalSale = floatval($input['total_sale'] ?? 0);
    $paidAmount = floatval($input['paid_amount'] ?? 0);
    $paymentMethod = !empty($input['payment_method']) ? trim($input['payment_method']) : 'Cash On Hand';
    $notes = !empty($input['notes']) ? trim($input['notes']) : '';
    $pricingType = !empty($input['pricing_type']) ? ucfirst($input['pricing_type']) : 'Retail';

    $status = 'Due';
    if ($paidAmount >= $totalSale && $totalSale > 0) {
        $status = 'Paid';
    } elseif ($paidAmount > 0) {
        $status = 'Partial';
    }

    // Generate consecutive clean invoice number e.g. #4, #5
    $nextId = $pdo->query("SELECT IFNULL(MAX(id), 0) + 1 FROM sales")->fetchColumn();
    $invNum = '#' . $nextId;

    // Items summary
    $items = $input['items'] ?? [];
    $itemNames = [];
    foreach ($items as $it) {
        $itemNames[] = ($it['qty'] ?? 1) . 'x ' . ($it['name'] ?? 'Item');
    }
    $itemsSummary = !empty($itemNames) ? implode(', ', $itemNames) : '1x Electronics item';

    $stmt = $pdo->prepare("
        INSERT INTO sales 
        (invoice_number, customer_name, phone, branch, sale_date, return_date, year, month, subtotal, discount, total_sale, paid_amount, payment_method, notes, items_summary, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $invNum, $customerName, $phone, $branch, $saleDate, $returnDate, $year, $month,
        $subtotal, $discount, $totalSale, $paidAmount, $paymentMethod, $notes, $itemsSummary, $status
    ]);

    $saleId = $pdo->lastInsertId();

    // Insert line items with full linkage for invoices.php and sales_items.php
    if (!empty($items)) {
        $stmtItem = $pdo->prepare("
            INSERT INTO sale_items 
            (sale_id, transaction_id, product_id, product_name, qty, unit_price, total_price, unit, sold_price, wholesale_price, sale_type, branch, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtStock = $pdo->prepare("UPDATE products SET stock_qty = GREATEST(0, stock_qty - ?) WHERE id = ?");

        foreach ($items as $it) {
            $qty = intval($it['qty'] ?? 1);
            $uPrice = floatval($it['unitPrice'] ?? $it['price'] ?? 0);
            $tPrice = $qty * $uPrice;
            $pId = !empty($it['id']) ? intval($it['id']) : null;
            $unit = !empty($it['unit']) ? $it['unit'] : 'Pcs';
            $whPrice = floatval($it['wholesalePrice'] ?? 0);

            $stmtItem->execute([
                $saleId,
                $invNum,
                $pId,
                $it['name'] ?? 'Item',
                $qty,
                $uPrice,
                $tPrice,
                $unit,
                $uPrice,
                $whPrice,
                $pricingType,
                $branch,
                $cashier,
                $saleDate
            ]);

            if (!empty($pId)) {
                $stmtStock->execute([$qty, $pId]);
            }
        }
    }

    // Record payment if paidAmount > 0
    if ($paidAmount > 0) {
        $refCode = 'REF-' . rand(100000, 999999);
        $stmtPay = $pdo->prepare("INSERT INTO payments (sale_id, invoice_number, amount, payment_method, reference_code, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtPay->execute([$saleId, $invNum, $paidAmount, $paymentMethod, $refCode, 'Initial payment at POS']);
    }

    // Update customer debt balance if applicable
    $remainingDebt = max(0, $totalSale - $paidAmount);
    if ($remainingDebt > 0) {
        $checkCust = $pdo->prepare("SELECT id FROM customers WHERE name = ?");
        $checkCust->execute([$customerName]);
        if ($checkCust->fetchColumn()) {
            $stmtCust = $pdo->prepare("UPDATE customers SET balance = balance + ? WHERE name = ?");
            $stmtCust->execute([$remainingDebt, $customerName]);
        } else {
            $stmtCust = $pdo->prepare("UPDATE customers SET balance = balance + ? WHERE code = 'walk-in'");
            $stmtCust->execute([$remainingDebt]);
        }
    }

    echo json_encode([
        'success' => true,
        'saleId' => $saleId,
        'invoiceNumber' => $invNum,
        'customerName' => $customerName,
        'date' => $saleDate,
        'totalSale' => $totalSale,
        'paidAmount' => $paidAmount,
        'remainingDebt' => $remainingDebt
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
