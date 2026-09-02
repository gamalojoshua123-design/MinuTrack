<?php
require_once __DIR__ . '/bootstrap.php';
requirePermission('branch_comparison_view');

$active_tab = 'branch_comparison';

$period = $_GET['period'] ?? 'month';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

switch ($period) {
    case 'today':
        $date_condition = "DATE(o.date_time) = CURDATE()";
        break;
    case 'week':
        $date_condition = "YEARWEEK(o.date_time, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'month':
        $date_condition = "MONTH(o.date_time) = MONTH(CURDATE()) AND YEAR(o.date_time) = YEAR(CURDATE())";
        break;
    case 'year':
        $date_condition = "YEAR(o.date_time) = YEAR(CURDATE())";
        break;
    case 'custom':
        if ($start_date && $end_date) {
            $date_condition = "DATE(o.date_time) BETWEEN :start_date AND :end_date";
            $date_params = [':start_date' => $start_date, ':end_date' => $end_date];
        } else {
            $date_condition = "MONTH(o.date_time) = MONTH(CURDATE()) AND YEAR(o.date_time) = YEAR(CURDATE())";
        }
        break;
    default:
        $date_condition = "MONTH(o.date_time) = MONTH(CURDATE()) AND YEAR(o.date_time) = YEAR(CURDATE())";
        $period = 'month';
}

$date_params = $date_params ?? [];
$branch_sales = [];
try {
    $stmt = $pdo->prepare("
        SELECT b.id, b.branch_name, b.location,
               COUNT(o.id) as total_orders,
               COALESCE(SUM(o.total_amount), 0) as total_sales
        FROM branches b
        LEFT JOIN users u ON u.branch_id = b.id AND u.status = 'active'
        LEFT JOIN orders o ON o.cashier_id = u.id AND $date_condition
        WHERE b.status = 'active'
        GROUP BY b.id, b.branch_name, b.location
        ORDER BY b.id
    ");
    $stmt->execute($date_params);
    $branch_sales = $stmt->fetchAll();
} catch (PDOException $e) {
    $branch_sales = [];
}

$best_branch = null;
$worst_branch = null;
$combined_sales = 0;
$combined_orders = 0;

if (!empty($branch_sales)) {
    $combined_sales = array_sum(array_column($branch_sales, 'total_sales'));
    $combined_orders = array_sum(array_column($branch_sales, 'total_orders'));

    $valid = array_values(array_filter($branch_sales, fn($b) => $b['total_orders'] > 0));
    if (!empty($valid)) {
        $best_branch = array_reduce($valid, function($a, $b) {
            return ($a['total_sales'] >= $b['total_sales']) ? $a : $b;
        }, $valid[0]);
        $worst_branch = array_reduce($valid, function($a, $b) {
            return ($a['total_sales'] <= $b['total_sales']) ? $a : $b;
        }, $valid[0]);
    }
}

$chart_labels = [];
$chart_sales = [];
$chart_orders = [];
foreach ($branch_sales as $b) {
    $chart_labels[] = $b['branch_name'];
    $chart_sales[] = (float)$b['total_sales'];
    $chart_orders[] = (int)$b['total_orders'];
}
$chart_labels_json = json_encode($chart_labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$chart_sales_json = json_encode($chart_sales);
$chart_orders_json = json_encode($chart_orders);

$trend_data = [];
try {
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $months[] = date('Y-m', strtotime("-$i months"));
    }
    foreach ($months as $m) {
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(o.total_amount), 0) as total
            FROM orders o
            JOIN users u ON o.cashier_id = u.id
            WHERE DATE_FORMAT(o.date_time, '%Y-%m') = '$m'
        ");
        $trend_data[$m] = (float)$stmt->fetchColumn();
    }
} catch (PDOException $e) {
    $trend_data = [];
}
$trend_labels_json = json_encode(array_keys($trend_data), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$trend_values_json = json_encode(array_values($trend_data));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Sales Comparison - Minute Burger Admin</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minute1/assets/css/admin.css">
    
    <!-- CHART.JS v2 - WORKS ON ALL TABLETS -->
    <script src="/minute1/assets/js/chart.v2.min.js"></script>
    
    <style>
        .filter-bar {
            display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
            background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg);
            padding: 1rem 1.25rem; margin-bottom: 1.5rem;
        }
        .filter-field { display: flex; align-items: center; gap: 0.5rem; min-width: 0; }
        .filter-field > span,
        .filter-field > label { font-size: 0.78rem; font-weight: 600; color: var(--text-muted); white-space: nowrap; }
        .period-btns { display: inline-flex; flex-wrap: wrap; gap: 0.35rem; min-width: 0; }
        .period-btn {
            padding: 0.45rem 0.9rem; border: 1.5px solid var(--border); border-radius: 8px;
            font-size: 0.82rem; font-weight: 600; font-family: inherit; color: var(--text-primary);
            background: var(--white); text-decoration: none; cursor: pointer; transition: var(--transition);
            white-space: nowrap; line-height: 1.4;
        }
        .period-btn:hover { border-color: var(--primary); color: var(--primary); }
        .period-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        .filter-bar input[type="date"] {
            padding: 0.45rem 0.75rem; border: 1.5px solid var(--border); border-radius: 8px;
            font-size: 0.85rem; font-family: inherit; background: var(--white); color: var(--text-primary);
            outline: none; transition: var(--transition); position: relative;
        }
        .filter-bar input[type="date"]:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(243,121,2,0.12); }
        .filter-bar input[type="date"]::-webkit-calendar-picker-indicator {
            opacity: 1; position: absolute; right: 0.4rem; width: 20px; height: 20px; cursor: pointer;
        }
        .filter-bar .btn {
            padding: 0.45rem 1.1rem; border: none; border-radius: 8px; font-size: 0.82rem;
            font-weight: 600; cursor: pointer; font-family: inherit; transition: var(--transition);
            background: var(--primary); color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem;
        }
        .filter-bar .btn:hover { background: var(--primary-dark); }
        .filter-bar .btn-outline {
            background: transparent; border: 1.5px solid var(--border); color: var(--text-primary);
        }
        .filter-bar .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
        .filter-divider { width: 1px; align-self: stretch; background: var(--border); margin: 0.25rem 0; }
        .custom-date-row { display: none; align-items: center; gap: 0.75rem; flex-wrap: wrap; flex: 1; }
        .custom-date-row.show { display: flex; }
        .custom-date-row .date-field { display: flex; align-items: center; gap: 0.5rem; }
        .custom-date-row .btn-group { display: inline-flex; gap: 0.5rem; margin-left: auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: var(--bg-card); border-radius: var(--radius); padding: 1.25rem; border: 1px solid var(--border); }
        .stat-header { display: flex; align-items: flex-start; justify-content: space-between; }
        .stat-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .icon-green { background: var(--green-light); color: var(--green); }
        .icon-blue { background: var(--blue-light); color: var(--blue); }
        .icon-purple { background: var(--purple-light); color: var(--purple); }
        .icon-amber { background: var(--amber-light); color: var(--amber); }
        .stat-title { font-size: 0.78rem; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.03em; }
        .stat-value { font-size: 1.5rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; line-height: 1.2; margin-top: 2px; }
        .stat-sub { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; }
        .chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.5rem; }
        .chart-card {
            background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border);
            padding: 1.25rem; min-width: 0; overflow: hidden;
        }
        .chart-card h3 {
            font-size: 0.9rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .chart-card h3 i { color: var(--text-muted); font-size: 1.1rem; }
        .chart-card.full { grid-column: 1 / -1; }
        .chart-wrapper { position: relative; width: 100%; height: 260px; }
        .chart-wrapper canvas { display: block; width: 100% !important; height: 100% !important; }
        .chart-empty-state {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            height: 100%; color: #999; text-align: center; padding: 1rem;
        }
        .chart-empty-state i { font-size: 2rem; opacity: 0.4; margin-bottom: 0.5rem; }
        .table-wrap {
            background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border);
            overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 1.5rem;
        }
        .table-header {
            padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border);
        }
        .table-header h3 { font-size: 0.9rem; font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem; }
        .table-header h3 i { color: var(--text-muted); }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: var(--bg); color: var(--text-muted); padding: 0.65rem 1.25rem; text-align: left; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--border); }
        .data-table td { padding: 0.75rem 1.25rem; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: var(--text-primary); }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table .highlight { font-weight: 700; color: var(--primary); }
        .data-table .muted { color: var(--text-muted); }
        .text-center { text-align: center; }
        .rank-badge {
            display: inline-flex; align-items: center; justify-content: center;
            width: 28px; height: 28px; border-radius: 50%;
            font-size: 0.75rem; font-weight: 700;
        }
        .rank-1 { background: var(--amber-light); color: #b45309; }
        .rank-2 { background: var(--blue-light); color: var(--blue); }
        .rank-3 { background: var(--green-light); color: var(--green); }
        @media (max-width: 1024px) { .chart-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr 1fr; } .filter-divider { display: none; } .custom-date-row .btn-group { margin-left: 0; } }
        @media (max-width: 480px) { .stats-grid { grid-template-columns: 1fr; } .chart-wrapper { height: 180px; } }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/includes/header.php'; ?>

            <div class="content-area">
                <div class="filter-bar">
                    <form method="GET" id="filterForm" style="display:flex; align-items:center; flex-wrap:wrap; gap:1rem; width:100%;">
                        <input type="hidden" name="period" value="<?php echo htmlspecialchars($period); ?>">
                        <div class="filter-field">
                            <span>Period:</span>
                            <div class="period-btns">
                                <a href="branch_comparison.php?period=today"  class="period-btn<?php echo $period==='today' ? ' active' : ''; ?>">Today</a>
                                <a href="branch_comparison.php?period=week"   class="period-btn<?php echo $period==='week' ? ' active' : ''; ?>">This Week</a>
                                <a href="branch_comparison.php?period=month"  class="period-btn<?php echo $period==='month' ? ' active' : ''; ?>">This Month</a>
                                <a href="branch_comparison.php?period=year"   class="period-btn<?php echo $period==='year' ? ' active' : ''; ?>">This Year</a>
                                <a href="branch_comparison.php?period=custom" class="period-btn<?php echo $period==='custom' ? ' active' : ''; ?>">Custom Range</a>
                            </div>
                        </div>
                        <div class="custom-date-row<?php echo $period === 'custom' ? ' show' : ''; ?>" id="customDateRow">
                            <span class="filter-divider"></span>
                            <div class="date-field">
                                <label>From:</label>
                                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                            </div>
                            <div class="date-field">
                                <label>To:</label>
                                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                            </div>
                            <div class="btn-group">
                                <button type="submit" class="btn"><i class='bx bx-filter-alt'></i> Apply</button>
                                <a href="branch_comparison.php" class="btn btn-outline"><i class='bx bx-reset'></i> Reset</a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-green"><span style="font-size:1.5rem;">₱</span></div>
                            <div class="stat-info" style="flex:1;">
                                <div class="stat-title">Combined Sales</div>
                                <div class="stat-value">₱<?php echo number_format($combined_sales, 2); ?></div>
                                <div class="stat-sub"><?php echo $combined_orders; ?> total orders</div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-blue"><i class='bx bx-trophy'></i></div>
                            <div class="stat-info" style="flex:1;">
                                <div class="stat-title">Best Branch</div>
                                <div class="stat-value" style="font-size:1.1rem;">
                                    <?php echo $best_branch ? htmlspecialchars($best_branch['branch_name']) : 'N/A'; ?>
                                </div>
                                <div class="stat-sub">₱<?php echo $best_branch ? number_format($best_branch['total_sales'], 2) : '0.00'; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-purple"><i class='bx bx-line-chart'></i></div>
                            <div class="stat-info" style="flex:1;">
                                <div class="stat-title">Avg Per Branch</div>
                                <div class="stat-value">
                                    ₱<?php echo number_format(count($branch_sales) > 0 ? $combined_sales / count($branch_sales) : 0, 2); ?>
                                </div>
                                <div class="stat-sub"><?php echo count($branch_sales); ?> active branches</div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-amber"><i class='bx bx-building-house'></i></div>
                            <div class="stat-info" style="flex:1;">
                                <div class="stat-title">Branches w/ Sales</div>
                                <div class="stat-value"><?php echo count(array_filter($branch_sales, fn($b) => $b['total_orders'] > 0)); ?></div>
                                <div class="stat-sub">of <?php echo count($branch_sales); ?> total</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-wrap">
                    <div class="table-header">
                        <h3><i class='bx bx-bar-chart-square'></i> Branch Performance</h3>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Branch</th>
                                <th>Location</th>
                                <th class="text-center">Orders</th>
                                <th class="text-center">Total Sales</th>
                                <th class="text-center">Avg Order</th>
                                <th class="text-center">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($branch_sales)): ?>
                                <tr><td colspan="7" class="text-center muted">No data available</td></tr>
                            <?php else: ?>
                                <?php $rank = 1; foreach ($branch_sales as $b): ?>
                                    <tr>
                                        <td><span class="rank-badge rank-<?php echo min($rank, 3); ?>"><?php echo $rank; ?></span></td>
                                        <td class="highlight"><?php echo htmlspecialchars($b['branch_name']); ?></td>
                                        <td class="muted"><?php echo htmlspecialchars($b['location']); ?></td>
                                        <td class="text-center"><?php echo $b['total_orders']; ?></td>
                                        <td class="text-center highlight">₱<?php echo number_format($b['total_sales'], 2); ?></td>
                                        <td class="text-center">₱<?php echo number_format($b['total_orders'] > 0 ? $b['total_sales'] / $b['total_orders'] : 0, 2); ?></td>
                                        <td class="text-center"><?php echo $combined_sales > 0 ? number_format($b['total_sales'] / $combined_sales * 100, 1) : 0; ?>%</td>
                                    </tr>
                                <?php $rank++; endforeach; ?>
                                <tr style="font-weight:700;background:var(--bg);">
                                    <td></td>
                                    <td>Total</td>
                                    <td></td>
                                    <td class="text-center"><?php echo $combined_orders; ?></td>
                                    <td class="text-center highlight">₱<?php echo number_format($combined_sales, 2); ?></td>
                                    <td class="text-center">₱<?php echo number_format($combined_orders > 0 ? $combined_sales / $combined_orders : 0, 2); ?></td>
                                    <td class="text-center">100%</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="chart-grid">
                    <div class="chart-card">
                        <h3><i class='bx bx-bar-chart-alt-2'></i> Sales by Branch</h3>
                        <div class="chart-wrapper" id="barChartWrapper">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3><i class='bx bx-pie-chart-alt-2'></i> Branch Contribution</h3>
                        <div class="chart-wrapper" id="pieChartWrapper">
                            <canvas id="pieChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card full">
                        <h3><i class='bx bx-line-chart'></i> Monthly Sales Trend</h3>
                        <div class="chart-wrapper" id="lineChartWrapper">
                            <canvas id="lineChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/includes/ai_chatbot_widget.php'; ?>

    <script>
    // ============================================================
    // CHART.JS v2 - BRANCH COMPARISON - WORKS ON TABLETS
    // ============================================================

    (function() {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js not loaded!');
            document.querySelectorAll('.chart-wrapper').forEach(function(w) {
                w.innerHTML = '<div class="chart-empty-state"><i class="bx bx-error-circle"></i><span>Chart library not loaded. Please refresh.</span></div>';
            });
            return;
        }

        console.log('Chart.js v2 loaded successfully');

        var branchLabels = <?php echo $chart_labels_json; ?>;
        var branchSales = <?php echo $chart_sales_json; ?>;
        var branchOrders = <?php echo $chart_orders_json; ?>;
        var trendLabels = <?php echo $trend_labels_json; ?>;
        var trendValues = <?php echo $trend_values_json; ?>;

        var colors = ['#F37902', '#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];

        function hasData(arr) {
            return Array.isArray(arr) && arr.length > 0 && arr.some(function(v) { return parseFloat(v) > 0; });
        }

        function renderChart(canvasId, wrapperId, createFn) {
            var canvas = document.getElementById(canvasId);
            var wrapper = document.getElementById(wrapperId);
            if (!canvas || !wrapper) return;

            if (wrapper) {
                var msgs = wrapper.querySelectorAll('.chart-empty-state');
                msgs.forEach(function(el) { el.remove(); });
            }

            canvas.style.display = 'block';
            canvas.style.width = '100%';
            canvas.style.height = '100%';

            if (canvas.chart) {
                canvas.chart.destroy();
                canvas.chart = null;
            }

            try {
                createFn(canvas, wrapper);
            } catch(e) {
                console.error('Chart error:', e.message);
                if (wrapper) {
                    wrapper.innerHTML = '<div class="chart-empty-state"><i class="bx bx-error-circle"></i><span>Error: ' + e.message + '</span></div>';
                }
            }
        }

        // ====== BAR CHART ======
        renderChart('barChart', 'barChartWrapper', function(canvas, wrapper) {
            if (!hasData(branchSales)) {
                canvas.style.display = 'none';
                if (wrapper) {
                    wrapper.innerHTML = '<div class="chart-empty-state"><i class="bx bx-bar-chart-alt-2"></i><span>No sales data for this period</span></div>';
                }
                return;
            }

            canvas.chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: branchLabels,
                    datasets: [{
                        label: 'Sales',
                        data: branchSales,
                        backgroundColor: branchLabels.map(function(_, i) {
                            return colors[i % colors.length] + '88';
                        }),
                        borderColor: branchLabels.map(function(_, i) {
                            return colors[i % colors.length];
                        }),
                        borderWidth: 2
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
                            }
                        }],
                        xAxes: [{
                            gridLines: {
                                display: false
                            }
                        }]
                    }
                }
            });
        });

        // ====== PIE CHART ======
        renderChart('pieChart', 'pieChartWrapper', function(canvas, wrapper) {
            if (!hasData(branchSales)) {
                canvas.style.display = 'none';
                if (wrapper) {
                    wrapper.innerHTML = '<div class="chart-empty-state"><i class="bx bx-pie-chart-alt-2"></i><span>No sales data for this period</span></div>';
                }
                return;
            }

            var bgColors = branchLabels.map(function(_, i) {
                return colors[i % colors.length];
            });

            canvas.chart = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: branchLabels,
                    datasets: [{
                        data: branchSales,
                        backgroundColor: bgColors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 12,
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
        });

        // ====== LINE CHART ======
        renderChart('lineChart', 'lineChartWrapper', function(canvas, wrapper) {
            if (!hasData(trendValues)) {
                canvas.style.display = 'none';
                if (wrapper) {
                    wrapper.innerHTML = '<div class="chart-empty-state"><i class="bx bx-line-chart"></i><span>No trend data yet</span></div>';
                }
                return;
            }

            canvas.chart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Monthly Sales',
                        data: trendValues,
                        borderColor: '#F37902',
                        backgroundColor: 'rgba(243, 121, 2, 0.08)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#F37902',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        borderWidth: 3
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
                            }
                        }],
                        xAxes: [{
                            gridLines: {
                                display: false
                            }
                        }]
                    }
                }
            });
        });

        console.log('All branch charts rendered successfully!');
    })();
    </script>
</body>
</html>
