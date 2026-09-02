<?php
require_once __DIR__ . '/bootstrap.php';
requireOwner();

$active_tab = 'dashboard';

function getDashboardStats(PDO $pdo): array
{
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(date_time) = CURDATE()");
        $stmt->execute();
        $today_stats = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM orders WHERE MONTH(date_time) = MONTH(CURDATE()) AND YEAR(date_time) = YEAR(CURDATE())");
        $stmt->execute();
        $month_stats = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM inventory WHERE quantity < min_stock AND deleted_at IS NULL");
        $stmt->execute();
        $low_stock = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT o.order_number, o.date_time, o.total_amount, u.full_name as username FROM orders o LEFT JOIN users u ON o.cashier_id = u.id ORDER BY o.date_time DESC LIMIT 5");
        $stmt->execute();
        $recent_orders = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT item_name as name, quantity as stock, min_stock FROM inventory WHERE quantity < min_stock AND deleted_at IS NULL ORDER BY quantity ASC LIMIT 5");
        $stmt->execute();
        $low_stock_items = $stmt->fetchAll();

        // Active branches count
        $branchCount = $pdo->query("SELECT COUNT(*) FROM branches WHERE status = 'active'")->fetchColumn();
        // Total users count
        $userCount = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
    } catch (PDOException $e) {
        $today_stats = ['count' => 0, 'total' => 0];
        $month_stats = ['count' => 0, 'total' => 0];
        $low_stock = ['count' => 0];
        $recent_orders = [];
        $low_stock_items = [];
        $branchCount = 0;
        $userCount = 0;
    }

    return compact('today_stats', 'month_stats', 'low_stock', 'recent_orders', 'low_stock_items', 'branchCount', 'userCount');
}

// Live-polling endpoint: same data the page renders, as JSON.
if (isset($_GET['ajax_stats'])) {
    header('Content-Type: application/json');
    $stats = getDashboardStats($pdo);
    echo json_encode([
        'success' => true,
        'today_total' => number_format((float)$stats['today_stats']['total'], 2),
        'today_count' => (int)$stats['today_stats']['count'],
        'month_total' => number_format((float)$stats['month_stats']['total'], 2),
        'low_stock_count' => (int)$stats['low_stock']['count'],
        'branch_count' => (int)$stats['branchCount'],
        'user_count' => (int)$stats['userCount'],
        'recent_orders' => array_map(fn($o) => [
            'order_number' => htmlspecialchars($o['order_number']),
            'date_time' => date('M j, g:i A', strtotime($o['date_time'])),
            'amount' => number_format((float)$o['total_amount'], 2),
            'cashier' => htmlspecialchars($o['username'] ?? 'Unknown'),
        ], $stats['recent_orders']),
        'low_stock_items' => array_map(fn($i) => [
            'name' => htmlspecialchars($i['name']),
            'stock' => (int)$i['stock'],
        ], $stats['low_stock_items']),
    ]);
    exit;
}

extract(getDashboardStats($pdo));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Minute Burger Admin</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minute1/assets/css/admin.css">
    <style>
        .welcome-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            padding: 1.5rem 1.75rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius-lg);
            background: linear-gradient(120deg, var(--primary) 0%, var(--primary-dark) 60%, var(--copperwood) 110%);
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 14px 30px -14px rgba(176, 72, 0, 0.55);
        }
        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -55%;
            right: -6%;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(250, 229, 29, 0.22) 0%, rgba(250, 229, 29, 0) 68%);
            pointer-events: none;
        }
        .welcome-kicker {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 0.3rem;
        }
        .welcome-heading {
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .welcome-sub {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.82);
            margin-top: 0.25rem;
        }
        .welcome-actions {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-shrink: 0;
        }
        .welcome-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.6rem 1.1rem;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition);
            white-space: nowrap;
        }
        .welcome-btn.solid {
            background: #fff;
            color: var(--primary-dark);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6), 0 4px 12px -4px rgba(0, 0, 0, 0.3);
        }
        .welcome-btn.solid:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px -8px rgba(0, 0, 0, 0.4);
        }
        .welcome-btn.ghost {
            background: rgba(255, 255, 255, 0.16);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.28);
        }
        .welcome-btn.ghost:hover {
            background: rgba(255, 255, 255, 0.26);
            transform: translateY(-2px);
        }
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 2.25rem 1rem;
            color: var(--text-muted);
            text-align: center;
        }
        .empty-state i {
            font-size: 1.9rem;
            opacity: 0.35;
            margin-bottom: 0.15rem;
        }
        .empty-state .empty-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-secondary);
        }
        .empty-state .empty-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(215px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: var(--bg-card); border-radius: var(--radius); padding: 1.25rem; border: 1px solid var(--border); transition: var(--transition); }
        .stat-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); }
        .stat-header { display: flex; align-items: flex-start; justify-content: space-between; }
        .stat-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .icon-sales { background: var(--green-light); color: var(--green); }
        .icon-orders { background: var(--blue-light); color: var(--blue); }
        .icon-trend { background: var(--purple-light); color: var(--purple); }
        .icon-alert { background: var(--amber-light); color: var(--amber); }
        .stat-title { font-size: 0.78rem; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.03em; }
        .stat-info { min-width: 0; }
        .stat-value { font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; line-height: 1.2; margin-top: 2px; font-size: 1.2rem; white-space: nowrap; }
        .content-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 1.25rem; margin-bottom: 1.5rem; }
        .card { background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border); }
        .card-header { padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); }
        .card-title { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; font-weight: 600; color: var(--text-primary); }
        .card-title i { color: var(--text-muted); font-size: 1.1rem; }
        .card-body { padding: 0; overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: var(--bg); color: var(--text-muted); padding: 0.65rem 1.25rem; text-align: left; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--border); }
        .data-table td { padding: 0.75rem 1.25rem; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: var(--text-primary); }
        .data-table tr:last-child td { border-bottom: none; }
        .text-center { text-align: center; }
        .text-muted { color: var(--text-muted); }
        /* Dashboard summary tables: last column is informational (Cashier/Status),
           not an Actions column — don't pin it (prevents overlap on scroll). */
        .table-container .data-table th:last-child,
        .table-container .data-table td:last-child {
            position: static;
            background: inherit;
        }
        .btn { padding: 0.55rem 1.1rem; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: var(--transition); font-family: inherit; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-outline { background: transparent; border: 1.5px solid var(--border); color: var(--text-primary); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
        .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.78rem; }
        .status-badge { padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600; display: inline-block; }
        .status-ok { background: var(--green-light); color: var(--green); }
        .status-inactive { background: var(--red-light); color: var(--red); }
        .status-low { background: var(--amber-light); color: #b45309; }
        @media (max-width: 1024px) {
            .content-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .welcome-banner { flex-direction: column; align-items: flex-start; padding: 1.25rem; }
            .welcome-actions { width: 100%; }
            .welcome-btn { flex: 1; justify-content: center; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 0.6rem; }
            .stat-card { padding: 0.9rem; }
            .stat-title { font-size: 0.7rem; }
            .stat-icon { width: 34px; height: 34px; font-size: 1rem; }
            .card-header { flex-direction: column; gap: 0.5rem; text-align: center; }
            .data-table { min-width: 500px; }
            .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; gap: 0.5rem; }
            .stat-card { padding: 0.75rem; }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/includes/header.php'; ?>

            <div class="content-area">
                <div class="welcome-banner">
                    <div>
                        <div class="welcome-kicker"><?php echo date('l, F j, Y'); ?></div>
                        <h2 class="welcome-heading">Good <?php echo ((int)date('H') < 12 ? 'Morning' : ((int)date('H') < 18 ? 'Afternoon' : 'Evening')); ?>, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Owner'); ?></h2>
                        <p class="welcome-sub">Here's what's happening across your business today.</p>
                    </div>
                    <div class="welcome-actions">
                        <a href="/minute1/cashier/pos.php" class="welcome-btn solid"><i class='bx bx-cart'></i> Open POS</a>
                        <a href="reports.php" class="welcome-btn ghost"><i class='bx bx-bar-chart'></i> Reports</a>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-sales"><i class='bx bx-wallet'></i></div>
                            <div class="stat-info" style="flex:1;">
                                <div class="stat-title">Today's Sales</div>
                                <div class="stat-value">₱<span id="stat-today-total"><?php echo number_format($today_stats['total'], 2); ?></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-orders"><i class='bx bx-cart'></i></div>
                            <div class="stat-info" style="flex:1;">
                                <div class="stat-title">Orders Today</div>
                                <div class="stat-value" id="stat-today-count"><?php echo $today_stats['count']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-trend"><i class='bx bx-trending-up'></i></div>
                            <div class="stat-info" style="flex:1;">
                                <div class="stat-title">Monthly Sales</div>
                                <div class="stat-value">₱<span id="stat-month-total"><?php echo number_format($month_stats['total'], 2); ?></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-alert"><i class='bx bx-error'></i></div>
                            <div class="stat-info" style="flex:1;">
                                <div class="stat-title">Low Stock Items</div>
                                <div class="stat-value" id="stat-low-stock-count"><?php echo $low_stock['count']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon" style="background:var(--blue-light);color:var(--blue);"><i class='bx bx-building-house'></i></div>
                            <div class="stat-info" style="flex:1;">
                                <div class="stat-title">Active Branches</div>
                                <div class="stat-value" id="stat-branch-count"><?php echo $branchCount; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon" style="background:var(--purple-light);color:var(--purple);"><i class='bx bx-user'></i></div>
                            <div class="stat-info" style="flex:1;">
                                <div class="stat-title">Active Users</div>
                                <div class="stat-value" id="stat-user-count"><?php echo $userCount; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-grid">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class='bx bx-time'></i>Recent Transactions</h3>
                            <a href="reports.php" class="btn btn-outline btn-sm">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table class="data-table">
                                    <thead><tr><th>Order #</th><th>Date</th><th>Amount</th><th>Cashier</th></tr></thead>
                                    <tbody id="recent-orders-body">
                                        <?php if (empty($recent_orders)): ?>
                                            <tr><td colspan="4">
                                                <div class="empty-state">
                                                    <i class='bx bx-receipt'></i>
                                                    <span class="empty-title">No transactions yet</span>
                                                    <span class="empty-sub">Sales will show up here as they come in.</span>
                                                </div>
                                            </td></tr>
                                        <?php else: ?>
                                            <?php foreach ($recent_orders as $order): ?>
                                                <tr>
                                                    <td><strong><?php echo $order['order_number']; ?></strong></td>
                                                    <td><?php echo date('M j, g:i A', strtotime($order['date_time'])); ?></td>
                                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($order['username'] ?? 'Unknown'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class='bx bx-error-circle'></i>Low Stock Alert</h3>
                            <a href="inventory.php" class="btn btn-outline btn-sm">Manage</a>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table class="data-table">
                                    <thead><tr><th>Product</th><th>Stock</th><th>Status</th></tr></thead>
                                    <tbody id="low-stock-body">
                                        <?php if (empty($low_stock_items)): ?>
                                            <tr><td colspan="3">
                                                <div class="empty-state">
                                                    <i class='bx bx-check-shield'></i>
                                                    <span class="empty-title">All stocked up</span>
                                                    <span class="empty-sub">No items below their minimum stock level.</span>
                                                </div>
                                            </td></tr>
                                        <?php else: ?>
                                            <?php foreach ($low_stock_items as $item): ?>
                                                <tr>
                                                    <td><strong><?php echo $item['name']; ?></strong></td>
                                                    <td><?php echo $item['stock']; ?></td>
                                                    <td><span class="status-badge <?php echo $item['stock'] == 0 ? 'status-inactive' : 'status-low'; ?>">
                                                        <?php echo $item['stock'] == 0 ? 'Out of Stock' : 'Low Stock'; ?>
                                                    </span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/includes/ai_chatbot_widget.php'; ?>
    <script>
        (function() {
            var POLL_INTERVAL_MS = 6000;

            function esc(s) {
                var d = document.createElement('div');
                d.textContent = s;
                return d.innerHTML;
            }

            function renderRecentOrders(orders) {
                var body = document.getElementById('recent-orders-body');
                if (!body) return;
                if (!orders.length) {
                    body.innerHTML = '<tr><td colspan="4"><div class="empty-state">' +
                        '<i class="bx bx-receipt"></i>' +
                        '<span class="empty-title">No transactions yet</span>' +
                        '<span class="empty-sub">Sales will show up here as they come in.</span>' +
                        '</div></td></tr>';
                    return;
                }
                body.innerHTML = orders.map(function(o) {
                    return '<tr>' +
                        '<td><strong>' + esc(o.order_number) + '</strong></td>' +
                        '<td>' + esc(o.date_time) + '</td>' +
                        '<td>₱' + esc(o.amount) + '</td>' +
                        '<td>' + esc(o.cashier) + '</td>' +
                        '</tr>';
                }).join('');
            }

            function renderLowStock(items) {
                var body = document.getElementById('low-stock-body');
                if (!body) return;
                if (!items.length) {
                    body.innerHTML = '<tr><td colspan="3"><div class="empty-state">' +
                        '<i class="bx bx-check-shield"></i>' +
                        '<span class="empty-title">All stocked up</span>' +
                        '<span class="empty-sub">No items below their minimum stock level.</span>' +
                        '</div></td></tr>';
                    return;
                }
                body.innerHTML = items.map(function(i) {
                    var badgeClass = i.stock === 0 ? 'status-inactive' : 'status-low';
                    var badgeText = i.stock === 0 ? 'Out of Stock' : 'Low Stock';
                    return '<tr>' +
                        '<td><strong>' + esc(i.name) + '</strong></td>' +
                        '<td>' + i.stock + '</td>' +
                        '<td><span class="status-badge ' + badgeClass + '">' + badgeText + '</span></td>' +
                        '</tr>';
                }).join('');
            }

            function setText(id, value) {
                var el = document.getElementById(id);
                if (el) el.textContent = value;
            }

            function refreshDashboard() {
                fetch('?ajax_stats=1', { credentials: 'same-origin' })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (!data || !data.success) return;
                        setText('stat-today-total', data.today_total);
                        setText('stat-today-count', data.today_count);
                        setText('stat-month-total', data.month_total);
                        setText('stat-low-stock-count', data.low_stock_count);
                        setText('stat-branch-count', data.branch_count);
                        setText('stat-user-count', data.user_count);
                        renderRecentOrders(data.recent_orders);
                        renderLowStock(data.low_stock_items);
                    })
                    .catch(function() { /* silent: keep last known-good values, try again next tick */ });
            }

            setInterval(refreshDashboard, POLL_INTERVAL_MS);
        })();
    </script>
</body>
</html>
