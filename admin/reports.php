<?php
require_once __DIR__ . '/bootstrap.php';
requirePermission('reports_view');
if (!isOwner() && !isManager()) {
    header('Location: /minute1/auth/unauthorized.php?page=' . urlencode($_SERVER['PHP_SELF']));
    exit();
}

$active_tab = 'reports';
$user_branch_id = getCurrentBranchId();

// Get branches for Owner filter
$branches = [];
if (isOwner()) {
    try {
        $branches = $pdo->query("SELECT id, branch_name FROM branches WHERE status = 'active' ORDER BY branch_name")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $branches = []; }
}

// Determine effective branch filter
if (isOwner()) {
    $filter_branch_id = isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int)$_GET['branch_id'] : null;
} else {
    $filter_branch_id = $user_branch_id;
}

// Branch scope label
$branch_label = 'All Branches';
if ($filter_branch_id !== null) {
    foreach ($branches as $b) {
        if ((int)$b['id'] === $filter_branch_id) { $branch_label = $b['branch_name']; break; }
    }
    if ($branch_label === 'All Branches') {
        try {
            $s = $pdo->prepare("SELECT branch_name FROM branches WHERE id = ?");
            $s->execute([$filter_branch_id]);
            $bl = $s->fetchColumn();
            if ($bl) $branch_label = $bl;
        } catch (PDOException $e) {}
    }
}

// Branch WHERE fragment
$order_branch_where = '';
if ($filter_branch_id !== null) {
    $fb = (int)$filter_branch_id;
    $order_branch_where = " AND (o.branch_id = $fb OR (o.branch_id IS NULL AND o.cashier_id IN (SELECT id FROM users WHERE branch_id = $fb)))";
}

// Get today's stats
$stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM orders o WHERE DATE(date_time) = CURDATE()" . $order_branch_where);
$stmt->execute();
$today_stats = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM orders o WHERE MONTH(date_time) = MONTH(CURDATE()) AND YEAR(date_time) = YEAR(CURDATE())" . $order_branch_where);
$stmt->execute();
$month_stats = $stmt->fetch();

// Last month stats
$prev_month = date('m', strtotime('-1 month'));
$prev_year = date('Y', strtotime('-1 month'));
$stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM orders o WHERE MONTH(date_time) = ? AND YEAR(date_time) = ?" . $order_branch_where);
$stmt->execute([$prev_month, $prev_year]);
$prev_month_stats = $stmt->fetch();

// Live-polling endpoint: today's/month's headline figures only (the heavier
// chart sections below stay on-demand via the existing filter form).
if (isset($_GET['ajax_headline_stats'])) {
    header('Content-Type: application/json');
    $month_growth_ajax = $prev_month_stats['total'] > 0
        ? (($month_stats['total'] - $prev_month_stats['total']) / $prev_month_stats['total']) * 100
        : 0;
    echo json_encode([
        'success' => true,
        'today_total' => number_format((float)$today_stats['total'], 2),
        'today_count' => (int)$today_stats['count'],
        'today_avg' => number_format($today_stats['count'] > 0 ? $today_stats['total'] / $today_stats['count'] : 0, 2),
        'month_total' => number_format((float)$month_stats['total'], 2),
        'month_count' => (int)$month_stats['count'],
        'month_growth' => round($month_growth_ajax, 1),
    ]);
    exit;
}

// 7-day sales trend
$seven_days_ago = date('Y-m-d', strtotime('-6 days'));
$stmt = $pdo->prepare("
    SELECT DATE(date_time) AS date, COUNT(*) AS order_count, COALESCE(SUM(total_amount), 0) AS total_sales
    FROM orders o
    WHERE DATE(date_time) >= ? AND DATE(date_time) <= CURDATE()" . $order_branch_where . "
    GROUP BY DATE(date_time) ORDER BY date ASC
");
$stmt->execute([$seven_days_ago]);
$trend_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Category breakdown
$cat_stmt = $pdo->prepare("
    SELECT p.category, SUM(oi.quantity) as total_qty, SUM(oi.subtotal) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE MONTH(o.date_time) = MONTH(CURDATE()) AND YEAR(o.date_time) = YEAR(CURDATE())" . $order_branch_where . "
    GROUP BY p.category
    ORDER BY total_revenue DESC
");
$cat_stmt->execute();
$category_data = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Hourly sales pattern
$hourly_stmt = $pdo->prepare("
    SELECT HOUR(date_time) AS hour, COUNT(*) AS order_count, COALESCE(SUM(total_amount), 0) AS total_sales
    FROM orders o
    WHERE DATE(date_time) = CURDATE()" . $order_branch_where . "
    GROUP BY HOUR(date_time) ORDER BY hour ASC
");
$hourly_stmt->execute();
$hourly_data = $hourly_stmt->fetchAll(PDO::FETCH_ASSOC);

// Day-of-week pattern
$dow_stmt = $pdo->prepare("
    SELECT DAYNAME(date_time) AS day_name, DAYOFWEEK(date_time) AS day_num, COUNT(*) AS order_count, COALESCE(SUM(total_amount), 0) AS total_sales
    FROM orders o
    WHERE date_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)" . $order_branch_where . "
    GROUP BY DAYNAME(date_time), DAYOFWEEK(date_time)
    ORDER BY day_num ASC
");
$dow_stmt->execute();
$dow_data = $dow_stmt->fetchAll(PDO::FETCH_ASSOC);

// Top products
$stmt = $pdo->prepare("
    SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.subtotal) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE MONTH(o.date_time) = MONTH(CURDATE()) AND YEAR(o.date_time) = YEAR(CURDATE())" . $order_branch_where . "
    GROUP BY p.id, p.name
    ORDER BY total_sold DESC
    LIMIT 10
");
$stmt->execute();
$top_products = $stmt->fetchAll();

// Low stock count
$low_stock_count = 0;
if ($filter_branch_id !== null) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE branch_id = ? AND quantity > 0 AND quantity <= min_stock AND deleted_at IS NULL");
    $s->execute([$filter_branch_id]);
    $low_stock_count = (int)$s->fetchColumn();
} else {
    $s = $pdo->query("SELECT COUNT(*) FROM inventory WHERE quantity > 0 AND quantity <= min_stock AND deleted_at IS NULL");
    $low_stock_count = (int)$s->fetchColumn();
}

// Get total products count
$total_products = 0;
if ($filter_branch_id !== null) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE branch_id = ? AND deleted_at IS NULL");
    $s->execute([$filter_branch_id]);
    $total_products = (int)$s->fetchColumn();
} else {
    $s = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
    $total_products = (int)$s->fetchColumn();
}

// Get cashiers count
$total_cashiers = 0;
if ($filter_branch_id !== null) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'cashier' AND status = 'active' AND branch_id = ?");
    $s->execute([$filter_branch_id]);
    $total_cashiers = (int)$s->fetchColumn();
} else {
    $s = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'cashier' AND status = 'active'");
    $total_cashiers = (int)$s->fetchColumn();
}

// Pass data to JavaScript
$trend_json = json_encode($trend_data);
$category_json = json_encode($category_data);
$hourly_json = json_encode($hourly_data);
$dow_json = json_encode($dow_data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Minute Burger Admin</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minute1/assets/css/admin.css">
    
    <!-- Use Chart.js v2 - Works on all devices including old tablets -->
    <script src="/minute1/assets/js/chart.v2.min.js"></script>
    
    <!-- Fallback: If local v2 fails, load from CDN -->
    <script>
    if (typeof Chart === 'undefined') {
        var fallback = document.createElement('script');
        fallback.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js';
        fallback.onload = function() {
            if (window.initReportsCharts) setTimeout(window.initReportsCharts, 200);
        };
        document.head.appendChild(fallback);
    }
    </script>
    
    <style>
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .report-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: box-shadow 0.2s ease;
        }
        .report-card:hover {
            box-shadow: var(--shadow-lg);
        }
        .report-card h3 {
            color: var(--brown);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            font-weight: 700;
        }
        .report-card h3 i {
            width: 28px; height: 28px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px;
            background: rgba(243, 121, 2, 0.08);
            color: var(--primary);
            font-size: 1rem;
            flex-shrink: 0;
        }
        .rp-filter-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--white);
            border-radius: 12px;
            padding: 0.85rem 1.25rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 0.75rem;
            border: 1px solid var(--border);
        }
        .rp-filter-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .rp-filter-left label {
            font-weight: 600;
            color: var(--brown);
            white-space: nowrap;
        }
        .rp-filter-left select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            padding: 0.55rem 2.25rem 0.55rem 0.85rem;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 0.875rem;
            font-family: inherit;
            font-weight: 500;
            background: #fff;
            color: var(--text);
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M2.5 4.5L6 8l3.5-3.5'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 12px;
            min-width: 200px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .rp-filter-left select:hover {
            border-color: #d1d5db;
        }
        .rp-filter-left select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(243, 121, 2, 0.1);
        }
        .rp-filter-right {
            display: flex;
            align-items: center;
        }
        .rp-scope {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--brown);
            background: var(--bg);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .chart-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .chart-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            min-width: 0;
            overflow: hidden;
        }
        .chart-card h3 {
            color: var(--brown);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }
        .chart-card h3 i {
            width: 28px; height: 28px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px;
            background: rgba(243, 121, 2, 0.08);
            color: var(--primary);
            font-size: 1rem;
            flex-shrink: 0;
        }
        .chart-wrapper { position: relative; height: 280px; width: 100%; }
        .chart-wrapper canvas { display: block; width: 100% !important; height: 100% !important; }
        .chart-empty-state {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            height: 100%; color: #999; text-align: center; padding: 1rem;
        }
        .chart-empty-state i { font-size: 2rem; opacity: 0.4; margin-bottom: 0.5rem; }
        .comparison-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .compare-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        .compare-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .compare-card .label { font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; }
        .compare-card .value { font-size: 1.5rem; font-weight: 800; color: var(--dark-gray); margin: 0.35rem 0 0.15rem; }
        .compare-card .sub { font-size: 0.78rem; color: var(--text-muted); }
        .compare-card .trend { font-size: 0.8rem; font-weight: 700; margin-top: 0.4rem; display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.15rem 0.5rem; border-radius: 999px; }
        .compare-card .trend.up { color: #15803d; background: #f0fdf4; }
        .compare-card .trend.down { color: #dc2626; background: #fef2f2; }
        .compare-card .trend.flat { color: var(--text-muted); background: var(--bg); }
        .dow-bar { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
        .dow-label { width: 40px; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-align: right; text-transform: uppercase; letter-spacing: 0.03em; }
        .dow-track { flex: 1; height: 22px; background: var(--bg); border-radius: 6px; overflow: hidden; }
        .dow-fill { height: 100%; border-radius: 6px; transition: width 0.5s ease; display: flex; align-items: center; padding-left: 8px; font-size: 0.65rem; font-weight: 700; color: white; min-width: 0; }
        .dow-fill .dow-val { white-space: nowrap; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .data-table th { text-align: left; padding: 0.65rem 0.8rem; border-bottom: 2px solid var(--border); font-weight: 700; color: var(--text-secondary); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em; background: var(--bg); }
        .data-table td { padding: 0.55rem 0.8rem; border-bottom: 1px solid #f1f5f9; }
        .data-table tr:hover { background: #f8fafc; }
        .text-center { text-align: center; }
        .rp-quick-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
        .rp-qs-item { display: flex; align-items: center; gap: 0.65rem; padding: 0.6rem 0.75rem; background: var(--bg); border-radius: 8px; transition: background 0.15s; }
        .rp-qs-item:hover { background: #eef2ff; }
        .rp-qs-item i { font-size: 1.15rem; color: var(--primary); width: 20px; text-align: center; flex-shrink: 0; }
        .rp-qs-info { display: flex; flex-direction: column; min-width: 0; }
        .rp-qs-value { font-size: 0.95rem; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
        .rp-qs-value.text-danger { color: var(--red); }
        .rp-qs-label { font-size: 0.68rem; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.03em; }
        @media (max-width: 480px) { .rp-quick-stats { grid-template-columns: 1fr; } }
        @media (max-width: 1024px) {
            .chart-row, .comparison-row { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .rp-filter-bar { flex-direction: column; align-items: stretch; }
            .rp-filter-left select { min-width: 100%; }
            .rp-filter-right { justify-content: flex-end; }
            .chart-wrapper { height: 220px; }
        }
        @media (max-width: 480px) {
            .chart-wrapper { height: 180px; }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/includes/header.php'; ?>
            
            <div class="content-area">
                <?php if (isOwner()): ?>
                <div class="rp-filter-bar">
                    <div class="rp-filter-left">
                        <label>Branch:</label>
                        <form method="GET" id="branchFilterForm" style="display:inline-flex; align-items:center; flex-wrap:wrap;">
                            <select name="branch_id" id="branchFilterSelect" onchange="this.form.submit()">
                                <option value="" <?php echo $filter_branch_id === null ? 'selected' : ''; ?>>All Branches</option>
                                <?php foreach ($branches as $b): ?>
                                    <option value="<?php echo (int)$b['id']; ?>" <?php echo $filter_branch_id !== null && (int)$b['id'] === $filter_branch_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b['branch_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <div class="rp-filter-right">
                        <span class="rp-scope"><i class='bx bx-building-house'></i> <?php echo htmlspecialchars($branch_label); ?></span>
                    </div>
                </div>
                <?php else: ?>
                <div class="rp-filter-bar">
                    <div class="rp-filter-right" style="margin-left:auto;">
                        <span class="rp-scope"><i class='bx bx-building-house'></i> <?php echo htmlspecialchars($branch_label); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Month-over-Month Comparison -->
                <div class="comparison-row">
                    <?php
                    $month_growth = $prev_month_stats['total'] > 0 ? (($month_stats['total'] - $prev_month_stats['total']) / $prev_month_stats['total']) * 100 : 0;
                    $aov_this = $month_stats['count'] > 0 ? $month_stats['total'] / $month_stats['count'] : 0;
                    $aov_prev = $prev_month_stats['count'] > 0 ? $prev_month_stats['total'] / $prev_month_stats['count'] : 0;
                    $aov_growth = $aov_prev > 0 ? (($aov_this - $aov_prev) / $aov_prev) * 100 : 0;
                    ?>
                    <div class="compare-card">
                        <div class="label">This Month Sales</div>
                        <div class="value">₱<span id="rp-month-total"><?php echo number_format($month_stats['total'], 2); ?></span></div>
                        <div class="sub"><span id="rp-month-count"><?php echo $month_stats['count']; ?></span> orders</div>
                        <div class="trend <?php echo $month_growth > 0 ? 'up' : ($month_growth < 0 ? 'down' : 'flat'); ?>" id="rp-month-trend">
                            <?php echo $month_growth > 0 ? '&#9650; +' . number_format($month_growth, 1) . '% vs last month' : ($month_growth < 0 ? '&#9660; ' . number_format($month_growth, 1) . '% vs last month' : '&#9644; No change'); ?>
                        </div>
                    </div>
                    <div class="compare-card">
                        <div class="label">Today's Sales</div>
                        <div class="value">₱<span id="rp-today-total"><?php echo number_format($today_stats['total'], 2); ?></span></div>
                        <div class="sub"><span id="rp-today-count"><?php echo $today_stats['count']; ?></span> orders</div>
                        <div class="sub" style="margin-top:0.2rem;">Avg: ₱<span id="rp-today-avg"><?php echo number_format($today_stats['count'] > 0 ? $today_stats['total'] / $today_stats['count'] : 0, 2); ?></span></div>
                    </div>
                    <div class="compare-card">
                        <div class="label">Avg Order Value</div>
                        <div class="value">₱<?php echo number_format($aov_this, 2); ?></div>
                        <div class="sub">This month average</div>
                        <div class="trend <?php echo $aov_growth > 0 ? 'up' : ($aov_growth < 0 ? 'down' : 'flat'); ?>">
                            <?php echo $aov_growth > 0 ? '&#9650; +' . number_format($aov_growth, 1) . '%' : ($aov_growth < 0 ? '&#9660; ' . number_format($aov_growth, 1) . '%' : '&#9644; 0%'); ?>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 1 -->
                <div class="chart-row">
                    <div class="chart-card">
                        <h3><i class='bx bx-line-chart'></i> 7-Day Sales Trend</h3>
                        <div class="chart-wrapper"><canvas id="trendChart"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <h3><i class='bx bx-pie-chart'></i> Sales by Category</h3>
                        <div class="chart-wrapper"><canvas id="categoryChart"></canvas></div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="chart-row">
                    <div class="chart-card">
                        <h3><i class='bx bx-time'></i> Hourly Sales Pattern (Today)</h3>
                        <div class="chart-wrapper"><canvas id="hourlyChart"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <h3><i class='bx bx-calendar'></i> Busiest Days (Last 30 Days)</h3>
                        <div id="dow-bars" style="padding: 0.5rem 0;"></div>
                    </div>
                </div>

                <div class="report-grid">
                    <div class="report-card">
                        <h3><i class='bx bx-trending-up'></i> Top Selling Products</h3>
                        <div class="table-container" style="overflow-x:auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_products)): ?>
                                        <tr><td colspan="4"><div class="empty-state"><i class="bx bx-package"></i><span class="empty-title">No sales data</span><span class="empty-sub">Products will appear here once orders are placed</span></div></td></tr>
                                    <?php else: ?>
                                        <?php foreach ($top_products as $i => $product): ?>
                                            <tr>
                                                <td><strong><?php echo $i + 1; ?></strong></td>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td><?php echo $product['total_sold']; ?></td>
                                                <td>₱<?php echo number_format($product['total_revenue'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="report-card">
                        <h3><i class='bx bx-bar-chart-alt-2'></i> Quick Stats</h3>
                        <div class="rp-quick-stats">
                            <div class="rp-qs-item">
                                <i class='bx bx-food-menu'></i>
                                <div class="rp-qs-info">
                                    <span class="rp-qs-value"><?php echo $total_products; ?></span>
                                    <span class="rp-qs-label">Products</span>
                                </div>
                            </div>
                            <div class="rp-qs-item">
                                <i class='bx bx-user'></i>
                                <div class="rp-qs-info">
                                    <span class="rp-qs-value"><?php echo $total_cashiers; ?></span>
                                    <span class="rp-qs-label">Cashiers</span>
                                </div>
                            </div>
                            <div class="rp-qs-item">
                                <i class='bx bx-error'></i>
                                <div class="rp-qs-info">
                                    <span class="rp-qs-value <?php echo $low_stock_count > 0 ? 'text-danger' : ''; ?>"><?php echo $low_stock_count; ?></span>
                                    <span class="rp-qs-label">Low Stock</span>
                                </div>
                            </div>
                            <div class="rp-qs-item">
                                <i class='bx bx-shopping-bag'></i>
                                <div class="rp-qs-info">
                                    <span class="rp-qs-value"><?php echo $today_stats['count']; ?></span>
                                    <span class="rp-qs-label">Today's Orders</span>
                                </div>
                            </div>
                            <div class="rp-qs-item">
                                <i class='bx bx-dollar'></i>
                                <div class="rp-qs-info">
                                    <span class="rp-qs-value">₱<?php echo number_format($prev_month_stats['total'], 0); ?></span>
                                    <span class="rp-qs-label">Prev. Month Sales</span>
                                </div>
                            </div>
                            <div class="rp-qs-item">
                                <i class='bx bx-revision'></i>
                                <div class="rp-qs-info">
                                    <span class="rp-qs-value"><?php echo $prev_month_stats['count']; ?></span>
                                    <span class="rp-qs-label">Prev. Month Orders</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Chart rendering function for v2
    window.initReportsCharts = function() {
        console.log('Initializing charts with Chart.js v2...');
        
        if (typeof Chart === 'undefined') {
            console.error('Chart.js not available');
            document.querySelectorAll('.chart-wrapper').forEach(function(wrapper) {
                wrapper.innerHTML = '<div class="chart-empty-state"><i class="bx bx-error-circle"></i><span>Chart library not loaded. Please refresh.</span></div>';
            });
            return;
        }

        var trendData = <?php echo $trend_json; ?>;
        var categoryData = <?php echo $category_json; ?>;
        var hourlyData = <?php echo $hourly_json; ?>;
        var dowData = <?php echo $dow_json; ?>;

        var colors = ['#F37902','#DC6902','#3498db','#27ae60','#9b59b6','#e74c3c','#f39c12','#1abc9c'];

        function hasData(arr) {
            if (!Array.isArray(arr) || arr.length === 0) return false;
            for (var i = 0; i < arr.length; i++) {
                var item = arr[i];
                if (item && (parseFloat(item.total_sales) > 0 || parseFloat(item.total_revenue) > 0 || parseFloat(item.order_count) > 0)) {
                    return true;
                }
            }
            return false;
        }

        // Move pie/doughnut legends to the bottom on phones so they don't
        // squeeze the chart in the 1-column (≤480px) layout
        function mbLegendPosition() {
            return window.innerWidth <= 480 ? 'bottom' : 'right';
        }

        function renderChart(canvasId, createFn) {
            var canvas = document.getElementById(canvasId);
            if (!canvas) {
                console.warn('Canvas not found:', canvasId);
                return;
            }
            
            var wrapper = canvas.closest('.chart-wrapper');
            if (wrapper) {
                var msgs = wrapper.querySelectorAll('.chart-empty-state');
                msgs.forEach(function(el) { el.remove(); });
            }
            
            canvas.style.display = 'block';
            canvas.style.width = '100%';
            canvas.style.height = '100%';
            
            // Destroy existing chart (v2 method)
            if (canvas.chart) {
                canvas.chart.destroy();
                canvas.chart = null;
            }
            
            try {
                createFn(canvas, wrapper);
            } catch(e) {
                console.error('Chart render error:', e.message);
                if (wrapper) {
                    wrapper.innerHTML = '<div class="chart-empty-state"><i class="bx bx-error-circle"></i><span>Error: ' + e.message + '</span></div>';
                }
            }
        }

        // ====== 1. TREND CHART ======
        renderChart('trendChart', function(canvas, wrapper) {
            if (!hasData(trendData)) {
                canvas.style.display = 'none';
                if (wrapper) {
                    wrapper.innerHTML = '<div class="chart-empty-state"><i class="bx bx-line-chart"></i><span>No trend data available</span></div>';
                }
                return;
            }

            var labels = trendData.map(function(d) {
                return new Date(d.date).toLocaleDateString('en-US', {month:'short',day:'numeric'});
            });
            var sales = trendData.map(function(d) { return parseFloat(d.total_sales) || 0; });
            var orders = trendData.map(function(d) { return parseInt(d.order_count) || 0; });

            canvas.chart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Sales (₱)',
                        data: sales,
                        borderColor: '#F37902',
                        backgroundColor: 'rgba(243,121,2,0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y-axis-0'
                    }, {
                        label: 'Orders',
                        data: orders,
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52,152,219,0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y-axis-1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            id: 'y-axis-0',
                            position: 'left',
                            ticks: {
                                callback: function(value) { return '₱' + value.toLocaleString(); }
                            },
                            scaleLabel: {
                                display: true,
                                labelString: 'Sales (₱)'
                            }
                        }, {
                            id: 'y-axis-1',
                            position: 'right',
                            gridLines: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                callback: function(value) { return value; }
                            },
                            scaleLabel: {
                                display: true,
                                labelString: 'Orders'
                            }
                        }]
                    },
                    legend: {
                        position: 'top'
                    }
                }
            });
            console.log('Trend chart rendered');
        });

        // ====== 2. CATEGORY CHART ======
        renderChart('categoryChart', function(canvas, wrapper) {
            if (!hasData(categoryData)) {
                canvas.style.display = 'none';
                if (wrapper) {
                    wrapper.innerHTML = '<div class="chart-empty-state"><i class="bx bx-pie-chart"></i><span>No category data available</span></div>';
                }
                return;
            }

            var labels = categoryData.map(function(c) { return c.category || 'Uncategorized'; });
            var values = categoryData.map(function(c) { return parseFloat(c.total_revenue) || 0; });
            var bgColors = [];
            for (var i = 0; i < labels.length; i++) {
                bgColors.push(colors[i % colors.length]);
            }

            canvas.chart = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: bgColors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        position: mbLegendPosition(),
                        labels: {
                            fontSize: 11
                        }
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var dataset = data.datasets[tooltipItem.datasetIndex];
                                var total = dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                var pct = total > 0 ? ((tooltipItem.yLabel / total) * 100).toFixed(1) : 0;
                                return data.labels[tooltipItem.index] + ': ₱' + tooltipItem.yLabel.toFixed(2) + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            });
            console.log('Category chart rendered');
        });

        // ====== 3. HOURLY CHART ======
        renderChart('hourlyChart', function(canvas, wrapper) {
            if (!hasData(hourlyData)) {
                canvas.style.display = 'none';
                if (wrapper) {
                    wrapper.innerHTML = '<div class="chart-empty-state"><i class="bx bx-time"></i><span>No hourly data available</span></div>';
                }
                return;
            }

            var allHours = [];
            for (var h = 6; h <= 23; h++) allHours.push(h);
            
            var hourMap = {};
            hourlyData.forEach(function(d) { hourMap[d.hour] = d; });

            var labels = allHours.map(function(h) {
                var ampm = h >= 12 ? 'PM' : 'AM';
                var h12 = h > 12 ? h - 12 : (h === 0 ? 12 : h);
                return h12 + ampm;
            });
            var values = allHours.map(function(h) {
                return hourMap[h] ? parseFloat(hourMap[h].total_sales) || 0 : 0;
            });

            canvas.chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Sales (₱)',
                        data: values,
                        backgroundColor: 'rgba(243,121,2,0.7)',
                        borderColor: '#F37902',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: false
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function(value) { return '₱' + value.toLocaleString(); }
                            },
                            scaleLabel: {
                                display: true,
                                labelString: 'Sales (₱)'
                            }
                        }],
                        xAxes: [{
                            scaleLabel: {
                                display: true,
                                labelString: 'Hour of Day'
                            }
                        }]
                    }
                }
            });
            console.log('Hourly chart rendered');
        });

        // ====== 4. DAY OF WEEK BARS ======
        var dowContainer = document.getElementById('dow-bars');
        if (dowContainer) {
            if (!hasData(dowData)) {
                dowContainer.innerHTML = '<p style="text-align:center;color:#999;padding:2rem;">No data available</p>';
            } else {
                try {
                    var maxSales = 0;
                    dowData.forEach(function(d) {
                        var val = parseFloat(d.total_sales) || 0;
                        if (val > maxSales) maxSales = val;
                    });

                    var dayOrder = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                    var dayShort = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                    var dayMap = {};
                    dowData.forEach(function(d) { dayMap[d.day_name] = d; });

                    var html = '';
                    dayOrder.forEach(function(day, i) {
                        var d = dayMap[day];
                        var sales = d ? parseFloat(d.total_sales) || 0 : 0;
                        var pct = maxSales > 0 ? (sales / maxSales) * 100 : 0;
                        var color = colors[i % colors.length];
                        html += '<div class="dow-bar">';
                        html += '<div class="dow-label">' + dayShort[i] + '</div>';
                        html += '<div class="dow-track">';
                        html += '<div class="dow-fill" style="width:' + pct + '%;background:' + color + ';">';
                        html += '<span class="dow-val">₱' + sales.toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0}) + '</span>';
                        html += '</div></div></div>';
                    });
                    dowContainer.innerHTML = html;
                    console.log('DOW bars rendered');
                } catch(e) {
                    console.error('DOW bars error:', e);
                    dowContainer.innerHTML = '<p style="text-align:center;color:#b45309;padding:2rem;">Error rendering data</p>';
                }
            }
        }

        console.log('All charts complete');
    };

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM ready, checking Chart.js...');
        if (typeof Chart !== 'undefined') {
            setTimeout(window.initReportsCharts, 300);
        } else {
            var attempts = 0;
            var checkInterval = setInterval(function() {
                attempts++;
                if (typeof Chart !== 'undefined') {
                    clearInterval(checkInterval);
                    setTimeout(window.initReportsCharts, 300);
                } else if (attempts > 20) {
                    clearInterval(checkInterval);
                    console.error('Chart.js failed to load');
                    document.querySelectorAll('.chart-wrapper').forEach(function(wrapper) {
                        wrapper.innerHTML = '<div class="chart-empty-state"><i class="bx bx-error-circle"></i><span>Chart library not loaded. Please refresh.</span></div>';
                    });
                }
            }, 500);
        }
    });

    // Handle page restore
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            console.log('Page restored from bfcache');
            setTimeout(window.initReportsCharts, 500);
        }
    });

    // Live headline figures (Today's/Month's Sales cards) — polls without a
    // page reload so new sales show up automatically. The heavier chart
    // sections below still refresh via the existing date-filter form.
    (function() {
        function setText(id, value) {
            var el = document.getElementById(id);
            if (el) el.textContent = value;
        }
        function refreshHeadlineStats() {
            var url = new URL(window.location.href);
            url.searchParams.set('ajax_headline_stats', '1');
            fetch(url.toString(), { credentials: 'same-origin' })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (!data || !data.success) return;
                    setText('rp-today-total', data.today_total);
                    setText('rp-today-count', data.today_count);
                    setText('rp-today-avg', data.today_avg);
                    setText('rp-month-total', data.month_total);
                    setText('rp-month-count', data.month_count);
                    var trendEl = document.getElementById('rp-month-trend');
                    if (trendEl) {
                        var g = data.month_growth;
                        trendEl.className = 'trend ' + (g > 0 ? 'up' : (g < 0 ? 'down' : 'flat'));
                        trendEl.innerHTML = g > 0 ? ('&#9650; +' + g + '% vs last month')
                            : (g < 0 ? ('&#9660; ' + g + '% vs last month') : '&#9644; No change');
                    }
                })
                .catch(function() { /* silent: try again next tick */ });
        }
        setInterval(refreshHeadlineStats, 6000);
    })();
    </script>
    
    <?php include __DIR__ . '/includes/ai_chatbot_widget.php'; ?>
</body>
</html>