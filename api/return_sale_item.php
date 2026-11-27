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

    $productName = $item['product_name'];
    $productId   = intval($item['product_id'] ?? 0);
    $returnQty   = $qty > 0 ? $qty : intval($item['qty']);

    $pdo->beginTransaction();

    // Add stock back to inventory using product_id (products.name not product_name)
    if ($productId > 0) {
        $upd = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ? LIMIT 1");
        $upd->execute([$returnQty, $productId]);
    } else {
        // Fallback: match by name column
        $upd = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE LOWER(name) = LOWER(?) LIMIT 1");
        $upd->execute([$returnQty, $productName]);
    }

    // Mark sale_item as returned
    $mark = $pdo->prepare("UPDATE sale_items SET sale_type = 'Returned' WHERE id = ?");
    $mark->execute([$id]);

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
