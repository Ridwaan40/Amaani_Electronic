<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

$data = json_decode(file_get_contents('php://input'), true);
$id   = intval($data['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Fetch the sale item first
    $stmtItem = $pdo->prepare("SELECT * FROM sale_items WHERE id = ?");
    $stmtItem->execute([$id]);
    $item = $stmtItem->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Record not found']);
        exit;
    }

    $saleId = intval($item['sale_id'] ?? 0);
    $txId = trim($item['transaction_id'] ?? '');
    $qty = intval($item['qty'] ?? 1);
    $prodId = intval($item['product_id'] ?? 0);
    $prodName = $item['product_name'] ?? '';

    // 2. Return stock to products table
    if ($prodId > 0) {
        $updStock = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?");
        $updStock->execute([$qty, $prodId]);
    } elseif (!empty($prodName)) {
        $updStock = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE LOWER(product_name) = LOWER(?) LIMIT 1");
        $updStock->execute([$qty, $prodName]);
    }

    // 3. Delete from sale_items
    $del = $pdo->prepare("DELETE FROM sale_items WHERE id = ?");
    $del->execute([$id]);

    // 4. Update or Delete the parent sale record in `sales`
    $sale = null;
    if ($saleId > 0) {
        $sQuery = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
        $sQuery->execute([$saleId]);
        $sale = $sQuery->fetch(PDO::FETCH_ASSOC);
    }
    if (!$sale && !empty($txId)) {
        $cleanTx = str_replace('#', '', $txId);
        $sQuery = $pdo->prepare("SELECT * FROM sales WHERE invoice_number = ? OR invoice_number = ? OR id = ?");
        $sQuery->execute([$txId, '#' . $cleanTx, $cleanTx]);
        $sale = $sQuery->fetch(PDO::FETCH_ASSOC);
    }

    if ($sale) {
        $curSaleId = $sale['id'];
        // Check remaining items for this sale
        $remQuery = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total_price), 0) as new_subtotal FROM sale_items WHERE sale_id = ? OR transaction_id = ?");
        $remQuery->execute([$curSaleId, $sale['invoice_number']]);
        $remData = $remQuery->fetch(PDO::FETCH_ASSOC);

        $remCount = intval($remData['cnt']);
        $newSubtotal = floatval($remData['new_subtotal']);

        if ($remCount <= 0) {
            // No items remain for this invoice -> Delete the invoice from sales table
            $delSale = $pdo->prepare("DELETE FROM sales WHERE id = ?");
            $delSale->execute([$curSaleId]);

            // If customer had an outstanding balance from this sale, adjust customer's balance
            $oldDebt = max(0, floatval($sale['total_sale']) - floatval($sale['paid_amount']));
            if ($oldDebt > 0 && !empty($sale['customer_name'])) {
                $updCust = $pdo->prepare("UPDATE customers SET balance = GREATEST(0, balance - ?) WHERE name = ?");
                $updCust->execute([$oldDebt, $sale['customer_name']]);
            }
        } else {
            // Items still remain -> update subtotal, total_sale, and status
            $discount = floatval($sale['discount'] ?? 0);
            $newTotalSale = max(0, $newSubtotal - $discount);
            $paid = floatval($sale['paid_amount'] ?? 0);
            $newStatus = ($paid >= $newTotalSale && $newTotalSale > 0) ? 'Paid' : (($paid > 0) ? 'Partial' : 'Due');

            $updSale = $pdo->prepare("UPDATE sales SET subtotal = ?, total_sale = ?, status = ? WHERE id = ?");
            $updSale->execute([$newSubtotal, $newTotalSale, $newStatus, $curSaleId]);

            // Adjust customer balance difference
            $oldDebt = max(0, floatval($sale['total_sale']) - floatval($sale['paid_amount']));
            $newDebt = max(0, $newTotalSale - $paid);
            $diffDebt = $oldDebt - $newDebt;
            if ($diffDebt != 0 && !empty($sale['customer_name'])) {
                $updCust = $pdo->prepare("UPDATE customers SET balance = GREATEST(0, balance - ?) WHERE name = ?");
                $updCust->execute([$diffDebt, $sale['customer_name']]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
