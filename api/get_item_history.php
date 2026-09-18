<?php
require_once __DIR__ . '/../bootstrap.php';

if (!hasPermission('inventory_view')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$item_id = (int) ($_GET['id'] ?? 0);

if ($item_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid item ID']);
    exit();
}

// Only expose history for items in the caller's own branch.
$branch_id = getCurrentBranchId();
$branch_sql = $branch_id === null ? '' : ' AND i.branch_id = ?';
$params = $branch_id === null ? [$item_id] : [$item_id, (int) $branch_id];

try {
    $stmt = $pdo->prepare("
        SELECT l.* FROM inventory_log l
        JOIN inventory i ON i.id = l.item_id
        WHERE l.item_id = ?" . $branch_sql . "
        ORDER BY l.update_date DESC
        LIMIT 50
    ");
    $stmt->execute($params);
    $history = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'history' => $history]);
} catch (Exception $e) {
    error_log('get_item_history error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to retrieve item history.']);
}