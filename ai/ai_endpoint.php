<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
requirePermission('reports_view');
require_once __DIR__ . '/ai_helper.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $question = trim($input['question'] ?? '');

    // Sanitize: limit length and strip potential injection attempts
    $question = substr($question, 0, 500);
    $question = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $question);

    if ($question === '') {
        echo json_encode([
            'success' => false,
            'answer' => 'Please enter a question.'
        ]);
        exit;
    }

    // Branch scoping: managers/inventory staff see only their branch; owners can
    // target a branch via branch_view or see all branches.
    $branch_id = getCurrentBranchId();
    $branch_clause = $branch_id !== null ? 'AND branch_id = ?' : '';
    $branch_on_clause = $branch_id !== null ? 'AND o.branch_id = ?' : '';

    // Get today's sales
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as order_count,
            COALESCE(SUM(total_amount), 0) as total_sales,
            COALESCE(AVG(total_amount), 0) as avg_order
        FROM orders
        WHERE date_time >= CURDATE()
          AND date_time < CURDATE() + INTERVAL 1 DAY
          $branch_clause
    ");
    if ($branch_id !== null) { $stmt->execute([$branch_id]); } else { $stmt->execute(); }
    $today = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get yesterday's sales
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as order_count,
            COALESCE(SUM(total_amount), 0) as total_sales,
            COALESCE(AVG(total_amount), 0) as avg_order
        FROM orders
        WHERE date_time >= CURDATE() - INTERVAL 1 DAY
          AND date_time < CURDATE()
          $branch_clause
    ");
    if ($branch_id !== null) { $stmt->execute([$branch_id]); } else { $stmt->execute(); }
    $yesterday = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get this month's sales
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as order_count,
            COALESCE(SUM(total_amount), 0) as total_sales,
            COALESCE(AVG(total_amount), 0) as avg_order
        FROM orders
        WHERE date_time >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
          AND date_time < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01')
          $branch_clause
    ");
    if ($branch_id !== null) { $stmt->execute([$branch_id]); } else { $stmt->execute(); }
    $month = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get top 10 products from order_items (using actual sales data)
    $stmt = $pdo->prepare("
        SELECT 
            p.name,
            p.stock,
            p.status,
            COALESCE(SUM(oi.quantity), 0) as qty_sold,
            COALESCE(SUM(oi.quantity * oi.price), 0) as total_sales
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
            AND o.date_time >= CURDATE() - INTERVAL 30 DAY
            $branch_on_clause
        WHERE p.status = 'active'
        GROUP BY p.id, p.name, p.stock, p.status
        ORDER BY qty_sold DESC, total_sales DESC
        LIMIT 10
    ");
    if ($branch_id !== null) { $stmt->execute([$branch_id]); } else { $stmt->execute(); }
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get product movement for last 7 days
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name,
            p.stock,
            p.status,
            COALESCE(SUM(
                CASE 
                    WHEN o.date_time >= NOW() - INTERVAL 7 DAY THEN oi.quantity
                    ELSE 0
                END
            ), 0) as sold_last_7_days
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
            $branch_on_clause
        WHERE p.status = 'active'
        GROUP BY p.id, p.name, p.stock, p.status
        ORDER BY sold_last_7_days DESC, p.name ASC
        LIMIT 20
    ");
    if ($branch_id !== null) { $stmt->execute([$branch_id]); } else { $stmt->execute(); }
    $productMovement = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get low stock items from INVENTORY table (branch-scoped when needed)
    $stmt = $pdo->prepare("
        SELECT 
            item_name as name, 
            quantity as stock,
            min_stock,
            unit
        FROM inventory
        WHERE quantity < min_stock 
          AND (status IS NULL OR status = 'active')
          AND deleted_at IS NULL
          $branch_clause
        ORDER BY quantity ASC
        LIMIT 15
    ");
    if ($branch_id !== null) { $stmt->execute([$branch_id]); } else { $stmt->execute(); }
    $lowStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate sales trend
    $salesTrendPercent = 0;
    if ((float) $yesterday['total_sales'] > 0) {
        $salesTrendPercent = (
            ((float) $today['total_sales'] - (float) $yesterday['total_sales']) /
            (float) $yesterday['total_sales']
        ) * 100;
    }

    // Format top products text
    $topProductsText = '';
    foreach ($topProducts as $row) {
        $topProductsText .= "- {$row['name']} | Sold: {$row['qty_sold']} | Sales: ₱" . number_format((float) $row['total_sales'], 2) . " | Stock: {$row['stock']}\n";
    }
    if ($topProductsText === '') {
        $topProductsText = "- No top product data available\n";
    }

    // Format movement text
    $movementText = '';
    foreach ($productMovement as $row) {
        $dailyAvg = ((float) $row['sold_last_7_days']) / 7;
        $daysLeft = $dailyAvg > 0 ? ((float) $row['stock'] / $dailyAvg) : 999;

        $movementText .= "- {$row['name']} | Stock: {$row['stock']} | Sold last 7 days: {$row['sold_last_7_days']} | Avg/day: " . number_format($dailyAvg, 2) . " | Days left: " . ($daysLeft === 999 ? 'No recent sales' : number_format($daysLeft, 1)) . "\n";
    }
    if ($movementText === '') {
        $movementText = "- No inventory movement data available\n";
    }

    // Format low stock text (from inventory table)
    $lowStockText = '';
    foreach ($lowStockItems as $row) {
        $minStock = isset($row['min_stock']) ? " (Min: {$row['min_stock']})" : '';
        $unit = isset($row['unit']) ? " {$row['unit']}" : '';
        $lowStockText .= "- {$row['name']} | Stock: {$row['stock']}{$unit}{$minStock}\n";
    }
    if ($lowStockText === '') {
        $lowStockText = "- No low stock items\n";
    }

    $systemPrompt = "You are an AI dashboard assistant for Minute Burger, a burger restaurant. Answer only based on the provided business data. Be concise, practical, and easy to understand. Give direct answers for sales, inventory, product movement, forecasts, and operations. Keep responses to 2-3 sentences unless the question requires more detail.";

    $userPrompt = "
The user asked this question:
{$question}

Here is the live dashboard business data:

TODAY'S SALES
- Orders: {$today['order_count']}
- Total Sales: ₱" . number_format((float) $today['total_sales'], 2) . "
- Average Order: ₱" . number_format((float) $today['avg_order'], 2) . "

YESTERDAY'S SALES
- Orders: {$yesterday['order_count']}
- Total Sales: ₱" . number_format((float) $yesterday['total_sales'], 2) . "
- Average Order: ₱" . number_format((float) $yesterday['avg_order'], 2) . "

THIS MONTH'S SALES
- Orders: {$month['order_count']}
- Total Sales: ₱" . number_format((float) $month['total_sales'], 2) . "
- Average Order: ₱" . number_format((float) $month['avg_order'], 2) . "

SALES TREND VS YESTERDAY
- " . number_format((float) $salesTrendPercent, 1) . "% " . ($salesTrendPercent > 0 ? 'increase' : ($salesTrendPercent < 0 ? 'decrease' : 'no change')) . "

TOP 10 PRODUCTS (LAST 30 DAYS)
{$topProductsText}

INVENTORY MOVEMENT (LAST 7 DAYS)
{$movementText}

LOW STOCK ITEMS (Stock < Min Stock)
{$lowStockText}

Instructions:
- Answer the user's question directly using ONLY the data provided above.
- If the question is about sales, mention specific numbers and compare with yesterday or month as relevant.
- If the question is about inventory, mention which items are at risk and suggest restocking priorities.
- If the question asks for predictions, base it on the 7-day movement and sales trends.
- If the question cannot be answered with the provided data, say so clearly.
- Keep answers short (2-3 sentences) unless the question specifically asks for details.
- Be friendly and helpful.

Answer the question now:
";

    $answer = askAI($userPrompt, $systemPrompt);

    echo json_encode([
        'success' => true,
        'answer' => $answer
    ]);
    
} catch (PDOException $e) {
    error_log('Database error in ai_endpoint: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'answer' => 'Sorry, I could not retrieve the data needed to answer your question. Please try again later.',
        'debug' => 'Database query failed: ' . $e->getMessage()
    ]);
} catch (Throwable $e) {
    error_log('Unexpected error in ai_endpoint: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'answer' => 'Sorry, I encountered an error processing your request. Please try again.',
        'debug' => $e->getMessage()
    ]);
}
?>