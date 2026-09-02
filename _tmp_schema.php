<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pos_system', 'root', '');
    echo "inventory_movements columns:\n";
    $stmt = $pdo->query('DESCRIBE inventory_movements');
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $r['Field'] . ' (' . $r['Type'] . ')' . "\n";
    }
    echo "\ninventory columns:\n";
    $stmt = $pdo->query('DESCRIBE inventory');
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $r['Field'] . ' (' . $r['Type'] . ')' . "\n";
    }
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
