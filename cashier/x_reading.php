<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requirePermission('pos_access');

$page_title = 'X Reading Report';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Check if cashier has active shift
$stmt = $pdo->prepare("
    SELECT * FROM cashier_shifts 
    WHERE cashier_id = ? AND status = 'active'
    ORDER BY id DESC LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$active_shift = $stmt->fetch();

if (!$active_shift) {
    header('Location: start_shift.php?message=' . urlencode('Please start your shift first before viewing X Reading.') . '&type=warning');
    exit();
}

$shift_id = $active_shift['id'];
$shift_type = $active_shift['shift_type'];
$shift_date = $active_shift['shift_date'];
$opening_cash = $active_shift['opening_cash'];
$shift_start_time = $active_shift['start_time'];
$cashier_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);

$message = '';
$message_type = '';

// Shift sales quota (use actual shift quota, not hardcoded)
$shift_quota = $active_shift['shift_quota'] ?? 10000;

// Get current shift's total sales
try {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as shift_sales 
        FROM orders 
        WHERE shift_id = ?
    ");
    $stmt->execute([$shift_id]);
    $shift_sales = $stmt->fetch(PDO::FETCH_ASSOC)['shift_sales'];

    // Calculate percentage for progress bar
    $percentage = min(($shift_sales / $shift_quota) * 100, 100);

    // Determine color based on percentage
    if ($percentage < 50) {
        $progress_color = '#e74c3c'; // Red - below 50%
        $progress_color_light = '#e74c3c';
    } elseif ($percentage < 80) {
        $progress_color = '#f39c12'; // Yellow - 50-80%
        $progress_color_light = '#f39c12';
    } else {
        $progress_color = '#27ae60'; // Green - 80% and above
        $progress_color_light = '#27ae60';
    }

} catch (PDOException $e) {
    $shift_sales = 0;
    $percentage = 0;
    $progress_color = '#e74c3c';
    $progress_color_light = '#e74c3c';
}

// Current time for display
$current_time = date('h:i A');
$current_date = date('F j, Y');
$current_day = date('l');

// Calculate shift duration in seconds
$shift_start_ts = strtotime($shift_start_time);
$shift_seconds = time() - $shift_start_ts;

// Format shift duration
if ($shift_seconds < 3600) {
    $minutes = round($shift_seconds / 60);
    $shift_duration_display = $minutes . ' min' . ($minutes != 1 ? 's' : '');
} else {
    $hours = floor($shift_seconds / 3600);
    $minutes = floor(($shift_seconds % 3600) / 60);
    if ($minutes > 0) {
        $shift_duration_display = $hours . ' hr ' . $minutes . ' min';
    } else {
        $shift_duration_display = $hours . ' hr' . ($hours != 1 ? 's' : '');
    }
}

// Calculate remaining time in shift
$shift_start_datetime = new DateTime($shift_start_time);
$shift_end_datetime = clone $shift_start_datetime;

// Set shift end time based on shift type
if ($shift_type == 'AM') {
    // AM shift: 6 AM to 6 PM
    $shift_end_datetime->setTime(18, 0, 0);
} else {
    // PM shift: 6 PM to 6 AM next day
    $shift_end_datetime->setTime(6, 0, 0);
    $shift_end_datetime->modify('+1 day');
}

$current_datetime = new DateTime();
$remaining_interval = $current_datetime->diff($shift_end_datetime);

if ($remaining_interval->invert == 1) {
    $remaining_display = 'Shift ending soon';
} elseif ($remaining_interval->h == 0 && $remaining_interval->i > 0) {
    $remaining_display = $remaining_interval->i . ' minute' . ($remaining_interval->i != 1 ? 's' : '') . ' left';
} elseif ($remaining_interval->h > 0) {
    $remaining_display = $remaining_interval->h . ' hour' . ($remaining_interval->h != 1 ? 's' : '') . ' left';
} else {
    $remaining_display = 'Shift ended';
}

// Fetch shift sales data
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_transactions,
        COALESCE(SUM(total_amount), 0) as total_sales,
        COALESCE(AVG(total_amount), 0) as average_transaction,
        COALESCE(MAX(total_amount), 0) as highest_transaction,
        COALESCE(MIN(total_amount), 0) as lowest_transaction
    FROM orders
    WHERE shift_id = ?
");
$stmt->execute([$shift_id]);
$totals = $stmt->fetch(PDO::FETCH_ASSOC);

// Get total items sold
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(oi.quantity), 0) as total_items
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    WHERE o.shift_id = ?
");
$stmt->execute([$shift_id]);
$total_items = $stmt->fetch(PDO::FETCH_ASSOC)['total_items'];

// Get hourly breakdown
$stmt = $pdo->prepare("
    SELECT 
        HOUR(date_time) as hour,
        COUNT(*) as transactions,
        COALESCE(SUM(total_amount), 0) as sales
    FROM orders
    WHERE shift_id = ?
    GROUP BY HOUR(date_time)
    ORDER BY hour ASC
");
$stmt->execute([$shift_id]);
$hourly_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top selling products
$stmt = $pdo->prepare("
    SELECT 
        p.name as product_name,
        COALESCE(SUM(oi.quantity), 0) as quantity_sold,
        COALESCE(SUM(oi.subtotal), 0) as total_revenue
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    INNER JOIN products p ON oi.product_id = p.id
    WHERE o.shift_id = ?
    GROUP BY oi.product_id, p.name
    ORDER BY total_revenue DESC
    LIMIT 10
");
$stmt->execute([$shift_id]);
$top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get cash drops
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(drop_amount), 0) as total_drops
    FROM cash_drop_log
    WHERE shift_id = ?
");
$stmt->execute([$shift_id]);
$cash_drops = $stmt->fetch(PDO::FETCH_ASSOC)['total_drops'];

// Calculate expected cash
$expected_cash = $opening_cash + $totals['total_sales'] - $cash_drops;

// Get low stock alerts
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM inventory
    WHERE quantity <= min_stock AND deleted_at IS NULL
");
$stmt->execute();
$low_stock_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Handle saving X Reading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_reading'])) {
    requireCsrfToken();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO x_reading_log 
            (shift_id, reading_time, total_sales, total_transactions, 
             average_transaction, highest_transaction, lowest_transaction, 
             total_items_sold, generated_by)
            VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $shift_id,
            $totals['total_sales'],
            $totals['total_transactions'],
            $totals['average_transaction'],
            $totals['highest_transaction'],
            $totals['lowest_transaction'],
            $total_items,
            $_SESSION['user_id']
        ]);

        $message = "X Reading saved successfully!";
        $message_type = "success";
    } catch (Exception $e) {
        $message = "Error saving X Reading: " . $e->getMessage();
        $message_type = "error";
    }
}

// Get last X reading
$stmt = $pdo->prepare("
    SELECT reading_time, total_sales, total_transactions
    FROM x_reading_log
    WHERE shift_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$shift_id]);
$last_reading = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>X Reading Report - Minute Burger</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet"
        href="../assets/css/admin.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/admin.css'); ?>">
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
            min-height: 100vh;
        }

        .report-container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Shift Sales Quota Progress Bar */
        .quota-container {
            background: var(--white);
            margin: 0;
            padding: 1rem 1.5rem;
            box-shadow: var(--shadow);
            border-bottom: 1px solid var(--apricot-cream);
        }

        .quota-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .quota-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: var(--dark-gray);
        }

        .quota-title i {
            color: var(--harvest-orange);
            font-size: 1.2rem;
        }

        .quota-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
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
            height: 12px;
            overflow: hidden;
            margin-bottom: 0.5rem;
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
            font-size: 0.75rem;
            color: #666;
        }

        .quota-message {
            font-weight: 500;
        }

        .quota-message i {
            margin-right: 0.25rem;
        }

        /* Mid Shift Container */
        .mid-container {
            max-width: 800px;
            margin: 1.5rem auto;
        }

        .mid-header-bar {
            background: linear-gradient(135deg, var(--harvest-orange), var(--chocolate));
            color: white;
            padding: 1.1rem 1.5rem;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .mid-title {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .mid-title i {
            font-size: 1.35rem;
        }

        .mid-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .mid-meta .shift-badge {
            background: rgba(255, 255, 255, 0.25);
            padding: 0.2rem 0.65rem;
            border-radius: 20px;
            font-size: 0.68rem;
            font-weight: 600;
        }

        .mid-meta .duration {
            font-size: 0.75rem;
            opacity: 0.9;
        }

        /* Mini Cash Row */
        .mini-cash-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            background: var(--apricot-cream);
            box-shadow: var(--shadow);
        }

        .mini-cash-item {
            background: var(--white);
            padding: 0.85rem;
            text-align: center;
        }

        .mini-cash-label {
            display: block;
            font-size: 0.63rem;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.15rem;
        }

        .mini-cash-value {
            display: block;
            font-size: 1rem;
            font-weight: 700;
            color: var(--dark-gray);
        }

        /* Sections */
        .mid-section {
            background: var(--white);
            box-shadow: var(--shadow);
        }

        .mid-section-header {
            background: linear-gradient(135deg, var(--harvest-orange), var(--copperwood));
            color: white;
            padding: 0.7rem 1.25rem;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .mid-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: var(--apricot-cream);
        }

        .mid-stat {
            background: var(--white);
            padding: 1rem 0.5rem;
            text-align: center;
        }

        .mid-stat-value {
            display: block;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--harvest-orange);
            margin-bottom: 0.1rem;
        }

        .mid-stat-label {
            display: block;
            font-size: 0.63rem;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Action Buttons */
        .mid-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            padding: 1rem 0;
        }

        .mid-btn {
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .mid-btn-primary {
            background: #27ae60;
            color: white;
        }

        .mid-btn-primary:hover {
            background: #229954;
            transform: translateY(-2px);
        }

        .mid-btn-secondary {
            background: var(--success);
            color: white;
        }

        .mid-btn-secondary:hover {
            background: #229954;
            transform: translateY(-2px);
        }

        /* Tables */
        .mid-table-wrap {
            overflow-x: auto;
        }

        .mid-table {
            width: 100%;
            border-collapse: collapse;
        }

        .mid-table th {
            text-align: left;
            padding: 0.65rem 0.85rem;
            background: var(--light-gray);
            color: var(--dark-gray);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 2px solid var(--apricot-cream);
        }

        .mid-table td {
            padding: 0.6rem 0.85rem;
            border-bottom: 1px solid var(--light-gray);
            font-size: 0.82rem;
            color: #555;
        }

        .mid-table tr:last-child td {
            border-bottom: none;
        }

        .mid-table tr:hover {
            background: #FFF5E8;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Alert */
        .mid-alert {
            background: #FFF8E1;
            border: 1px solid #FFE082;
            border-left: 4px solid var(--warning);
            padding: 0.85rem 1rem;
            border-radius: 10px;
            font-size: 0.83rem;
            color: #8D6E00;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .mid-alert i {
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        /* Footer */
        .mid-footer {
            text-align: center;
            padding: 1rem;
            font-size: 0.72rem;
            color: #aaa;
        }

        /* Receipt Print Version - Hidden by default */
        .receipt-print {
            display: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .report-container {
                padding: 10px;
            }

            .mid-container {
                margin: 1rem auto;
            }

            .mid-header-bar {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }

            .mid-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .mid-actions {
                flex-direction: column;
            }

            .mid-btn {
                width: 100%;
                justify-content: center;
            }

            .quota-stats {
                flex-direction: column;
                gap: 0.25rem;
            }
        }

        @media (max-width: 480px) {
            .mini-cash-row {
                grid-template-columns: 1fr;
            }

            .mid-stats-grid {
                grid-template-columns: 1fr;
            }

            .mid-table th,
            .mid-table td {
                padding: 0.45rem;
                font-size: 0.72rem;
            }
        }

        /* Print Styles for Receipt */
        @media print {
            body * {
                visibility: hidden;
            }

            .receipt-print,
            .receipt-print * {
                visibility: visible;
            }

            .receipt-print {
                display: block !important;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                font-family: 'Courier New', monospace;
            }
        }
    </style>
</head>

<body>
    <?php require __DIR__ . '/header.php'; ?>
    <div class="report-container">

        <!-- Shift Sales Quota Progress Bar -->
        <div class="quota-container">
            <div class="quota-header">
                <div class="quota-title">
                    <i class='bx bx-target'></i>
                    <span>Shift Sales Quota (<?php echo $shift_type; ?> Shift)</span>
                </div>
                <div class="quota-stats">
                    <span class="current">₱<?php echo number_format($shift_sales, 2); ?></span>
                    <span>/</span>
                    <span class="target">₱<?php echo number_format($shift_quota, 2); ?></span>
                </div>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar"
                    style="width: <?php echo $percentage; ?>%; --progress-color: <?php echo $progress_color; ?>; --progress-color-light: <?php echo $progress_color_light; ?>;">
                </div>
            </div>
            <div class="quota-footer">
                <span class="quota-message">
                    <?php
                    if ($percentage >= 100) {
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

        <div class="mid-container">
            <div class="mid-header-bar">
                <div class="mid-title">
                    <i class='bx bx-line-chart'></i>
                    <span>Mid Shift Report</span>
                </div>
                <div class="mid-meta">
                    <span class="shift-badge"><?php echo $shift_type; ?> SHIFT</span>
                    <span class="duration"><?php echo $shift_duration_display; ?></span>
                </div>
            </div>

            <div class="mini-cash-row">
                <div class="mini-cash-item">
                    <span class="mini-cash-label">Opening Cash</span>
                    <span class="mini-cash-value">₱<?php echo number_format($opening_cash, 2); ?></span>
                </div>
                <div class="mini-cash-item">
                    <span class="mini-cash-label">Expected Cash</span>
                    <span class="mini-cash-value">₱<?php echo number_format($expected_cash, 2); ?></span>
                </div>
                <div class="mini-cash-item">
                    <span class="mini-cash-label">Cash Drops</span>
                    <span class="mini-cash-value">₱<?php echo number_format($cash_drops, 2); ?></span>
                </div>
            </div>

            <div class="mid-section">
                <div class="mid-section-header">
                    <i class='bx bx-dollar-circle'></i> Sales Performance
                </div>
                <div class="mid-stats-grid">
                    <div class="mid-stat">
                        <span class="mid-stat-value">₱<?php echo number_format($totals['total_sales'], 2); ?></span>
                        <span class="mid-stat-label">Total Sales</span>
                    </div>
                    <div class="mid-stat">
                        <span class="mid-stat-value"><?php echo $totals['total_transactions']; ?></span>
                        <span class="mid-stat-label">Transactions</span>
                    </div>
                    <div class="mid-stat">
                        <span
                            class="mid-stat-value">₱<?php echo number_format($totals['average_transaction'], 2); ?></span>
                        <span class="mid-stat-label">Avg Transaction</span>
                    </div>
                    <div class="mid-stat">
                        <span class="mid-stat-value"><?php echo $total_items; ?></span>
                        <span class="mid-stat-label">Items Sold</span>
                    </div>
                </div>
            </div>

            <div class="mid-section">
                <div class="mid-section-header">
                    <i class='bx bx-detail'></i> Performance Detail
                </div>
                <div class="mid-stats-grid">
                    <div class="mid-stat">
                        <span
                            class="mid-stat-value">₱<?php echo number_format($totals['highest_transaction'], 2); ?></span>
                        <span class="mid-stat-label">Highest Transaction</span>
                    </div>
                    <div class="mid-stat">
                        <span
                            class="mid-stat-value">₱<?php echo number_format($totals['lowest_transaction'], 2); ?></span>
                        <span class="mid-stat-label">Lowest Transaction</span>
                    </div>
                    <div class="mid-stat">
                        <span
                            class="mid-stat-value">₱<?php echo number_format($shift_seconds > 0 ? $totals['total_sales'] / ($shift_seconds / 3600) : 0, 2); ?></span>
                        <span class="mid-stat-label">Hourly Rate</span>
                    </div>
                    <div class="mid-stat">
                        <span class="mid-stat-value">₱<?php echo number_format($cash_drops, 2); ?></span>
                        <span class="mid-stat-label">Cash Drops</span>
                    </div>
                </div>
            </div>

            <div class="mid-actions">
                <button onclick="printReceipt()" class="mid-btn mid-btn-primary">
                    <i class='bx bx-printer'></i> Print Receipt
                </button>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <button type="submit" name="save_reading" class="mid-btn mid-btn-secondary">
                        <i class='bx bx-save'></i> Save Report
                    </button>
                </form>
            </div>

            <?php if (!empty($hourly_breakdown)): ?>
                <div class="mid-section">
                    <div class="mid-section-header">
                        <i class='bx bx-time-five'></i> Hourly Sales Breakdown
                    </div>
                    <div class="mid-table-wrap">
                        <table class="mid-table">
                            <thead>
                                <tr>
                                    <th>Time Period</th>
                                    <th class="text-center">Transactions</th>
                                    <th class="text-right">Sales Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hourly_breakdown as $h): ?>
                                    <tr>
                                        <td><?php echo date('g:i A', mktime($h['hour'], 0, 0)); ?> -
                                            <?php echo date('g:i A', mktime($h['hour'] + 1, 0, 0)); ?></td>
                                        <td class="text-center"><?php echo $h['transactions']; ?></td>
                                        <td class="text-right">₱<?php echo number_format($h['sales'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($top_products)): ?>
                <div class="mid-section">
                    <div class="mid-section-header">
                        <i class='bx bx-trophy'></i> Top Selling Products
                    </div>
                    <div class="mid-table-wrap">
                        <table class="mid-table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th class="text-center">Quantity Sold</th>
                                    <th class="text-right">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $p): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($p['product_name']); ?></td>
                                        <td class="text-center"><?php echo $p['quantity_sold']; ?></td>
                                        <td class="text-right">₱<?php echo number_format($p['total_revenue'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($low_stock_count > 0): ?>
                <div class="mid-alert">
                    <i class='bx bx-error-circle'></i>
                    <strong><?php echo $low_stock_count; ?> item(s)</strong> are currently low in stock. Please restock
                    soon.
                </div>
            <?php endif; ?>

            <div class="mid-footer">
                <i class='bx bx-receipt'></i> X READING REPORT &middot; <?php echo $shift_type; ?> SHIFT &middot;
                <?php echo date('F j, Y'); ?> &middot; Generated <?php echo date('h:i A'); ?> by
                <?php echo $cashier_name; ?>
                <?php if ($last_reading): ?>
                    <br><small><i class='bx bx-history'></i> Previous:
                        <?php echo date('h:i A', strtotime($last_reading['reading_time'])); ?>
                        (₱<?php echo number_format($last_reading['total_sales'], 2); ?> &middot;
                        <?php echo $last_reading['total_transactions']; ?> trans)</small>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <!-- Receipt Print Version -->
    <div class="receipt-print">
        <div style="text-align:center; font-weight:bold; font-size:14px;">MINUTE BURGER</div>
        <div style="text-align:center; font-size:10px;">"Serving More Delicious, More Affordable Food"</div>
        <div style="text-align:center; font-weight:bold; margin:5px 0;">X READING REPORT</div>
        <div style="border-bottom:1px dashed #000; margin:5px 0;"></div>

        <div>Cashier: <?php echo $cashier_name; ?></div>
        <div>Shift: <?php echo $shift_type; ?> SHIFT</div>
        <div>Date: <?php echo $current_date; ?></div>
        <div>Time: <?php echo $current_time; ?></div>
        <div>Started: <?php echo date('h:i A', strtotime($shift_start_time)); ?></div>
        <div>Duration: <?php echo $shift_duration_display; ?></div>
        <div>Remaining: <?php echo $remaining_display; ?></div>

        <div style="border-bottom:1px dashed #000; margin:5px 0;"></div>

        <div>Quota: ₱<?php echo number_format($shift_sales, 2); ?> / ₱<?php echo number_format($shift_quota, 2); ?>
            (<?php echo round($percentage, 1); ?>%)</div>

        <div style="border-bottom:1px dashed #000; margin:5px 0;"></div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:5px; margin:5px 0;">
            <div>Total Sales: ₱<?php echo number_format($totals['total_sales'], 2); ?></div>
            <div>Transactions: <?php echo $totals['total_transactions']; ?></div>
            <div>Items Sold: <?php echo $total_items; ?></div>
            <div>Avg Transaction: ₱<?php echo number_format($totals['average_transaction'], 2); ?></div>
            <div>Highest: ₱<?php echo number_format($totals['highest_transaction'], 2); ?></div>
            <div>Lowest: ₱<?php echo number_format($totals['lowest_transaction'], 2); ?></div>
            <div>Hourly Rate:
                ₱<?php echo number_format($shift_seconds > 0 ? $totals['total_sales'] / ($shift_seconds / 3600) : 0, 2); ?>
            </div>
            <div>Cash Drops: ₱<?php echo number_format($cash_drops, 2); ?></div>
        </div>

        <div style="border-bottom:1px dashed #000; margin:5px 0;"></div>

        <div>Opening Cash: ₱<?php echo number_format($opening_cash, 2); ?></div>
        <div>Total Sales: + ₱<?php echo number_format($totals['total_sales'], 2); ?></div>
        <div>Cash Drops: - ₱<?php echo number_format($cash_drops, 2); ?></div>
        <div style="border-top:1px dotted #000; margin:3px 0; padding-top:3px; font-weight:bold;">Expected Cash:
            ₱<?php echo number_format($expected_cash, 2); ?></div>

        <?php if (!empty($hourly_breakdown)): ?>
            <div style="border-bottom:1px dashed #000; margin:5px 0;"></div>
            <div style="font-weight:bold;">HOURLY BREAKDOWN</div>
            <?php foreach ($hourly_breakdown as $h): ?>
                <div><?php echo date('g:i A', mktime($h['hour'], 0, 0)); ?>: <?php echo $h['transactions']; ?> trans /
                    ₱<?php echo number_format($h['sales'], 2); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($top_products)): ?>
            <div style="border-bottom:1px dashed #000; margin:5px 0;"></div>
            <div style="font-weight:bold;">TOP PRODUCTS</div>
            <?php foreach (array_slice($top_products, 0, 5) as $p): ?>
                <div><?php echo htmlspecialchars(substr($p['product_name'], 0, 20)); ?>: <?php echo $p['quantity_sold']; ?> /
                    ₱<?php echo number_format($p['total_revenue'], 2); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="border-top:2px dashed #000; margin:8px 0; text-align:center; padding-top:5px;">
            <div>THANK YOU FOR YOUR SERVICE!</div>
            <div>Generated: <?php echo date('h:i A'); ?></div>
            <div>========================</div>
        </div>
    </div>

    <script>
        /* ════════════════════════════════════════════════════════════════
           ES5 COMPATIBLE JAVASCRIPT - WORKS ON OLD TABLETS
           ════════════════════════════════════════════════════════════════ */

        // -------- Print Receipt --------
        function printReceipt() {
            var originalTitle = document.title;
            document.title = 'X Reading Receipt';
            window.print();
            document.title = originalTitle;
        }

        // -------- Prevent Double Submit --------
        (function () {
            var submitting = false;
            var forms = document.querySelectorAll('form');

            for (var i = 0; i < forms.length; i++) {
                (function (form) {
                    form.addEventListener('submit', function (e) {
                        if (submitting) {
                            e.preventDefault();
                            return;
                        }
                        submitting = true;
                        setTimeout(function () {
                            submitting = false;
                        }, 3000);
                    });
                })(forms[i]);
            }
        })();

        // -------- (Optional) Any other functions below --------
    </script>
</body>

</html>