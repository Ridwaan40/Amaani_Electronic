<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

echo "--- CUSTOMERS COLUMNS ---\n";
$stmt = $pdo->query("DESCRIBE customers");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$r['Field']} | {$r['Type']} | Null: {$r['Null']} | Default: {$r['Default']}\n";
}

echo "\n--- PAYMENTS COLUMNS ---\n";
$stmt2 = $pdo->query("DESCRIBE payments");
while ($r = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    echo "{$r['Field']} | {$r['Type']} | Null: {$r['Null']} | Default: {$r['Default']}\n";
}
