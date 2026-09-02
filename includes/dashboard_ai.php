<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../ai/ai_helper.php';

requirePermission('reports_view');
header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total
        FROM orders
        WHERE date_time >= CURDATE()
          AND date_time < CURDATE() + INTERVAL 1 DAY
    ");
    $stmt->execute();
    $today = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as low_stock_count
        FROM products
        WHERE stock < 10 AND status = 'active'
    ");
    $stmt->execute();
    $lowStock = $stmt->fetch(PDO::FETCH_ASSOC);

    $salesPrompt = "
Today's sales: ₱" . number_format((float)$today['total'], 2) . "
Today's orders: {$today['count']}

Give a short practical sales insight for the dashboard.
";

    $inventoryPrompt = "
Low stock active products count: {$lowStock['low_stock_count']}

Give a short practical inventory recommendation for the dashboard.
";

    $sales = askAI($salesPrompt, 'You are a concise sales dashboard assistant.');
    $inventory = askAI($inventoryPrompt, 'You are a concise inventory dashboard assistant.');

    echo json_encode([
        'sales' => $sales,
        'inventory' => $inventory
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'sales' => 'Failed to generate sales insight.',
        'inventory' => 'Failed to generate inventory insight.'
    ]);
}