<?php
require_once 'bootstrap.php';

if (!hasPermission('products_view')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$item_id = $_GET['id'] ?? 0;

if ($item_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid item ID']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT * FROM inventory_log
        WHERE item_id = ?
        ORDER BY update_date DESC
        LIMIT 50
    ");
    $stmt->execute([$item_id]);
    $history = $stmt->fetchAll();

    echo json_encode(['success' => true, 'history' => $history]);
} catch (Exception $e) {
    error_log('get_item_history.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Unable to load item history.']);
}