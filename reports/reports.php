<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
requirePermission('reports_view');

$branch_id = getCurrentBranchId();
$branch_param = $branch_id !== null ? $branch_id : null;

$page_title = 'Reports';
$active_page = 'reports';

/*
|--------------------------------------------------------------------------
| EXCEL EXPORT
|--------------------------------------------------------------------------
*/
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    try {
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $end_date = $_GET['end_date'] ?? date('Y-m-d');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
            throw new Exception('Invalid date format');
        }

        if ($start_date > $end_date) {
            throw new Exception('Start date cannot be greater than end date.');
        }

        $start_datetime = $start_date . ' 00:00:00';
        $end_datetime = $end_date . ' 23:59:59';

        // Get sales data
        $excel_params = [$start_datetime, $end_datetime];
        $branch_sql_excel = '';
        if ($branch_param !== null) {
            $branch_sql_excel = ' AND (branch_id = ? OR (branch_id IS NULL AND cashier_id IN (SELECT id FROM users WHERE branch_id = ?)))';
            $excel_params[] = $branch_param;
            $excel_params[] = $branch_param;
        }
        $stmt = $pdo->prepare("
            SELECT
                DATE(date_time) AS date,
                COUNT(*) AS order_count,
                COALESCE(SUM(total_amount), 0) AS total_sales
            FROM orders
            WHERE date_time BETWEEN ? AND ?" . $branch_sql_excel . "
            GROUP BY DATE(date_time)
            ORDER BY date ASC
        ");
        $stmt->execute($excel_params);
        $daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get top products
        $excel_params2 = [$start_datetime, $end_datetime];
        $branch_sql_excel2 = '';
        if ($branch_param !== null) {
            $branch_sql_excel2 = ' AND (o.branch_id = ? OR (o.branch_id IS NULL AND o.cashier_id IN (SELECT id FROM users WHERE branch_id = ?)))';
            $excel_params2[] = $branch_param;
            $excel_params2[] = $branch_param;
        }
        $stmt = $pdo->prepare("
            SELECT
                p.name AS product_name,
                COALESCE(SUM(oi.quantity), 0) AS total_quantity,
                COALESCE(SUM(oi.subtotal), 0) AS total_revenue
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.id
            INNER JOIN products p ON oi.product_id = p.id
            WHERE o.date_time BETWEEN ? AND ?" . $branch_sql_excel2 . "
            GROUP BY oi.product_id, p.name
            ORDER BY total_revenue DESC
            LIMIT 20
        ");
        $stmt->execute($excel_params2);
        $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get summary
        $total_sales = array_sum(array_column($daily_sales, 'total_sales'));
        $total_orders = array_sum(array_column($daily_sales, 'order_count'));
        $days_count = count($daily_sales);

        // Set headers for Excel download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="Sales_Report_' . date('Y-m-d') . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output Excel content
        echo '<!DOCTYPE html>';
        echo '<html>';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<title>Sales Report</title>';
        echo '<style>
            body { font-family: \'Inter\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', sans-serif; margin: 20px; }
            .header { background: #F37902; color: white; padding: 15px; text-align: center; font-size: 20px; font-weight: bold; }
            .sub-header { background: #f0f0f0; padding: 10px; margin: 10px 0; }
            table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            th { background: #F37902; color: white; padding: 10px; text-align: left; border: 1px solid #ddd; }
            td { padding: 8px; border: 1px solid #ddd; }
            tr:nth-child(even) { background: #f9f9f9; }
            .summary { background: #FFF7ED; padding: 15px; border: 1px solid #F37902; margin: 10px 0; }
            .total-row { font-weight: bold; background: #F37902; color: white; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>';
        echo '</head>';
        echo '<body>';

        // Header
        echo '<div class="header">MINUTE BURGER - SALES REPORT</div>';
        echo '<div class="sub-header">';
        echo 'Report Period: <strong>' . date('F d, Y', strtotime($start_date)) . '</strong> to <strong>' . date('F d, Y', strtotime($end_date)) . '</strong>';
        echo '<br>Generated: ' . date('F d, Y h:i A');
        echo '</div>';

        // Summary Section
        echo '<div class="summary">';
        echo '<h3>📊 Summary</h3>';
        echo '<table style="width: auto; border: none;">';
        echo '<tr><td style="border: none; font-weight: bold;">Total Revenue:</td><td style="border: none;">₱' . number_format($total_sales, 2) . '</td></tr>';
        echo '<tr><td style="border: none; font-weight: bold;">Total Orders:</td><td style="border: none;">' . $total_orders . '</td></tr>';
        echo '<tr><td style="border: none; font-weight: bold;">Average Order Value:</td><td style="border: none;">₱' . number_format($total_orders > 0 ? $total_sales / $total_orders : 0, 2) . '</td></tr>';
        echo '<tr><td style="border: none; font-weight: bold;">Days Covered:</td><td style="border: none;">' . $days_count . ' day(s)</td></tr>';
        echo '<tr><td style="border: none; font-weight: bold;">Average Daily Sales:</td><td style="border: none;">₱' . number_format($days_count > 0 ? $total_sales / $days_count : 0, 2) . '</td></tr>';
        echo '</table>';
        echo '</div>';

        // Daily Sales Table
        echo '<h3>📈 Daily Sales Breakdown</h3>';
        echo '<table>';
        echo '<thead>';
        echo '<tr><th>Date</th><th>Orders</th><th>Total Sales</th></tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($daily_sales as $row) {
            echo '<tr>';
            echo '<td>' . date('F d, Y', strtotime($row['date'])) . '</td>';
            echo '<td>' . $row['order_count'] . '</td>';
            echo '<td>₱' . number_format($row['total_sales'], 2) . '</td>';
            echo '</tr>';
        }
        echo '<tr class="total-row">';
        echo '<td><strong>TOTAL</strong></td>';
        echo '<td><strong>' . $total_orders . '</strong></td>';
        echo '<td><strong>₱' . number_format($total_sales, 2) . '</strong></td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';

        // Top Products Table
        echo '<h3>🏆 Top Selling Products</h3>';
        echo '<table>';
        echo '<thead>';
        echo '<tr><th>#</th><th>Product Name</th><th>Quantity Sold</th><th>Total Revenue</th></tr>';
        echo '</thead>';
        echo '<tbody>';
        $rank = 1;
        foreach ($top_products as $product) {
            echo '<tr>';
            echo '<td>' . $rank++ . '</td>';
            echo '<td>' . htmlspecialchars($product['product_name']) . '</td>';
            echo '<td>' . $product['total_quantity'] . '</td>';
            echo '<td>₱' . number_format($product['total_revenue'], 2) . '</td>';
            echo '</tr>';
        }
        if (empty($top_products)) {
            echo '<tr><td colspan="4" style="text-align: center;">No product data available</td></tr>';
        }
        echo '</tbody>';
        echo '</table>';

        // Footer
        echo '<div class="footer">';
        echo 'This report is automatically generated by Minute Burger POS System.<br>';
        echo '© ' . date('Y') . ' Minute Burger. All rights reserved.';
        echo '</div>';

        echo '</body>';
        echo '</html>';
        exit;

    } catch (Exception $e) {
        header('Location: reports.php?message=' . urlencode('Error exporting report: ' . $e->getMessage()) . '&type=error');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| AJAX: SALES REPORT DATA
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_sales_by_date') {
    try {
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $end_date = $_GET['end_date'] ?? date('Y-m-d');

        if (
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) ||
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)
        ) {
            throw new Exception('Invalid date format');
        }

        if ($start_date > $end_date) {
            throw new Exception('Start date cannot be greater than end date.');
        }

        $start_datetime = $start_date . ' 00:00:00';
        $end_datetime = $end_date . ' 23:59:59';

        $ajax_params = [$start_datetime, $end_datetime];
        $branch_sql_ajax = '';
        if ($branch_param !== null) {
            $branch_sql_ajax = ' AND (branch_id = ? OR (branch_id IS NULL AND cashier_id IN (SELECT id FROM users WHERE branch_id = ?)))';
            $ajax_params[] = $branch_param;
            $ajax_params[] = $branch_param;
        }

        // DAILY SALES FROM ORDERS
        $stmt = $pdo->prepare("
            SELECT
                DATE(date_time) AS date,
                COUNT(*) AS order_count,
                COALESCE(SUM(total_amount), 0) AS total_sales
            FROM orders
            WHERE date_time BETWEEN ? AND ?" . $branch_sql_ajax . "
            GROUP BY DATE(date_time)
            ORDER BY date ASC
        ");
        $stmt->execute($ajax_params);
        $daily_sales_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ITEMS SOLD FROM ORDER_ITEMS
        $items_params = [$start_datetime, $end_datetime];
        $branch_sql_items = '';
        if ($branch_param !== null) {
            $branch_sql_items = ' AND (o.branch_id = ? OR (o.branch_id IS NULL AND o.cashier_id IN (SELECT id FROM users WHERE branch_id = ?)))';
            $items_params[] = $branch_param;
            $items_params[] = $branch_param;
        }
        $stmt = $pdo->prepare("
            SELECT
                DATE(o.date_time) AS date,
                COALESCE(SUM(oi.quantity), 0) AS total_items_sold
            FROM orders o
            INNER JOIN order_items oi ON o.id = oi.order_id
            WHERE o.date_time BETWEEN ? AND ?" . $branch_sql_items . "
            GROUP BY DATE(o.date_time)
        ");
        $stmt->execute($items_params);
        $items_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items_map = [];
        foreach ($items_data as $row) {
            $items_map[$row['date']] = (int)$row['total_items_sold'];
        }

        $daily_sales = [];
        foreach ($daily_sales_raw as $row) {
            $date = $row['date'];
            $daily_sales[] = [
                'date' => $date,
                'order_count' => (int)$row['order_count'],
                'total_items_sold' => $items_map[$date] ?? 0,
                'total_sales' => (float)$row['total_sales']
            ];
        }

        // MONTHLY SALES AGGREGATION
        $monthly_sales = [];
        foreach ($daily_sales as $day) {
            $month = date('Y-m', strtotime($day['date']));
            if (!isset($monthly_sales[$month])) {
                $monthly_sales[$month] = [
                    'month' => $month,
                    'month_name' => date('M Y', strtotime($day['date'])),
                    'total_sales' => 0,
                    'order_count' => 0,
                    'total_items' => 0
                ];
            }
            $monthly_sales[$month]['total_sales'] += $day['total_sales'];
            $monthly_sales[$month]['order_count'] += $day['order_count'];
            $monthly_sales[$month]['total_items'] += $day['total_items_sold'];
        }
        $monthly_sales = array_values($monthly_sales);

        // TOP SELLING PRODUCTS
        $top_params = [$start_datetime, $end_datetime];
        $branch_sql_top = '';
        if ($branch_param !== null) {
            $branch_sql_top = ' AND (o.branch_id = ? OR (o.branch_id IS NULL AND o.cashier_id IN (SELECT id FROM users WHERE branch_id = ?)))';
            $top_params[] = $branch_param;
            $top_params[] = $branch_param;
        }
        $stmt = $pdo->prepare("
            SELECT
                p.name AS product_name,
                COALESCE(SUM(oi.quantity), 0) AS total_quantity,
                COALESCE(SUM(oi.subtotal), 0) AS total_revenue
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.id
            INNER JOIN products p ON oi.product_id = p.id
            WHERE o.date_time BETWEEN ? AND ?" . $branch_sql_top . "
            GROUP BY oi.product_id, p.name
            ORDER BY total_revenue DESC
            LIMIT 10
        ");
        $stmt->execute($top_params);
        $product_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // INVENTORY USAGE
        $inventory_usage = [];

        // CATEGORY BREAKDOWN
        $cat_params = [$start_datetime, $end_datetime];
        $branch_sql_cat = '';
        if ($branch_param !== null) {
            $branch_sql_cat = ' AND (o.branch_id = ? OR (o.branch_id IS NULL AND o.cashier_id IN (SELECT id FROM users WHERE branch_id = ?)))';
            $cat_params[] = $branch_param;
            $cat_params[] = $branch_param;
        }
        $stmt = $pdo->prepare("
            SELECT p.category, SUM(oi.quantity) AS total_qty, SUM(oi.subtotal) AS total_revenue
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.id
            INNER JOIN products p ON oi.product_id = p.id
            WHERE o.date_time BETWEEN ? AND ?" . $branch_sql_cat . "
            GROUP BY p.category ORDER BY total_revenue DESC
        ");
        $stmt->execute($cat_params);
        $category_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // HOURLY SALES PATTERN (across the selected period)
        $hour_params = [$start_datetime, $end_datetime];
        $branch_sql_hour = '';
        if ($branch_param !== null) {
            $branch_sql_hour = ' AND (branch_id = ? OR (branch_id IS NULL AND cashier_id IN (SELECT id FROM users WHERE branch_id = ?)))';
            $hour_params[] = $branch_param;
            $hour_params[] = $branch_param;
        }
        $stmt = $pdo->prepare("
            SELECT HOUR(date_time) AS hour, COUNT(*) AS order_count, COALESCE(SUM(total_amount), 0) AS total_sales
            FROM orders
            WHERE date_time BETWEEN ? AND ?" . $branch_sql_hour . "
            GROUP BY HOUR(date_time) ORDER BY hour ASC
        ");
        $stmt->execute($hour_params);
        $hourly_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // DAY-OF-WEEK PATTERN (across the selected period)
        $dow_params = [$start_datetime, $end_datetime];
        $branch_sql_dow = '';
        if ($branch_param !== null) {
            $branch_sql_dow = ' AND (branch_id = ? OR (branch_id IS NULL AND cashier_id IN (SELECT id FROM users WHERE branch_id = ?)))';
            $dow_params[] = $branch_param;
            $dow_params[] = $branch_param;
        }
        $stmt = $pdo->prepare("
            SELECT DAYNAME(date_time) AS day_name, DAYOFWEEK(date_time) AS day_num, COUNT(*) AS order_count, COALESCE(SUM(total_amount), 0) AS total_sales
            FROM orders
            WHERE date_time BETWEEN ? AND ?" . $branch_sql_dow . "
            GROUP BY DAYNAME(date_time), DAYOFWEEK(date_time) ORDER BY day_num ASC
        ");
        $stmt->execute($dow_params);
        $day_of_week_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // SUMMARY
        $total_sales = array_sum(array_column($daily_sales, 'total_sales'));
        $total_orders = array_sum(array_column($daily_sales, 'order_count'));
        $total_items = array_sum(array_column($daily_sales, 'total_items_sold'));
        $days_count = count($daily_sales);

        // PREVIOUS PERIOD COMPARISON
        $days = max(1, (int)((strtotime($end_date) - strtotime($start_date)) / 86400) + 1);
        $prev_start = date('Y-m-d', strtotime($start_date . " -$days days"));
        $prev_end = date('Y-m-d', strtotime($start_date . ' -1 day'));

        $prev_params = [$prev_start . ' 00:00:00', $prev_end . ' 23:59:59'];
        $branch_sql_prev = '';
        if ($branch_param !== null) {
            $branch_sql_prev = ' AND (branch_id = ? OR (branch_id IS NULL AND cashier_id IN (SELECT id FROM users WHERE branch_id = ?)))';
            $prev_params[] = $branch_param;
            $prev_params[] = $branch_param;
        }
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total_amount), 0) AS previous_total
            FROM orders
            WHERE date_time BETWEEN ? AND ?" . $branch_sql_prev . "
        ");
        $stmt->execute($prev_params);
        $prev_total = (float)$stmt->fetchColumn();

        $growth = 0;
        if ($prev_total > 0) {
            $growth = (($total_sales - $prev_total) / $prev_total) * 100;
        }

        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        echo json_encode([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_sales' => (float)$total_sales,
                    'total_orders' => (int)$total_orders,
                    'total_items' => (int)$total_items,
                    'average_daily_sales' => $days_count > 0 ? (float)($total_sales / $days_count) : 0,
                    'average_order_value' => $total_orders > 0 ? (float)($total_sales / $total_orders) : 0,
                    'growth_percentage' => round($growth, 2),
                    'days_count' => (int)$days_count
                ],
                'daily_sales' => $daily_sales,
                'monthly_sales' => $monthly_sales,
                'product_sales' => $product_sales,
                'inventory_usage' => $inventory_usage,
                'category_sales' => $category_sales,
                'hourly_sales' => $hourly_sales,
                'day_of_week_sales' => $day_of_week_sales,
                'comparison' => [
                    'growth' => round($growth, 2)
                ]
            ]
        ]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// Get low stock count from inventory for notification badge
$inv_params = [];
$branch_sql_inv = '';
if ($branch_param !== null) {
    $branch_sql_inv = ' AND branch_id = ?';
    $inv_params[] = $branch_param;
}
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM inventory
    WHERE quantity <= min_stock
      AND (status IS NULL OR status = 'active')
      AND deleted_at IS NULL" . $branch_sql_inv . "
");
$stmt->execute($inv_params);
$low_stock_total = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get out of stock count
$oos_params = [];
$branch_sql_oos = '';
if ($branch_param !== null) {
    $branch_sql_oos = ' AND branch_id = ?';
    $oos_params[] = $branch_param;
}
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM inventory
    WHERE quantity <= 0
      AND deleted_at IS NULL" . $branch_sql_oos . "
");
$stmt->execute($oos_params);
$out_of_stock_total = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$total_alerts = $low_stock_total + $out_of_stock_total;

// Fetch out of stock items for notification
$oos_items_params = [];
$branch_sql_oos_items = '';
if ($branch_param !== null) {
    $branch_sql_oos_items = ' AND branch_id = ?';
    $oos_items_params[] = $branch_param;
}
$stmt = $pdo->prepare("
    SELECT id, item_name, quantity, min_stock, unit
    FROM inventory
    WHERE quantity <= 0
      AND deleted_at IS NULL" . $branch_sql_oos_items . "
    ORDER BY item_name ASC
    LIMIT 10
");
$stmt->execute($oos_items_params);
$out_of_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch low stock items for notification (excluding out of stock)
$ls_params = [];
$branch_sql_ls = '';
if ($branch_param !== null) {
    $branch_sql_ls = ' AND branch_id = ?';
    $ls_params[] = $branch_param;
}
$stmt = $pdo->prepare("
    SELECT id, item_name, quantity, min_stock, unit
    FROM inventory
    WHERE quantity <= min_stock
      AND quantity > 0
      AND deleted_at IS NULL" . $branch_sql_ls . "
    ORDER BY quantity ASC
    LIMIT 10
");
$stmt->execute($ls_params);
$low_stock_notify = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Minute Burger Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="../assets/js/chart.v2.min.js"></script>
    <link rel="stylesheet" href="../assets/css/reports.css">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="content-area">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class='bx bx-bar-chart'></i>Sales Reports</h3>
                        <div style="display: flex; gap: 0.5rem;">
                            <button class="export-btn" onclick="exportExcel()">
                                <i class='bx bx-file'></i> Export Excel
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="date-range-picker">
                            <div class="date-range-presets">
                                <button class="preset-btn" data-range="today">Today</button>
                                <button class="preset-btn" data-range="yesterday">Yesterday</button>
                                <button class="preset-btn" data-range="7days">Last 7 Days</button>
                                <button class="preset-btn active" data-range="30days">Last 30 Days</button>
                                <button class="preset-btn" data-range="month">This Month</button>
                                <button class="preset-btn" data-range="lastmonth">Last Month</button>
                                <button class="preset-btn" data-range="year">This Year</button>
                                <button class="preset-btn" data-range="custom">Custom Range</button>
                            </div>
                            <div class="date-range-inputs" id="dateRangeInputs" style="display: none;">
                                <div class="date-range-item">
                                    <label for="start-date">From Date</label>
                                    <input type="date" id="start-date" class="form-control"
                                        value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>"
                                        max="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="date-range-item">
                                    <label for="end-date">To Date</label>
                                    <input type="date" id="end-date" class="form-control"
                                        value="<?php echo date('Y-m-d'); ?>"
                                        max="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <button class="btn btn-primary" onclick="loadSalesData()">
                                    <i class='bx bx-filter-alt'></i> Apply Filter
                                </button>
                            </div>
                        </div>

                        <div class="sales-summary-grid" id="summary-cards">
                            <div class="sales-summary-card">
                                <div class="card-icon"><i class='bx bx-wallet'></i></div>
                                <div class="label">Total Sales</div>
                                <div class="value" id="total-sales">&#8369;0.00</div>
                                <div class="sub-text" id="sales-growth"></div>
                            </div>
                            <div class="sales-summary-card">
                                <div class="card-icon"><i class='bx bx-cart'></i></div>
                                <div class="label">Total Orders</div>
                                <div class="value" id="total-orders">0</div>
                                <div class="sub-text" id="avg-order"></div>
                            </div>
                            <div class="sales-summary-card">
                                <div class="card-icon"><i class='bx bx-package'></i></div>
                                <div class="label">Items Sold</div>
                                <div class="value" id="total-items">0</div>
                                <div class="sub-text" id="items-per-order"></div>
                            </div>
                            <div class="sales-summary-card">
                                <div class="card-icon"><i class='bx bx-trending-up'></i></div>
                                <div class="label">Avg. Daily Sales</div>
                                <div class="value" id="avg-daily">&#8369;0.00</div>
                                <div class="sub-text" id="days-count"></div>
                            </div>
                        </div>

                        <div class="chart-grid">
                            <div class="chart-container">
                                <h4><i class='bx bx-line-chart'></i> Daily Sales Trend</h4>
                                <canvas id="dailySalesChart"></canvas>
                            </div>
                            <div class="chart-container">
                                <h4><i class='bx bx-pie-chart'></i> Top Products (by Revenue)</h4>
                                <canvas id="pieChart"></canvas>
                            </div>
                        </div>

                        <div class="chart-grid">
                            <div class="chart-container">
                                <h4><i class='bx bx-category'></i> Sales by Category</h4>
                                <canvas id="categoryChart"></canvas>
                            </div>
                            <div class="chart-container">
                                <h4><i class='bx bx-time'></i> Hourly Sales Pattern</h4>
                                <canvas id="hourlyChart"></canvas>
                            </div>
                        </div>

                        <div class="chart-grid">
                            <div class="chart-container">
                                <h4><i class='bx bx-calendar'></i> Sales by Day of Week</h4>
                                <canvas id="dowChart"></canvas>
                            </div>
                            <div class="chart-container">
                                <h4><i class='bx bx-calendar'></i> Monthly Sales Overview</h4>
                                <canvas id="monthlySalesChart"></canvas>
                            </div>
                        </div>

                        <div class="detailed-sales" id="detailed-sales" style="display: none;">
                            <div class="card" style="margin-top: 1.25rem;">
                                <div class="card-header">
                                    <h3 class="card-title"><i class='bx bx-trophy'></i>Top Selling Products</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-container">
                                        <table class="data-table" id="top-products-table">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Quantity Sold</th>
                                                    <th>Revenue</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td colspan="3" class="text-center text-muted">No data available</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="card" style="margin-top: 1.25rem;">
                                <div class="card-header">
                                    <h3 class="card-title"><i class='bx bx-calendar'></i>Daily Breakdown</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-container">
                                        <table class="data-table" id="daily-breakdown-table">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Orders</th>
                                                    <th>Items Sold</th>
                                                    <th>Total Sales</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td colspan="4" class="text-center text-muted">No data available</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Panel -->
        <div class="notification-panel" id="notificationPanel">
            <div class="notification-header">
                <h4><i class='bx bx-bell'></i> Inventory Alerts</h4>
                <button class="notification-close" aria-label="Close notifications" onclick="closeNotificationPanel()"><i class='bx bx-x'></i></button>
            </div>
            <div class="notification-body" id="notificationBody"></div>
        </div>
    </div>

    <script>
        // Store alert data
        const alertData = {
            outOfStock: <?php echo json_encode($out_of_stock_items); ?>,
            lowStock: <?php echo json_encode($low_stock_notify); ?>,
            totalAlerts: <?php echo $total_alerts; ?>
        };

        // Move pie/doughnut legends to the bottom on phones so they don't
        // squeeze the chart in the 1-column (≤480px) layout
        function mbLegendPosition() {
            return window.innerWidth <= 480 ? 'bottom' : 'right';
        }

        let dailyChart = null;
        let monthlyChart = null;
        let pieChart = null;
        let notifPanel = document.getElementById('notificationPanel');
        let notifVisible = false;

        // Render notification panel
        function renderNotificationPanel() {
            const body = document.getElementById('notificationBody');
            if (!body) return;

            let html = '';

            if (alertData.outOfStock && alertData.outOfStock.length > 0) {
                alertData.outOfStock.forEach(item => {
                    html += `
                        <div class="notification-item critical">
                            <div class="item-info">
                                <div class="item-name">${escapeHtml(item.item_name)}</div>
                                <div class="item-stock">Stock: <span class="stock-critical">0 ${escapeHtml(item.unit || 'piece')}</span> (Min: ${item.min_stock})</div>
                            </div>
                            <button class="update-btn" onclick="goToInventory(${item.id})">Update</button>
                        </div>
                    `;
                });
            }

            if (alertData.lowStock && alertData.lowStock.length > 0) {
                alertData.lowStock.forEach(item => {
                    html += `
                        <div class="notification-item warning">
                            <div class="item-info">
                                <div class="item-name">${escapeHtml(item.item_name)}</div>
                                <div class="item-stock">Stock: <span class="stock-warning">${item.quantity} ${escapeHtml(item.unit || 'piece')}</span> (Min: ${item.min_stock})</div>
                            </div>
                            <button class="update-btn" onclick="goToInventory(${item.id})">Update</button>
                        </div>
                    `;
                });
            }

            if (!html) {
                html = '<div class="empty-notification"><i class="bx bx-check-circle"></i><p>All inventory items are well stocked!</p></div>';
            }

            body.innerHTML = html;
        }

        function goToInventory(id) {
            closeNotificationPanel();
            window.location.href = '../inventory/inventory.php';
        }

        function toggleNotificationPanel() {
            const panel = document.getElementById('notificationPanel');
            if (notifVisible) {
                panel.style.display = 'none';
                notifVisible = false;
            } else {
                renderNotificationPanel();
                panel.style.display = 'block';
                notifVisible = true;
            }
        }

        function closeNotificationPanel() {
            const panel = document.getElementById('notificationPanel');
            if (panel) {
                panel.style.display = 'none';
                notifVisible = false;
            }
        }

        // ===== EXPORT EXCEL =====
        function exportExcel() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            
            if (!startDate || !endDate) {
                showToastMsg('Please select both start and end dates.', 'warning');
                return;
            }
            
            if (startDate > endDate) {
                showToastMsg('Start date cannot be greater than end date.', 'warning');
                return;
            }
            
            // Build URL with parameters
            const url = `reports.php?export=excel&start_date=${startDate}&end_date=${endDate}`;
            
            // Open in new window or trigger download
            window.open(url, '_blank');
        }

        document.addEventListener('DOMContentLoaded', function () {
            loadSalesData();

            const badge = document.getElementById('alert-count-badge');
            if (badge && alertData.totalAlerts > 0) {
                badge.textContent = alertData.totalAlerts;
                badge.style.display = 'flex';
            } else if (badge) {
                badge.style.display = 'none';
            }

            // Date range presets
            const dateRangeInputs = document.getElementById('dateRangeInputs');

            document.querySelectorAll('.preset-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    const range = this.dataset.range;
                    const today = new Date();
                    let start, end;

                    switch(range) {
                        case 'today':
                            start = end = formatDate(today);
                            break;
                        case 'yesterday':
                            const yest = new Date(today);
                            yest.setDate(yest.getDate() - 1);
                            start = end = formatDate(yest);
                            break;
                        case '7days':
                            start = formatDate(new Date(today.setDate(today.getDate() - 6)));
                            end = formatDate(new Date());
                            break;
                        case '30days':
                            start = formatDate(new Date(new Date().setDate(new Date().getDate() - 29)));
                            end = formatDate(new Date());
                            break;
                        case 'month':
                            start = formatDate(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
                            end = formatDate(new Date());
                            break;
                        case 'lastmonth':
                            const lm = new Date();
                            lm.setMonth(lm.getMonth() - 1);
                            start = formatDate(new Date(lm.getFullYear(), lm.getMonth(), 1));
                            end = formatDate(new Date(lm.getFullYear(), lm.getMonth() + 1, 0));
                            break;
                        case 'year':
                            start = formatDate(new Date(new Date().getFullYear(), 0, 1));
                            end = formatDate(new Date());
                            break;
                        case 'custom':
                            // Only Custom Range reveals the date inputs
                            dateRangeInputs.style.display = 'flex';
                            document.getElementById('start-date').focus();
                            return;
                    }

                    dateRangeInputs.style.display = 'none';
                    document.getElementById('start-date').value = start;
                    document.getElementById('end-date').value = end;
                    loadSalesData();
                });
            });

            // Custom range inputs: Enter applies the filter; clicking anywhere
            // on a date field opens the calendar (desktop Chrome only opens it
            // via the tiny hidden indicator icon otherwise)
            ['start-date', 'end-date'].forEach(id => {
                const input = document.getElementById(id);
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') loadSalesData();
                });
                input.addEventListener('click', function() {
                    if (typeof this.showPicker === 'function') {
                        try { this.showPicker(); } catch (err) { /* needs user gesture or unsupported */ }
                    }
                });
            });

            setInterval(() => { if (!document.hidden) loadSalesData(); }, 6000);
        });

        function formatDate(d) {
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        }

        function loadSalesData() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;

            if (!startDate || !endDate) {
                return;
            }

            if (startDate > endDate) {
                showToastMsg('Start date cannot be greater than end date.', 'warning');
                return;
            }

            fetch(`reports.php?action=get_sales_by_date&start_date=${startDate}&end_date=${endDate}&_=${Date.now()}`)
                .then(response => response.json())
                .then(result => {
                    if (!result.success) {
                        throw new Error(result.error || 'Failed to load sales data');
                    }

                    const data = result.data;
                    // Render each section independently so one bad payload
                    // section can never blank out the whole dashboard
                    const renderStep = (label, fn) => {
                        try { fn(); } catch (err) { console.error('Render error in ' + label + ':', err); }
                    };

                    renderStep('summary', () => updateSalesSummary(data));
                    renderStep('daily chart', () => updateDailySalesChart(data.daily_sales));
                    renderStep('monthly chart', () => updateMonthlySalesChart(data.monthly_sales));
                    renderStep('top products', () => updatePieChart(data.product_sales));
                    renderStep('category chart', () => updateCategoryChart(data.category_sales));
                    renderStep('hourly chart', () => updateHourlyChart(data.hourly_sales));
                    renderStep('day of week chart', () => updateDowChart(data.day_of_week_sales));
                    renderStep('top products table', () => updateTopProducts(data.product_sales));
                    renderStep('daily breakdown', () => updateDailyBreakdown(data.daily_sales));

                    document.getElementById('detailed-sales').style.display = 'block';
                })
                .catch(error => {
                    console.error(error);
                    resetDisplay();
                });
        }

        function updateDailySalesChart(dailySales) {
            const ctx = document.getElementById('dailySalesChart').getContext('2d');

            if (dailyChart) {
                dailyChart.destroy();
            }

            if (!dailySales || dailySales.length === 0) {
                return;
            }

            const labels = dailySales.map(day => {
                const date = new Date(day.date);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });

            const salesData = dailySales.map(day => day.total_sales);
            const orderData = dailySales.map(day => day.order_count);

            dailyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Sales (₱)',
                            data: salesData,
                            borderColor: '#F37902',
                            backgroundColor: 'rgba(243, 121, 2, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Orders',
                            data: orderData,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { position: 'top' },
                    tooltips: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(tooltipItem, data) {
                                let label = data.datasets[tooltipItem.datasetIndex].label || '';
                                let value = tooltipItem.yLabel;
                                if (label === 'Sales (₱)') {
                                    return label + ': ₱' + Number(value).toFixed(2);
                                }
                                return label + ': ' + value;
                            }
                        }
                    },
                    scales: {
                        xAxes: [{ id: 'x', display: true }],
                        yAxes: [
                            {
                                id: 'y',
                                display: true,
                                position: 'left',
                                scaleLabel: { display: true, labelString: 'Sales (₱)' },
                                ticks: { callback: function(value) { return '₱' + Number(value).toLocaleString(); } }
                            },
                            {
                                id: 'y1',
                                display: true,
                                position: 'right',
                                scaleLabel: { display: true, labelString: 'Orders' },
                                gridLines: { drawOnChartArea: false }
                            }
                        ]
                    }
                }
            });
        }

        function updateMonthlySalesChart(monthlySales) {
            const ctx = document.getElementById('monthlySalesChart').getContext('2d');

            if (monthlyChart) {
                monthlyChart.destroy();
            }

            if (!monthlySales || monthlySales.length === 0) {
                return;
            }

            const labels = monthlySales.map(month => month.month_name);
            const salesData = monthlySales.map(month => month.total_sales);
            const orderData = monthlySales.map(month => month.order_count);

            monthlyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Sales (₱)',
                            data: salesData,
                            backgroundColor: 'rgba(243, 121, 2, 0.7)',
                            borderColor: '#F37902',
                            borderWidth: 1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Orders',
                            data: orderData,
                            backgroundColor: 'rgba(52, 152, 219, 0.7)',
                            borderColor: '#3498db',
                            borderWidth: 1,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { position: 'top' },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                let label = data.datasets[tooltipItem.datasetIndex].label || '';
                                let value = tooltipItem.yLabel;
                                if (label === 'Sales (₱)') {
                                    return label + ': ₱' + Number(value).toFixed(2);
                                }
                                return label + ': ' + value;
                            }
                        }
                    },
                    scales: {
                        xAxes: [{ id: 'x', display: true }],
                        yAxes: [
                            {
                                id: 'y',
                                display: true,
                                position: 'left',
                                scaleLabel: { display: true, labelString: 'Sales (₱)' },
                                ticks: { callback: function(value) { return '₱' + Number(value).toLocaleString(); } }
                            },
                            {
                                id: 'y1',
                                display: true,
                                position: 'right',
                                scaleLabel: { display: true, labelString: 'Orders' },
                                gridLines: { drawOnChartArea: false }
                            }
                        ]
                    }
                }
            });
        }

        function updatePieChart(products) {
            const ctx = document.getElementById('pieChart').getContext('2d');

            if (pieChart) {
                pieChart.destroy();
            }

            if (!products || products.length === 0) {
                return;
            }

            const top5 = products.slice(0, 5);
            const labels = top5.map(p => p.product_name);
            const data = top5.map(p => p.total_revenue);

            const otherTotal = products.slice(5).reduce((sum, p) => sum + p.total_revenue, 0);

            if (otherTotal > 0) {
                labels.push('Others');
                data.push(otherTotal);
            }

            const colors = ['#F37902', '#DC6902', '#BE6B03', '#EDD0A9', '#FAE51D', '#3498db', '#27ae60', '#9b59b6', '#e74c3c', '#f39c12'];

            pieChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        borderWidth: 1,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { position: mbLegendPosition(), labels: { fontSize: 11 } },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                const idx = tooltipItem.index;
                                const dataset = data.datasets[tooltipItem.datasetIndex];
                                const value = dataset.data[idx];
                                const total = dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return tooltipItem.label + ': ₱' + Number(value).toFixed(2) + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            });
        }

        function updateSalesSummary(data) {
            const formatCurrency = amount => '₱' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            const formatNumber = num => num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');

            document.getElementById('total-sales').textContent = formatCurrency(data.summary.total_sales);
            document.getElementById('total-orders').textContent = formatNumber(data.summary.total_orders);
            document.getElementById('total-items').textContent = formatNumber(data.summary.total_items);
            document.getElementById('avg-daily').textContent = formatCurrency(data.summary.average_daily_sales);

            const growthEl = document.getElementById('sales-growth');
            const growth = data.comparison.growth;

            if (growth > 0) {
                growthEl.innerHTML = `↑ ${growth}% vs previous period`;
                growthEl.className = 'growth-indicator positive';
            } else if (growth < 0) {
                growthEl.innerHTML = `↓ ${Math.abs(growth)}% vs previous period`;
                growthEl.className = 'growth-indicator negative';
            } else {
                growthEl.innerHTML = '0% vs previous period';
                growthEl.className = 'growth-indicator';
            }

            document.getElementById('avg-order').innerHTML = `Avg Order: ${formatCurrency(data.summary.average_order_value)}`;

            const itemsPerOrder = data.summary.total_orders > 0 ? (data.summary.total_items / data.summary.total_orders).toFixed(1) : '0';
            document.getElementById('items-per-order').innerHTML = `${itemsPerOrder} items/order`;
            document.getElementById('days-count').innerHTML = `${data.summary.days_count} days`;
        }

        let categoryChart = null;
        function updateCategoryChart(categories) {
            const ctx = document.getElementById('categoryChart').getContext('2d');
            if (categoryChart) categoryChart.destroy();
            if (!categories || categories.length === 0) return;

            const colors = ['#F37902','#DC6902','#BE6B03','#EDD0A9','#FAE51D','#3498db','#27ae60','#9b59b6'];
            categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: categories.map(c => c.category),
                    datasets: [{
                        data: categories.map(c => c.total_revenue),
                        backgroundColor: colors.slice(0, categories.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { position: mbLegendPosition(), labels: { fontSize: 11 } },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                const idx = tooltipItem.index;
                                const dataset = data.datasets[tooltipItem.datasetIndex];
                                const raw = dataset.data[idx];
                                const total = dataset.data.reduce((a, b) => a + b, 0);
                                const pct = ((raw / total) * 100).toFixed(1);
                                return tooltipItem.label + ': ₱' + Number(raw).toFixed(2) + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            });
        }

        let hourlyChart = null;
        function updateHourlyChart(hourlySales) {
            const ctx = document.getElementById('hourlyChart').getContext('2d');
            if (hourlyChart) hourlyChart.destroy();
            if (!hourlySales || hourlySales.length === 0) return;

            const allHours = [];
            for (let h = 6; h <= 23; h++) allHours.push(h);
            const hourMap = {};
            hourlySales.forEach(d => hourMap[d.hour] = d);

            hourlyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: allHours.map(h => {
                        const ampm = h >= 12 ? 'PM' : 'AM';
                        const h12 = h > 12 ? h - 12 : (h === 0 ? 12 : h);
                        return h12 + ampm;
                    }),
                    datasets: [{
                        label: 'Sales (₱)',
                        data: allHours.map(h => hourMap[h] ? hourMap[h].total_sales : 0),
                        backgroundColor: 'rgba(243,121,2,0.7)',
                        borderColor: '#F37902',
                        borderWidth: 1
                    },{
                        label: 'Orders',
                        data: allHours.map(h => hourMap[h] ? hourMap[h].order_count : 0),
                        backgroundColor: 'rgba(52,152,219,0.7)',
                        borderColor: '#3498db',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { position: 'top' },
                    scales: {
                        xAxes: [{ id: 'x', scaleLabel: { display: true, labelString: 'Hour' } }],
                        yAxes: [
                            { id: 'y', position: 'left', scaleLabel: { display: true, labelString: 'Sales (₱)' }, ticks: { callback: v => '₱' + Number(v).toLocaleString() } },
                            { id: 'y1', position: 'right', scaleLabel: { display: true, labelString: 'Orders' }, gridLines: { drawOnChartArea: false } }
                        ]
                    }
                }
            });
        }

        let dowChart = null;
        function updateDowChart(dowSales) {
            const ctx = document.getElementById('dowChart').getContext('2d');
            if (dowChart) dowChart.destroy();
            if (!dowSales || dowSales.length === 0) return;

            const dayOrder = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            const dayShort = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
            const dayMap = {};
            dowSales.forEach(d => dayMap[d.day_name] = d);

            const salesData = dayOrder.map(day => dayMap[day] ? dayMap[day].total_sales : 0);
            const orderData = dayOrder.map(day => dayMap[day] ? dayMap[day].order_count : 0);

            dowChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: dayShort,
                    datasets: [{
                        label: 'Sales (₱)',
                        data: salesData,
                        backgroundColor: 'rgba(243,121,2,0.7)',
                        borderColor: '#F37902',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },{
                        label: 'Orders',
                        data: orderData,
                        backgroundColor: 'rgba(52,152,219,0.7)',
                        borderColor: '#3498db',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { position: 'top' },
                    scales: {
                        xAxes: [{ id: 'x', display: true }],
                        yAxes: [
                            { id: 'y', position: 'left', scaleLabel: { display: true, labelString: 'Sales (₱)' }, ticks: { callback: v => '₱' + Number(v).toLocaleString() } },
                            { id: 'y1', position: 'right', scaleLabel: { display: true, labelString: 'Orders' }, gridLines: { drawOnChartArea: false } }
                        ]
                    }
                }
            });
        }

        function updateTopProducts(products) {
            const tableBody = document.querySelector('#top-products-table tbody');
            if (!products || products.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No product sales data</td></tr>';
                return;
            }

            let html = '';
            products.forEach(product => {
                html += `
                    <tr>
                        <td><strong>${escapeHtml(product.product_name)}</strong></td>
                        <td>${product.total_quantity}</td>
                        <td><strong class="text-muted">₱${parseFloat(product.total_revenue).toFixed(2)}</strong></td>
                    </tr>
                `;
            });
            tableBody.innerHTML = html;
        }

        function updateDailyBreakdown(dailySales) {
            const tableBody = document.querySelector('#daily-breakdown-table tbody');
            if (!dailySales || dailySales.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No daily sales data</td></tr>';
                return;
            }

            let html = '';
            dailySales.forEach(day => {
                const date = new Date(day.date);
                const formattedDate = date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                html += `
                    <tr>
                        <td><strong>${formattedDate}</strong></td>
                        <td>${day.order_count}</td>
                        <td>${day.total_items_sold}</td>
                        <td><strong class="text-muted">₱${parseFloat(day.total_sales).toFixed(2)}</strong></td>
                    </tr>
                `;
            });
            tableBody.innerHTML = html;
        }

        function resetDisplay() {
            document.getElementById('total-sales').textContent = '₱0.00';
            document.getElementById('total-orders').textContent = '0';
            document.getElementById('total-items').textContent = '0';
            document.getElementById('avg-daily').textContent = '₱0.00';
            if (categoryChart) { categoryChart.destroy(); categoryChart = null; }
            if (hourlyChart) { hourlyChart.destroy(); hourlyChart = null; }
            if (dowChart) { dowChart.destroy(); dowChart = null; }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close notification panel when clicking outside
        document.addEventListener('click', function(e) {
            const bell = document.querySelector('.notification-bell');
            // no optional chaining (?.): older iOS WebKit (iPad, Safari 12)
            // cannot parse it, which kills the whole script block
            if (notifVisible && !notifPanel.contains(e.target) && !(bell && bell.contains(e.target))) {
                closeNotificationPanel();
            }
        });
    </script>
    <?php include __DIR__ . '/../includes/confirm_modal.php'; ?>
</body>
</html>