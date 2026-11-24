<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

try {
    $saleId = intval($_POST['sale_id'] ?? 0);
    $invoiceNumber = trim($_POST['invoice_number'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $paymentMethod = trim($_POST['payment_method'] ?? 'ZAAD (Telesom)');
    $referenceCode = trim($_POST['reference_code'] ?? ('REF-' . rand(100000, 999999)));
    $notes = trim($_POST['notes'] ?? 'Settlement recorded');

    if ($saleId <= 0 && empty($invoiceNumber)) {
        throw new Exception("Invalid sale identifier.");
    }

    if ($amount <= 0) {
        throw new Exception("Payment amount must be greater than zero.");
    }

    // Fetch sale record
    if ($saleId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
        $stmt->execute([$saleId]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM sales WHERE invoice_number = ?");
        $stmt->execute([$invoiceNumber]);
    }
    $sale = $stmt->fetch();

    if (!$sale) {
        throw new Exception("Invoice record not found.");
    }

    $newPaid = $sale['paid_amount'] + $amount;
    $newStatus = ($newPaid >= $sale['total_sale']) ? 'Paid' : 'Partial';

    // Record payment
    $stmtPay = $pdo->prepare("INSERT INTO payments (sale_id, invoice_number, amount, payment_method, reference_code, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtPay->execute([$sale['id'], $sale['invoice_number'], $amount, $paymentMethod, $referenceCode, $notes]);

    // Update sale
    $stmtUp = $pdo->prepare("UPDATE sales SET paid_amount = ?, status = ? WHERE id = ?");
    $stmtUp->execute([$newPaid, $newStatus, $sale['id']]);

    // Update customer balance
    if ($sale['customer_name'] !== 'Walk-In Customer') {
        $stmtCust = $pdo->prepare("UPDATE customers SET balance = GREATEST(0, balance - ?) WHERE name = ?");
        $stmtCust->execute([$amount, $sale['customer_name']]);
    }

    echo json_encode([
        'success' => true,
        'invoiceNumber' => $sale['invoice_number'],
        'amount' => number_format($amount, 2),
        'totalPaid' => number_format($newPaid, 2),
        'remaining' => number_format(max(0, $sale['total_sale'] - $newPaid), 2),
        'status' => $newStatus
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
