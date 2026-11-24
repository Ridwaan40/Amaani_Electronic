<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

$data  = json_decode(file_get_contents('php://input'), true);
$id    = intval($data['id']           ?? 0);
$name  = trim($data['product_name']   ?? '');
$qty   = intval($data['qty']          ?? 0);
$price = floatval($data['sold_price'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Fetch existing item
    $itemStmt = $pdo->prepare("SELECT * FROM sale_items WHERE id = ?");
    $itemStmt->execute([$id]);
    $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Record not found']);
        exit;
    }

    $newQty = $qty > 0 ? $qty : intval($item['qty']);
    $newPrice = $price > 0 ? $price : floatval($item['unit_price']);
    $newTotal = $newQty * $newPrice;
    $newName = $name !== '' ? $name : $item['product_name'];

    // Update sale_item
    $upd = $pdo->prepare("UPDATE sale_items SET product_name = ?, qty = ?, unit_price = ?, sold_price = ?, total_price = ? WHERE id = ?");
    $upd->execute([$newName, $newQty, $newPrice, $newPrice, $newTotal, $id]);

    // Recalculate parent sale in `sales`
    $saleId = intval($item['sale_id'] ?? 0);
    $txId = trim($item['transaction_id'] ?? '');

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
        $remQuery = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) as new_subtotal FROM sale_items WHERE sale_id = ? OR transaction_id = ?");
        $remQuery->execute([$curSaleId, $sale['invoice_number']]);
        $newSubtotal = floatval($remQuery->fetchColumn());

        $discount = floatval($sale['discount'] ?? 0);
        $newTotalSale = max(0, $newSubtotal - $discount);
        $paid = floatval($sale['paid_amount'] ?? 0);
        $newStatus = ($paid >= $newTotalSale && $newTotalSale > 0) ? 'Paid' : (($paid > 0) ? 'Partial' : 'Due');

        $updSale = $pdo->prepare("UPDATE sales SET subtotal = ?, total_sale = ?, status = ? WHERE id = ?");
        $updSale->execute([$newSubtotal, $newTotalSale, $newStatus, $curSaleId]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
