<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

$data = json_decode(file_get_contents('php://input'), true);
$id   = intval($data['id']  ?? 0);
$qty  = intval($data['qty'] ?? 1);

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM sale_items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Sale item not found']);
        exit;
    }

    $productName  = $item['product_name'];
    $productId    = intval($item['product_id'] ?? 0);
    $returnQty    = $qty > 0 ? $qty : intval($item['qty']);
    $itemTotal    = floatval($item['total_price'] ?? ($item['sold_price'] * $returnQty));
    $txId         = $item['transaction_id'] ?? '';
    $saleId       = intval($item['sale_id'] ?? 0);

    $pdo->beginTransaction();

    // 1. Add stock back to inventory using product_id
    if ($productId > 0) {
        $upd = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ? LIMIT 1");
        $upd->execute([$returnQty, $productId]);
    } else {
        $upd = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE LOWER(name) = LOWER(?) LIMIT 1");
        $upd->execute([$returnQty, $productName]);
    }

    // 2. Mark sale_item as returned
    $mark = $pdo->prepare("UPDATE sale_items SET sale_type = 'Returned' WHERE id = ?");
    $mark->execute([$id]);

    // 3. Reduce sales table total_sale & paid_amount so dashboard revenue is correct
    //    Find the sales row by sale_id or transaction_id
    $salesRow = null;
    if ($saleId > 0) {
        $sf = $pdo->prepare("SELECT id, total_sale, paid_amount FROM sales WHERE id = ? LIMIT 1");
        $sf->execute([$saleId]);
        $salesRow = $sf->fetch(PDO::FETCH_ASSOC);
    }
    if (!$salesRow && $txId !== '') {
        $cleanTx = str_replace('#', '', $txId);
        $sf2 = $pdo->prepare("SELECT id, total_sale, paid_amount FROM sales WHERE invoice_number = ? OR invoice_number = ? LIMIT 1");
        $sf2->execute([$txId, $cleanTx]);
        $salesRow = $sf2->fetch(PDO::FETCH_ASSOC);
    }
    if ($salesRow) {
        $newTotal   = max(0, floatval($salesRow['total_sale']) - $itemTotal);
        $newPaid    = max(0, min(floatval($salesRow['paid_amount']), $newTotal));
        $pdo->prepare("UPDATE sales SET total_sale = ?, paid_amount = ? WHERE id = ? LIMIT 1")
            ->execute([$newTotal, $newPaid, $salesRow['id']]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'product' => $productName,
        'qty_returned' => $returnQty
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
