<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/inventory_functions.php';

requirePermission('pos_access');

$branch_id = getCurrentBranchId();

// Check if cashier has active shift (skip for Owner)
if (!isOwner()) {
    $stmt = $pdo->prepare("
        SELECT * FROM cashier_shifts 
        WHERE cashier_id = ? AND status = 'active'
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $active_shift = $stmt->fetch();
    
    if (!$active_shift) {
        header('Location: start_shift.php?message=' . urlencode('Please start your shift before using the POS.') . '&type=warning');
        exit();
    }
    
    $_SESSION['active_shift_id'] = $active_shift['id'];
    $_SESSION['active_shift_type'] = $active_shift['shift_type'];
    $shift_type = $active_shift['shift_type'];
} else {
    $shift_type = 'ADMIN';
    
    if (isset($_SESSION['active_shift_id'])) {
        unset($_SESSION['active_shift_id']);
    }
    if (isset($_SESSION['active_shift_type'])) {
        unset($_SESSION['active_shift_type']);
    }
}

// Define category order for sorting
$category_order = [
    'BIG TIME Burgers' => 1,
    'MinuteBurgers' => 2,
    'Hotdogs' => 3,
    'Drinks' => 4,
    'Add-ons' => 5
];

// Get low stock threshold
$low_stock_threshold = 10;

// Shift sales quota (per shift, not per day)
$shift_quota = $_SESSION['shift_quota'] ?? 10000;

// Get all products for AI suggestions
try {
    $stmt = $pdo->prepare("SELECT id, name, price, category FROM products WHERE status = 'active' ORDER BY price ASC");
    $stmt->execute();
    $all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_products = [];
}

enrichBranchStock($pdo, $all_products, $branch_id);

try {
    // Get current shift's total sales (skip for admin)
    if (isset($_SESSION['active_shift_id']) && !isAdmin()) {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total_amount), 0) as shift_sales 
            FROM orders 
            WHERE shift_id = ?
        ");
        $stmt->execute([$_SESSION['active_shift_id']]);
        $shift_sales = $stmt->fetch(PDO::FETCH_ASSOC)['shift_sales'];
    } else {
        // Admin: get today's total sales or show 0
        $shift_sales = 0;
        // Optionally, you can show today's total sales for admin
        if (isAdmin()) {
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(total_amount), 0) as today_sales 
                FROM orders 
                WHERE DATE(date_time) = CURDATE()
            ");
            $stmt->execute();
            $shift_sales = $stmt->fetch(PDO::FETCH_ASSOC)['today_sales'];
        }
    }
    
    // Calculate percentage for progress bar
    $percentage = min(($shift_sales / $shift_quota) * 100, 100);
    
    // Determine color based on percentage
    if ($percentage < 50) {
        $progress_color = '#e74c3c';
        $progress_color_light = '#e74c3c';
    } elseif ($percentage < 80) {
        $progress_color = '#f39c12';
        $progress_color_light = '#f39c12';
    } else {
        $progress_color = '#27ae60';
        $progress_color_light = '#27ae60';
    }
    
} catch (PDOException $e) {
    $shift_sales = 0;
    $percentage = 0;
    $progress_color = '#e74c3c';
    $progress_color_light = '#e74c3c';
}

// Get products
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'active' ORDER BY category, name");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    usort($products, function ($a, $b) use ($category_order) {
        $order_a = $category_order[$a['category']] ?? 999;
        $order_b = $category_order[$b['category']] ?? 999;

        if ($order_a == $order_b) {
            return strcmp($a['name'], $b['name']);
        }
        return $order_a - $order_b;
    });

} catch (PDOException $e) {
    $products = [];
    error_log("Error fetching products: " . $e->getMessage());
}

enrichBranchStock($pdo, $products, $branch_id);

// Get low stock alerts from branch inventory via BOM
$low_stock_items = [];
$out_of_stock_items = [];
$total_alerts = 0;

if ($branch_id !== null) {
    try {
        $threshold = $low_stock_threshold;
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.category
            FROM products p
            WHERE p.status = 'active'
              AND EXISTS (
                  SELECT 1 FROM product_ingredients pi
                  WHERE pi.product_id = p.id
              )
            ORDER BY p.name
        ");
        $stmt->execute();
        $bom_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT i.template_id, i.quantity
            FROM inventory i
            WHERE i.branch_id = ? AND i.deleted_at IS NULL
        ");
        $stmt->execute([$branch_id]);
        $inventory_map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $inventory_map[$row['template_id']] = (int)$row['quantity'];
        }

        $stmt = $pdo->prepare("
            SELECT pi.product_id, pi.template_id, pi.qty_required
            FROM product_ingredients pi
            WHERE pi.product_id IN (
                SELECT id FROM products WHERE status = 'active'
            )
        ");
        $stmt->execute();
        $bom_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $bom_by_product = [];
        foreach ($bom_rows as $row) {
            $bom_by_product[$row['product_id']][] = $row;
        }

        foreach ($bom_products as $p) {
            $bom = $bom_by_product[$p['id']] ?? [];
            if (empty($bom)) continue;
            $available = PHP_INT_MAX;
            foreach ($bom as $ing) {
                $invStock = $inventory_map[$ing['template_id']] ?? 0;
                $possible = (int)($invStock / $ing['qty_required']);
                $available = min($available, $possible);
            }
            $stock = max(0, $available);
            if ($stock === 0) {
                $out_of_stock_items[] = ['id' => $p['id'], 'name' => $p['name'], 'stock' => 0, 'category' => $p['category']];
            } elseif ($stock < $threshold) {
                $low_stock_items[] = ['id' => $p['id'], 'name' => $p['name'], 'stock' => $stock, 'category' => $p['category']];
            }
        }

        usort($low_stock_items, fn($a, $b) => $a['stock'] - $b['stock']);
        usort($out_of_stock_items, fn($a, $b) => strcmp($a['name'], $b['name']));

        $total_alerts = count($low_stock_items) + count($out_of_stock_items);
    } catch (PDOException $e) {
        error_log("Error fetching low stock items: " . $e->getMessage());
    }
}

// Handle AJAX request to get latest stock alerts
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check_alerts'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'total_alerts' => $total_alerts,
        'low_stock_count' => count($low_stock_items),
        'out_of_stock_count' => count($out_of_stock_items),
        'low_stock_items' => $low_stock_items,
        'out_of_stock_items' => $out_of_stock_items
    ]);
    exit;
}

// Handle AJAX request to get AI suggestion based on change amount
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_ai_suggestion'])) {
    header('Content-Type: application/json');
    $change = floatval($_GET['change'] ?? 0);
    $cart_total = floatval($_GET['total'] ?? 0);
    
    if ($change <= 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No change to suggest add-ons.'
        ]);
        exit;
    }
    
    $suggestions = [];
    foreach ($all_products as $product) {
        if ($product['price'] <= $change) {
            $suggestions[] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'category' => $product['category'],
                'difference' => $change - $product['price']
            ];
        }
    }
    
    usort($suggestions, function($a, $b) {
        return $a['price'] - $b['price'];
    });
    
    $suggestions = array_slice($suggestions, 0, 3);
    
    if (empty($suggestions)) {
        echo json_encode([
            'success' => true,
            'message' => 'No add-ons available within the change amount.'
        ]);
        exit;
    }
    
    $message = "💡 With ₱" . number_format($change, 2) . " change, you can add:\n";
    foreach ($suggestions as $s) {
        $message .= "• " . $s['name'] . " (₱" . number_format($s['price'], 2) . ")";
        if ($s['difference'] > 0) {
            $message .= " - ₱" . number_format($s['difference'], 2) . " left\n";
        } else {
            $message .= "\n";
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'suggestions' => $suggestions
    ]);
    exit;
}

// Handle AJAX request to save cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cart'])) {
    requireCsrfTokenJson();
    $_SESSION['saved_cart'] = json_decode($_POST['cart_data'], true);
    echo json_encode(['success' => true]);
    exit;
}

// Handle AJAX request to load cart
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['load_cart'])) {
    $cart = isset($_SESSION['saved_cart']) ? $_SESSION['saved_cart'] : [];
    echo json_encode(['success' => true, 'cart' => $cart]);
    exit;
}

// Handle AJAX request to clear saved cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_saved_cart'])) {
    requireCsrfTokenJson();
    unset($_SESSION['saved_cart']);
    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    requireCsrfToken();
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $payment = floatval($_POST['payment'] ?? 0);
    $change = floatval($_POST['change'] ?? 0);
    $cart_items = json_decode($_POST['cart_items'] ?? '[]', true);

    $error = '';

    if ($total_amount <= 0) {
        $error = 'Cart is empty. Please add items before checkout.';
    } elseif ($payment <= 0) {
        $error = 'Please enter cash amount.';
    } elseif ($payment > 1000000) {
        $error = 'Cash amount cannot exceed ₱1,000,000.';
    } elseif ($payment < $total_amount) {
        $error = 'Insufficient payment. Cash received must be greater than or equal to total amount.';
    } elseif (empty($cart_items)) {
        $error = 'No items in cart.';
    }

    if (empty($error)) {
        try {
            $pdo->beginTransaction();

            // Generate unique order number with retry loop
            $order_number = null;
            for ($attempt = 0; $attempt < 100; $attempt++) {
                $candidate = 'ORD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_number = ?");
                $checkStmt->execute([$candidate]);
                if ($checkStmt->fetchColumn() == 0) {
                    $order_number = $candidate;
                    break;
                }
            }
            if ($order_number === null) {
                throw new Exception("Failed to generate a unique order number. Please try again.");
            }

            // For admin orders, shift_id can be null
            $shift_id = isset($_SESSION['active_shift_id']) && !isAdmin() ? $_SESSION['active_shift_id'] : null;

            $stmt = $pdo->prepare("
                INSERT INTO orders (order_number, total_amount, payment, `change`, cashier_id, shift_id, branch_id, date_time) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $order_number,
                $total_amount,
                $payment,
                $change,
                $_SESSION['user_id'],
                $shift_id,
                $branch_id
            ]);
            $order_id = $pdo->lastInsertId();

            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("
                    SELECT id, name, category, status, is_bogo, price
                    FROM products
                    WHERE id = ?
                    LIMIT 1
                ");
                $stmt->execute([$item['id']]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    throw new Exception("Product not found during checkout.");
                }

                $db_price = (float)$product['price'];
                $item_qty = intval($item['quantity']);
                $item_subtotal = $db_price * $item_qty;

                // Save order item using server-side price (never trust client)
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $order_id,
                    $item['id'],
                    $item_qty,
                    $db_price,
                    $item_subtotal
                ]);

                // Deduct BOM ingredients via inventory_functions
                $productQty = intval($item['quantity']);
                if (in_array($product['category'] ?? '', ['Add-ons', 'Drinks'])) {
                    continue;
                }
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM product_ingredients WHERE product_id = ?");
                $checkStmt->execute([$item['id']]);
                if ($checkStmt->fetchColumn() == 0) {
                    continue;
                }

                $multiplier = !empty($product['is_bogo']) ? 2 : 1;
                $actual_units = $productQty * $multiplier;

                $deduct_result = deductProductIngredients($pdo, $item['id'], $actual_units, $order_id);
                if (!$deduct_result['success']) {
                    throw new Exception("Checkout failed: " . $deduct_result['message']);
                }
            }

            $pdo->commit();
            
            if (isset($_SESSION['saved_cart'])) {
                unset($_SESSION['saved_cart']);
            }

            header("Location: receipt.php?id=" . $order_id . "&from_pos=1");
            exit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Checkout failed: " . $e->getMessage();
            error_log($error);
        }
    }

    if (!empty($error)) {
        echo '<script>window.__pendingToast = { msg: ' . json_encode($error) . ', type: "error" };</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minute Burger - POS</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/admin.css'); ?>">
    <style>
        :root {
            --harvest-orange: #F37902ff;
            --chocolate: #DC6902ff;
            --apricot-cream: #EDD0A9ff;
            --copperwood: #BE6B03ff;
            --bright-lemon: #FAE51Dff;
            --light-gray: #f5f5f5;
            --dark-gray: #333333;
            --white: #ffffff;
            --shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #3498db;
        }

        body {
            background-color: var(--light-gray);
            color: var(--dark-gray);
            line-height: 1.6;
        }

        .pos-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-height: 100dvh;
            position: relative;
            overflow: hidden;
        }

        /* Viewport-locked shell on two-column tiers: products and cart each
           scroll internally so the Complete Order button never requires
           scrolling the page to reach */
        @media (min-width: 481px) {
            .pos-container {
                height: 100vh;
                height: 100dvh;
            }
        }

        /* AI Assistant */
        .ai-assistant {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 1000;
            cursor: pointer;
            transition: var(--transition);
        }

        .ai-burger-icon {
            width: 52px;
            height: 52px;
            min-width: 48px;
            min-height: 48px;
            background: linear-gradient(135deg, var(--harvest-orange), var(--chocolate));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.3);
            transition: var(--transition);
            animation: bounce 2s ease-in-out infinite;
        }

        .ai-burger-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(243, 121, 2, 0.4);
        }

        .ai-burger-icon span {
            font-size: 2.5rem;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .ai-suggestion-card {
            position: fixed;
            right: 90px;
            bottom: 24px;
            width: 280px;
            max-width: calc(100vw - 100px);
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            display: none;
            z-index: 1001;
            animation: slideInRight 0.3s ease;
            border: 1px solid var(--apricot-cream);
        }

        .ai-suggestion-card.show {
            display: block;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .ai-card-header {
            background: linear-gradient(135deg, var(--harvest-orange), var(--chocolate));
            color: white;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ai-card-header h3 {
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .ai-card-header h3 i {
            font-size: 1.2rem;
        }

        .ai-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .ai-close:hover {
            transform: scale(1.1);
        }

        .ai-card-body {
            padding: 16px;
            background: var(--white);
        }

        .ai-suggestion-text {
            font-size: 0.9rem;
            line-height: 1.5;
            color: var(--dark-gray);
            white-space: pre-line;
            margin-bottom: 12px;
        }

        .ai-suggestion-products {
            margin-top: 12px;
            border-top: 1px solid var(--apricot-cream);
            padding-top: 12px;
        }

        .suggestion-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-name {
            font-weight: 600;
            color: var(--dark-gray);
        }

        .suggestion-price {
            color: var(--harvest-orange);
            font-weight: 700;
        }

        .suggestion-difference {
            font-size: 0.7rem;
            color: var(--success);
        }

        .ai-card-footer {
            padding: 12px 16px;
            background: var(--light-gray);
            border-top: 1px solid var(--apricot-cream);
            font-size: 0.7rem;
            color: #666;
            text-align: center;
        }

        .quota-container {
            background: var(--white);
            margin: 0 1rem 0.5rem 1rem;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }

        .quota-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.25rem;
            flex-wrap: wrap;
            gap: 0.2rem;
        }

        .quota-title {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-weight: 600;
            color: var(--dark-gray);
            font-size: 0.78rem;
        }

        .quota-title i {
            color: var(--harvest-orange);
            font-size: 0.9rem;
        }

        .quota-stats {
            display: flex;
            gap: 0.5rem;
            font-size: 0.72rem;
        }

        .quota-stats span {
            font-weight: 700;
        }

        .quota-stats .current {
            color: var(--harvest-orange);
        }

        .quota-stats .target {
            color: var(--success);
        }

        .progress-bar-container {
            background: var(--light-gray);
            border-radius: 20px;
            height: 6px;
            overflow: hidden;
            margin-bottom: 0.2rem;
        }

        .progress-bar {
            height: 100%;
            border-radius: 20px;
            transition: width 0.5s ease;
            background: linear-gradient(90deg, var(--progress-color), var(--progress-color-light));
        }

        .quota-footer {
            display: flex;
            justify-content: space-between;
            font-size: 0.62rem;
            color: #666;
        }

        .quota-message {
            font-weight: 500;
        }

        .quota-message i {
            margin-right: 0.25rem;
        }

        .toast-container {
            position: fixed;
            top: 50px;
            right: 12px;
            z-index: 1000;
            max-width: 280px;
        }

        .toast {
            background: white;
            border-radius: 10px;
            padding: 0.65rem 1rem;
            margin-bottom: 0.6rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
            border-left: 4px solid;
            cursor: pointer;
            transition: var(--transition);
        }

        .toast:hover {
            transform: translateX(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }

        .toast.warning {
            border-left-color: var(--warning);
        }

        .toast.error {
            border-left-color: var(--danger);
        }

        .toast.success {
            border-left-color: var(--success);
        }

        .toast.info {
            border-left-color: var(--info);
        }

        .toast i {
            font-size: 1.5rem;
        }

        .toast.warning i {
            color: var(--warning);
        }

        .toast.error i {
            color: var(--danger);
        }

        .toast.success i {
            color: var(--success);
        }

        .toast.info i {
            color: var(--info);
        }

.toast-content {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        .toast-title {
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--dark-gray);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .toast-message {
            font-size: 0.9rem;
            color: #666;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .toast-title {
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--dark-gray);
        }

        .toast-message {
            font-size: 0.9rem;
            color: #666;
        }

        .toast-close {
            color: #999;
            font-size: 1.2rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .toast-close:hover {
            color: var(--danger);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pos-main {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 0.5rem;
            padding: 0.4rem;
            flex: 1;
            min-height: 0;
        }

        .products-section {
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 0.75rem 1rem;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        .cart-section {
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 0.6rem 0.75rem;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        @media (hover: none) {
            .product-card:hover {
                transform: none !important;
                box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08) !important;
                border-color: transparent !important;
            }

            .btn-add:hover:not(:disabled) {
                transform: none !important;
                box-shadow: 0 3px 8px rgba(243, 121, 2, 0.3) !important;
            }

            .btn-checkout:hover:not(:disabled) {
                transform: none !important;
                box-shadow: 0 4px 14px rgba(250, 229, 29, 0.3) !important;
            }

            .ai-burger-icon:hover {
                transform: none !important;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
            }

            .toast:hover {
                transform: none !important;
            }
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            flex-shrink: 0;
        }

        .section-header h2 {
            color: var(--dark-gray);
            font-size: 1rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .stock-indicator {
            background: var(--light-gray);
            border-radius: 20px;
            padding: 0.15rem 0.5rem;
            font-size: 0.65rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.15rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .stock-indicator:hover {
            background: var(--apricot-cream);
        }

        .stock-indicator.warning {
            background: var(--warning);
            color: white;
        }

        .stock-indicator.danger {
            background: var(--danger);
            color: white;
        }

        .search-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            flex-shrink: 0;
        }

        .search-box {
            flex: 1 1 160px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.4rem 0.65rem 0.4rem 1.8rem;
            border: 2px solid var(--apricot-cream);
            border-radius: 8px;
            font-size: 0.8rem;
            transition: var(--transition);
            background: var(--white);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--harvest-orange);
            box-shadow: 0 0 0 3px rgba(243, 121, 2, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 0.6rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--chocolate);
            font-size: 0.9rem;
        }

        .category-filter {
            flex: 1 1 150px;
        }

        .category-filter select {
            width: 100%;
            padding: 0.4rem 0.65rem;
            border: 2px solid var(--apricot-cream);
            border-radius: 8px;
            font-size: 0.8rem;
            background: var(--white);
            cursor: pointer;
            transition: var(--transition);
        }

        .category-filter select:focus {
            outline: none;
            border-color: var(--harvest-orange);
            box-shadow: 0 0 0 3px rgba(243, 121, 2, 0.1);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 0.5rem;
            overflow-y: auto;
            padding-right: 0.3rem;
            min-height: 0;
            flex: 1;
            align-content: start;
        }

        .products-grid::-webkit-scrollbar {
            width: 6px;
        }

        .products-grid::-webkit-scrollbar-track {
            background: var(--light-gray);
            border-radius: 10px;
        }

        .products-grid::-webkit-scrollbar-thumb {
            background: var(--harvest-orange);
            border-radius: 10px;
        }

        .product-card {
            background: var(--white);
            border-radius: 8px;
            padding: 0.5rem;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
            border: 2px solid transparent;
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            border-color: var(--bright-lemon);
        }

        .stock-badge {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            padding: 0.1rem 0.3rem;
            border-radius: 3px;
            font-size: 0.55rem;
            font-weight: 700;
            color: white;
        }

        .stock-badge.warning {
            background: var(--warning);
        }

        .stock-badge.danger {
            background: var(--danger);
        }

        .product-image {
            font-size: 2rem;
            margin-bottom: 0.3rem;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .product-name {
            font-weight: 700;
            margin-bottom: 0.2rem;
            flex: 1;
            color: var(--dark-gray);
            font-size: 0.78rem;
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            min-width: 0;
        }

        .product-price {
            font-weight: 800;
            color: var(--harvest-orange);
            font-size: 0.88rem;
            margin-bottom: 0.2rem;
        }

        .product-stock {
            font-size: 0.68rem;
            margin-bottom: 0.35rem;
            color: var(--dark-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.15rem;
        }

        .low-stock {
            color: var(--danger);
            font-weight: 700;
        }

        .btn-add {
            background: linear-gradient(135deg, var(--harvest-orange), var(--chocolate));
            color: var(--white);
            border: none;
            padding: 0.35rem 0.4rem;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
            margin-top: auto;
            box-shadow: 0 2px 6px rgba(243, 121, 2, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            min-height: 32px;
        }

        .btn-add:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--chocolate), var(--harvest-orange));
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(243, 121, 2, 0.4);
        }

        .btn-add:disabled {
            background: var(--light-gray);
            color: #999;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.35rem;
            padding-bottom: 0.3rem;
            border-bottom: 2px solid var(--bright-lemon);
            flex-shrink: 0;
        }

        .cart-header h2 {
            color: var(--dark-gray);
            font-size: 1rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .cart-count {
            background: var(--harvest-orange);
            color: white;
            border-radius: 20px;
            padding: 0.1rem 0.5rem;
            font-size: 0.68rem;
            font-weight: 600;
        }

        .btn-clear {
            background: var(--light-gray);
            color: var(--dark-gray);
            border: 2px solid var(--apricot-cream);
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-clear:hover {
            background: var(--danger);
            color: var(--white);
            border-color: var(--danger);
        }

        .cart-items {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 0.4rem;
            padding-right: 0.3rem;
            min-height: 0;
        }

        .cart-items::-webkit-scrollbar {
            width: 6px;
        }

        .cart-items::-webkit-scrollbar-track {
            background: var(--light-gray);
            border-radius: 10px;
        }

        .cart-items::-webkit-scrollbar-thumb {
            background: var(--bright-lemon);
            border-radius: 10px;
        }

        .empty-cart-message {
            text-align: center;
            color: #999;
            padding: 1.5rem 1rem;
            font-style: italic;
            font-size: 0.82rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100px;
            opacity: 0.7;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.3rem 0.3rem;
            border-bottom: 1px solid var(--apricot-cream);
            transition: var(--transition);
            border-radius: 5px;
            animation: fadeIn 0.3s ease;
        }

        .cart-item:hover {
            background: var(--light-gray);
        }

        .cart-item-info {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        .cart-item-name {
            font-weight: 700;
            margin-bottom: 0.1rem;
            color: var(--dark-gray);
            font-size: 0.8rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cart-item-price {
            color: var(--chocolate);
            font-size: 0.7rem;
            font-weight: 600;
        }

        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .quantity-btn {
            background: var(--light-gray);
            border: 2px solid var(--apricot-cream);
            width: 28px;
            height: 28px;
            min-width: 44px;
            min-height: 44px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-weight: bold;
            font-size: 0.95rem;
            padding: 0;
        }

        .quantity-btn:hover:not(:disabled) {
            background: var(--harvest-orange);
            color: var(--white);
            border-color: var(--harvest-orange);
        }

        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .cart-item-quantity {
            font-weight: 700;
            min-width: 28px;
            text-align: center;
            color: var(--dark-gray);
            font-size: 0.88rem;
            flex-shrink: 0;
        }

        .cart-item-subtotal {
            font-weight: 800;
            color: var(--harvest-orange);
            min-width: 55px;
            text-align: right;
            font-size: 0.85rem;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .cart-summary {
            border-top: 2px solid var(--bright-lemon);
            padding-top: 0.4rem;
            flex-shrink: 0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.2rem;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .payment-section {
            margin-top: 0.35rem;
        }

        .payment-input {
            margin-bottom: 0.4rem;
        }

        .payment-input label {
            display: block;
            margin-bottom: 0.2rem;
            font-weight: 700;
            color: var(--dark-gray);
            font-size: 0.78rem;
        }

        .payment-input input {
            width: 100%;
            padding: 0.4rem 0.65rem;
            border: 2px solid var(--apricot-cream);
            border-radius: 6px;
            font-size: 0.88rem;
            transition: var(--transition);
            background: var(--white);
        }

        .payment-input input:focus {
            outline: none;
            border-color: var(--harvest-orange);
            box-shadow: 0 0 0 3px rgba(243, 121, 2, 0.1);
        }

        .payment-input .warning {
            border-color: var(--danger);
            background: #FFF5F5;
        }

        .payment-change {
            text-align: center;
            padding: 0.35rem;
            background: var(--light-gray);
            border-radius: 6px;
            margin-bottom: 0.35rem;
            font-weight: 800;
            font-size: 0.88rem;
            color: var(--harvest-orange);
            border: 2px dashed var(--bright-lemon);
        }

        .payment-warning {
            background: #FEF2F0;
            color: #C0392B;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.65rem;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
            border-left: 3px solid var(--danger);
        }

        .btn-checkout {
            width: 100%;
            background: linear-gradient(135deg, var(--bright-lemon), #ffeb3b);
            color: var(--chocolate);
            border: none;
            padding: 0.45rem;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 800;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 3px 10px rgba(250, 229, 29, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            min-height: 40px;
        }

        .btn-checkout:hover:not(:disabled) {
            background: linear-gradient(135deg, #ffeb3b, var(--bright-lemon));
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(250, 229, 29, 0.4);
        }

        .btn-checkout:disabled {
            background: var(--light-gray);
            color: #999;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .confirmation-modal .modal-content {
            background: var(--white);
            border-radius: 15px;
            width: 90%;
            max-width: 450px;
            overflow: hidden;
            animation: modalSlideIn 0.3s ease;
        }

        .confirmation-modal .modal-header {
            background: linear-gradient(135deg, var(--harvest-orange), var(--copperwood));
            color: white;
            padding: 1rem 1.25rem;
        }

        .confirmation-modal .modal-body {
            padding: 1.25rem;
            text-align: center;
        }

        .confirmation-modal .modal-body i {
            font-size: 3rem;
            color: var(--warning);
            margin-bottom: 0.5rem;
        }

        .confirmation-modal .modal-body h3 {
            margin-bottom: 0.4rem;
            color: var(--dark-gray);
            font-size: 1.1rem;
        }

        .confirmation-modal .modal-body p {
            color: #666;
            margin-bottom: 0.4rem;
            font-size: 0.88rem;
        }

        .confirmation-modal .modal-body .order-details {
            background: var(--light-gray);
            padding: 0.75rem;
            border-radius: 6px;
            margin: 0.75rem 0;
            text-align: left;
            font-size: 0.85rem;
        }

        .confirmation-modal .modal-footer {
            padding: 1rem 1.25rem;
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            border-top: 1px solid var(--light-gray);
        }

        .btn-confirm {
            background: var(--success);
            color: white;
            padding: 0.55rem 1.25rem;
            border-radius: 7px;
            border: none;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-confirm:hover {
            background: #229954;
            transform: translateY(-1px);
        }

        .btn-cancel {
            background: var(--light-gray);
            color: var(--dark-gray);
            padding: 0.55rem 1.25rem;
            border-radius: 7px;
            border: none;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-cancel:hover {
            background: var(--danger);
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--white);
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, var(--harvest-orange), var(--copperwood));
            color: white;
            padding: 1rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.3rem;
            cursor: pointer;
            color: white;
            transition: var(--transition);
        }

        .modal-close:hover {
            transform: scale(1.1);
        }

        .modal-body {
            padding: 1rem 1.25rem;
        }

        .alert-section {
            margin-bottom: 1.25rem;
        }

        .alert-section h4 {
            margin-bottom: 0.65rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding-bottom: 0.35rem;
            border-bottom: 2px solid var(--light-gray);
            font-size: 0.9rem;
        }

        .alert-section.danger h4 {
            color: var(--danger);
        }

        .alert-section.warning h4 {
            color: var(--warning);
        }

        .alert-list {
            max-height: 160px;
            overflow-y: auto;
            border-radius: 6px;
            border: 1px solid var(--light-gray);
        }

        .alert-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid var(--light-gray);
            transition: var(--transition);
        }

        .alert-item:last-child {
            border-bottom: none;
        }

        .alert-item.danger .alert-quantity {
            background: var(--danger);
            color: white;
        }

        .alert-item.warning .alert-quantity {
            background: var(--warning);
            color: white;
        }

        .alert-name {
            font-weight: 600;
            color: var(--dark-gray);
            font-size: 0.85rem;
        }

        .alert-quantity {
            padding: 0.15rem 0.55rem;
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.8rem;
        }

        .modal-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--light-gray);
            display: flex;
            justify-content: flex-end;
            background: var(--light-gray);
        }

        .btn-modal {
            padding: 0.55rem 1.2rem;
            border-radius: 7px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-size: 0.82rem;
        }

        .btn-modal-primary {
            background: var(--harvest-orange);
            color: white;
        }

        .btn-modal-primary:hover {
            background: var(--chocolate);
        }

        /* ── Tier 1: Large desktop (>1280px) ── */
        @media (min-width: 1281px) {
            .pos-main {
                grid-template-columns: 2fr 1fr;
                padding: 0.75rem 1.25rem;
                gap: 1rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
                gap: 0.6rem;
            }

            .product-card {
                padding: 0.6rem;
            }

            .product-image {
                height: 56px;
            }

            .product-image img {
                width: 56px;
                height: 56px;
            }
        }

        /* ── Tier 2: Standard desktop (1025–1280px) ── */
        @media (min-width: 769px) and (max-width: 1280px) {
            .pos-main {
                grid-template-columns: 1.5fr 1fr;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            }
        }

        /* ── Tier 3: Tablet landscape / iPad Air landscape (769–1024px) ── */
        @media (min-width: 769px) and (max-width: 1024px) {
            .pos-main {
                grid-template-columns: 1.4fr 1fr;
                padding: 0.5rem 0.75rem;
                gap: 0.75rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 0.5rem;
            }

            .product-card {
                padding: 0.5rem;
            }

            .product-image {
                height: 48px;
            }

            .product-image img {
                width: 48px;
                height: 48px;
            }

            .product-name {
                font-size: 0.74rem;
            }

            .cart-item-subtotal {
                min-width: 50px;
                font-size: 0.82rem;
            }

            .ai-suggestion-card {
                width: 260px;
                right: 75px;
            }
        }

        /* ── Tier 4: Tablet portrait / iPad Air portrait (481–768px) ── */
        @media (min-width: 481px) and (max-width: 768px) {
            .pos-main {
                grid-template-columns: 1.2fr 1fr;
                padding: 0.4rem;
                gap: 0.5rem;
            }

            .products-section,
            .cart-section {
                padding: 0.5rem;
                min-height: 0;
            }

            .quota-container {
                margin: 0 0.4rem 0.3rem 0.4rem;
                padding: 0.35rem 0.5rem;
            }

            .quota-stats {
                flex-direction: column;
                gap: 0.05rem;
            }

            .search-filters {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .search-box {
                flex: 1 1 140px;
            }

            .category-filter {
                flex: 1 1 140px;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(115px, 1fr));
                gap: 0.4rem;
            }

            .product-image {
                height: 42px;
            }

            .product-image img {
                width: 42px;
                height: 42px;
            }

            .product-name {
                font-size: 0.72rem;
            }

            .product-price {
                font-size: 0.82rem;
            }

            .cart-item-subtotal {
                min-width: 45px;
                font-size: 0.8rem;
            }

            .quantity-btn {
                min-width: 40px;
                min-height: 40px;
                width: 26px;
                height: 26px;
            }

            .toast-container {
                max-width: 90%;
                right: 5%;
            }

            .ai-suggestion-card {
                width: 240px;
                right: 60px;
                bottom: 20px;
            }

            .ai-burger-icon {
                width: 48px;
                height: 48px;
            }

            .ai-burger-icon span {
                font-size: 1.5rem;
            }
        }

        /* ── Tier 5: Phone landscape (361–480px) ── */
        @media (min-width: 361px) and (max-width: 480px) {
            .pos-main {
                grid-template-columns: 1fr;
                padding: 0.35rem;
                gap: 0.35rem;
            }

            .products-section,
            .cart-section {
                padding: 0.5rem;
                min-height: auto;
            }

            .quota-container {
                margin: 0 0.35rem 0.3rem 0.35rem;
                padding: 0.3rem 0.45rem;
            }

            .quota-header {
                gap: 0.1rem;
            }

            .quota-title {
                font-size: 0.7rem;
            }

            .quota-stats {
                font-size: 0.65rem;
            }

            .search-filters {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .search-box {
                flex: 1 1 100%;
            }

            .category-filter {
                flex: 1 1 100%;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 0.3rem;
            }

            .product-image {
                height: 36px;
                font-size: 1.3rem;
            }

            .product-image img {
                width: 36px;
                height: 36px;
            }

            .product-name {
                font-size: 0.68rem;
                -webkit-line-clamp: 1;
            }

            .product-price {
                font-size: 0.75rem;
            }

            .product-stock {
                font-size: 0.62rem;
            }

            .btn-add {
                padding: 0.3rem;
                font-size: 0.68rem;
                min-height: 30px;
            }

            .cart-header h2 {
                font-size: 0.88rem;
            }

            .cart-item {
                padding: 0.35rem 0.25rem;
            }

            .cart-item-name {
                font-size: 0.75rem;
            }

            .cart-item-price {
                font-size: 0.65rem;
            }

            .cart-item-subtotal {
                min-width: 40px;
                font-size: 0.75rem;
            }

            .quantity-btn {
                min-width: 40px;
                min-height: 40px;
                width: 24px;
                height: 24px;
            }

            .cart-item-quantity {
                font-size: 0.82rem;
            }

            .btn-checkout {
                padding: 0.45rem;
                font-size: 0.82rem;
            }

            .empty-cart-message {
                padding: 1rem 0.5rem;
                font-size: 0.78rem;
            }

            .toast-container {
                max-width: 92%;
                right: 4%;
            }

            .ai-suggestion-card {
                width: 220px;
                right: 55px;
                bottom: 12px;
            }
        }

        /* ── Tier 6: Phone portrait (≤360px) ── */
        @media (max-width: 360px) {
            .pos-main {
                grid-template-columns: 1fr;
                padding: 0.3rem;
                gap: 0.3rem;
            }

            .products-section,
            .cart-section {
                padding: 0.4rem;
            }

            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.25rem;
            }

            .product-card {
                padding: 0.4rem;
            }

            .product-image {
                height: 32px;
                font-size: 1.2rem;
            }

            .product-image img {
                width: 32px;
                height: 32px;
            }

            .product-name {
                font-size: 0.65rem;
                -webkit-line-clamp: 1;
            }

            .product-price {
                font-size: 0.7rem;
            }

            .product-stock {
                font-size: 0.58rem;
            }

            .btn-add {
                padding: 0.25rem;
                font-size: 0.62rem;
                min-height: 28px;
            }

            .cart-item {
                padding: 0.3rem 0.2rem;
            }

            .cart-item-name {
                font-size: 0.7rem;
            }

            .cart-item-price {
                font-size: 0.6rem;
            }

            .cart-item-subtotal {
                min-width: 36px;
                font-size: 0.7rem;
            }

            .quantity-btn {
                min-width: 36px;
                min-height: 36px;
                width: 22px;
                height: 22px;
                font-size: 0.85rem;
            }

            .cart-item-quantity {
                font-size: 0.78rem;
                min-width: 22px;
            }

            .btn-checkout {
                padding: 0.4rem;
                font-size: 0.78rem;
            }

            .payment-input input {
                font-size: 16px;
            }

            .empty-cart-message {
                padding: 0.8rem 0.4rem;
                font-size: 0.72rem;
            }

            .empty-cart-message i {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <div class="pos-container">
        <?php require __DIR__ . '/header.php'; ?>

        <div class="quota-container">
            <div class="quota-header">
                <div class="quota-title">
                    <i class='bx bx-target'></i>
                    <span>
                        <?php if (isAdmin()): ?>
                            Today's Sales Overview
                        <?php else: ?>
                            Shift Sales Quota (<?php echo $shift_type; ?> Shift)
                        <?php endif; ?>
                    </span>
                </div>
                <div class="quota-stats">
                    <span class="current">₱<?php echo number_format($shift_sales, 2); ?></span>
                    <span>/</span>
                    <span class="target">₱<?php echo number_format($shift_quota, 2); ?></span>
                </div>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?php echo $percentage; ?>%; --progress-color: <?php echo $progress_color; ?>; --progress-color-light: <?php echo $progress_color_light; ?>;"></div>
            </div>
            <div class="quota-footer">
                <span class="quota-message">
                    <?php
                    if (isAdmin()) {
                        echo '<i class="bx bx-chart"></i> Admin view - showing today\'s sales';
                    } elseif ($percentage >= 100) {
                        echo '<i class="bx bx-trophy"></i> Goal achieved! Great job!';
                    } elseif ($percentage >= 80) {
                        echo '<i class="bx bx-check-circle"></i> Almost there! Keep going!';
                    } elseif ($percentage >= 50) {
                        echo '<i class="bx bx-line-chart"></i> On track!';
                    } else {
                        echo '<i class="bx bx-hourglass"></i> Need more sales to reach goal';
                    }
                    ?>
                </span>
                <span class="quota-percentage"><?php echo round($percentage, 1); ?>% complete</span>
            </div>
        </div>

        <div class="toast-container" id="toast-container"></div>

        <div class="confirmation-modal" id="confirmation-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Confirm Order</h3>
                </div>
                <div class="modal-body">
                    <h3>Process this order?</h3>
                    <p>Please review the order details before confirming.</p>
                    <div class="order-details" id="order-details-preview"></div>
                    <p style="font-size: 0.85rem; color: var(--warning);">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button class="btn-cancel" onclick="closeConfirmationModal()">
                        <i class='bx bx-x'></i> Cancel
                    </button>
                    <button class="btn-confirm" id="confirm-order-btn">
                        <i class='bx bx-check'></i> Confirm Order
                    </button>
                </div>
            </div>
        </div>

        <main class="pos-main">
            <section class="products-section">
                <div class="section-header">
                    <h2>Products</h2>
                    <div class="stock-indicator <?php echo $total_alerts > 0 ? ($out_of_stock_items ? 'danger' : 'warning') : ''; ?>"
                        onclick="showLowStockModal()" title="Click to view stock alerts">
                        <i class='bx bx-package'></i>
                    </div>
                </div>

                <div class="search-filters">
                    <div class="search-box">
                        <span class="search-icon"><i class='bx bx-search'></i></span>
                        <input type="text" id="search" placeholder="Search products...">
                    </div>
                    <div class="category-filter">
                        <select id="category">
                            <option value="all">All Categories</option>
                            <option value="BIG TIME Burgers">BIGTIME Burgers</option>
                            <option value="MinuteBurgers">Minute Burgers</option>
                            <option value="Hotdogs">Hotdogs</option>
                            <option value="Drinks">Drinks</option>
                            <option value="Add-ons">Add-ons</option>
                        </select>
                    </div>
                </div>

                <div class="products-grid" id="products-grid">
                    <?php
                    $display_order = [
                        'BIG TIME Burgers' => 1,
                        'MinuteBurgers' => 2,
                        'Hotdogs' => 3,
                        'Drinks' => 4,
                        'Add-ons' => 5
                    ];

                    $sorted_products = $products;
                    usort($sorted_products, function ($a, $b) use ($display_order) {
                        $order_a = $display_order[$a['category']] ?? 999;
                        $order_b = $display_order[$b['category']] ?? 999;

                        if ($order_a == $order_b) {
                            return strcmp($a['name'], $b['name']);
                        }
                        return $order_a - $order_b;
                    });

                    foreach ($sorted_products as $product):
                        $is_low_stock = $product['stock'] > 0 && $product['stock'] < 10;
                        $is_out_of_stock = $product['stock'] == 0;
                        ?>
                        <div class="product-card" data-id="<?php echo $product['id']; ?>"
                            data-category="<?php echo $product['category']; ?>"
                            data-category-order="<?php echo $display_order[$product['category']] ?? 999; ?>"
                            data-stock="<?php echo $product['stock']; ?>">

                            <?php if ($is_low_stock): ?>
                                <div class="stock-badge warning">Low Stock</div>
                            <?php elseif ($is_out_of_stock): ?>
                                <div class="stock-badge danger">Out of Stock</div>
                            <?php endif; ?>

                            <div class="product-image">
                                <?php
                                $image_path = '../assets/images/products/' . ($product['image'] ?? 'default-product.png');
                                if (file_exists($image_path) && !empty($product['image']) && $product['image'] !== 'default-product.png'):
                                    ?>
                                    <img src="<?php echo $image_path; ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        onerror="this.style.display='none';">
                                <?php endif; ?>
                            </div>
                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                            <div class="product-stock <?php echo ($is_low_stock || $is_out_of_stock) ? 'low-stock' : ''; ?>">
                                <i class='bx bx-package'></i> Stock: <?php echo $product['stock']; ?>
                            </div>
                            <button class="btn-add" data-id="<?php echo $product['id']; ?>"
                                data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                data-price="<?php echo $product['price']; ?>" data-stock="<?php echo $product['stock']; ?>"
                                <?php echo $product['stock'] === 0 ? 'disabled' : ''; ?>>
                                <?php echo $product['stock'] === 0 ? '<i class="bx bx-x"></i> Out of Stock' : '<i class="bx bx-cart-add"></i> Add to Cart'; ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="cart-section">
                <div class="cart-header">
                    <h2>
                        <i class='bx bx-cart'></i> Order Summary
                        <span class="cart-count" id="cart-count">0</span>
                    </h2>
                    <button class="btn-clear" id="clear-cart">
                        <i class='bx bx-trash'></i> Clear
                    </button>
                </div>

                <div class="cart-items" id="cart-items">
                    <div class="empty-cart-message">
                        <i class='bx bx-cart' style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                        <p>Your cart is empty</p>
                        <p style="font-size: 0.9rem;">Click on products to add them to cart</p>
                    </div>
                </div>

                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="cart-total">₱0.00</span>
                    </div>

                    <div class="payment-section">
                        <form id="checkout-form" method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="total_amount" id="total-amount" value="0">
                            <input type="hidden" name="cart_items" id="cart-items-data" value="[]">
                            <input type="hidden" name="change" id="change-value" value="0">

                            <div class="payment-input">
                                <label for="cash">Cash Received:</label>
                                <input type="number" id="cash" name="payment" placeholder="0.00" step="0.01" min="0" max="1000000" required>
                                <div id="cash-warning" class="payment-warning" style="display: none;">
                                    <i class='bx bx-error-circle'></i>
                                    <span id="cash-warning-text"></span>
                                </div>
                            </div>

                            <div class="payment-change" id="change-display">
                                Change: <span id="change">₱0.00</span>
                            </div>

                            <button type="button" class="btn-checkout" id="checkout" disabled>
                                Complete Order
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div class="ai-assistant" id="ai-assistant">
        <div class="ai-burger-icon" onclick="toggleAISuggestion()">
            <span>🍔</span>
        </div>
        <div class="ai-suggestion-card" id="ai-suggestion-card">
            <div class="ai-card-header">
                <h3>
                    <i class='bx bx-bulb'></i>
                    AI Assistant
                </h3>
                <button class="ai-close" aria-label="Close AI suggestion" onclick="closeAISuggestion()">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="ai-card-body">
                <div class="ai-suggestion-text" id="ai-suggestion-text">
                    Click the burger icon to get suggestions based on your change amount!
                </div>
                <div class="ai-suggestion-products" id="ai-suggestion-products" style="display: none;"></div>
            </div>
            <div class="ai-card-footer">
                Powered by AI • Suggestions based on change amount
            </div>
        </div>
    </div>

    <div class="modal" id="low-stock-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class='bx bx-error-circle'></i>
                    Stock Alerts (<?php echo $total_alerts; ?>)
                </h3>
                <button class="modal-close" aria-label="Close modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (count($out_of_stock_items) > 0): ?>
                    <div class="alert-section danger">
                        <h4>
                            <i class='bx bx-x-circle'></i>
                            Out of Stock (<?php echo count($out_of_stock_items); ?>)
                        </h4>
                        <div class="alert-list">
                            <?php foreach ($out_of_stock_items as $item): ?>
                                <div class="alert-item danger">
                                    <span class="alert-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                    <span class="alert-quantity">0</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (count($low_stock_items) > 0): ?>
                    <div class="alert-section warning">
                        <h4>
                            <i class='bx bx-error'></i>
                            Low Stock (Below 10) - <?php echo count($low_stock_items); ?>
                        </h4>
                        <div class="alert-list">
                            <?php foreach ($low_stock_items as $item): ?>
                                <div class="alert-item warning">
                                    <span class="alert-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                    <span class="alert-quantity"><?php echo $item['stock']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($total_alerts === 0): ?>
                    <div style="text-align: center; padding: 2rem;">
                        <i class='bx bx-check-circle' style="font-size: 4rem; color: var(--success);"></i>
                        <p style="margin-top: 1rem; color: var(--success); font-weight: 600;">All items are well stocked!</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button class="btn-modal btn-modal-primary" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        const productsData = <?php echo json_encode($products); ?>;
        const totalAlerts = <?php echo $total_alerts; ?>;
        const MAX_CASH = 1000000;
        const WARNING_CASH = 100000;

        const categoryOrder = {
            'BIG TIME Burgers': 1,
            'MinuteBurgers': 2,
            'Hotdogs': 3,
            'Drinks': 4,
            'Add-ons': 5
        };

        let cart = [];
        let lastSuggestionTotal = 0;
        let lastSuggestionCash = 0;

        function toggleAISuggestion() {
            const card = document.getElementById('ai-suggestion-card');
            if (card.classList.contains('show')) {
                card.classList.remove('show');
            } else {
                card.classList.add('show');
                updateAISuggestion();
            }
        }

        function closeAISuggestion() {
            document.getElementById('ai-suggestion-card').classList.remove('show');
        }

        function updateAISuggestion() {
            const total = parseFloat(document.getElementById('total-amount').value) || 0;
            const cash = parseFloat(document.getElementById('cash').value) || 0;
            const change = cash - total;
            
            if (change <= 0) {
                document.getElementById('ai-suggestion-text').innerHTML = 'No change amount available. Enter cash amount to see add-on suggestions.';
                document.getElementById('ai-suggestion-products').style.display = 'none';
                return;
            }
            
            fetch(`?get_ai_suggestion=1&change=${change}&total=${total}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('ai-suggestion-text').innerHTML = data.message;
                        
                        if (data.suggestions && data.suggestions.length > 0) {
                            let productsHtml = '<div style="margin-top: 8px;"><strong>Add-on suggestions:</strong></div>';
                            data.suggestions.forEach(s => {
                                productsHtml += `
                                    <div class="suggestion-item">
                                        <span class="suggestion-name">${s.name}</span>
                                        <div>
                                            <span class="suggestion-price">₱${s.price.toFixed(2)}</span>
                                            ${s.difference > 0 ? `<span class="suggestion-difference"> (₱${s.difference.toFixed(2)} left)</span>` : ''}
                                        </div>
                                    </div>
                                `;
                            });
                            document.getElementById('ai-suggestion-products').innerHTML = productsHtml;
                            document.getElementById('ai-suggestion-products').style.display = 'block';
                        } else {
                            document.getElementById('ai-suggestion-products').style.display = 'none';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching AI suggestion:', error);
                });
        }

        function saveCartToStorage() {
            sessionStorage.setItem('pos_cart', JSON.stringify(cart));
        }

        function loadCartFromStorage() {
            const savedCart = sessionStorage.getItem('pos_cart');
            if (savedCart) {
                try {
                    const parsedCart = JSON.parse(savedCart);
                    const validCart = parsedCart.filter(item => {
                        const product = productsData.find(p => p.id === item.id);
                        if (!product) return false;
                        if (product.stock < item.quantity) return false;
                        return true;
                    });
                    cart = validCart;
                    updateCartDisplay();
                } catch (e) {
                    console.error('Error loading cart:', e);
                }
            }
        }

        function clearSavedCart() {
            sessionStorage.removeItem('pos_cart');
        }

        function validateCashAmount(value) {
            const cashAmount = parseFloat(value) || 0;
            const warningDiv = document.getElementById('cash-warning');
            const warningText = document.getElementById('cash-warning-text');
            const cashInput = document.getElementById('cash');
            
            if (cashAmount > MAX_CASH) {
                warningDiv.style.display = 'flex';
                warningText.innerHTML = `Cash amount exceeds ₱${MAX_CASH.toLocaleString()}. Maximum allowed is ₱${MAX_CASH.toLocaleString()}.`;
                cashInput.classList.add('warning');
                return false;
            } else if (cashAmount > WARNING_CASH) {
                warningDiv.style.display = 'flex';
                warningText.innerHTML = `Warning: Cash amount (₱${cashAmount.toLocaleString()}) exceeds ₱${WARNING_CASH.toLocaleString()}. Please verify the amount with your manager.`;
                cashInput.classList.add('warning');
                return true;
            } else {
                warningDiv.style.display = 'none';
                cashInput.classList.remove('warning');
                return true;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            loadCartFromStorage();
            
            setInterval(checkAlerts, 300000);

            document.querySelector('.modal-close').addEventListener('click', function () {
                closeModal();
            });

            document.getElementById('low-stock-modal').addEventListener('click', function (e) {
                if (e.target === this) {
                    closeModal();
                }
            });

            document.getElementById('clear-cart').addEventListener('click', function () {
                if (cart.length > 0) {
                    askConfirmCallback('Are you sure you want to clear the cart?', function () {
                        cart = [];
                        updateCartDisplay();
                        clearSavedCart();
                    });
                }
            });

            document.getElementById('cash').addEventListener('input', function () {
                let value = parseFloat(this.value) || 0;
                if (value < 0) {
                    this.value = 0;
                    value = 0;
                }
                if (value > MAX_CASH) {
                    this.value = MAX_CASH;
                    value = MAX_CASH;
                }
                validateCashAmount(this.value);
                calculateChange();
                updateAISuggestion();
            });

            document.querySelectorAll('.btn-add').forEach(button => {
                button.addEventListener('click', function () {
                    const productId = parseInt(this.getAttribute('data-id'));
                    const productName = this.getAttribute('data-name');
                    const productPrice = parseFloat(this.getAttribute('data-price'));
                    const productStock = parseInt(this.getAttribute('data-stock'));

                    addToCart(productId, productName, productPrice, productStock);
                });
            });

            let searchTimeout;
            document.getElementById('search').addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    filterProducts();
                }, 300);
            });

            document.getElementById('category').addEventListener('change', function () {
                filterProducts();
            });

            document.getElementById('checkout').addEventListener('click', function () {
                const cash = parseFloat(document.getElementById('cash').value) || 0;
                const total = parseFloat(document.getElementById('total-amount').value) || 0;
                
                if (cart.length === 0) {
                    showToast('error', 'Cart Empty', 'Please add items to cart before checkout.');
                    return;
                }
                
                if (cash < total) {
                    showToast('error', 'Insufficient Payment', 'Cash received must be greater than or equal to total amount.');
                    return;
                }
                
                if (cash <= 0) {
                    showToast('error', 'Invalid Payment', 'Please enter cash amount.');
                    return;
                }
                
                if (cash > MAX_CASH) {
                    showToast('error', 'Invalid Amount', `Cash amount cannot exceed ₱${MAX_CASH.toLocaleString()}.`);
                    return;
                }
                
                showConfirmationModal();
            });

            updateCartDisplay();
        });

        function showConfirmationModal() {
            const modal = document.getElementById('confirmation-modal');
            const orderDetailsDiv = document.getElementById('order-details-preview');
            const total = parseFloat(document.getElementById('total-amount').value) || 0;
            const cash = parseFloat(document.getElementById('cash').value) || 0;
            const change = cash - total;
            
            let orderHtml = `
                <div style="margin-bottom: 0.5rem;">
                    <strong>Items:</strong>
                </div>
                <div style="max-height: 150px; overflow-y: auto; margin-bottom: 0.75rem;">
            `;
            
            cart.forEach(item => {
                orderHtml += `
                    <div style="display: flex; justify-content: space-between; padding: 0.25rem 0; border-bottom: 1px solid #eee;">
                        <span>${escapeHtml(item.name)} x ${item.quantity}</span>
                        <span>₱${(item.price * item.quantity).toFixed(2)}</span>
                    </div>
                `;
            });
            
            orderHtml += `
                </div>
                <div style="border-top: 1px solid #ddd; padding-top: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span>Total:</span>
                        <span><strong>₱${total.toFixed(2)}</strong></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span>Cash:</span>
                        <span>₱${cash.toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Change:</span>
                        <span style="color: var(--success);">₱${change.toFixed(2)}</span>
                    </div>
                </div>
            `;
            
            orderDetailsDiv.innerHTML = orderHtml;
            
            const confirmBtn = document.getElementById('confirm-order-btn');
            const oldConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(oldConfirmBtn, confirmBtn);
            
            oldConfirmBtn.addEventListener('click', function() {
                closeConfirmationModal();
                submitOrder();
            });
            
            modal.style.display = 'flex';
        }
        
        function closeConfirmationModal() {
            document.getElementById('confirmation-modal').style.display = 'none';
        }
        
        function submitOrder() {
            clearSavedCart();
            
            const form = document.getElementById('checkout-form');
            const cashInput = document.getElementById('cash');
            const totalAmountInput = document.getElementById('total-amount');
            const cartItemsInput = document.getElementById('cart-items-data');
            const changeInput = document.getElementById('change-value');
            
            const csrfInput = form.querySelector('input[name="csrf_token"]');
            const csrfToken = csrfInput ? csrfInput.value : '';
            
            const submitForm = document.createElement('form');
            submitForm.method = 'POST';
            submitForm.action = '';
            
            const csrfField = document.createElement('input');
            csrfField.type = 'hidden';
            csrfField.name = 'csrf_token';
            csrfField.value = csrfToken;
            submitForm.appendChild(csrfField);
            
            const totalField = document.createElement('input');
            totalField.type = 'hidden';
            totalField.name = 'total_amount';
            totalField.value = totalAmountInput.value;
            submitForm.appendChild(totalField);
            
            const cartField = document.createElement('input');
            cartField.type = 'hidden';
            cartField.name = 'cart_items';
            cartField.value = cartItemsInput.value;
            submitForm.appendChild(cartField);
            
            const changeField = document.createElement('input');
            changeField.type = 'hidden';
            changeField.name = 'change';
            changeField.value = changeInput.value;
            submitForm.appendChild(changeField);
            
            const paymentField = document.createElement('input');
            paymentField.type = 'hidden';
            paymentField.name = 'payment';
            paymentField.value = cashInput.value;
            submitForm.appendChild(paymentField);
            
            const checkoutField = document.createElement('input');
            checkoutField.type = 'hidden';
            checkoutField.name = 'checkout';
            checkoutField.value = '1';
            submitForm.appendChild(checkoutField);
            
            document.body.appendChild(submitForm);
            submitForm.submit();
        }

        function showToast(type, title, message, duration = 5000) {
            if (type === 'success' || type === 'info') {
                return;
            }

            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;

            const icons = {
                success: 'bx-check-circle',
                error: 'bx-error-circle',
                warning: 'bx-error',
                info: 'bx-info-circle'
            };

            toast.innerHTML = `
                <i class='bx ${icons[type]}'></i>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <span class="toast-close"><i class='bx bx-x'></i></span>
            `;

            toast.querySelector('.toast-close').addEventListener('click', function (e) {
                e.stopPropagation();
                removeToast(toast);
            });

            container.appendChild(toast);

            setTimeout(() => {
                removeToast(toast);
            }, duration);
        }

        function removeToast(toast) {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 300);
        }

        function checkAlerts() {
            fetch('?check_alerts=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const alertCount = document.getElementById('alert-count');
                        if (alertCount) {
                            alertCount.textContent = data.total_alerts;
                        }

                        if (data.out_of_stock_count > 0 || data.low_stock_count > 0) {
                            let message = '';
                            if (data.out_of_stock_count > 0) {
                                message += `${data.out_of_stock_count} item(s) out of stock. `;
                            }
                            if (data.low_stock_count > 0) {
                                message += `${data.low_stock_count} item(s) low on stock.`;
                            }
                            showToast('warning', 'Stock Alert Updated', message);
                        }
                    }
                })
                .catch(error => console.error('Error checking alerts:', error));
        }

        function addToCart(productId, productName, productPrice, productStock) {
            const existingItem = cart.find(item => item.id === productId);

            if (existingItem) {
                if (existingItem.quantity >= productStock) {
                    showToast('warning', 'Stock Limit', `Cannot add more ${productName}. Only ${productStock} available.`);
                    return;
                }
                existingItem.quantity += 1;
                existingItem.subtotal = existingItem.quantity * existingItem.price;
            } else {
                if (productStock < 1) {
                    showToast('error', 'Out of Stock', `${productName} is out of stock.`);
                    return;
                }
                cart.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    quantity: 1,
                    subtotal: productPrice
                });
            }

            updateCartDisplay();
            saveCartToStorage();
        }

        function updateCartDisplay() {
            const cartItemsContainer = document.getElementById('cart-items');
            const cartTotalElement = document.getElementById('cart-total');
            const cartCountElement = document.getElementById('cart-count');
            const totalAmountInput = document.getElementById('total-amount');
            const cartItemsDataInput = document.getElementById('cart-items-data');
            const checkoutButton = document.getElementById('checkout');

            if (cart.length === 0) {
                cartItemsContainer.innerHTML = `
                    <div class="empty-cart-message">
                        <i class='bx bx-cart' style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                        <p>Your cart is empty</p>
                        <p style="font-size: 0.9rem;">Click on products to add them to cart</p>
                    </div>
                `;
                cartTotalElement.textContent = '₱0.00';
                cartCountElement.textContent = '0';
                totalAmountInput.value = '0';
                cartItemsDataInput.value = '[]';
                checkoutButton.disabled = true;
                return;
            }

            let total = 0;
            let cartHTML = '';

            cart.forEach((item, index) => {
                const subtotal = item.price * item.quantity;
                total += subtotal;

                cartHTML += `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${escapeHtml(item.name)}</div>
                            <div class="cart-item-price">₱${item.price.toFixed(2)} each</div>
                        </div>
                        <div class="cart-item-controls">
                            <button class="quantity-btn" aria-label="Decrease quantity" onclick="updateQuantity(${index}, -1)">-</button>
                            <span class="cart-item-quantity">${item.quantity}</span>
                            <button class="quantity-btn" aria-label="Increase quantity" onclick="updateQuantity(${index}, 1)" ${item.quantity >= getProductStock(item.id) ? 'disabled' : ''}>+</button>
                        </div>
                        <div class="cart-item-subtotal">₱${subtotal.toFixed(2)}</div>
                    </div>
                `;
            });

            cartItemsContainer.innerHTML = cartHTML;
            cartTotalElement.textContent = `₱${total.toFixed(2)}`;
            cartCountElement.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
            totalAmountInput.value = total.toFixed(2);
            cartItemsDataInput.value = JSON.stringify(cart);
            checkoutButton.disabled = false;

            calculateChange();
            updateAISuggestion();
        }

        function getProductStock(productId) {
            const product = productsData.find(p => p.id === productId);
            return product ? product.stock : 0;
        }

        function updateQuantity(index, change) {
            const product = productsData.find(p => p.id === cart[index].id);
            const newQuantity = cart[index].quantity + change;

            if (newQuantity <= 0) {
                cart.splice(index, 1);
            } else if (product && newQuantity > product.stock) {
                showToast('warning', 'Stock Limit', `Cannot add more ${cart[index].name}. Only ${product.stock} available.`);
                return;
            } else {
                cart[index].quantity = newQuantity;
                cart[index].subtotal = cart[index].quantity * cart[index].price;
            }

            updateCartDisplay();
            saveCartToStorage();
        }

        function calculateChange() {
            const cashInput = document.getElementById('cash');
            const changeElement = document.getElementById('change');
            const changeDisplay = document.getElementById('change-display');
            const changeValueInput = document.getElementById('change-value');
            const total = parseFloat(document.getElementById('total-amount').value) || 0;
            let cash = parseFloat(cashInput.value) || 0;

            if (cash > MAX_CASH) {
                cash = MAX_CASH;
                cashInput.value = MAX_CASH;
            }

            const change = cash - total;

            if (change >= 0) {
                changeElement.textContent = `₱${change.toFixed(2)}`;
                changeValueInput.value = change.toFixed(2);
                changeDisplay.style.backgroundColor = 'var(--light-gray)';
                changeDisplay.style.color = 'var(--harvest-orange)';
            } else {
                changeElement.textContent = `₱0.00`;
                changeValueInput.value = '0';
                changeDisplay.style.backgroundColor = '#f8d7da';
                changeDisplay.style.color = '#721c24';
            }
        }

        function filterProducts() {
            const searchTerm = document.getElementById('search').value.toLowerCase().trim();
            const categoryFilter = document.getElementById('category').value;

            const productCards = Array.from(document.querySelectorAll('.product-card'));

            const filteredCards = productCards.filter(card => {
                const productName = card.querySelector('.product-name').textContent.toLowerCase();
                const productCategory = card.getAttribute('data-category');

                const matchesSearch = productName.includes(searchTerm);
                const matchesCategory = categoryFilter === 'all' || productCategory === categoryFilter;

                return matchesSearch && matchesCategory;
            });

            filteredCards.sort((a, b) => {
                const orderA = parseInt(a.getAttribute('data-category-order')) || 999;
                const orderB = parseInt(b.getAttribute('data-category-order')) || 999;

                if (orderA === orderB) {
                    const nameA = a.querySelector('.product-name').textContent;
                    const nameB = b.querySelector('.product-name').textContent;
                    return nameA.localeCompare(nameB);
                }
                return orderA - orderB;
            });

            productCards.forEach(card => {
                card.style.display = 'none';
            });

            filteredCards.forEach(card => {
                card.style.display = 'flex';
            });

            const productsGrid = document.getElementById('products-grid');
            const existingMessage = document.getElementById('no-results-message');

            if (filteredCards.length === 0) {
                if (!existingMessage) {
                    const noResults = document.createElement('div');
                    noResults.id = 'no-results-message';
                    noResults.style.textAlign = 'center';
                    noResults.style.padding = '2rem';
                    noResults.style.color = '#666';
                    noResults.style.width = '100%';
                    noResults.style.gridColumn = '1 / -1';
                    noResults.innerHTML = `
                        <i class='bx bx-search' style="font-size: 3rem; color: #ccc;"></i>
                        <p>No products found matching your search.</p>
                    `;
                    productsGrid.appendChild(noResults);
                }
            } else if (existingMessage) {
                existingMessage.remove();
            }
        }

        function showLowStockModal() {
            document.getElementById('low-stock-modal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('low-stock-modal').style.display = 'none';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
    <?php include __DIR__ . '/../includes/confirm_modal.php'; ?>
</body>

</html>