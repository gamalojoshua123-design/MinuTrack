<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/ai_helper.php';

// Log errors only — do not display to users
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

requirePermission('reports_view');
header('Content-Type: application/json');

// Add timeout to prevent hanging
set_time_limit(30);

try {
    // Branch scoping: managers/inventory staff see only their branch
    $branch_id = getCurrentBranchId();
    $branch_clause = $branch_id !== null ? 'AND branch_id = ?' : '';

    // Get today's sales
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total
        FROM orders
        WHERE date_time >= CURDATE()
          AND date_time < CURDATE() + INTERVAL 1 DAY
          $branch_clause
    ");
    if ($branch_id !== null) { $stmt->execute([$branch_id]); } else { $stmt->execute(); }
    $today = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get low stock count from live inventory table (branch-scoped when needed)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as low_stock_count
        FROM inventory
        WHERE quantity < min_stock
          AND (status IS NULL OR status = 'active')
          AND deleted_at IS NULL
          $branch_clause
    ");
    if ($branch_id !== null) { $stmt->execute([$branch_id]); } else { $stmt->execute(); }
    $lowStock = $stmt->fetch(PDO::FETCH_ASSOC);

    // Prepare prompts
    $salesPrompt = "Today's sales: ₱" . number_format((float)$today['total'], 2) . " with {$today['count']} orders. Give a short practical sales insight (1 sentence).";
    $inventoryPrompt = "Low stock active products count: {$lowStock['low_stock_count']}. Give a short practical inventory recommendation (1 sentence).";

    // Get AI insights with timeout
    $sales = '';
    $inventory = '';
    
    // Try to get sales insight
    try {
        $sales = askAI($salesPrompt, 'You are a concise sales dashboard assistant. Respond in 1 short sentence.');
        if (empty($sales) || strlen($sales) < 5) {
            $sales = "Today's sales: ₱" . number_format($today['total'], 2) . " from {$today['count']} orders.";
        }
    } catch (Exception $e) {
        error_log("Sales insight error: " . $e->getMessage());
        $sales = "Today's sales: ₱" . number_format($today['total'], 2) . " from {$today['count']} orders.";
    }
    
    // Try to get inventory insight
    try {
        $inventory = askAI($inventoryPrompt, 'You are a concise inventory dashboard assistant. Respond in 1 short sentence.');
        if (empty($inventory) || strlen($inventory) < 5) {
            $inventory = $lowStock['low_stock_count'] > 0 
                ? "⚠️ {$lowStock['low_stock_count']} items are low in stock. Please check inventory."
                : "All inventory items are adequately stocked.";
        }
    } catch (Exception $e) {
        error_log("Inventory insight error: " . $e->getMessage());
        $inventory = $lowStock['low_stock_count'] > 0 
            ? "⚠️ {$lowStock['low_stock_count']} items need restocking."
            : "Inventory levels are good.";
    }

    echo json_encode([
        'success' => true,
        'sales' => $sales,
        'inventory' => $inventory,
        'debug' => [
            'sales_prompt' => $salesPrompt,
            'inventory_prompt' => $inventoryPrompt,
            'today_total' => $today['total'],
            'low_stock_count' => $lowStock['low_stock_count']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'sales' => 'Unable to retrieve sales data',
        'inventory' => 'Unable to retrieve inventory data'
    ]);
} catch (Throwable $e) {
    error_log('Unexpected error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'sales' => 'Service temporarily unavailable',
        'inventory' => 'Please try again later'
    ]);
}
?>